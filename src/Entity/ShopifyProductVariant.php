<?php

/**
 * @file
 * Contains \Drupal\shopify\Entity\ShopifyProductVariant.
 */

namespace Drupal\shopify\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\file\FileInterface;
use Drupal\shopify\ShopifyProductVariantInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Shopify product variant entity.
 *
 * @ingroup shopify
 *
 * @ContentEntityType(
 *   id = "shopify_product_variant",
 *   label = @Translation("Shopify product variant"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\shopify\ShopifyProductVariantListBuilder",
 *     "views_data" = "Drupal\shopify\Entity\ShopifyProductVariantViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\shopify\Entity\Form\ShopifyProductVariantForm",
 *       "add" = "Drupal\shopify\Entity\Form\ShopifyProductVariantForm",
 *       "edit" = "Drupal\shopify\Entity\Form\ShopifyProductVariantForm",
 *       "delete" = "Drupal\shopify\Entity\Form\ShopifyProductVariantDeleteForm",
 *     },
 *     "access" = "Drupal\shopify\ShopifyProductVariantAccessControlHandler",
 *   },
 *   base_table = "shopify_product_variant",
 *   admin_permission = "administer ShopifyProductVariant entity",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/shopify_product_variant/{shopify_product_variant}",
 *     "edit-form" = "/admin/shopify_product_variant/{shopify_product_variant}/edit",
 *     "delete-form" = "/admin/shopify_product_variant/{shopify_product_variant}/delete"
 *   },
 *   field_ui_base_route = "shopify_product_variant.settings"
 * )
 */
class ShopifyProductVariant extends ContentEntityBase implements ShopifyProductVariantInterface {
  use EntityChangedTrait;
  use ShopifyEntityTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    if (isset($values['id'])) {
      // We don't want to set the incoming product_id as the entity ID.
      $values['variant_id'] = $values['id'];
      unset($values['id']);
    }

    // Format timestamps properly.
    self::formatDatetimeAsTimestamp($values, [
      'created_at',
      'updated_at',
    ]);

    // Setup image.
    if (isset($values['image']) && !empty($values['image'])) {
      $file = self::setupProductImage($values['image']->src);
      if ($file instanceof FileInterface) {
        $values['image'] = $file;
      }
    }

    parent::preCreate($storage_controller, $values);
    $values += array(
      'user_id' => \Drupal::currentUser()->id(),
    );
  }

  public function delete() {
    if ($this->image instanceof FileInterface) {
      $this->image->delete();
    }
    parent::delete();
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Shopify product variant entity.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Shopify product variant entity.'))
      ->setReadOnly(TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Shopify product variant entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\node\Entity\Node::getCurrentUserId')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ),
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The title of the Shopify product variant entity.'))
      ->setRequired(TRUE)
      ->setSettings(array(
        'max_length' => 50,
        'text_processing' => 0,
      ))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The language code for the Shopify product variant entity.'));

    $fields['inventory_management'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Inventory management'))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => 5,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 5,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['inventory_policy'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Inventory policy'))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => 6,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 6,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['sku'] = BaseFieldDefinition::create('string')
      ->setLabel(t('SKU'))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => 7,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 7,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['fulfillment_service'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Fulfillment service'))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => 4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['barcode'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Barcode'))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => 3,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 3,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['grams'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Grams'))
      ->setSettings(array(
        'unsigned' => TRUE,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'integer',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'number',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['inventory_quantity'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Inventory quantity'))
      ->setSettings(array(
        'unsigned' => TRUE,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'integer',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'number',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['inventory_old_quantity'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Inventory old quantity'))
      ->setSettings(array(
        'unsigned' => TRUE,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'integer',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'number',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['position'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Position'))
      ->setSettings(array(
        'unsigned' => TRUE,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'integer',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'number',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['weight'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Weight'))
      ->setSettings(array(
        'precision' => 10,
        'scale' => 2,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'decimal',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'number',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['weight_unit'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Weight unit'))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => 8,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 8,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['price'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Price'))
      ->setSettings(array(
        'precision' => 10,
        'scale' => 2,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'decimal',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'number',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['compare_at_price'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Compare at price'))
      ->setSettings(array(
        'precision' => 10,
        'scale' => 2,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'decimal',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'number',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['taxable'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Taxable'))
      ->setSettings(array(
        'on_label' => 'Taxable',
        'off_label' => 'Not taxable',
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'boolean',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'boolean_checkbox',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['requires_shipping'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Requires shipping'))
      ->setSettings(array(
        'on_label' => 'Require shipping',
        'off_label' => 'Do not require shipping',
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'boolean',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'boolean_checkbox',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['image'] = BaseFieldDefinition::create('image')
      ->setLabel(t('Image'))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'image',
        'weight' => 2,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'image',
        'weight' => 2,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['option1'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Option 1'))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => 4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['option2'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Option 2'))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => 4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['option3'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Option 3'))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => 4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);


    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last udpated.'));

    $fields['created_at'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the product was created.'));

    $fields['updated_at'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Updated'))
      ->setDescription(t('The time that the product was last updated.'));

    // @todo: option_values.

    return $fields;
  }

}
