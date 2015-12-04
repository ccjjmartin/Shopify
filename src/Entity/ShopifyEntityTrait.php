<?php

namespace Drupal\shopify\Entity;

use Drupal\file\Entity\File;

trait ShopifyEntityTrait {

  public static function formatDatetimeAsTimestamp(array &$values = [], array $fields) {
    foreach ($fields as $field) {
      if (isset($values[$field]) && !is_int($values[$field])) {
        $values[$field] = strtotime($values[$field]);
      }
    }
  }

  public static function setupProductImage($image_url) {
    $directory = file_build_uri('shopify_images');
    if (!file_prepare_directory($directory, FILE_CREATE_DIRECTORY)) {
      // If our directory doesn't exist and can't be created, use the default.
      $directory = NULL;
    }
    return system_retrieve_file($image_url, $directory, TRUE, FILE_EXISTS_REPLACE);
  }

}