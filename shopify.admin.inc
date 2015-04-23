<?php
/**
 * @file
 * Contains functions and forms for admin pages.
 */

/**
 * Admin page callback.
 */
function shopify_admin_page() {
  try {
    $info = shopify_api_get_shop_info($refresh = TRUE);
  } catch (Exception $e) {
    drupal_set_message(t('Unable to connect to your Shopify store.'), 'error');
    return t('Hmm.. we\'re unable to connect to your Shopify Store. <ul><li>Do you have a <a href="https://www.shopify.com/?ref=bonify">Shopify account</a>?</li><li>If you have an account you\'ll need to create your API key if you haven\'t already. See <a href="https://docs.shopify.com/support/configuration/apps/where-do-i-find-my-api-key">https://docs.shopify.com/support/configuration/apps/where-do-i-find-my-api-key</a> for more information.</li><li>Have you checked your <a href="/admin/config/services/shopify">API settings to make sure they match?</a></li><li><a href="/admin/reports/status">Can your server make outgoing HTTP requests?</li></ul>');
  }
  $store_meta = array(
    'My Store Admin' => l($info->myshopify_domain, 'https://' . $info->myshopify_domain . '/admin'),
    'Owned By' => $info->shop_owner,
    'Address' => $info->address1,
    'City' => $info->city,
    'State/Province' => $info->province,
  );

  $return = '';
  foreach ($store_meta as $label => $meta) {
    $return .= '<strong>' . $label . '</strong>: ' . $meta . '<br />';
  }
  return $return;
}

/**
 * Shopify products sync page form.
 */
function shopify_admin_product_sync_form($form, &$form_state) {
  try {
    shopify_api_get_shop_info();
  } catch (Exception $e) {
    drupal_set_message(t('Unable to connect to your Shopify store.'), 'error');
    $form['oops'] = array('#markup' => t('<a href="/admin/shopify">Unable to connect to your store.</a>'));
    return $form;
  }

  $last_updated = variable_get('shopify_last_sync');
  if ($last_updated == '1970-01-01 00:00') {
    $last_updated = 'never';
  }

  $form['description'] = array(
    '#type' => 'markup',
    '#markup' => t('Last updated !last_updated.', array('!last_updated' => $last_updated)),
  );

  $form['num_per_batch'] = array(
    '#type' => 'select',
    '#title' => 'Choose how many products to sync per batch operation (not per batch).',
    '#options' => array(
      '1' => t('One at a time'),
      '50' => t('50 at a time'),
      '250' => t('250 (Max API limit)'),
    ),
  );

  $form['delete'] = array(
    '#type' => 'checkbox',
    '#title' => t('Delete all products then re-import everything.'),
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => 'Sync Products',
  );

  return $form;
}

/**
 * Shopify product sync form submit handler.
 *
 * @see shopify_admin_product_sync_form()
 */
function shopify_admin_product_sync_form_submit($form, &$form_state) {
  // @todo Probably should have a batch just for this.
  if ($form_state['values']['delete']) {
    shopify_delete_all_products();
    $datetime = new DateTime('1/1/1970');
    $last_updated = $datetime->format('Y-m-d H:i');
    variable_set('shopify_last_sync', $last_updated);
  }
  $batch = shopify_sync_products_batch(array('num_per_batch' => $form_state['values']['num_per_batch']));
  batch_set($batch);
}

/**
 * Shopify products sync batch callback.
 */
function shopify_sync_products_batch($params) {
  // Get count of products
  $num_products = shopify_api_get_product_count(
    array(
      'updated_at_min' => variable_get('shopify_last_sync')
    )
  );
  $num_operations = ceil($num_products / $params['num_per_batch']);
  $operations = array();
  for ($page = 1; $page <= $num_operations; $page++) {
    $operations[] = array(
      'shopify_sync_products_batch_op_1',
      array(
        $page,
        $params['num_per_batch'],
        t('(Processing page @operation)', array('@operation' => $page)),
      ),
    );
  }
  $batch = array(
    'operations' => $operations,
    'finished' => 'shopify_sync_products_batch_finished',
    'file' => drupal_get_path('module', 'shopify') . '/shopify.admin.inc',
  );
  return $batch;
}

/**
 * Shopify products sync batch operation handler.
 */
function shopify_sync_products_batch_op_1($page, $num_per_batch, $operation_details, &$context) {
  $products = shopify_api_get_products(
    array(
      'page' => $page,
      'limit' => $num_per_batch,
      'updated_at_min' => variable_get('shopify_last_sync')
    )
  );
  foreach ($products as $product) {

    $shopify_product = shopify_product_update($product['id'], 0, $product);
    $shopify_product->save();

    foreach ($product['variants'] as $v) {
      $variant = shopify_product_update($product['id'], $v['id'], $v);
      $variant->save();
      $context['results'][] = $variant->product_id . ' : ' . check_plain($variant->title);
    }
    $context['results'][] = $shopify_product->product_id . ' : ' . check_plain($shopify_product->title);
  }

  $context['message'] = t('Syncing product "@title" and its variants', array('@title' => $shopify_product->title)) . ' ' . $operation_details;
}

/**
 * Shopify product sync batch operation finished callback.
 *
 * @see shopify_sync_products_batch_op_1()
 */
function shopify_sync_products_batch_finished($success, $results, $operations) {
  if ($success) {
    drupal_set_message(t('Processed @count products and variants.', array('@count' => count($results))));
    $datetime = new DateTime();
    $last_updated = $datetime->format('Y-m-d H:i');
    variable_set('shopify_last_sync', $last_updated);
  }
  else {
    // An error occurred.
    // $operations contains the operations that remained unprocessed.
    $error_operation = reset($operations);
    drupal_set_message(
      t('An error occurred while processing @operation with arguments : @args',
        array(
          '@operation' => $error_operation[0],
          '@args' => print_r($error_operation[0], TRUE),
        )
      )
    );
  }
}
