<?php

namespace Drupal\shopify\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Shopify\PrivateApp;

/**
 * Form for Shopify API connection settings.
 */
class ShopifyApiAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shopify_api_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'shopify.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('shopify.settings');

    // Connection.
    $form['connection'] = [
      '#type' => 'details',
      '#title' => t('Connection'),
      '#open' => TRUE,
    ];
    $form['connection']['help'] = [
      '#type' => 'details',
      '#title' => t('Help'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];
    $form['connection']['help']['list'] = [
      '#theme' => 'item_list',
      '#type' => 'ol',
      '#items' => [
        t('Log in to your Shopify store in order to access the administration section.'),
        t('Click on "Apps" on the left-side menu.'),
        t('Click "Private Apps" on the top-right of the page.'),
        t('Enter a name for the application. This is private and the name does not matter.'),
        t('Click "Save App".'),
        t('Copy the API Key, Password, and Shared Secret values into the connection form.'),
        t('Enter your Shopify store URL as the "Domain". It should be in the format of [STORE_NAME].myshopify.com.'),
        t('Click "Save configuration".'),
      ],
    ];
    $form['connection']['domain'] = [
      '#type' => 'textfield',
      '#title' => t('Domain'),
      '#required' => TRUE,
      '#default_value' => $config->get('api.domain'),
      '#description' => t('Do not include http:// or https://.'),
    ];
    $form['connection']['key'] = [
      '#type' => 'textfield',
      '#title' => t('API key'),
      '#required' => TRUE,
      '#default_value' => $config->get('api.key'),
    ];
    $form['connection']['password'] = [
      '#type' => 'textfield',
      '#title' => t('Password'),
      '#required' => TRUE,
      '#default_value' => $config->get('api.password'),
    ];
    $form['connection']['secret'] = [
      '#type' => 'textfield',
      '#title' => t('Shared Secret'),
      '#required' => TRUE,
      '#default_value' => $config->get('api.secret'),
    ];
    $form['connection']['storefront_access_token'] = [
      '#type' => 'textfield',
      '#title' => t('Storefront Access Token'),
      '#required' => TRUE,
      '#default_value' => $config->get('api.storefront_access_token'),
    ];

    // Buy Button.
    $form['buy_button'] = [
      '#type' => 'details',
      '#title' => t('Buy Button'),
      '#open' => TRUE,
    ];
    $form['buy_button']['corner_radius'] = [
      '#type' => 'number',
      '#title' => t('Corner Radius'),
      '#required' => TRUE,
      '#default_value' => $config->get('button.styles.corner_radius'),
    ];
    $form['buy_button']['width'] = [
      '#type' => 'number',
      '#title' => t('Width'),
      '#required' => TRUE,
      '#default_value' => $config->get('button.styles.width'),
    ];
    $form['buy_button']['background_color'] = [
      '#type' => 'color',
      '#title' => t('Background Color'),
      '#required' => TRUE,
      '#default_value' => $config->get('button.styles.background_color'),
    ];
    $form['buy_button']['text_color'] = [
      '#type' => 'color',
      '#title' => t('Text Color'),
      '#required' => TRUE,
      '#default_value' => $config->get('button.styles.text_color'),
    ];
    $form['buy_button']['font_size'] = [
      '#type' => 'number',
      '#title' => t('Font Size'),
      '#required' => TRUE,
      '#default_value' => $config->get('button.styles.font_size'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    try {
      $client = new PrivateApp($form_state->getValue('domain'), $form_state->getValue('key'), $form_state->getValue('password'), $form_state->getValue('secret'));
      $shop_info = $client->getShopInfo();
      $this->messenger()->addMessage(t('Successfully connected to %store.', ['%store' => $shop_info->name]));
    }
    catch (\Exception $e) {
      $form_state->setErrorByName(NULL, 'API Error: ' . $e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('shopify.settings')
      ->set('api.domain', $form_state->getValue('domain'))
      ->set('api.key', $form_state->getValue('key'))
      ->set('api.password', $form_state->getValue('password'))
      ->set('api.secret', $form_state->getValue('secret'))
      ->set('api.storefront_access_token', $form_state->getValue('storefront_access_token'))
      ->set('button.styles.corner_radius', $form_state->getValue('corner_radius'))
      ->set('button.styles.width', $form_state->getValue('width'))
      ->set('button.styles.background_color', $form_state->getValue('background_color'))
      ->set('button.styles.text_color', $form_state->getValue('text_color'))
      ->set('button.styles.font_size', $form_state->getValue('font_size'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
