<?php
/**
 * @file
 * Contains entity classes for Shopify products.
 */

/**
 * Class ShopifyProduct
 */
class ShopifyProduct extends Entity {

  /**
   * The name of the product.
   * @var string
   */
  public $title;

  /**
   * The description of the product, complete with HTML formatting.
   * @var string
   */
  public $body_html;

  /**
   * A human-friendly unique string for the Product automatically generated from its title.
   * They are used by the Liquid templating language to refer to objects.
   * @var string
   */
  public $handle;

  /**
   * The unique numeric identifier for the product.
   * Product ids are unique across the entire Shopify system;
   * no two products will have the same id, even if they're from different shops.
   * @var int
   */
  public $product_id;

  /**
   * The unique numeric identifier for the product variant.
   * @var int
   */
  public $variant_id;

  /**
   * A categorization that a product can be tagged with,
   * commonly used for filtering and searching.
   * @var string
   */
  public $product_type;

  /**
   * The sales channels in which the product is visible.
   * @var string
   */
  public $published_scope;

  /**
   * The name of the vendor of the product.
   * @var string
   */
  public $vendor;

  /**
   * A categorization that a product can be tagged with,
   * commonly used for filtering and searching.
   * @var string
   */
  public $tags;

  /**
   * Custom properties that a shop owner can use to define product variants.
   * Multiple options can exist. Serialized array?
   *
   * @todo: What?
   * @var array
   */
  public $options;

  /**
   * The barcode, UPC or ISBN number for the product.
   * @var string
   */
  public $barcode;

  /**
   * The competitors prices for the same item.
   * @var double
   */
  public $compare_at_price;

  /**
   * Service who is doing the fulfillment. Valid values are: manual.
   * @var string
   */
  public $fulfillment_service;

  /**
   * The weight of the product variant in grams.
   * @var int
   */
  public $grams;

  /**
   * Specifies whether or not Shopify tracks the number of items in stock for this product variant.
   * Valid values are:
   *  - blank: Shopify does not track the number of items in stock for this product variant.
   *  - shopify: Shopify does track the number of items in stock for this product variant.
   * @var string
   */
  public $inventory_management;

  /**
   * Specifies whether or not customers are allowed to place an order for a product variant when it's out of stock.
   * Valid values are:
   *  - deny (default): Customers are not allowed to place orders for a product variant when it's out of stock.
   *  - continue: Customers are allowed to place orders for a product variatn when it's out of stock.
   * @var string
   */
  public $inventory_policy;

  /**
   * The number of items in stock for this product variant.
   * @var int
   */
  public $inventory_quantity;

  /**
   * The original stock level the client believes the product variant has.
   * This should be sent to avoid a race condition when the item being adjusted is simultaneously sold online.
   * @var int
   */
  public $old_inventory_quantity;

  /**
   * Custom product property names like "Size", "Color", and "Material".
   * Products are based on permutations of these options.
   * A product may have a maximum of 3 options. 255 characters limit each.
   * @var array
   */
  public $option_values;

  /**
   * The price of the product variant.
   * @var double
   */
  public $price;

  /**
   * Specifies whether or not a customer needs to provide a shipping address when placing an order for this product variant.
   * Valid values are:
   *  - true: Customer needs to supply a shipping address.
   *  - false: Customer does not need to supply a shipping address.
   * @var boolean
   */
  public $requires_shipping;

  /**
   * A unique identifier for the product in the shop.
   * @var string
   */
  public $sku;

  /**
   * Specifies whether or not a tax is charged when the product variant is sole.
   * @var boolean
   */
  public $taxable;

  /**
   * The weight of the product variant in the unit system specified with weight_unit.
   * @var double
   */
  public $weight;

  /**
   * The unit system that the product variant's weight is measure in.
   * The weight_unit can be either "g", "kg, "oz", or "lb".
   * @var string
   */
  public $weight_unit;

  /**
   * The date and time when the product was created.
   * @var string
   */
  public $created_at;

  /**
   * The date and time when the product was published.
   * @var string
   */
  public $published_at;

  /**
   * The date and time when the product was last modified.
   * @var string
   */
  public $updated_at;

  public function __construct(array $values = array()) {

    // Handle shopify product and variant IDs.
    if (isset($values['id']) && !$values['product_id']) {
      $values['product_id'] = $values['id'];
    }
    elseif (isset($values['id']) && $values['product_id']) {
      $values['variant_id'] = $values['id'];
    }
    unset($values['id']);

    if (empty($values['variant_id'])) {
      $values['variant_id'] = 0;
    }

    // Format variant option values.
    if (!$values['variants']) {
      // This is a variant product.
      $values['option_values'] = $this->formatVariantOptions($values);
    }

    // Options should be stored as a serialized array.
    if (isset($values['options'])) {
      $values['options'] = serialize($values['options']);
    }

    parent::__construct($values, 'shopify_product');
  }

  /**
   * Formats variant option values.
   *
   * @param array $values
   *
   * @return string
   *   Serialized array of option values.
   */
  private function formatVariantOptions(array $values = array()) {
    $option_values = array();
    foreach ($values as $key => $value) {
      if (strpos($key, 'option') !== FALSE) {
        $option_values[] = $value;
      }
    }
    return serialize($option_values);
  }

}

class ShopifyProductController extends EntityAPIController {

}