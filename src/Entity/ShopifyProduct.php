<?php

/**
 * @file
 * Contains \Drupal\shopify\Entity\ShopifyProduct.
 */

namespace Drupal\shopify\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\file\FileInterface;
use Drupal\shopify\ShopifyProductInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\UserInterface;

/**
 * Defines the Shopify product entity.
 *
 * @ingroup shopify
 *
 * @ContentEntityType(
 *   id = "shopify_product",
 *   label = @Translation("Shopify product"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\shopify\ShopifyProductListBuilder",
 *     "views_data" = "Drupal\shopify\Entity\ShopifyProductViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\shopify\Entity\Form\ShopifyProductForm",
 *       "add" = "Drupal\shopify\Entity\Form\ShopifyProductForm",
 *       "edit" = "Drupal\shopify\Entity\Form\ShopifyProductForm",
 *       "delete" = "Drupal\shopify\Entity\Form\ShopifyProductDeleteForm",
 *     },
 *     "access" = "Drupal\shopify\ShopifyProductAccessControlHandler",
 *   },
 *   base_table = "shopify_product",
 *   admin_permission = "administer ShopifyProduct entity",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/shopify_product/{shopify_product}",
 *     "edit-form" = "/admin/shopify_product/{shopify_product}/edit",
 *     "delete-form" = "/admin/shopify_product/{shopify_product}/delete"
 *   },
 *   field_ui_base_route = "shopify_product.settings"
 * )
 */
class ShopifyProduct extends ContentEntityBase implements ShopifyProductInterface {
  use EntityChangedTrait;
  use ShopifyEntityTrait;

