<?php

/**
 * @file
 * Contains \Drupal\shopify\Form\ShopifyWebhooksAdminForm.
 */

namespace Drupal\shopify\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ShopifyWebhooksAdminForm.
 *
 * @package Drupal\shopify\Form
 */
class ShopifyWebhooksAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'shopify.shopifywebhooksadmin'
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shopify_webhooks_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('shopify.shopifywebhooksadmin');

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

    $this->config('shopify.shopifywebhooksadmin')
      ->save();
  }

}
