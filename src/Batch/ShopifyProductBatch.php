<?php
/**
 * @file
 * Contains code specific to the product sync batch.
 */

namespace Drupal\shopify\Batch;

use Drupal\shopify\Entity\ShopifyProduct;
use Shopify\Client;
use Drupal\Component\Utility\Html;

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
    $params = [];
    $params['limit'] = $settings['num_per_batch'];

    if (!$settings['force_update'] && $settings['updated_at_min'] && !$settings['delete_products_first']) {
      $params['updated_at_min'] = date(DATE_ISO8601, $settings['updated_at_min']);
    }

    $num_products = $this->client->getProductsCount();
    $num_operations = ceil($num_products / $params['limit']);

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
          $params,
          t('(Processing page @operation)', ['@operations' => $page]),
        ]
      ];
    }

    if (!$settings['delete_products_first']) {
      // Setup operation to delete stale products.
      $this->operations[] = [
        [__CLASS__, 'cleanUpProducts'],
        [
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
   * Deletes products on the site that don't exist on Shopify anymore.
   */
  public function cleanUpProducts($operation_details, &$context) {
    // Get all Shopify product_ids and variant_ids.
    $client = shopify_api_client();
    $products = $client->getProducts(['query' => ['fields' => 'id,variants']]);
    $product_count = $client->getProductsCount();
    $product_ids = $variant_ids = [];

    // Build up arrays of products and variant IDs.
    foreach ($products as $product) {
      $product_ids[] = $product->id;
      foreach ($product->variants as $variant) {
        $variant_ids[] = $variant->id;
      }
    }

    // Sanity check to make sure we've gotten all data back from Shopify.
    if ($product_count != count($product_ids)) {
      // Something went wrong.
      return;
    }

    // Go ahead and delete all rogue products.
    $query = \Drupal::entityQuery('shopify_product');
    $query->condition('product_id', $product_ids, 'NOT IN');
    $result = $query->execute();
    if ($result) {
      $manager = \Drupal::entityManager()
        ->getStorage('shopify_product');
      $product_entities = $manager->loadMultiple($result);
      $manager->delete($product_entities);
      drupal_set_message(t('Deleted @products.', [
        '@products' => \Drupal::translation()
          ->formatPlural(count($product_entities), '@count product', '@count products'),
      ]));
    }

    // Go ahead and delete all rogue variants.
    $query = \Drupal::entityQuery('shopify_product_variant');
    $query->condition('variant_id', $variant_ids, 'NOT IN');
    $result = $query->execute();
    if ($result) {
      $manager = \Drupal::entityManager()
        ->getStorage('shopify_product_variant');
      $variant_entities = $manager->loadMultiple($result);
      $manager->delete($variant_entities);
      drupal_set_message(t('Deleted @variants.', [
        '@variants' => \Drupal::translation()
          ->formatPlural(count($variant_entities), '@count variant', '@count variants'),
      ]));
    }
  }

  /**
   * Product sync operation.
   */
  public static function operation(array $settings = [], $operation_details, &$context) {
    $client = shopify_api_client();
    $result = $client->get('products', ['query' => $settings]);
    if (isset($result->products) && !empty($result->products)) {
      foreach ($result->products as $product) {
        $entity = ShopifyProduct::loadByProductId($product->id);
        if (!$entity) {
          // Need to create this product.
          $entity = ShopifyProduct::create((array) $product);
          $entity->save();
        }
        else {
          $entity->update((array) $product);
          $entity->save();
        }
        $context['results'][] = $entity->product_id . ' : ' . Html::escape($entity->title);
      }
      $context['message'] = t('Syncing @products.', [
        '@products' => \Drupal::translation()
          ->formatPlural(count($result->products), '@count product', '@count products'),
      ]);
    }
  }

  public static function finished($success, $results, $operations) {
    // Update the product sync time.
    \Drupal::state()->set('shopify.sync.products_last_sync_time', REQUEST_TIME);
    drupal_set_message(t('Synced @count.', [
      '@count' => \Drupal::translation()
        ->formatPlural(count($results), '@count product', '@count products')
    ]));
  }

}
