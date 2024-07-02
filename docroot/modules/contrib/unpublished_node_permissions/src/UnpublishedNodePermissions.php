<?php

namespace Drupal\unpublished_node_permissions;

use Drupal\node\Entity\NodeType;
use Drupal\node\NodePermissions;

/**
 * Provides dynamic permissions for nodes of different types.
 */
class UnpublishedNodePermissions extends NodePermissions {

  /**
   * Returns a list of node permissions for a given node type.
   *
   * @param \Drupal\node\Entity\NodeType $type
   *   The node type.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildPermissions(NodeType $type) {
    return [
      "view {$type->id()} unpublished content" => [
        'title' => $this->t('%type_name: View unpublished content', ['%type_name' => $type->label()]),
      ],
    ];
  }

}
