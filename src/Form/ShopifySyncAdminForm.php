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
//    $config = $this->config('shopify.sync');

    $products_last_sync_time = \Drupal::state()
      ->get('shopify.sync.products_last_sync_time');

    if (empty($products_last_sync_time)) {
      $products_last_sync_time_formatted = t('Never');
    }
    else {
      $products_last_sync_time_formatted = \Drupal::service('date.formatter')
        ->format($products_last_sync_time, 'medium');
    }

    $form['products'] = [
      '#type' => 'details',
      '#title' => t('Sync Products'),
      '#description' => t('Last sync time: @time', [
        '@time' => $products_last_sync_time_formatted,
      ]),
    ];
    $form['products']['num_per_batch'] = array(
      '#type' => 'select',
      '#title' => 'Choose how many products to sync per batch operation (not per batch).',
      '#options' => array(
        '1' => t('1 at a time'),
        '10' => t('10 at a time'),
        '50' => t('50 at a time'),
        '100' => t('100 at a time'),
        '250' => t('250 (Max API limit)'),
      ),
      '#default_value' => 250,
    );
    $form['products']['delete_products_first'] = array(
      '#type' => 'checkbox',
      '#title' => t('Delete all products then re-import fresh.') . '<br /><strong>' . t('CAUTION: Product entities will be completely deleted then re-imported. Custom fields will be erased, comments deleted, etc.') . '</strong>',
    );
    $form['products']['force_update'] = array(
      '#type' => 'checkbox',
      '#title' => t('Update all products regardless of last sync time. Product entities will be updated, not deleted.'),
    );
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

    $last_sync_time = \Drupal::state()
      ->get('shopify.sync.products_last_sync_time');

    $batch->prepare([
      'num_per_batch' => $form_state->getValue('num_per_batch'),
      'delete_products_first' => $form_state->getValue('delete_products_first'),
      'force_update' => $form_state->getValue('force_update'),
      'updated_at_min' => $last_sync_time,
    ])->set();
  }

}
