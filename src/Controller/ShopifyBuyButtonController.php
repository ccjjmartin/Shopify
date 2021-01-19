<?php

namespace Drupal\shopify\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Renderer;
use Drupal\shopify\Entity\ShopifyProduct;
use Drupal\shopify\Utility\ShopifyBuyButtonUtility;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handles Shopify buy button creation.
 */
class ShopifyBuyButtonController extends ControllerBase {

  /**
   * Renderer object.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Controller constructor.
   */
  public function __construct(Renderer $renderer) {
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer')
    );
  }

  /**
   * Builds buy button for a product.
   *
   * @param \Drupal\shopify\Entity\ShopifyProduct $product
   *   The product to build the buy button for, if NULL only a cart element will
   *   be provided.
   *
   * @return array
   *   Render array.
   */
  public function buildForProduct(ShopifyProduct $product = NULL) {

    $build = [];

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
    $build['#cache']['tags'] = $config->getCacheTags();

    if ($product) {

      // Generate element with product id.
      $product_id = $product->get('product_id')->get(0)->value;
      $product_html_id = Html::getUniqueId("shopify-product-$product_id");
      $build['button'] = [
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
      $build['#cache']['tags'] = Cache::mergeTags($build['#cache']['tags'], $product->getCacheTags());
    }

    $build['#attached']['library'][] = 'shopify/shopify.buy_button';

    // Render all component templates and pass to settings object. Templates may
    // be empty, in which case nothing will be passed and Shopify will provide
    // a default template.
    $buy_button_config['templates'] = [];
    $component_template_types = [
      'cart' => ShopifyBuyButtonUtility::CART_COMPONENTS,
      'line-item' => ShopifyBuyButtonUtility::LINE_ITEM_COMPONENTS,
      'option' => ShopifyBuyButtonUtility::OPTION_COMPONENTS,
      'product' => ShopifyBuyButtonUtility::PRODUCT_COMPONENTS,
      'toggle' => ShopifyBuyButtonUtility::TOGGLE_COMPONENTS,
      'money' => ShopifyBuyButtonUtility::MONEY_COMPONENTS,
    ];

    foreach ($component_template_types as $component => $templates) {
      $component_lower_camel = ShopifyBuyButtonUtility::transformSnakeCaseToLowerCamelCase($component);
      $buy_button_config['templates'][$component_lower_camel] = new \stdClass();
      foreach ($templates as $theme_suffix) {
        $render_array = ['#theme' => "shopify_buy_button__{$component}__{$theme_suffix}"];
        $rendered_template = $this->renderer->render($render_array);
        $cleaned_template = ShopifyBuyButtonUtility::removeCommentsAndWhitespace($rendered_template);
        if (!empty($cleaned_template)) {
          $theme_suffix_lower_camel = ShopifyBuyButtonUtility::transformSnakeCaseToLowerCamelCase($theme_suffix);
          $buy_button_config['templates'][$component_lower_camel]->$theme_suffix_lower_camel = $cleaned_template;
        }
      }

    }

    $build['#attached']['drupalSettings']['shopify']['buyButton'] = $buy_button_config;
    return $build;

  }

}
