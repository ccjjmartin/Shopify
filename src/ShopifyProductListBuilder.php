<?php

/**
 * @file
 * Contains \Drupal\shopify\ShopifyProductListBuilder.
 */

namespace Drupal\shopify;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Shopify product entities.
 *
 * @ingroup shopify
 */
class ShopifyProductListBuilder extends EntityListBuilder {
  use LinkGeneratorTrait;
  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Shopify product ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\shopify\Entity\ShopifyProduct */
    $row['id'] = $entity->id();
    $row['name'] = $this->l(
      $this->getLabel($entity),
      new Url(
        'entity.shopify_product.edit_form', array(
          'shopify_product' => $entity->id(),
        )
      )
    );
    return $row + parent::buildRow($entity);
  }

}
