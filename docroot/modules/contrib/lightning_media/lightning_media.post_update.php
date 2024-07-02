<?php

/**
 * @file
 * Contains post-update functions for Lightning Media.
 */

/**
 * Implements hook_removed_post_updates().
 */
function lightning_media_removed_post_updates(): array {
  return [
    'lightning_media_post_update_change_action_plugins' => '5.0.0',
  ];
}
