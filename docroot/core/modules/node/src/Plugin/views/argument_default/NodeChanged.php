<?php

namespace Drupal\node\Plugin\views\argument_default;

use Drupal\node\NodeInterface;

/**
 * Provides the 'changed' time of the current node as default argument value.
 *
 * @ingroup views_argument_default_plugins
 *
 * @ViewsArgumentDefault(
 *   id = "node_changed",
 *   title = @Translation("Current node 'changed' time")
 * )
 */
class NodeChanged extends NodeDateArgumentDefaultPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function getNodeDateValue(NodeInterface $node): int {
    return $node->getChangedTime();
  }

}
