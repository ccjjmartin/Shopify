<?php
/**
 * @file
 * Contains functions and forms for admin pages.
 */

/**
 * Admin page callback.
 */
function shopify_admin_page() {
  try {
    $info = shopify_api_get_shop_info($refresh = TRUE);
  } catch (Exception $e) {
    drupal_set_message(t('Unable to connect to your Shopify store.'), 'error');
    return t('Hmm.. we\'re unable to connect to your Shopify Store. <ul><li>Do you have a <a href="https://www.shopify.com/?ref=bonify">Shopify account</a>?</li><li>If you have an account you\'ll need to create your API key if you haven\'t already. See <a href="https://docs.shopify.com/support/configuration/apps/where-do-i-find-my-api-key">https://docs.shopify.com/support/configuration/apps/where-do-i-find-my-api-key</a> for more information.</li><li>Have you checked your <a href="/admin/config/services/shopify">API settings to make sure they match?</a></li><li><a href="/admin/reports/status">Can your server make outgoing HTTP requests?</li></ul>');
  }
  $store_meta = array(
    'My Store Admin' => l($info->myshopify_domain, 'https://' . $info->myshopify_domain . '/admin'),
    'Owned By' => $info->shop_owner,
    'Address' => $info->address1,
    'City' => $info->city,
    'State/Province' => $info->province,
  );

  $return = '';
  foreach ($store_meta as $label => $meta) {
    $return .= '<strong>' . $label . '</strong>: ' . $meta . '<br />';
  }
  return $return;
}

/**
 * Shopify products sync page form.
 */
function shopify_admin_product_sync_form($form, &$form_state) {
  try {
    shopify_api_get_shop_info();
  } catch (Exception $e) {
    drupal_set_message(t('Unable to connect to your Shopify store.'), 'error');
    $form['oops'] = array('#markup' => t('<a href="/admin/shopify">Unable to connect to your store.</a>'));
    return $form;
  }

  $last_updated = variable_get('shopify_last_sync');
  if ($last_updated == '1970-01-01 00:00') {
    $last_updated = 'never';
  }

  $info = 'Last sync !last_updated. Only products updated since the last sync will ';
  $info .= 'be pulled and synced. Products updated via webhooks will be updated anyway. ';
  $info .= 'Custom fields you\'ve added yourself will not be overridden on update.';

  $form['info'] = array(
    '#type' => 'markup',
    '#markup' => t($info, array('!last_updated' => $last_updated)),
  );

  $form['num_per_batch'] = array(
    '#type' => 'select',
    '#title' => 'Choose how many products to sync per batch operation (not per batch).',
    '#options' => array(
      '1' => t('One at a time'),
      '50' => t('50 at a time'),
      '250' => t('250 (Max API limit)'),
    ),
    '#default_value' => 250,
  );

  $form['delete'] = array(
    '#type' => 'checkbox',
    '#title' => t('Delete all products then re-import fresh. CAUTION: Product entities will be completely deleted then re-imported. Custom fields will be erased, comments deleted, etc.'),
  );

  $form['update_anyway'] = array(
    '#type' => 'checkbox',
    '#title' => t('Update all products regardless of last sync time. Product entities will be updated, not  deleted.'),
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => 'Sync Products',
  );

  return $form;
}

/**
 * Shopify products sync page form.
 */
function shopify_admin_collection_sync_form($form, &$form_state) {
  try {
    shopify_api_get_shop_info();
  } catch (Exception $e) {
    drupal_set_message(t('Unable to connect to your Shopify store.'), 'error');
    $form['oops'] = array('#markup' => t('<a href="/admin/shopify">Unable to connect to your store.</a>'));
    return $form;
  }

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => 'Sync Collections',
  );

  return $form;
}

