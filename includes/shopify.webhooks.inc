<?php
/**
 * @file
 * Contains functions relating to Shopify webhooks.
 */

/**
 * Shopify webhook menu callback.
 */
function shopify_incoming_webhook() {
  $json = file_get_contents('php://input');
  $payload = json_decode($json, TRUE);
  $hook = str_replace('/', '_', $_SERVER['HTTP_X_SHOPIFY_TOPIC']);

  // Log webhooks if the debug option is set.
  if (variable_get('shopify_webhooks_debug', FALSE)) {
    watchdog('shopify_webhook', $hook . ':' . check_plain(var_export($payload, TRUE)))s;
  }

  // Allow modules to hook into webhooks firing.
  module_invoke_all('shopify_webhook', $hook, $payload);
  module_invoke_all('shopify_webhook_' . $hook, $payload);

  print '';
}

/**
 * Implements hook_shopify_webhook_WEBHOOK().
 */
function shopify_shopify_webhook_products_create($payload) {
  shopify_shopify_webhook_products_update($payload);
}

/**
 * Implements hook_shopify_webhook_WEBHOOK().
 */
function shopify_shopify_webhook_collections_update($payload) {
  shopify_collection_update($payload, $sync_products = TRUE);
}

/**
 * Implements hook_shopify_webhook_WEBHOOK().
 */
function shopify_shopify_webhook_collections_delete($payload) {
  shopify_collection_delete($payload['id']);
}

/**
 * Implements hook_shopify_webhook_WEBHOOK().
 */
function shopify_shopify_webhook_orders_paid($payload) {
  // Update product inventory counts for purchased products.
  foreach ($payload['line_items'] as $line_item) {
    if ($variant = shopify_product_load_by_variant_id($line_item['variant_id'])) {
      shopify_product_update($line_item['product_id'], $variant->variant_id, shopify_api_get_variant($variant->variant_id, array(
        'fields' => 'inventory_quantity,old_inventory_quantity',
      )));
    }
  }
}

/**
 * Implements hook_shopify_webhook_WEBHOOK().
 */
function shopify_shopify_webhook_shop_update($payload) {
  // Update store info on the site.
  variable_set('shopify_store_info', $payload);
}

/**
 * Implements hook_shopify_webhook_WEBHOOK().
 */
function shopify_shopify_webhook_collections_create($payload) {
  shopify_collection_create($payload, $sync_products = TRUE);
}

/**
 * Implements hook_shopify_webhook_WEBHOOK().
 */
function shopify_shopify_webhook_products_update($product) {
  $product_entity = shopify_product_update($product['id'], 0, $product);
  $product_entity->save();
  foreach ($product['variants'] as $v) {
    $variant = shopify_product_update($product['id'], $v['id'], $v);
    $variant->save();
  }
}

/**
 * Implements hook_shopify_webhook_WEBHOOK().
 */
function shopify_shopify_webhook_products_delete($product) {
  shopify_delete_by_product_id($product['id']);
}
