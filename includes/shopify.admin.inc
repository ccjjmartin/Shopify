<?php
/**
 * @file
 * Contains functions and forms for admin pages.
 */

/**
 * Theme download admin page.
 */
function shopify_admin_theme_download($form, $form_state) {
  $form = array();
  $form['download'] = array(
    '#type' => 'fieldset',
    '#title' => t('Shopify Theme'),
    '#description' => t('Download and upload this Shopify theme to your !link.', array(
        '!link' => l(t('Shopify store'), 'http://' . shopify_store_info()->myshopify_domain . '/admin/themes', array('attributes' => array('target' => '_blank'))),
      )) . '<br />' . t('This theme will disable most Shopify store features except for the shopping cart and will redirect the user to the store on the Drupal site.'),
  );
  $form['download']['actions'] = array(
    '#type' => 'actions',
    'submit' => array(
      '#type' => 'submit',
      '#value' => t('Download'),
    ),
  );
  return $form;
}

/**
 * Submit callback for download the Shopify theme folder.
 */
function shopify_admin_theme_download_submit($form, $form_state) {
  // Copy the default theme to a temp folder.
  try {
    shopify_recurse_copy(drupal_get_path('module', 'shopify') . '/default_shopify_theme', file_directory_temp() . '/default_shopify_theme');
  } catch (Exception $e) {
    drupal_set_message(t('Could not copy default_shopify_theme to the temp directory: @error', array('@error' => $e->getMessage())), 'error');
  }

  // Modify the {{ replace }} contents within theme files.
  try {
    shopify_find_and_replace(file_directory_temp() . '/default_shopify_theme/*', '{{ drupal.store.url }}', url(shopify_store_path(), array('absolute' => TRUE)));
  } catch (Exception $e) {
    drupal_set_message(t('Could not find and replace placeholder text: @error', array('@error' => $e->getMessage())), 'error');
  }

  // Zip the theme folder.
  try {
    shopify_zip_folder(file_directory_temp() . '/default_shopify_theme');
  } catch (Exception $e) {
    drupal_set_message(t('Could not ZIP the default_shopify_theme folder: @error', array('@error' => $e->getMessage())), 'error');
  }

  // Download the file to the user's browser.
  try {
    shopify_zip_download(file_directory_temp() . '/default_shopify_theme.zip', 'default_shopify_theme.zip');
  } catch (Exception $e) {
    drupal_set_message(t('Could not download the ZIP folder: @error', array('@error' => $e->getMessage())), 'error');
  }
}

/**
 * Download a zip file to the user's browser.
 *
 * @param string $filepath
 *   Full file path on the server.
 * @param string $filename
 *   Name of the file when downloaded.
 */
function shopify_zip_download($filepath, $filename) {
  // Reset any previous headers.
  header("Pragma: public");
  header("Expires: 0");
  header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
  header("Cache-Control: public");
  header("Content-Description: File Transfer");
  header("Content-type: application/octet-stream");
  header("Content-Disposition: attachment; filename=\"$filename\"");
  header("Content-Transfer-Encoding: binary");
  header("Content-Length: " . filesize($filepath));
  @readfile($filepath);
  ob_end_flush();
}

/**
 * ZIP a folder.
 *
 * @param string $path
 *   Path to the folder.
 *
 * @link http://stackoverflow.com/questions/4914750/how-to-zip-a-whole-folder-using-php @endlink
 */
function shopify_zip_folder($path) {
  // Get real path for our folder
  $root_path = realpath($path);

  // Initialize archive object
  $zip = new ZipArchive();
  $zip->open($path . '.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);

  // Create recursive directory iterator
  $files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root_path),
    RecursiveIteratorIterator::LEAVES_ONLY
  );

  foreach ($files as $name => $file) {
    // Skip directories (they would be added automatically)
    if (!$file->isDir()) {
      // Get real and relative path for current file
      $file_path = $file->getRealPath();
      $relative_path = substr($file_path, strlen($root_path) + 1);

      // Add current file to archive
      $zip->addFile($file_path, $relative_path);
    }
  }

  // Zip archive will be created only after closing object
  $zip->close();
}

/**
 * Find and replace text in a folder recursively.
 *
 * @param string $dir
 *   Directory to search in.
 * @param string $find_text
 *   Text to find.
 * @param string $replace_text
 *   Text to replace the found text.
 */
