<?php

/**
 * @file
 */

namespace Drupal\shopify;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the shopify_product entity type.
 */
class ShopifyProductViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();
    // @todo: No idea what I'm doing.
    // Something matching this setup: /core/modules/taxonomy/src/TermViewsData.php

//    $data['shopify_product']['shopify_product_tags']['entity field'] = 'shopify_product_tags';
//    $data['shopify_product']['shopify_product_tags']['help'] = t('');
//    $data['shopify_product']['shopify_product_tags']['field']['id'] = 'field';
//    $data['shopify_product']['shopify_product_tags']['filter']['id'] = 'numeric';
//    $data['shopify_product']['shopify_product_tags']['sort']['id'] = 'standard';
//    $data['shopify_product']['shopify_product_tags']['title'] = t('Shopify Product Tags');
//    $data['shopify_product']['shopify_product_tags']['relationship']['base'] = 'shopify_product__tags';
//    $data['shopify_product']['shopify_product_tags']['relationship']['base field'] = 'entity_id';
//    $data['shopify_product']['shopify_product_tags']['relationship']['id'] = 'standard';
//    $data['shopify_product']['shopify_product_tags']['relationship']['label'] = t('Shopify Product Tags');
//    $data['shopify_product']['shopify_product_tags']['relationship']['title'] = t('Shopify Product Tags');
    return $data;
  }

}
