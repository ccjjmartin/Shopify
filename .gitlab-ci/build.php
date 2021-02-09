<?php

/**
 * @file
 * Custom GitlabCI build steps.
 *
 * @see https://mog33.gitlab.io/gitlab-ci-drupal/advanced-usage/#custom-build
 */

// Add module dependencies. This must match the module's composer.json.
$this->composerRequire()
  ->dependency('donutdan4114/shopify', 'v2020.01.*')
  ->run();
