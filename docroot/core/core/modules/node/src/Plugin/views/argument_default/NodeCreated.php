<?php

namespace Drupal\node\Plugin\views\argument_default;

use Drupal\node\NodeInterface;

/**
 * Provides the created time of the current node as default argument value.
 *
 * @ingroup views_argument_default_plugins
 *
 * @ViewsArgumentDefault(
 *   id = "node_created",
 *   title = @Translation("Current node 'created' time")
 * )
 */
class NodeCreated extends NodeDateArgumentDefaultPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function getNodeDateValue(NodeInterface $node): int {
    return $node->getCreatedTime();
  }

}