function shopify_find_and_replace($dir, $find_text, $replace_text) {
  foreach (glob($dir) as $path_to_file) {
    if (strpos($path_to_file, '.') === FALSE) {
      // This is a directory.
      shopify_find_and_replace($path_to_file . '/*', $find_text, $replace_text);
    }
    else {
      $file_contents = file_get_contents($path_to_file);
      $file_contents = str_replace($find_text, $replace_text, $file_contents);
      file_put_contents($path_to_file, $file_contents);
    }
  }
}

/**
 * Copy an entire directory and contents.
 *
 * @param string $src
 *   Source directory.
 * @param string $dst
 *   Destination directory.
 *
 * @link http://stackoverflow.com/questions/2050859/copy-entire-contents-of-a-directory-to-another-using-php @endlink
 */
function shopify_recurse_copy($src, $dst) {
  $dir = opendir($src);
  @mkdir($dst);
  while (FALSE !== ($file = readdir($dir))) {
    if (($file != '.') && ($file != '..')) {
      if (is_dir($src . '/' . $file)) {
        shopify_recurse_copy($src . '/' . $file, $dst . '/' . $file);
      }
      else {
        copy($src . '/' . $file, $dst . '/' . $file);
      }
    }
  }
  closedir($dir);
}

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
function shopify_admin_sync_form($form, &$form_state) {

  try {
    shopify_api_get_shop_info();
  } catch (Exception $e) {
    drupal_set_message(t('Unable to connect to your Shopify store.'), 'error');
    $form['oops'] = array('#markup' => t('<a href="/admin/shopify">Unable to connect to your store.</a>'));
    return $form;
  }

  $last_updated = variable_get('shopify_last_product_sync');
  if ($last_updated == '1970-01-01 00:00' || !$last_updated) {
    $last_updated = 'never';
  }

  $info = 'Last sync !last_updated. Only products updated since the last sync will ';
  $info .= 'be pulled and synced. Products updated via webhooks will be updated anyway. ';
  $info .= 'Custom fields you\'ve added yourself will not be overridden on update.';

  $form['products'] = array(
    '#type' => 'fieldset',
    '#title' => t('Sync Products'),
    '#description' => t($info, array('!last_updated' => $last_updated)),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#tree' => TRUE,
  );

  $form['products']['num_per_batch'] = array(
    '#type' => 'select',
    '#title' => 'Choose how many products to sync per batch operation (not per batch).',
    '#options' => array(
      '1' => t('One at a time'),
      '50' => t('50 at a time'),
      '250' => t('250 (Max API limit)'),
    ),
    '#default_value' => 250,
  );

  $form['products']['delete'] = array(
    '#type' => 'checkbox',
    '#title' => t('Delete all products then re-import fresh.') . '<br /><strong>' . t('CAUTION: Product entities will be completely deleted then re-imported. Custom fields will be erased, comments deleted, etc.') . '</strong>',
  );

  $form['products']['update_anyway'] = array(
    '#type' => 'checkbox',
    '#title' => t('Update all products regardless of last sync time. Product entities will be updated, not deleted.'),
  );

  $form['products']['submit'] = array(
    '#type' => 'submit',
    '#value' => 'Sync Products',
    '#submit' => array('shopify_admin_product_sync_form_submit'),
  );

  $last_updated = variable_get('shopify_last_collection_sync');
  if ($last_updated == '1970-01-01 00:00' || !$last_updated) {
    $last_updated = 'never';
  }
  $form['collections'] = array(
    '#type' => 'fieldset',
    '#title' => t('Sync Collections'),
    '#description' => t('Last sync !last_updated.', array('!last_updated' => $last_updated)),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#tree' => TRUE,
  );
  $form['collections']['delete'] = array(
    '#type' => 'checkbox',
    '#title' => t('Delete all collections then re-import fresh.') . '<br /><strong>' . t('CAUTION: Collection terms will be completely deleted then re-imported. Custom fields will be erased, comments deleted, etc.') . '</strong>',
  );
  $form['collections']['submit'] = array(
    '#type' => 'submit',
    '#value' => 'Sync Collections',
    '#submit' => array('shopify_admin_collection_sync_form_submit'),
  );

  return $form;
}

/**
 * Submit handler for collection syncing.
 */
function shopify_admin_collection_sync_form_submit($form, &$form_state) {
  if ($form_state['values']['collections']['delete']) {
    shopify_delete_all_collections();
  }
  $batch = shopify_sync_collections_batch();
  batch_set($batch);
}