function shopify_admin_collection_sync_form_submit($form, $form_state) {
  // Pull collection information.
  $client = shopify_api_client();
  $collects = $client->call('GET', '/admin/collects.json');
  if (!$collects) {
    return;
  }

  // Handle the creation of new collections.
  foreach ($collects as $c) {
    try {
      $c_info = $client->call('GET', '/admin/smart_collections/' . $c['collection_id'] . '.json');
    } catch (Exception $e) {
      // Perchance this is actually a custom_collection?
      try {
        $c_info = $client->call('GET', '/admin/custom_collections/' . $c['collection_id'] . '.json');
      } catch (Exception $e) {
        $c_info = FALSE;
      }
    }
    if ($c_info) {
      // Check if this collection already exists.
      $vocab = taxonomy_vocabulary_machine_name_load('shopify_collections');
      $t = taxonomy_get_term_by_name($c_info['title'], $vocab->machine_name);
      if (!$t) {
        // Need to create this taxonomy.
        $new_term = entity_create('taxonomy_term', array(
          'vid' => $vocab->vid,
          'name' => $c_info['title'],
          'description' => $c_info['body_html'],
        ));
        if (taxonomy_term_save($new_term) && isset($c_info['image']['src'])) {
          // Save the image for this term.
          shopify_collections_save_image($new_term, $c_info['image']['src']);
        }

//        shopify_collections_save_image($new_term, )
        drupal_set_message(t('Created new collection: @name', array('@name' => $c_info['title'])));
        unset($new_term);
      }
      // Update this product information.
      $t = taxonomy_get_term_by_name($c_info['title'], $vocab->machine_name);
      $t = reset($t);
      if ($t) {
        $product = shopify_product_load_single($c['product_id']);
        $collections = $product->wrapper()->shopify_product_collections->value();
        foreach ($collections as $collect) {
          if ($collect->tid == $t->tid) {
            // Product already in collection.
            continue 2;
          }
        }
        $product->wrapper()->shopify_product_collections[] = $t->tid;
        $product->save();
        drupal_set_message(t('Put product @p into collection @c.', array(
          '@p' => $product->title,
          '@c' => $t->name,
        )));
      }
    }
  }
}

function shopify_collections_save_image($term, $image_url) {
  $directory = file_build_uri('shopify_images');
  if (!file_prepare_directory($directory, FILE_CREATE_DIRECTORY)) {
    // If our directory doesn't exist and can't be created, use the default.
    $directory = NULL;
  }
  $file = system_retrieve_file($image_url, $directory, TRUE, FILE_EXISTS_REPLACE);
  $term = entity_metadata_wrapper('taxonomy_term', $term);
  $term->shopify_collection_image->set((array) $file);
  $term->save();
}

/**
 * Shopify product sync form submit handler.
 *
 * @see shopify_admin_product_sync_form()
 */
function shopify_admin_product_sync_form_submit($form, &$form_state) {
  // @todo Probably should have a batch just for this.
  if ($form_state['values']['delete']) {
    shopify_delete_all_products();
    shopify_products_reset_sync_datestamp();
  }
  if ($form_state['values']['update_anyway']) {
    shopify_products_reset_sync_datestamp();
  }
  $batch = shopify_sync_products_batch(array('num_per_batch' => $form_state['values']['num_per_batch']));
  batch_set($batch);
}


/**
 * Shopify products sync batch callback.
 */
function shopify_sync_products_batch($params) {
  // Get count of products
  $num_products = shopify_api_get_product_count(
    array(
      'updated_at_min' => variable_get('shopify_last_sync')
    )
  );
  $num_operations = ceil($num_products / $params['num_per_batch']);
  $operations = array();
  for ($page = 1; $page <= $num_operations; $page++) {
    $operations[] = array(
      'shopify_sync_products_batch_op_1',
      array(
        $page,
        $params['num_per_batch'],
        t('(Processing page @operation)', array('@operation' => $page)),
      ),
    );
  }
  $batch = array(
    'operations' => $operations,
    'finished' => 'shopify_sync_products_batch_finished',
    'file' => drupal_get_path('module', 'shopify') . '/shopify.admin.inc',
  );
  return $batch;
}

/**
 * Shopify products sync batch operation handler.
 */
function shopify_sync_products_batch_op_1($page, $num_per_batch, $operation_details, &$context) {
  $products = shopify_api_get_products(
    array(
      'page' => $page,
      'limit' => $num_per_batch,
      'updated_at_min' => variable_get('shopify_last_sync')
    )
  );
  foreach ($products as $product) {
    $shopify_product = shopify_product_update($product['id'], 0, $product);
    $shopify_product->save();

    foreach ($product['variants'] as $v) {
      $variant = shopify_product_update($product['id'], $v['id'], $v);
      $variant->save();
      $context['results'][] = $variant->product_id . ' : ' . check_plain($variant->title);
    }
    $context['results'][] = $shopify_product->product_id . ' : ' . check_plain($shopify_product->title);
  }

  $context['message'] = t('Syncing product "@title" and its variants', array('@title' => $shopify_product->title)) . ' ' . $operation_details;
}

