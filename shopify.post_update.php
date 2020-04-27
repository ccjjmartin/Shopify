<?php

/**
 * @file
 * Post update functions for Shopify.
 */

/**
 * Convert the field_shopify_collection_id to bigint.
 */
function shopify_post_update_field_collection_id_bigint(&$sandbox) {
  $updateManager = \Drupal::entityDefinitionUpdateManager();

  /** @var \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface $last_installed_schema_repository */
  $last_installed_schema_repository = \Drupal::service('entity.last_installed_schema.repository');
  $entity_type = $updateManager->getEntityType('taxonomy_term');
  $field_storage_definitions = $last_installed_schema_repository->getLastInstalledFieldStorageDefinitions('taxonomy_term');
  $field_storage_definitions['field_shopify_collection_id']->setSetting('size', 'big');
  $updateManager->updateFieldableEntityType($entity_type, $field_storage_definitions, $sandbox);
}