/**
 * Shopify collections sync batch callback.
 */
function shopify_sync_collections_batch() {
  // Get all smart/custom collection data.
  $collections = shopify_api_get_collections(array('limit' => 250));
  $operations = array();
  foreach ($collections as $count => $c) {
    $operations[] = array(
      'shopify_sync_collections_batch_op',
      array(
        $c,
        t('(Processing page @operation)', array('@operation' => $count + 1)),
      ),
    );
  }
  $batch = array(
    'operations' => $operations,
    'finished' => 'shopify_sync_collections_batch_finished',
    'file' => drupal_get_path('module', 'shopify') . '/includes/shopify.admin.inc',
  );
  return $batch;
}

/**
 * Batch operation for syncing Shopify collections.
 *
 * @param array $collection
 *   Collection data.
 * @param string $operation_details
 *   Operation details.
 * @param array $context
 *   Batch context.
 */
function shopify_sync_collections_batch_op($collection, $operation_details, &$context) {
  $term = shopify_collection_load($collection['id']);

  if (!$term) {
    // Need to create a new collection.
    shopify_collection_create($collection, TRUE);
  }
  else {
    shopify_collection_update($collection, TRUE);
  }

  $context['results'][] = $collection;
  $context['message'] = t('Syncing collection @title...', array('@title' => $collection['title']));
}

/**
 * Batch finished callback for collections batch.
 */
function shopify_sync_collections_batch_finished($success, $results, $operations) {
  if ($success) {
    drupal_set_message(t('Processed @count collections.', array('@count' => count($results))));
    $datetime = new DateTime();
    $last_updated = $datetime->format('Y-m-d H:i');
    variable_set('shopify_last_collection_sync', $last_updated);
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

/**
 * Shopify product sync form submit handler.
 *
 * @see shopify_admin_product_sync_form()
 */
function shopify_admin_product_sync_form_submit($form, &$form_state) {
  // @todo Probably should have a batch just for this.
  if ($form_state['values']['products']['delete']) {
    shopify_delete_all_products();
    shopify_products_reset_sync_datestamp();
  }
  if ($form_state['values']['products']['update_anyway']) {
    shopify_products_reset_sync_datestamp();
  }
  $batch = shopify_sync_products_batch(array('num_per_batch' => $form_state['values']['products']['num_per_batch']));
  batch_set($batch);
}


/**
 * Shopify products sync batch callback.
 */
function shopify_sync_products_batch($params) {
  // Get count of products
  $num_products = shopify_api_get_product_count(
    array(
      'updated_at_min' => variable_get('shopify_last_product_sync')
    )
  );
  $num_operations = ceil($num_products / $params['num_per_batch']);
  $operations = array();
  for ($page = 1; $page <= $num_operations; $page++) {
    $operations[] = array(
      'shopify_sync_products_batch_op',
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
    'file' => drupal_get_path('module', 'shopify') . '/includes/shopify.admin.inc',
  );
  return $batch;
}

/**
 * Shopify products sync batch operation handler.
 */
function shopify_sync_products_batch_op($page, $num_per_batch, $operation_details, &$context) {
  $products = shopify_api_get_products(
    array(
      'page' => $page,
      'limit' => $num_per_batch,
      'updated_at_min' => variable_get('shopify_last_product_sync')
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
 * @see shopify_sync_products_batch_op()
 */
function shopify_sync_products_batch_finished($success, $results, $operations) {
  if ($success) {
    drupal_set_message(t('Processed @count products and variants.', array('@count' => count($results))));
    $datetime = new DateTime();
    $last_updated = $datetime->format('Y-m-d H:i');
    variable_set('shopify_last_product_sync', $last_updated);
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

/**
 * Shopify webhooks admin form.
 */
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

/**
 * Shopify webhooks admin form remove submit handler.
 */
function shopify_remove_webhooks_submit($form, &$form_state) {
  if ($form_state['values']['op'] == 'Remove') {
    foreach ($form_state['values']['registered'] as $id => $v) {
      if ($v == 1) {
        shopify_api_delete_webhook($id);
      }
    }
  }
}

/**
 * Shopify webhooks admin form validation handler.
 */
function shopify_register_webhooks_validate($form, &$form_state) {
  $hostname = &$form_state['values']['register']['hostname'];
  $hostname = rtrim($hostname, '/');
}

/**
 * Shopify webhooks register submit handler.
 */
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