/**
 * Shopify product sync batch operation finished callback.
 *
 * @see shopify_sync_products_batch_op_1()
 */
function shopify_sync_products_batch_finished($success, $results, $operations) {
  if ($success) {
    drupal_set_message(t('Processed @count products and variants.', array('@count' => count($results))));
    $datetime = new DateTime();
    $last_updated = $datetime->format('Y-m-d H:i');
    variable_set('shopify_last_sync', $last_updated);
  }
  else {
    // An error occurred.
    // $operations contains the operations that remained unprocessed.
    $error_operation = reset($operations);
    drupal_set_message(
      t('An error occurred while processing @operation with arguments : @args',
        array(
          '@operation' => $error_operation[0],
          '@args' => print_r($error_operation[0], TRUE),
        )
      )
    );
  }
}

function shopify_webhooks_admin($form, &$form_state) {
  try {
    $webhooks = shopify_api_get_webhooks();
  } catch (ShopifyApiException $e) {
    $response = $e->getResponse();
    drupal_set_message(t('Shopify says there was a problem: @message', array('@message' => $response['errors'])), 'warning');
    return '';
  }
  $form['#tree'] = TRUE;

  $form['help'] = array(
    '#markup' => t('<p>It is <strong>highly recommended</strong> that you at least have webhooks registered for product events to keep your store automatically in sync with Shopify. Make sure you\'ve registered all development, staging and production environment URLs.</p>'),
  );


  $form['registered'] = array(
    '#type' => 'fieldset',
    '#title' => t('Webhooks Registered'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );

  if (empty($webhooks)) {
    $form['registered']['none'] = array(
      '#markup' => t('<p>No webhooks registed on Shopify for this App.</p><p>It is highly recommended that you at least have webhooks registered for product events to keep your store automatically in sync with Shopify.</p><p>Hooks created via the Shopify website will not be displayed here but will still work with the hook system. Caution: Internal paths are different for each hook.</p>'),
    );
  }

  foreach ($webhooks as $hook) {
    $form['registered'][$hook['id']] = array(
      '#type' => 'checkbox',
      '#title' => $hook['topic'] . ' via: ' . $hook['address'],
      '#description' => t('React to this event using: @hook', array('@hook' => 'HOOK_shopify_webook_' . str_replace('/', '_', $hook['topic']))),
    );
  }

  $form['remove_submit'] = array(
    '#type' => 'submit',
    '#value' => t('Remove'),
    '#submit' => array('shopify_remove_webhooks_submit'),
  );

  $form['register'] = array(
    '#type' => 'fieldset',
    '#title' => t('Register New Webhooks'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );

  $possible_hooks = array(
    'app' => array(
      'uninstalled' => t('App Uninstalled'),
    ),
    'carts' => array(
      'create' => t('Cart Created'),
      'update' => t('Cart Updated'),
    ),
    'checkouts' => array(
      'create' => t('Checkout Created'),
      'update' => t('Checkout Updated'),
      'delete' => t('Checkout Deleted'),
    ),
    'collections' => array(
      'create' => t('Collection Created'),
      'update' => t('Collection Updated'),
      'delete' => t('Collection Deleted'),
    ),
    'customers' => array(
      'create' => t('Customer Created'),
      'enable' => t('Customer Enabled'),
      'disable' => t('Customer Disabled'),
      'update' => t('Customer Updated'),
      'delete' => t('Customer Deleted'),
    ),
    'customer_groups' => array(
      'create' => t('Customer Group Created'),
      'update' => t('Customer Group Updated'),
      'delete' => t('Customer Group Deleted'),
    ),
    'fulfillments' => array(
      'create' => t('Fulfillment Created'),
      'update' => t('Fullfillment Updated'),
    ),
    'orders' => array(
      'create' => t('Order Created'),
      'delete' => t('Order Deleted'),
      'updated' => t('Order Updated'),
      'paid' => t('Order Paid'),
      'cancelled' => t('Order Cancelled'),
      'fulfilled' => t('Order Fulfilled'),
      'partially_fulfilled' => t('Order Partially Fulfilled'),
    ),
    'order_transactions' => array(
      'create' => t('Order Transaction Created'),
    ),
    'products' => array(
      'create' => t('Product Created - Highly Recommended'),
      'update' => t('Product Updated - Highly Recommended'),
      'delete' => t('Product Deleted - Highly Recommended'),
    ),
    'refunds' => array(
      'create' => t('Refund Created'),
    ),
    'shop' => array(
      'update' => t('Shop Updated'),
    ),
  );

  foreach ($possible_hooks as $group_name => $group_options) {

    $form['register'][$group_name] = array(
      '#type' => 'fieldset',
      '#title' => t(str_replace('_', ' ', ucwords($group_name))),
      '#collapsible' => TRUE,
      '#collapsed' => $group_name == 'products' ? FALSE : TRUE,
    );

    foreach ($group_options as $topic => $description) {
      $form['register'][$group_name][$topic][$description] = array(
        '#type' => 'checkbox',
        '#title' => $description,
      );
    }
  }

  $protocol = stripos($_SERVER['SERVER_PROTOCOL'], 'https') === TRUE ? 'https://' : 'http://';
  $current = $protocol . "$_SERVER[HTTP_HOST]";

  $form['register']['hostname'] = array(
    '#type' => 'textfield',
    '#title' => t('Hostname'),
    '#default_value' => variable_get('shopify_last_registered_host', $current),
    '#size' => 60,
    '#required' => TRUE,
    '#description' => t('Do not include a trailing slash.'),
  );

  $form['register']['register_submit'] = array(
    '#type' => 'submit',
    '#value' => t('Register'),
    '#submit' => array('shopify_register_webhooks_submit'),
    '#validate' => array('shopify_register_webhooks_validate'),
  );

  $message = '<p>Pro Tip: If you\'re developing locally and need to test webhooks ';
  $message .= 'download in install <a href="https://ngrok.com">ngrok</a> for a tunnel to your localhost. ';
  $message .= 'The service is free. Here is a commandline example: <br /><code>';
  $message .= 'ngrok http -host-header=YOUR_LOCALHOST_NAME_HERE.COM 80</code>. ';
  $message .= 'Place the "Forwarding" address into the hostname field above and ';
  $message .= 'register the hooks you want to test. The forwarding address ';
  $message .= 'address will look something like <code>http://0e1ff1cb.ngrok.io</code></p>';

  $form['tips'] = array(
    '#type' => 'fieldset',
    '#title' => t('Tips'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );

  $form['tips']['UI'] = array(
    '#markup' => t('<p>Webhooks created via the Shopify website will not appear here, but event hooks will still work.</p>'),
  );

  $form['tips']['standard_hooks'] = array(
    '#markup' => t('<p>Be sure to add the product webhooks to automatically keep your products in sync with Shopify. Other hooks do nothing unless you write code implementing the hook yourself.</p>'),
  );

  $form['tips']['ngrok'] = array(
    '#markup' => t($message),
  );

  return $form;
}

function shopify_remove_webhooks_submit($form, &$form_state) {
  if ($form_state['values']['op'] == 'Remove') {
    foreach ($form_state['values']['registered'] as $id => $v) {
      if ($v == 1) {
        shopify_api_delete_webhook($id);
      }
    }
  }
}

function shopify_register_webhooks_validate($form, &$form_state) {
  $hostname = &$form_state['values']['register']['hostname'];
  $hostname = rtrim($hostname, '/');
}

function shopify_register_webhooks_submit($form, &$form_state) {
  $values = $form_state['values'];
  variable_set('shopify_last_registered_host', $values['register']['hostname']);

  if ($form_state['values']['op'] == 'Register') {

    unset($values['register']['register_submit']);
    $hostname = $values['register']['hostname'];
    unset($values['register']['hostname']);

    foreach ($values['register'] as $group => $topics) {
      foreach ($topics as $topic => $selection) {
        if (reset($selection) == 1) {
          $hook = array(
            'webhook' => array(
              'topic' => "$group/$topic",
              'address' => $hostname . '/shopify/webhook/' . $group . '_' . $topic,
              'format' => "json"
            )
          );
          try {
            shopify_api_create_webhook($hook);
          } catch (ShopifyApiException $e) {
            $response = $e->getResponse();
            if (isset($response['errors']['address'])) {
              $message = $hostname . ' address ' . $response['errors']['address'][0];
            }
            else {
              $message = $response['errors'];
            }
            drupal_set_message(t('Shopify says there was a problem with @topic: @errors', array(
              '@errors' => $message,
              '@topic' => "$group/$topic"
            )), 'warning');
          }
        }
      }
    }
  }
}
