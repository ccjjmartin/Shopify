<?php
/**
 * @file
 * Contains code specific to the product sync batch.
 */

namespace Drupal\shopify\Batch;

use Drupal\shopify\Entity\ShopifyProduct;
use Shopify\Client;

/**
 * Class ShopifyProductBatch
 *
 * Used for creating a product syncing batch.
 *
 * @package Drupal\shopify\Batch
 */
class ShopifyProductBatch {

  private $batch;
  private $operations;
  private $client;

  public function __construct() {
    $this->client = shopify_api_client();
  }

  /**
   * Prepares the product sync batch from passed settings.
   *
   * @param array $settings
   *   Batch specific settings. Valid values include:
   *    - num_per_batch: The number of items to sync per operation.
   *    - delete_products_first: Deletes all products from the site first.
   *    - force_udpate: Ignores last sync time and updates everything anyway.
   *
   * @return $this
   */
  public function prepare(array $settings = []) {
    $limit = $settings['num_per_batch'];
    $updated_at_min = REQUEST_TIME - 3600; // @todo: Make dynamic.

    $num_products = $this->client->getProductsCount();
    $num_operations = ceil($num_products / $limit);

    if ($settings['delete_products_first']) {
      // Set the first operation to delete all products.
      $this->operations[] = [
        [__CLASS__, 'deleteAllProducts'],
        [
          t('Deleting all products...'),
        ],
      ];
    }

    for ($page = 1; $page <= $num_operations; $page++) {
      $this->operations[] = [
        [__CLASS__, 'operation'],
        [
          [
            'page' => $page,
            'limit' => $limit,
            'updated_at_min' => $updated_at_min,
          ],
          t('(Processing page @operation)', ['@operations' => $page]),
        ]
      ];
    }

    $this->batch = array(
      'operations' => $this->operations,
      'finished' => [__CLASS__, 'finished'],
    );

    return $this;
  }

  public function set() {
    batch_set($this->batch);
  }

  public function getBatch() {
    return $this->batch;
  }

  /**
   * Deletes all products and variants from the database.
   */
  public static function deleteAllProducts($operation_details, &$context) {
    shopify_product_delete_all();
    $context['message'] = $operation_details;
  }

  /**
   * Product sync operation.
   */
  public static function operation(array $settings = [], $operation_details, &$context) {
    $client = shopify_api_client();
    $result = $client->get('products', $settings);
    if (isset($result->products) && !empty($result->products)) {

      foreach ($result->products as $product) {
        // Remove id property since that would error with the entity key.
//        dpm($product);
//        return;
        $entity = shopify_product_load_by_product_id($product->id);
        if (!$entity) {
          // Need to create this product.
          $entity = ShopifyProduct::create((array) $product);
          $entity->save();
        }
        else {
//          $entity->update($product);
        }
      }


      // Create product entities or load.

      /*
       * OLD CODE....
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
      */
    }
    $context['message'] = t('Syncing...');
  }

  public static function finished($success, $results, $operations) {
    drupal_set_message(t('Done!'));
  }

}