  const SHOPIFY_COLLECTIONS_VID = 'shopify_collections';
  const SHOPIFY_TAGS_VID = 'shopify_tags';

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage, array &$values) {
    $values = self::formatValues($values);
    parent::preCreate($storage, $values);
    $values += array(
      'user_id' => \Drupal::currentUser()->id(),
    );
  }

  private static function formatValues(array $values) {
    if (isset($values['id'])) {
      // We don't want to set the incoming product_id as the entity ID.
      $values['product_id'] = $values['id'];
      unset($values['id']);
    }

    // Format timestamps properly.
    self::formatDatetimeAsTimestamp($values, [
      'created_at',
      'published_at',
      'updated_at',
    ]);

    // Set the image for this product.
    if (isset($values['image']) && !empty($values['image'])) {
      $file = self::setupProductImage($values['image']->src);
      if ($file instanceof FileInterface) {
        $values['image'] = $file;
      }
    }
    else {
      $values['image'] = NULL;
    }

    // Format variant images as File entities.
    if (isset($values['images']) && is_array($values['images']) && !empty($values['images'])) {
      foreach ($values['images'] as $variant_image) {
        foreach ($variant_image->variant_ids as $variant_id) {
          foreach ($values['variants'] as &$variant) {
            if ($variant->id == $variant_id) {
              // Set an image for this variant.
              $variant->image = $variant_image;
            }
          }
        }
      }
    }

    if (isset($values['tags']) && !is_array($values['tags']) && !empty($values['tags'])) {
      $values['tags'] = explode(', ', $values['tags']);
      $values['tags'] = self::setupTags($values['tags']);
    }
    else {
      $values['tags'] = NULL;
    }

    // Format variants as entities.
    foreach ($values['variants'] as &$variant) {
      // Attempt to load this variant.
      $entity = shopify_variant_load_by_variant_id($variant->id);
      if ($entity instanceof ShopifyProductVariant) {
        $entity->update((array) $variant);
        $entity->save();
        $variant = $entity;
      }
      elseif (is_object($variant)) {
        $variant = ShopifyProductVariant::create((array) $variant);
      }
    }

    // Convert options.
    $values['options'] = serialize($values['options']);
    return $values;
  }

  private static function setupTags(array $tags = []) {
    $terms = [];
    foreach ($tags as $tag) {
      // Find out if this tag already exists.
      $term = taxonomy_term_load_multiple_by_name($tag, self::SHOPIFY_TAGS_VID);
      $term = reset($term);
      if ($term) {
        $terms[] = $term;
      }
      else {
        // Need to create this term.
        $terms[] = Term::create([
          'name' => $tag,
          'vid' => self::SHOPIFY_TAGS_VID,
        ]);
      }
    }
    return $terms;
  }

  /**
   * Updates existing product and variants.
   *
   * @param array $values
   *   Shopify product array.
   */
  public function update(array $values = []) {
    $entity_id = $this->id();
    $values = self::formatValues($values);
    foreach ($values as $key => $value) {
      if ($this->__isset($key)) {
        $this->set($key, $value);
      }
    }
    $this->set('id', $entity_id);
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    // Delete this products image.
    if ($this->image instanceof FileInterface) {
      $this->image->delete();
    }
    // Delete all variants for this product.
    foreach ($this->get('variants') as $variant) {
      $variant = ShopifyProductVariant::load($variant->target_id);
      $variant->delete();
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
      ->setDescription(t('The ID of the Shopify product entity.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Shopify product entity.'))
      ->setReadOnly(TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The title of the Shopify product entity.'))
      ->setRequired(TRUE)
      ->setSettings(array(
        'max_length' => 50,
        'text_processing' => 0,
      ))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -50,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -50,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Shopify product entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\node\Entity\Node::getCurrentUserId')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'author',
        'weight' => -25,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => -25,
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ),
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The language code for the Shopify product entity.'));

    $fields['variants'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Product variants'))
      ->setDescription(t('Product variants for this product.'))
      ->setSetting('target_type', 'shopify_product_variant')
      ->setSetting('handler', 'default')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'entity_reference_entity_view',
        'weight' => -25,
      ))
      ->setDisplayOptions('form', array(
//        'type' => 'inline_entity_form_complex', // @todo: Would prefer to use inline entity form, but it's buggy, not working...
        'type' => 'entity_reference_autocomplete_tags',
        'weight' => -25,
        'settings' => array(
//          'match_operator' => 'CONTAINS',
//          'autocomplete_type' => 'tags',
//          'placeholder' => '',
        ),
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['product_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Product ID'))
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

    $fields['body_html'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Body HTML'))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'text_default',
        'weight' => -2,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'text_textfield',
        'weight' => -2,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['handle'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Handle'))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -1,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -1,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['product_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Product type'))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => 0,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 0,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['published_scope'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Published scope'))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => 1,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 1,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['vendor'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Vendor'))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => 2,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 2,
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
        'type' => 'image_image',
        'weight' => 2,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['options'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Options'))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'map',
        'weight' => 2,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'map',
        'weight' => 2,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tags'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tags'))
      ->setDescription(t('Product tags.'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('target_bundles', ['shopify_tags'])// @todo: Not working.
      ->setSetting('handler', 'default')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'entity_reference_entity_view',
        'weight' => -25,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete_tags',
        'weight' => -25,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['collections'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Collections'))
      ->setDescription(t('Product collections.'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('target_bundles', ['shopify_collections'])// @todo: Not working.
      ->setSetting('handler', 'default')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'entity_reference_entity_view',
        'weight' => -25,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete_tags',
        'weight' => -25,
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

    $fields['published_at'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Published'))
      ->setDescription(t('The time that the product was published.'));

//    $fields['options'] = BaseFieldDefinition::create('list_string')
//      ->setLabel(t('Options'))
//      ->setDefaultValue('')
//      ->setDisplayOptions('view', array(
//        'label' => 'above',
//        'type' => 'list_default',
//        'weight' => 2,
//      ))
//      ->setDisplayOptions('form', array(
//        'type' => 'list_key',
//        'weight' => 2,
//      ))
//      ->setDisplayConfigurable('form', TRUE)
//      ->setDisplayConfigurable('view', TRUE);

    // @todo: tags.
    // @todo: options.

    return $fields;
  }

}

































