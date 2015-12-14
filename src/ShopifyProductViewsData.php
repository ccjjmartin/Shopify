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

    $data['shopify_product']['tags']['title'] = t('Shopify Product Tags');
    $data['shopify_product']['tags']['help'] = t('Select based on tagged term ID.');
    $data['shopify_product']['tags']['entity field'] = 'tags';
    $data['shopify_product']['tags']['field']['id'] = 'field';
    $data['shopify_product']['tags']['filter']['field'] = 'tags_target_id';
    $data['shopify_product']['tags']['filter']['id'] = 'numeric';
//    $data['shopify_product']['tags']['table']['base'] = 'shopify_product__tags';
    $data['shopify_product']['tags']['table']['join']['shopify_product__tags'] = [
      'left_field' => 'entity_id',
      'field' => 'id',
    ];
    $data['shopify_product']['tags']['relationship'] = [
      'base' => 'shopify_product__tags',
      'base field' => 'entity_id',
      'id' => 'numeric',
      'label' => t('Tag'),
      'title' => t('Tag'),
    ];

    return $data;
  }

}
