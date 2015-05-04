<?php
/**
 * @file
 * Contains hooks/functions relating to Rules integration.
 */

/**
 * Implements hook_rules_event_info().
 */
function shopify_rules_event_info() {
  return array(
    'shopify_webhook_fired' => array(
      'label' => t('Webhook fired'),
      'module' => 'shopify',
      'group' => t('Shopify Webhook'),
      'variables' => array(
        'hook' => array(
          'type' => 'text',
          'label' => t('Hook'),
        ),
      ),
    ),
    'shopify_webhook_product_created' => array(
      'label' => t('Product created'),
      'module' => 'shopify',
      'group' => t('Shopify Webhook'),
      'variables' => array(
        'shopify_product' => array(
          'type' => 'shopify_product',
          'label' => t('Shopify Product'),
        ),
      ),
    ),
    'shopify_webhook_product_updated' => array(
      'label' => t('Product updated'),
      'module' => 'shopify',
      'group' => t('Shopify Webhook'),
      'variables' => array(
        'shopify_product' => array(
          'type' => 'shopify_product',
          'label' => t('Shopify Product'),
        ),
      ),
    ),
    'shopify_webhook_product_deleted' => array(
      'label' => t('Product deleted'),
      'module' => 'shopify',
      'group' => t('Shopify Webhook'),
      'variables' => array(
        'shopify_product' => array(
          'type' => 'shopify_product',
          'label' => t('Shopify Product'),
        ),
      ),
    ),
  );
}
