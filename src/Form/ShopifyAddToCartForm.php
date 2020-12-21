<?php

namespace Drupal\shopify\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\shopify\Entity\ShopifyProduct;

/**
 * Provides the buy button and cart elements.
 */
class ShopifyAddToCartForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shopify_add_to_cart_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ShopifyProduct $product = NULL) {

    // Retrieve shopify settings.
    $config = $this->config('shopify.settings');
    $shopify_settings = $config->get();

    // Remove sensitive information from settings.
    unset(
      $shopify_settings['api']['key'],
      $shopify_settings['api']['password'],
      $shopify_settings['api']['secret']
    );

    $buy_button_config = [
      'config' => $shopify_settings,
    ];

    // Rebuild form when settings change.
    $form['#cache']['tags'] = $config->getCacheTags();

    if ($product) {

      // Generate element with product id.
      $product_id = $product->get('product_id')->get(0)->value;
      $product_html_id = Html::getUniqueId("shopify-product-$product_id");
      $form['button'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => [
          'id' => $product_html_id,
        ],
      ];

      // Add product-specific settings.
      $buy_button_config['product'] = [
        'id' => $product_id,
        'html_id' => $product_html_id,
      ];

      // Rebuild form when product changes.
      $form['#cache']['tags'] = Cache::mergeTags($form['#cache']['tags'], $product->getCacheTags());
    }

    $form['#attached']['library'][] = 'shopify/shopify.buy_button';
    $form['#attached']['drupalSettings']['shopify']['buyButton'] = $buy_button_config;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
