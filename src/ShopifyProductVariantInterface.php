<?php

/**
 * @file
 * Contains \Drupal\shopify\ShopifyProductVariantInterface.
 */

namespace Drupal\shopify;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Shopify product variant entities.
 *
 * @ingroup shopify
 */
interface ShopifyProductVariantInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {
  // Add get/set methods for your configuration properties here.

}
