<?php

/**
 * @file
 * Contains post-update functions for Lightning Workflow.
 */

/**
 * Implements hook_removed_post_updates().
 */
function lightning_workflow_removed_post_updates(): array {
  return [
    'lightning_workflow_post_update_import_moderated_content_view' => '4.0.0',
  ];
}
