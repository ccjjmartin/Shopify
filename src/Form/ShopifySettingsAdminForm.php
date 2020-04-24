<?php

namespace Drupal\shopify\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Class ShopifySettingsAdminForm.
 *
 * @package Drupal\shopify\Form
 */
class ShopifySettingsAdminForm extends FormBase {

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
    try {
      $info = shopify_shop_info('', $refresh = TRUE);
    }
    catch (\Exception $e) {
      $messenger = \Drupal::messenger();
      // Error connecting to the store.
      $messenger->addError(t('Could not connect to the Shopify store.'));
      return [];
    }
    $store_info = [
      'My Store Admin' => Link::fromTextAndUrl($info->domain, Url::fromUri('https://' . $info->domain . '/admin', ['attributes' => ['target' => '_blank']])),
      'Owned By' => $info->shop_owner . ' &lt;<a href="mailto:' . $info->email . '">' . $info->email . '</a>&gt;',
      'Address' => $info->address1,
      'City' => $info->city,
      'State/Province' => $info->province,
      'Zip' => $info->zip,
    ];
    foreach ($store_info as $label => $data) {
      $form[$label] = [
        '#type' => 'item',
        '#title' => $label,
        '#markup' => $data,
      ];
    }
    $form['all_info'] = [
      '#type' => 'details',
      '#title' => t('More info'),
    ];
    foreach ($info as $label => $data) {
      if (is_null($data)) {
        $data = '[EMPTY]';
      }
      elseif (is_bool($data)) {
        $data = $data ? 'true' : 'false';
      }
      elseif (!is_scalar($data)) {
        $data = print_r($data, TRUE);
      }
      $form['all_info'][$label] = [
        '#type' => 'item',
        '#title' => $label,
        '#markup' => $data,
      ];
    }
    return $form;
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
  }

}
