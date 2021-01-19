<?php

namespace Drupal\shopify\Plugin\Block;

use Drupal\Core\Block\BlockBase;

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
  public function getCacheTags() {
    // Rebuild when module settings change.
    return ['config:shopify.setting'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    /** @var \Drupal\shopify\Controller\ShopifyBuyButtonController $buy_button_controller */
    $buy_button_controller = \Drupal::service('shopify.buy_button_controller');
    return $buy_button_controller->buildForProduct(NULL);
  }

}
