<?php

namespace Drupal\shopify;

use Shopify\Clients\Rest;

class Shopify {

  /**
   * The Shopify REST client.
   *
   * @var \Shopify\Clients\Rest $shopify_client
   */
  protected $shopifyClient;

  /**
   * Constructs Shopify object.
   *
   * @param \Shopify\Clients\Rest $shopify_client
   *   The Shopify REST client.
   */
  public function __construct(Rest $shopify_client) {
    $this->shopifyClient = $shopify_client;
  }

  /**
   * Returns Shopify object.
   *
   * @return \Drupal\shopify\Shopify
   *   Shopify object.
   */
  public static function get() {
    return \Drupal::service('shopify.shopify');
  }

}
