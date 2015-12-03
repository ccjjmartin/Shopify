<?php

/**
 * @file
 * Contains \Drupal\shopify\Form\ShopifySyncAdminForm.
 */

namespace Drupal\shopify\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\shopify\Batch\ShopifyProductBatch;

/**
 * Class ShopifySyncAdminForm.
 *
 * @package Drupal\shopify\Form
 */
class ShopifySyncAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'shopify.sync'
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shopify_sync_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('shopify.sync');
    $form['products'] = [
      '#type' => 'details',
      '#title' => t('Products'),
    ];
    $form['products']['sync'] = [
      '#type' => 'submit',
      '#value' => t('Sync Products'),
      '#name' => 'sync_products',
    ];
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
    switch ($form_state->getTriggeringElement()['#name']) {
      case 'sync_products':
        $this->batchSyncProducts($form, $form_state);
        break;
      default:
        parent::submitForm($form, $form_state);
        $this->config('shopify.sync')
          ->save();
    }
  }

  private function batchSyncProducts(array &$form, FormStateInterface $form_state) {
    $batch = new ShopifyProductBatch();
    $batch->prepare()->set();
  }

}
