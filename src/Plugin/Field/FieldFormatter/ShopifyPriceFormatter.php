<?php

/**
 * @file
 */

namespace Drupal\shopify\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\NumericFormatterBase;

/**
 * Plugin implementation of the Shopify price formatter.
 *
 * @FieldFormatter(
 *   id = "shopify_price",
 *   label = @Translation("Price"),
 *   field_types = {
 *     "decimal",
 *   }
 * )
 */
class ShopifyPriceFormatter extends NumericFormatterBase {

  public function numberFormat($number) {
    $number = number_format($number, 2);
    return shopify_currency_format($number);
  }

}
