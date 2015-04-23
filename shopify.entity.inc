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

  /**
   * Product variants.
   * @var ShopifyProduct[]
   */
  public $variants;

  public function __construct(array $values = array()) {
    // Handle shopify product and variant IDs.
    if (!empty($values)) {
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
    }
    parent::__construct($values, 'shopify_product');
    if (!empty($values)) {
      $this->setTerms();
      $this->setImages();
    }
  }

  /**
   * Set up the object instance on construction or unserializiation.
   */
  public function setUp() {
    if (!$this->variant_id) {
      // This is a regular product, need to load sub-variants.
      $this->variants = $this->loadVariants();
    }
    if (!is_array($this->options) && isset($this->options)) {
      $this->options = unserialize($this->options);
    }
    if (!is_array($this->option_values) && isset($this->option_values)) {
      $this->option_values = unserialize($this->option_values);
    }
    parent::setUp();
  }

  public function save() {
    // Ensure that we are saving serialized array data.
    if (is_array($this->options)) {
      $this->options = serialize($this->options);
    }
    if (is_array($this->option_values)) {
      $this->option_values = serialize($this->option_values);
    }
    return parent::save();
  }

  function setImages() {
    $directory = file_build_uri('shopify_images');
    if (!file_prepare_directory($directory, FILE_CREATE_DIRECTORY)) {
      // If our directory doesn't exist and can't be created, use the default.
      $directory = NULL;
    }
    // @todo images on variants
    if ($this->variant_id == 0 && isset($this->images[0]['src'])) {
      foreach ($this->images as $product_image) {
        $file = system_retrieve_file($product_image['src'], $directory, TRUE, FILE_EXISTS_REPLACE);
        $files[] = (array) $file;
      }
      $this->wrapper()->shopify_product_images->set($files);
    }
  }

  function setTerms() {
    foreach (array_reverse(explode(',', $this->tags)) as $tag) {
      $tag = trim($tag);
      if ($tag) {
        $term = taxonomy_get_term_by_name($tag, 'shopify_tags');
        if (!empty($term)) {
          $tid = reset($term)->tid;
        }
        else {
          $vocab = taxonomy_vocabulary_machine_name_load('shopify_tags');
          $term = new stdClass();
          $term->name = $tag;
          $term->vid = $vocab->vid;
          taxonomy_term_save($term);
          $tid = $term->tid;
        }
        $terms[] = $tid;
      }
    }
    $this->wrapper()->shopify_product_tags->set($terms);
  }

  /**
   * Load variants for this product.
   *
   * @return ShopifyProduct[]
   *   ShopifyProducts that are variants of this product.
   */
  private function loadVariants() {
    $variants = array();
    $query = new EntityFieldQuery();
    $result = $query->entityCondition('entity_type', 'shopify_product')
      ->propertyCondition('variant_id', 0, '!=')
      ->propertyCondition('product_id', $this->product_id)
      ->execute();
    if (isset($result['shopify_product']) && $result['shopify_product']) {
      $variants = entity_load('shopify_product', array_keys($result['shopify_product']));
//      if (count($variants) == 1 && strtolower(reset($variants)->title) == 'default title') {
//        // Don't need to consider this as a variant.
//        $variants = array();
//      }
    }
    return $variants;
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

  public function buildContent($entity, $view_mode = 'full', $langcode = NULL, $content = array()) {
    switch ($view_mode) {
      case 'full':
        $content['add_to_cart'] = drupal_get_form('shopify_add_to_cart_form', $entity);
        break;
    }
    return parent::buildContent($entity, $view_mode, $langcode, $content);
  }

  /**
   * Renders a single entity property.
   */
  protected function renderEntityProperty($wrapper, $name, $property, $view_mode, $langcode, &$content) {
    $info = $property->info();

    $content[$name] = array(
      // Overrridden to hide labels by default
      '#label_hidden' => TRUE,
      '#label' => $info['label'],
      '#entity_wrapped' => $wrapper,
      '#theme' => 'entity_property',
      '#property_name' => $name,
      '#access' => $property->access('view'),
      '#entity_type' => $this->entityType,
    );
    $content['#attached']['css']['entity.theme'] = drupal_get_path('module', 'entity') . '/theme/entity.theme.css';
  }
}

class ShopifyProductMetadataController extends EntityDefaultMetadataController {
  public function entityPropertyInfo() {
    // uncomment to add all properties as extra fields
    //$info = parent::entityPropertyInfo();
    $info[$this->type]['properties']['product_id'] = array(
      'label' => t('Shopify Product ID'),
      'type' => 'integer',
      'description' => t('Shopify Product ID'),
      'schema field' => 'id',
      'setter callback' => 'entity_property_verbatim_set',
      'getter callback' => 'entity_property_verbatim_get',
    );
    $info[$this->type]['properties']['variant_id'] = array(
      'label' => t('Shopify Variant ID'),
      'type' => 'integer',
      'description' => t('Shopify Variant ID'),
      'schema field' => 'variant_id',
      'setter callback' => 'entity_property_verbatim_set',
      'getter callback' => 'entity_property_verbatim_get',
    );
    $info[$this->type]['properties']['body_html'] = array(
      'label' => t('Body'),
      'type' => 'text',
      'description' => t('Body HTML'),
      'schema field' => 'body_html',
      'setter callback' => 'entity_property_verbatim_set',
      'getter callback' => 'entity_property_verbatim_get',
    );
    // @todo price is on variants not on products, update with variant info
    $info[$this->type]['properties']['price'] = array(
      'label' => t('Price'),
      'type' => 'decimal',
      'description' => t('Product Price'),
      'schema field' => 'price',
      'setter callback' => 'entity_property_verbatim_set',
      'getter callback' => 'entity_property_verbatim_get',
    );

    return $info;
  }
}

class ShopifyProductsUIController extends EntityDefaultUIController {

  public function hook_menu() {
    $items = parent::hook_menu();
    $items[$this->path]['type'] = MENU_LOCAL_TASK;
    return $items;
  }

  public function overviewForm($form, &$form_state) {
    // Only show products, not variants.
    $conditions = array('variant_id' => 0);
    $form['table'] = $this->overviewTable($conditions);
    $form['pager'] = array('#theme' => 'pager');
    return $form;
  }

  /**
   * Generates the render array for a overview table for arbitrary entities
   * matching the given conditions.
   *
   * @param $conditions
   *   An array of conditions as needed by entity_load().
   * @return Array
   *   A renderable array.
   */
  public function overviewTable($conditions = array()) {

    $query = new EntityFieldQuery();
    $query->entityCondition('entity_type', $this->entityType);

    // Add all conditions to query.
    foreach ($conditions as $key => $value) {
      $query->propertyCondition($key, $value);
    }

    if ($this->overviewPagerLimit) {
      $query->pager($this->overviewPagerLimit);
    }

    $results = $query->execute();

    $ids = isset($results[$this->entityType]) ? array_keys($results[$this->entityType]) : array();
    $entities = $ids ? entity_load($this->entityType, $ids) : array();
    ksort($entities);

    $rows = array();
    foreach ($entities as $entity) {
      $shopify_link = l('Edit on Shopify', 'http://' . variable_get('shopify_api_domain') . '/admin/products/' . $entity->product_id, array('attributes' => array('target' => '_blank')));
      $rows[] = $this->overviewTableRow($conditions, entity_id($this->entityType, $entity), $entity, array($shopify_link));
    }

    $render = array(
      '#theme' => 'table',
      '#header' => $this->overviewTableHeaders($conditions, $rows),
      '#rows' => $rows,
      '#empty' => t('None.'),
    );
    return $render;
  }

}
