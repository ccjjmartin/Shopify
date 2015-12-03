<?php

/**
 * @file
 * Contains \Drupal\shopify\Entity\Form\ShopifyProductForm.
 */

namespace Drupal\shopify\Entity\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\Language;

/**
 * Form controller for Shopify product edit forms.
 *
 * @ingroup shopify
 */
class ShopifyProductForm extends ContentEntityForm {
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\shopify\Entity\ShopifyProduct */
    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;

    // User cannot alter the 'name' field.
//    $this->setDisabled($form, ['name', 'product_id','body_html','handle','product_type','published_scope','vendor','barcode','fulfillment_service','inventory_management','inventory_policy','sku','weight_unit','published']);

    $form['langcode'] = array(
      '#title' => $this->t('Language'),
      '#type' => 'language_select',
      '#default_value' => $entity->langcode->value,
      '#languages' => Language::STATE_ALL,
    );

    return $form;
  }

  private function setDisabled(array &$form, array $fields) {
    foreach ($fields as $name) {
      $form[$name]['#disabled'] = TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, FormStateInterface $form_state) {
    // Build the entity object from the submitted values.
    $entity = parent::submit($form, $form_state);

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $status = $entity->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Shopify product.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Shopify product.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.shopify_product.edit_form', ['shopify_product' => $entity->id()]);
  }

}
