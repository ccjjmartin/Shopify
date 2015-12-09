<?php

namespace Drupal\shopify;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;

class ShopifyProductViewBuilder extends EntityViewBuilder {

  protected function alterBuild(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode) {
    $form = $display->getComponent('add_to_cart_form');
    if ($form) {
      // Need to display the add to cart form.
      $build['add_to_cart_form']['variant_options'] = \Drupal::formBuilder()
        ->getForm('Drupal\shopify\Form\ShopifyVariantOptionsForm', $entity);

      $build['add_to_cart_form']['add_to_cart'] = \Drupal::formBuilder()
        ->getForm('Drupal\shopify\Form\ShopifyAddToCartForm', $entity);

      $build['add_to_cart_form']['#weight'] = $form['#weight'];
    }
  }

}
