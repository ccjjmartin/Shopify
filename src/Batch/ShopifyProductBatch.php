<?php

namespace Drupal\shopify\Batch;

use Drupal\shopify\Entity\ShopifyProduct;
use Shopify\Client;

class ShopifyProductBatch {

  private $batch;
  private $operations;
  private $client;

  public function __construct() {
    $this->client = shopify_api_client();
  }

  public function prepare(array $settings = []) {
    $limit = 250; // @todo: make dynamic from settings.
    $updated_at_min = REQUEST_TIME - 3600; // @todo: Make dynamic.

    $num_products = $this->client->getProductsCount();
    $num_operations = ceil($num_products / $limit);

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

  public static function operation(array $settings = [], $operation_details, &$context) {
    $client = shopify_api_client();
    $result = $client->get('products', $settings);
    if (isset($result->products) && !empty($result->products)) {

      foreach ($result->products as $product) {
        // Remove id property since that would error with the entity key.
//        dpm($product);
//        return;
        $entity = shopify_product_load_by_product_id($product->product_id);
        if (!$entity) {
          // Need to create this product.
          $entity = ShopifyProduct::create((array) $product);
          $entity->save();
        }
        else {
          $entity->update($product);
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
