<?php

namespace Drupal\shopify\Entity;

trait ShopifyEntityTrait {

  public static function formatDatetimeAsTimestamp(array &$values = [], array $fields) {
    foreach ($fields as $field) {
      if (isset($values[$field]) && !is_int($values[$field])) {
        $values[$field] = strtotime($values[$field]);
      }
    }
  }

}