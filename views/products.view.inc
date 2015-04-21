<?php

$view = new view();
$view->name = 'products';
$view->description = 'A View of Shopify Products';
$view->tag = 'shopify';
$view->base_table = 'shopify_products';
$view->human_name = 'Shopify Products';
$view->core = 7;
$view->api_version = '3.0';
$view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

/* Display: Master */
$handler = $view->new_display('default', 'Master', 'default');
$handler->display->display_options['title'] = 'products';
$handler->display->display_options['use_more_always'] = FALSE;
$handler->display->display_options['access']['type'] = 'none';
$handler->display->display_options['cache']['type'] = 'none';
$handler->display->display_options['query']['type'] = 'views_query';
$handler->display->display_options['exposed_form']['type'] = 'basic';
$handler->display->display_options['pager']['type'] = 'full';
$handler->display->display_options['pager']['options']['items_per_page'] = '10';
$handler->display->display_options['style_plugin'] = 'default';
$handler->display->display_options['row_plugin'] = 'entity';
$handler->display->display_options['row_options']['view_mode'] = 'default';
/* Field: Shopify Product: Shopify product ID */
$handler->display->display_options['fields']['id']['id'] = 'id';
$handler->display->display_options['fields']['id']['table'] = 'shopify_products';
$handler->display->display_options['fields']['id']['field'] = 'id';
/* Field: Shopify Product: Price */
$handler->display->display_options['fields']['price']['id'] = 'price';
$handler->display->display_options['fields']['price']['table'] = 'shopify_products';
$handler->display->display_options['fields']['price']['field'] = 'price';
$handler->display->display_options['fields']['price']['precision'] = '0';
$handler->display->display_options['fields']['price']['prefix'] = '$';
/* Field: Shopify Product: Label */
$handler->display->display_options['fields']['title']['id'] = 'title';
$handler->display->display_options['fields']['title']['table'] = 'shopify_products';
$handler->display->display_options['fields']['title']['field'] = 'title';
/* Field: Shopify Product: Option_values */
$handler->display->display_options['fields']['option_values']['id'] = 'option_values';
$handler->display->display_options['fields']['option_values']['table'] = 'shopify_products';
$handler->display->display_options['fields']['option_values']['field'] = 'option_values';
/* Sort criterion: Shopify Product: Label */
$handler->display->display_options['sorts']['title']['id'] = 'title';
$handler->display->display_options['sorts']['title']['table'] = 'shopify_products';
$handler->display->display_options['sorts']['title']['field'] = 'title';
/* Contextual filter: Shopify Product: Shopify Product Tags (shopify_product_tags) */
$handler->display->display_options['arguments']['shopify_product_tags_tid']['id'] = 'shopify_product_tags_tid';
$handler->display->display_options['arguments']['shopify_product_tags_tid']['table'] = 'field_data_shopify_product_tags';
$handler->display->display_options['arguments']['shopify_product_tags_tid']['field'] = 'shopify_product_tags_tid';
$handler->display->display_options['arguments']['shopify_product_tags_tid']['default_argument_type'] = 'fixed';
$handler->display->display_options['arguments']['shopify_product_tags_tid']['summary']['number_of_records'] = '0';
$handler->display->display_options['arguments']['shopify_product_tags_tid']['summary']['format'] = 'default_summary';
$handler->display->display_options['arguments']['shopify_product_tags_tid']['summary_options']['items_per_page'] = '25';
/* Filter criterion: Shopify Product: Variant_id */
$handler->display->display_options['filters']['variant_id']['id'] = 'variant_id';
$handler->display->display_options['filters']['variant_id']['table'] = 'shopify_products';
$handler->display->display_options['filters']['variant_id']['field'] = 'variant_id';
$handler->display->display_options['filters']['variant_id']['value']['value'] = '0';

/* Display: Page */
$handler = $view->new_display('page', 'Page', 'page');
$handler->display->display_options['path'] = 'products';

/* Display: Page */
$handler = $view->new_display('page', 'Page', 'page_1');
$handler->display->display_options['defaults']['relationships'] = FALSE;
/* Relationship: Shopify Product: Shopify Product Tags (shopify_product_tags) */
$handler->display->display_options['relationships']['shopify_product_tags_tid']['id'] = 'shopify_product_tags_tid';
$handler->display->display_options['relationships']['shopify_product_tags_tid']['table'] = 'field_data_shopify_product_tags';
$handler->display->display_options['relationships']['shopify_product_tags_tid']['field'] = 'shopify_product_tags_tid';
$handler->display->display_options['defaults']['arguments'] = FALSE;
/* Contextual filter: Shopify Product: Shopify Product Tags (shopify_product_tags) */
$handler->display->display_options['arguments']['shopify_product_tags_tid']['id'] = 'shopify_product_tags_tid';
$handler->display->display_options['arguments']['shopify_product_tags_tid']['table'] = 'field_data_shopify_product_tags';
$handler->display->display_options['arguments']['shopify_product_tags_tid']['field'] = 'shopify_product_tags_tid';
$handler->display->display_options['arguments']['shopify_product_tags_tid']['default_action'] = 'not found';
$handler->display->display_options['arguments']['shopify_product_tags_tid']['default_argument_type'] = 'fixed';
$handler->display->display_options['arguments']['shopify_product_tags_tid']['summary']['number_of_records'] = '0';
$handler->display->display_options['arguments']['shopify_product_tags_tid']['summary']['format'] = 'default_summary';
$handler->display->display_options['arguments']['shopify_product_tags_tid']['summary_options']['items_per_page'] = '25';
$handler->display->display_options['path'] = 'products/tags/%';
