<?php
/**
 * @file
 * Contains functions for working with the Shopify API.
 */

/**
 * Gets the Shopify shop info from the API or the variable.
 *
 * @param bool $refresh
 *   Whether to refresh the info from the API.
 *
 * @return object
 *   Return object of store information.
 *
 * @throws \ShopifyApiException
 */
function shopify_api_get_shop_info($refresh = FALSE) {
  $info = (array) shopify_store_info();
  if ($refresh == TRUE || empty($info)) {
    $client = shopify_api_client();
    $info = $client->call('GET', '/admin/shop.json');
    variable_set('shopify_store_info', $info);
  }
  return (object) $info;
}

/**
 * Gets the admin path for the Shopify store.
 */
function shopify_api_get_shop_admin_path() {
  return 'https://' . shopify_api_get_shop_info()->domain . '/admin';
}

/**
 * Gets the Shopify store product path.
 *
 * @param int $product_id
 *   Product ID.
 * @param int $variant_id
 *   Variant ID.
 *
 * @return string
 *   Product path.
 */
function shopify_api_get_product_path($product_id, $variant_id = 0) {
  $variant_path = '';
  if ($variant_id != 0) {
    $variant_path = '/variants/' . $variant_id;
  }
  return shopify_api_get_shop_admin_path() . '/products/' . $product_id . $variant_path;
}

/**
 * Get Shopify products from the API.
 *
 * @param array $options
 *   Arguments to pass to the API.
 *
 * @return array
 *   Returns the API response data.
 *
 * @throws \ShopifyApiException
 */
function shopify_api_get_products($options = array()) {
  $client = shopify_api_client();
  return $client->call('GET', '/admin/products.json', $options);
}

/**
 * Get Shopify collections from the API.
 *
 * @param array $options
 *   Arguments to pass to the API.
 * @param string $type
 *   Type of collections to get, options are:
 *   - both: Get smart and custom collections.
 *   - smart: Get smart collections only.
 *   - custom: Get custom collections only.
 *
 * @return array
 *   Returns the API response data.
 *
 * @throws \ShopifyApiException
 */
function shopify_api_get_collections($options = array(), $type = 'both') {
  $client = shopify_api_client();
  $smart_collections = $custom_collections = array();
  if ($type == 'both' || $type == 'smart') {
    $smart_collections = $client->call('GET', '/admin/smart_collections.json', $options);
  }
  if ($type == 'both' || $type == 'custom') {
    $custom_collections = $client->call('GET', '/admin/custom_collections.json', $options);
  }
  return array_merge($smart_collections, $custom_collections);
}

/**
 * Get Shopify collects from the API.
 *
 * @param array $options
 *   Arguments to pass the API.
 *
 * @return array
 *   Returns the API response data.
 *
 * @throws \ShopifyApiException
 */
function shopify_api_get_collects($options = array()) {
  $client = shopify_api_client();
  return $client->call('GET', '/admin/collects.json', $options);
}

/**
 * Get the Shopify product count from the API.
 *
 * @param array $options
 *   Arguments to pass to the API.
 *
 * @return array
 *   Returns the API response data.
 *
 * @throws \ShopifyApiException
 */
function shopify_api_get_product_count($options = array()) {
  $client = shopify_api_client();
  return $client->call('GET', '/admin/products/count.json', $options);
}

/**
 * Get Shopify collections count from the API.
 *
 * @param array $options
 *   Arguments to pass to the API.
 * @param string $type
 *   Type of collections to count, options are:
 *   - both: Get smart and custom collections count.
 *   - smart: Get smart collections count only.
 *   - custom: Get custom collections count only.
 *
 * @return array
 *   Returns the API response data.
 *
 * @throws \ShopifyApiException
 */
function shopify_api_get_collection_count($options = array(), $type = 'both') {
  $client = shopify_api_client();
  $smart_count = $custom_count = 0;
  if ($type == 'both' || $type == 'smart') {
    $smart_count = $client->call('GET', '/admin/smart_collections/count.json', $options);
  }
  if ($type == 'both' || $type == 'custom') {
    $custom_count = $client->call('GET', '/admin/custom_collections/count.json', $options);
  }
  return $custom_count + $smart_count;
}

/**
 * Get the Shopify product information.
 *
 * @param int $product_id
 *   Shopify Product ID.
 *
 * @return array
 *   Returns the API response data.
 *
 * @throws \ShopifyApiException
 */
function shopify_api_get_product($product_id) {
  $client = shopify_api_client();
  return $client->call('GET', "/admin/products/{$product_id}.json");
}

/**
 * Get the Shopify product variants.
 *
 * @param int $product_id
 *   Shopify Product ID.
 *
 * @return array
 *   Returns the API response data.
 *
 * @throws \ShopifyApiException
 */
function shopify_api_get_product_variants($product_id) {
  $client = shopify_api_client();
  return $client->call('GET', "/admin/products/{$product_id}/variants.json");
}

/**
 * Get the Shopify variant information.
 *
 * @param int $variant_id
 *   Shopify Variant ID.
 *
 * @return array
 *   Returns the API response data.
 *
 * @throws \ShopifyApiException
 */
function shopify_api_get_variant($variant_id) {
  $client = shopify_api_client();
  return $client->call('GET', "/admin/variants/{$variant_id}.json");
}

/**
 * Get the Shopify product images.
 *
 * @param int $product_id
 *   Shopify Product ID.
 *
 * @return array
 *   Returns the API response data.
 *
 * @throws \ShopifyApiException
 */
function shopify_api_get_product_images($product_id) {
  $client = shopify_api_client();
  return $client->call('GET', "/admin/products/{$product_id}/images.json");
}

/**
 * Get Shopify webhooks.
 *
 * @return array
 *   Returns the API response data.
 *
 * @throws \ShopifyApiException
 */
function shopify_api_get_webhooks() {
  $client = shopify_api_client();
  return $client->call('GET', "/admin/webhooks.json");
}

/**
 * Get the Shopify webhooks count.
 *
 * @return array
 *   Returns the API response data.
 *
 * @throws \ShopifyApiException
 */
function shopify_api_get_webhook_count($options = array()) {
  $client = shopify_api_client();
  return $client->call('GET', "/admin/webhooks/count.json");
}

/**
 * Creates a new Shopify webhook.
 *
 * @param array $post
 *   Data to POST to the API.
 *
 * @return array
 *   Returns the API response data.
 *
 * @throws \ShopifyApiException
 */
function shopify_api_create_webhook($post) {
  $client = shopify_api_client();
  return $client->call('POST', "/admin/webhooks.json", $post);
}

/**
 * Deletes a new Shopify webhook.
 *
 * @param int $id
 *   Webhook ID to delete.
 *
 * @return array
 *   Returns the API response data.
 *
 * @throws \ShopifyApiException
 */
function shopify_api_delete_webhook($id) {
  $client = shopify_api_client();
  return $client->call('DELETE', "/admin/webhooks/{$id}.json");
}
