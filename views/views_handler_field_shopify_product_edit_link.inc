<?php
/**
 * @file
 * Field handler to edit a product.
 */

class views_handler_field_shopify_product_edit_link extends views_handler_field_shopify_product_link {

  function render_link($entity, $values) {
    $uri = entity_uri('shopify_product', $entity);
    $uri['path'] .= '/edit';
    $this->options['alter']['make_link'] = TRUE;
    $this->options['alter']['path'] = $uri['path'];
    $this->options['alter']['query'] = drupal_get_destination();
    $text = !empty($this->options['text']) ? $this->options['text'] : t('edit');

    return $text;
  }
}
