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

    $data['shopify_product']['tags']['title'] = t('Shopify Product Tags');
    $data['shopify_product']['tags']['help'] = t('Select based on tagged term ID.');
    $data['shopify_product']['tags']['entity field'] = 'tags';
    $data['shopify_product']['tags']['field']['id'] = 'field';

    $data['shopify_product']['tags']['filter']['field'] = 'tags_target_id';
    $data['shopify_product']['tags']['filter']['id'] = 'shopify_tags_filter';

    $data['shopify_product']['tags']['argument']['field'] = 'tags_target_id';
    $data['shopify_product']['tags']['argument']['id'] = 'shopify_tags_argument';
    $data['shopify_product']['tags']['argument']['name field'] = 'tid';
    $data['shopify_product']['tags']['argument']['name table'] = 'taxonomy_term_field_data';

    return $data;
  }

}
