<?php

namespace Drupal\shopify\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\shopify\Form\ShopifyAddToCartForm;

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
    return \Drupal::formBuilder()->getForm(ShopifyAddToCartForm::class, NULL);
  }

}
