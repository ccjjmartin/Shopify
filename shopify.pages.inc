<?php

function shopify_admin() {
  return 'show store info';
}

function shopify_admin_product_sync_form($form, &$form_state){
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
    '#title' => 'Choose how many products to sync.',
    '#options' => array(
      '1' => t('One at a time'),
      '50' => t('50 at a time'),
      '250' => t('250 (Max API limit)'),
    ),
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
  $batch = shopify_sync_products_batch(array('num_per_batch' => $form_state['values']['num_per_batch']));
  batch_set($batch);
}

/**
 * @return array
 */
function shopify_sync_products_batch($params) {
  // Get count of products
  $num_products = shopify_api_get_product_count(array('updated_at_min' => variable_get('shopify_last_sync')));
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
    'file' => drupal_get_path('module', 'shopify') . '/shopify.pages.inc',
  );
  return $batch;
}

function shopify_sync_products_batch_op_1($page, $num_per_batch, $operation_details, &$context) {
  $products = shopify_api_get_products(array('page' => $page, 'limit' => $num_per_batch, 'updated_at_min' => variable_get('shopify_last_sync')));
  //watchdog('shopify', print_r($products, TRUE));
  foreach ($products as $product) {
    $shopify_product = new ShopifyProduct($product);
    shopify_product_set_images($shopify_product, $product ,'product');
    shopify_product_set_terms($shopify_product, $product);
    foreach ($product['variants'] as $v) {
      $variant = new ShopifyProduct($v);
      $variant->save();
      $context['results'][] = $variant->product_id . ' : ' . check_plain($variant->title);
    }
    $shopify_product->save();
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
