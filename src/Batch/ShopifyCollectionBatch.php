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
 * Class ShopifyCollectionBatch
 *
 * Used for creating a collection syncing batch.
 *
 * @package Drupal\shopify\Batch
 */
class ShopifyCollectionBatch {

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
    if ($settings['delete_collections_first']) {
      // Set the first operation to delete all products.
      $this->operations[] = [
        [__CLASS__, 'deleteAllCollections'],
        [
          t('Deleting all collections...'),
        ],
      ];
    }

    $collections = shopify_api_get_collections(['query' => ['limit' => 250]]);

    foreach ($collections as $count => $c) {
      $this->operations[] = [
        [__CLASS__, 'operation'],
        [
          $c,
          t('(Processing collection @name)', ['@name' => $c->title]),
        ],
      ];
    }

//    if (!$params['delete_collections']) {
//      // Last operation to delete orphaned collections (deleted in Shopify).
//      $operations[] = array(
//        'shopify_sync_collections_deleted_batch_op',
//        array(),
//      );
//    }

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
  public static function deleteAllCollections($operation_details, &$context) {
    shopify_delete_all_collections();
    $context['message'] = $operation_details;
  }

  /**
   * Product sync operation.
   */
  public static function operation($collection, $operation_details, &$context) {
    $term = shopify_collection_load($collection->id);

    if (!$term) {
      // Need to create a new collection.
      shopify_collection_create($collection, TRUE);
    }
    else {
      shopify_collection_update($collection, TRUE);
    }

    $context['results'][] = $collection;
    $context['message'] = $operation_details;
  }

  public static function finished($success, $results, $operations) {
    // Update the collections sync time.
    \Drupal::state()
      ->set('shopify.sync.collections_last_sync_time', REQUEST_TIME);
    drupal_set_message(t('Synced @count.', [
      '@count' => \Drupal::translation()
        ->formatPlural(count($results), '@count collection', '@count collections')
    ]));
  }

}