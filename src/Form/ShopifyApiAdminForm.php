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

    // Buy button interface elements.
    $form['buy_button']['button'] = [
      '#type' => 'details',
      '#title' => t('Button elements'),
      '#collapsible' => TRUE,
      '#open' => TRUE,
    ];
    $form['buy_button']['button']['button_text'] = [
      '#type' => 'textfield',
      '#title' => t('Button text'),
      '#required' => TRUE,
      '#default_value' => $config->get('button.interface.button_text'),
    ];
    $form['buy_button']['button']['show_price'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable product variant prices'),
      '#default_value' => $config->get('button.interface.show_price'),
    ];
    $form['buy_button']['button']['show_title'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable product variant titles'),
      '#default_value' => $config->get('button.interface.show_title'),
    ];
    $form['buy_button']['button']['show_image'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable product variant images'),
      '#default_value' => $config->get('button.interface.show_image'),
    ];

    // Shopping cart interface text.
    $form['buy_button']['cart'] = [
      '#type' => 'details',
      '#title' => t('Shopping cart elements'),
      '#open' => TRUE,
    ];
    $form['buy_button']['cart']['heading_label'] = [
      '#type' => 'textfield',
      '#title' => t('Cart heading'),
      '#required' => TRUE,
      '#default_value' => $config->get('cart.interface.heading_label'),
    ];
    $form['buy_button']['cart']['subtotal_label'] = [
      '#type' => 'textfield',
      '#title' => t('Subtotal label'),
      '#required' => TRUE,
      '#default_value' => $config->get('cart.interface.subtotal_label'),

    ];
    $form['buy_button']['cart']['show_order_note'] = [
      '#type' => 'checkbox',
      '#title' => t('Provide order note field'),
      '#required' => FALSE,
      '#default_value' => $config->get('cart.interface.show_order_note'),
    ];
    $form['buy_button']['cart']['order_note_label'] = [
      '#type' => 'textfield',
      '#title' => t('Order note label'),
      '#required' => FALSE,
      '#default_value' => $config->get('cart.interface.order_note_label'),
      '#states' => [
        'visible' => [
          ':input[name="show_order_note"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['buy_button']['cart']['additional_info_text'] = [
      '#type' => 'textfield',
      '#title' => t('Additional information'),
      '#required' => TRUE,
      '#default_value' => $config->get('cart.interface.additional_info_text'),
    ];
    $form['buy_button']['cart']['checkout_button_label'] = [
      '#type' => 'textfield',
      '#title' => t('Checkout button label'),
      '#required' => TRUE,
      '#default_value' => $config->get('cart.interface.checkout_button_label'),
    ];
    $form['buy_button']['cart']['empty_message'] = [
      '#type' => 'textfield',
      '#title' => t('Empty cart message'),
      '#required' => TRUE,
      '#default_value' => $config->get('cart.interface.empty_message'),
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
      ->set('button.interface.button_text', $form_state->getValue('button_text'))
      ->set('button.interface.show_price', $form_state->getValue('show_price'))
      ->set('button.interface.show_title', $form_state->getValue('show_title'))
      ->set('button.interface.show_image', $form_state->getValue('show_image'))
      ->set('cart.interface.heading_label', $form_state->getValue('heading_label'))
      ->set('cart.interface.subtotal_label', $form_state->getValue('subtotal_label'))
      ->set('cart.interface.show_order_note', $form_state->getValue('show_order_note'))
      ->set('cart.interface.order_note_label', $form_state->getValue('order_note_label'))
      ->set('cart.interface.additional_info_text', $form_state->getValue('additional_info_text'))
      ->set('cart.interface.checkout_button_label', $form_state->getValue('checkout_button_label'))
      ->set('cart.interface.empty_message', $form_state->getValue('empty_message'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
