<?php

namespace Drupal\shopify;

use Drupal\Core\Config\ConfigFactoryInterface;
use Shopify\Auth\FileSessionStorage;
use Shopify\Clients\Rest;
use Shopify\Context;

/**
 * Defines the Shopify factory.
 */
class ShopifyFactory {

  /**
   * Configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Constructs ShopifyFactory object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->config = $configFactory->get('shopify.settings');
  }

  /**
   * Creates Shopify client.
   *
   * @return \Shopify\Clients\Rest
   *   Shopify Rest/Http client.
   */
  public function create() {
    if (!_shopify_api_client_has_valid_config()) {
      return NULL;
    }

    $scopes = [
      'read_products',
    ];

    Context::initialize(
      apiKey: $this->config->get('api.key'),
      apiSecretKey: $this->config->get('api.password'),
      scopes: $scopes,
      hostName: $this->config->get('api.domain'),
      sessionStorage: new FileSessionStorage('/tmp/php_sessions'),
      // @todo Make API version configurable.
      apiVersion: '2023-04',
      isPrivateApp: TRUE,
    );

    return new Rest($this->config->get('api.domain'), $this->config->get('api.password'));
  }

}
