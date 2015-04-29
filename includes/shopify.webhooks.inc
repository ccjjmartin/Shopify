<?php
/**
 * @file
 * Contains functions relating to Shopify webhooks.
 */

/**
 * Shopify webhook menu callback.
 *
 * @param string $hook
 *   Hook name.
 */
function shopify_incoming_webhook($hook) {
  $json = file_get_contents('php://input');
  $payload = json_decode($json, TRUE);

  // Log webhooks if the debug option is set.
  if (variable_get('shopify_webhooks_debug', FALSE)) {
    watchdog('shopify_webhook', $hook . ':' . var_export($payload, TRUE));
  }

  // Allow modules to hook into webhooks firing.
  module_invoke_all('shopify_webhook', $payload);
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
