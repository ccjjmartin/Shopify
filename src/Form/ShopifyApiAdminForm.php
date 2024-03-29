<?php

namespace Drupal\shopify\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Shopify\PrivateApp;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for Shopify API connection settings.
 */
class ShopifyApiAdminForm extends ConfigFormBase {

  /**
   * HTTP request client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Base URL for retrieving API version tags.
   *
   * @var string
   */
  const BUY_BUTTON_API_VERSION_REQUEST_BASE = 'https://api.github.com/repos/Shopify/buy-button-js/tags';

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, Client $http_client) {
    parent::__construct($config_factory);
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('http_client')
    );
  }

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

    // Use config factory so configuration overrides will be applied.
    $config = $this->configFactory()->get('shopify.settings');

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

    $api_replace_help = t('If left blank, your currently stored value will be used.');
    $api_empty_help = t('You have not provided a value for this information.');
    $has_api_key = !empty($config->get('api.key'));
    $form['connection']['key'] = [
      '#type' => 'password',
      '#title' => t('API key'),
      '#required' => !$has_api_key,
      '#description' => t('Enter your API key.&nbsp;'),
    ];
    $form['connection']['key']['#description'] .= $has_api_key ? $api_replace_help : $api_empty_help;

    $has_api_password = !empty($config->get('api.password'));
    $form['connection']['password'] = [
      '#type' => 'password',
      '#title' => t('Password'),
      '#required' => !$has_api_password,
      '#description' => t('Enter your API password.&nbsp;'),
    ];
    $form['connection']['password']['#description'] .= $has_api_password ? $api_replace_help : $api_empty_help;

    $has_api_secret = !empty($config->get('api.secret'));
    $form['connection']['secret'] = [
      '#type' => 'password',
      '#title' => t('Shared Secret'),
      '#required' => !$has_api_secret,
      '#description' => t('Enter your API secret.&nbsp;'),
    ];
    $form['connection']['secret']['#description'] .= $has_api_secret ? $api_replace_help : $api_empty_help;

    $has_storefront_token = !empty($config->get('api.storefront_access_token'));
    $form['connection']['storefront_access_token'] = [
      '#type' => 'password',
      '#title' => t('Storefront Access Token'),
      '#required' => !$has_storefront_token,
      '#description' => t('Enter your storefront access token.&nbsp;'),
    ];
    $form['connection']['storefront_access_token']['#description'] .= $has_storefront_token ? $api_replace_help : $api_empty_help;

    // Buy Button.
    $form['buy_button'] = [
      '#type' => 'details',
      '#title' => t('Buy Button'),
      '#open' => TRUE,
    ];

    $request_page = 1;
    $buy_button_version_options = ['latest' => 'Latest'];

    while (TRUE) {
      try {
        // Query for available tags.
        // @see https://docs.github.com/en/rest/reference/repos#list-repository-tags
        $response = $this->httpClient->get(
          self::BUY_BUTTON_API_VERSION_REQUEST_BASE,
          [
            'headers' => [
              'Accept' => 'application/vnd.github.v3+json',
            ],
            'query' => [
              'per_page' => 50,
              'page' => $request_page,
            ],
          ],
        );
      }
      catch (RequestException $e) {
        $this->messenger()->addError('Failed to retrieve buy button version availability data.');
        break;
      }

      if ($response->getStatusCode() !== 200) {
        $this->messenger()->addError('Failed to retrieve complete buy button version availability data.');
        break;
      }

      $versions = json_decode($response->getBody(), TRUE);
      // Body will be empty when paging results in no additional versions.
      $request_page++;
      if (empty($versions)) {
        break;
      }

      foreach ($versions as $version) {
        $version_tag = $version['name'];
        $buy_button_version_options[$version_tag] = $version_tag;
      }

    }

    // Buy button library version.
    $form['buy_button']['library_version'] = [
      '#type' => 'select',
      '#options' => $buy_button_version_options,
      '#title' => t('Library version'),
      '#default_value' => $config->get('api.buy_button_version'),
      '#required' => TRUE,
    ];

    // Buy button integration behaviors.
    $form['buy_button']['behavior'] = [
      '#type' => 'details',
      '#title' => t('Behaviors'),
      '#collapsible' => TRUE,
      '#open' => TRUE,
    ];

    $form['buy_button']['behavior']['checkout'] = [
      '#type' => 'select',
      '#title' => t('Checkout behavior'),
      '#options' => [
        'popup' => t('Popup'),
        'redirect' => t('Redirect'),
      ],
      '#required' => TRUE,
      '#default_value' => $config->get('cart.behavior.checkout'),
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
    $config = $this->configFactory()->get('shopify.settings');
    $domain = $form_state->getValue('domain');

    // Test connection using new values if they were provided, otherwise test
    // using previously provided values. This also allows API connection values
    // to be overridden in settings.php.
    $key = !empty($form_state->getValue('key')) ? $form_state->getValue('key') : $config->get('api.key');
    $password = !empty($form_state->getValue('password')) ? $form_state->getValue('password') : $config->get('api.password');
    $secret = !empty($form_state->getValue('secret')) ? $form_state->getValue('secret') : $config->get('api.secret');
    try {
      $client = new PrivateApp($domain, $key, $password, $secret);
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
    $config = $this->config('shopify.settings');

    // If the library version changes, shopify_library_info_build() needs to
    // re-run.
    if ($form_state->getValue('library_version') !== $config->get('api.buy_button_version')) {
      Cache::invalidateTags(['library_info']);
    }

    $config
      ->set('api.domain', $form_state->getValue('domain'))
      ->set('api.buy_button_version', $form_state->getValue('library_version'))
      ->set('cart.behavior.checkout', $form_state->getValue('checkout'))
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
      ->set('cart.interface.empty_message', $form_state->getValue('empty_message'));

    // Don't set API connection values if none are provided.
    if (!empty($form_state->getValue('key'))) {
      $config->set('api.key', $form_state->getValue('key'));
    }
    if (!empty($form_state->getValue('password'))) {
      $config->set('api.password', $form_state->getValue('password'));
    }
    if (!empty($form_state->getValue('secret'))) {
      $config->set('api.secret', $form_state->getValue('secret'));
    }
    if (!empty($form_state->getValue('storefront_access_token'))) {
      $config->set('api.storefront_access_token', $form_state->getValue('storefront_access_token'));
    }

    $config->save();
    shopify_shop_info(NULL, TRUE);
    parent::submitForm($form, $form_state);
  }

}
