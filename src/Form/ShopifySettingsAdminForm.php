<?php

/**
 * @file
 * Contains \Drupal\shopify\Form\ShopifySettingsAdminForm.
 */

namespace Drupal\shopify\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ShopifySettingsAdminForm.
 *
 * @package Drupal\shopify\Form
 */
class ShopifySettingsAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'shopify.shopifysettingsadmin'
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shopify_settings_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('shopify.shopifysettingsadmin');

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('shopify.shopifysettingsadmin')
      ->save();
  }

}
