<?php

namespace Drupal\shopify\Utility;

/**
 * Provides a wrapper around commonly-used values related to the buy button.
 */
class ShopifyBuyButtonUtility {

  const REGEX_LINE_TERM = '/\r?\n|\r/';
  const REGEX_HTML_COMMENT = '/<!--(.*?)-->/';

  /**
   * Cart components.
   *
   * @see https://github.com/Shopify/buy-button-js/blob/master/src/templates/cart.js
   */
  const CART_COMPONENTS = [
    'footer',
    'line-items',
    'title',
  ];

  /**
   * Product components.
   *
   * @see https://github.com/Shopify/buy-button-js/blob/master/src/templates/product.js
   *
   * @todo The following could also be added to support additional features:
   * description, imgWithCarousel, quantity, buttonWithQuantity.
   */
  const PRODUCT_COMPONENTS = [
    'button',
    'img',
    'options',
    'price',
    'title',
    'variant-title',
  ];

  /**
   * Cart toggle components.
   *
   * @see https://github.com/Shopify/buy-button-js/blob/master/src/templates/toggle.js
   *
   * @todo The following could also be added to support additional features:
   * title.
   */
  const TOGGLE_COMPONENTS = [
    'count',
    'icon',
  ];

  /**
   * Line item components.
   *
   * @see https://github.com/Shopify/buy-button-js/blob/master/src/templates/line-item.js
   */
  const LINE_ITEM_COMPONENTS = [
    'image',
    'price-with-discounts',
    'price',
    'quantity',
    'title',
    'variant-title',
  ];

  /**
   * Option item components.
   *
   * @see https://github.com/Shopify/buy-button-js/blob/master/src/templates/option.js
   */
  const OPTION_COMPONENTS = ['option'];

  /**
   * Money components.
   *
   * @see https://github.com/Shopify/buy-button-js/blob/master/src/defaults/money-format.js
   */
  const MONEY_COMPONENTS = ['format'];

  /**
   * Transforms a snake case string to a lower camel case string.
   *
   * Shopify JS object uses lower camel case and templates use snake case.
   *
   * @param string $suffix
   *   Snake case string.
   *
   * @return string
   *   Lower camel case string.
   */
  public static function transformSnakeCaseToLowerCamelCase($suffix) {
    return lcfirst(str_replace('-', '', ucwords($suffix, '-')));
  }

  /**
   * Removes HTML comments and whitespace.
   *
   * @param string $html
   *   HTML string.
   *
   * @return string
   *   Cleaned string.
   */
  public static function removeCommentsAndWhitespace($html) {
    $without_line_terms = preg_replace(self::REGEX_LINE_TERM, '', $html);
    $without_html_comments = preg_replace(self::REGEX_HTML_COMMENT, '', $without_line_terms);
    return trim($without_html_comments);
  }

}
