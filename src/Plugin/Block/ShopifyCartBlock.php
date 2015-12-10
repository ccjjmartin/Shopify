<?php

/**
 * @file
 * Contains \Drupal\shopify\Plugin\Block\ShopifyCartBlock.
 */

namespace Drupal\shopify\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;

/**
 * Provides the shopping cart block.
 *
 * @Block(
 *  id = "shopify_cart",
 *  admin_label = @Translation("Cart")
 * )
 */
class ShopifyCartBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    // Contents of cart don't depend on the page or user or any other
    // cache context we have available.
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build[] = [
      '#theme' => 'shopify_cart',
      '#domain' => shopify_shop_info('domain'),
      '#url' => Url::fromUri('https://' . shopify_shop_info('domain') . '/cart'),
      '#attached' => [
        'library' => ['shopify/shopify.js'],
        'drupalSettings' => ['shopify' => shopify_drupal_js_data()],
      ],
    ];
    return $build;
  }

}
