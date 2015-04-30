<?php
/**
 * @file
 * Drush Shopify Command File.
 */

/**
 * Implements hook_drush_command().
 */
function shopify_drush_command() {
  $items = array();
  $items['shopify-products'] = array(
    'description' => 'View synced Shopify products.',
    'arguments' => array(),
    'options' => array(
      'limit' => 'Limit the results to a max count. (default=10)',
      'sort-desc' => 'Sort by a column DESC.',
      'sort-asc' => 'Sort by a column ASC.',
    ),
    'examples' => array(
      'drush shopify-products' => 'View most recent 10 created products.',
      'drush shopify-products --limit=100' => 'View most recent 100 created products.',
      'drush shopify-products --sort-desc=price' => 'View most expensive products.',
    ),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
  );
  $items['shopify-api'] = array(
    'description' => 'Run Shopify API calls.',
    'arguments' => array(
      'method' => 'API method: GET, POST, PUT, DELETE',
      'path' => 'API path, such as: products, products/count, orders',
    ),
    'options' => array(
      'options' => 'Array of options to pass to the API call. Pass as query string params or JSON.',
    ),
    'examples' => array(
      'drush shopify-api get products' => 'Get products from the API',
      'drush shopify-api get shop' => 'Get shop info from the API',
      'drush shopify-api get shop --options="fields=domain"' => 'Get your shop domain',
      'drush shopify-api get products --options="limit=5&page=2"' => 'Get 5 products from the second page from the API',
      'drush shopify-api get product/5/variants --options="fields=variant_id,title"' => 'Get variants for product #5 and only return specific fields.',
      'drush shopify-api delete product/5' => 'Delete product #5',
      'drush shopify-api post products --options=\'{"product":{"title":"test this out"}}\'' => 'Create a new product by passing JSON',
    ),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
  );
  return $items;
}

/**
 * Implements hook_drush_help().
 */
function shopify_drush_help($command) {
  switch ($command) {
    case 'drush:shopify':
      return dt('View Shopify products, collections and run API calls.');
  }
}

/**
 * Display information about Shopify products.
 */
function drush_shopify_products() {
  $query = new EntityFieldQuery();
  $query->entityCondition('entity_type', 'shopify_product');
  if ($limit = drush_get_option('limit', 10)) {
    $query->range(0, $limit);
  }
  if ($sort_asc = drush_get_option('sort-asc')) {
    $query->propertyOrderBy($sort_asc, 'ASC');
  }
  if ($sort_desc = drush_get_option('sort-desc', 'id')) {
    $query->propertyOrderBy($sort_desc, 'DESC');
  }
  $result = $query->execute();
  if ($result['shopify_product']) {
    $products = entity_load('shopify_product', array_keys($result['shopify_product']));
    $rows = array();
    // Setup the header.
    $rows[] = array('id', 'product_id', 'variant_id', 'title', 'price');
    foreach ($products as $product) {
      $rows[] = array(
        $product->id,
        $product->product_id,
        $product->variant_id,
        $product->label(),
        $product->price,
      );
    }
    drush_print_table($rows, TRUE);
  }
  else {
    drush_print_error('No products found.');
  }
}

/**
 * Perform an API call.
 */
function drush_shopify_api($method, $path) {
  $method = strtoupper($method);
  if (!in_array($method, array('POST', 'GET', 'PUT', 'DELETE'))) {
    return drush_set_error('Invalid method "' . $method . '" given');
  }
  $options = array();
  if ($query = drush_get_option('options')) {
    if ($json = json_decode($query)) {
      $options = (array) $json;
    }
    else {
      $options = drupal_get_query_array($query);
    }
  }
  try {
    $response = shopify_api_call($method, $path, $options);
  } catch (Exception $e) {
    return drush_set_error('API Error: ' . $e->getMessage());
  }
  drush_print('Results:');
  drush_print(var_export($response, TRUE));
  drush_print('');
}
