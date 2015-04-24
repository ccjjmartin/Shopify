<?php

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
 * Submit handler.
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
 * @return array
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

/**
 * orders/create, orders/delete, orders/updated, orders/paid, orders/cancelled, orders/fulfilled, orders/partially_fulfilled, order_transactions/create, carts/create, carts/update, checkouts/create, checkouts/update, checkouts/delete, refunds/create, products/create, products/update, products/delete, collections/create, collections/update, collections/delete, customer_groups/create, customer_groups/update, customer_groups/delete, customers/create, customers/enable, customers/disable, customers/update, customers/delete, fulfillments/create, fulfillments/update, shop/update, disputes/create, disputes/update, app/uninstalled
 */
function shopify_webhooks_admin($form, &$form_state) {
  $webhooks = shopify_api_get_webhooks();
  $form['#tree'] = TRUE;
  $form['registered'] = array(
    '#type' => 'fieldset',
    '#title' => t('Webhooks Registered'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );
  foreach ($webhooks as $hook) {
    $form['registered'][$hook['id']] = array(
      '#type' => 'checkbox',
      '#title' => $hook['topic'] . ', applies to: ' . $hook['address'],
    );
  }

  $form['remove_submit'] = array(
    '#type' => 'submit',
    '#value' => t('Remove'),
    '#submit' => array('shopify_remove_webhooks_submit'),
  );

  $form['register'] = array(
    '#type' => 'fieldset',
    '#title' => t('Register New Webhooks'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );

  $product_topics = array(
    'create' => t('Product created'),
    'update' => t('Product updated'),
    'delete' => t('Product deleted'),
  );

  foreach ($product_topics as $topic => $description) {
    $form['register']['products'][$topic] = array(
      '#type' => 'checkbox',
      '#title' => $description,
    );
  }

  $protocol = stripos($_SERVER['SERVER_PROTOCOL'],'https') === true ? 'https://' : 'http://';

  $form['register']['hostname'] = array(
    '#type' => 'textfield',
    '#title' => t('Hostname'),
    '#default_value' => $protocol . "$_SERVER[HTTP_HOST]",
    '#size' => 60,
    '#required' => TRUE,
  );

  $form['register']['register_submit'] = array(
    '#type' => 'submit',
    '#value' => t('Register'),
    '#submit' => array('shopify_register_webhooks_submit'),
  );

  return $form;
}

function shopify_remove_webhooks_submit($form, &$form_state) {
  if ($form_state['values']['op'] == 'Remove') {
    foreach ($form_state['values']['registered'] as $id => $v) {
      if ($v == 1) {
        shopify_api_delete_webhook($id);
      }
    }
  }
}

function shopify_register_webhooks_submit($form, &$form_state) {
  $values = $form_state['values'];
  if ($form_state['values']['op'] == 'Register') {
    unset($values['register']['register_submit']);
    $hostname = $values['register']['hostname'];
    unset($values['register']['hostname']);
    foreach ($values['register'] as $type => $topics) {
      foreach ($topics as $topic => $v)
        if ($v == 1) {
          $hook = array(
            'webhook' => array(
              'topic' => "$type/$topic",
              // @TODO Update this! must include a path like /shopify/webhook/product_update!
              'address' => $hostname,
              'format' => "json"
            )
          );
          shopify_api_create_webhook($hook);
        }
      }
    }
}