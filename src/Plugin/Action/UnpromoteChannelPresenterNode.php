<?php

/**
 * @file
 * Contains \Drupal\node\Plugin\Action\DemoteNode.
 */

namespace Drupal\infinite_base\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Unpromotes a node from channel presenter.
 *
 * @Action(
 *   id = "node_unpromote_channel_presenter_action",
 *   label = @Translation("Unpromote selected content from channel presenter"),
 *   type = "node"
 * )
 */
class UnpromoteChannelPresenterNode extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    if ($entity->hasField('promote_channel_presenter')) {
      $entity->set('promote_channel_presenter', 0);
      $entity->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\node\NodeInterface $object */
    $result = $object->access('update', $account, TRUE)
      ->andIf($object->promote->access('edit', $account, TRUE));

    return $return_as_object ? $result : $result->isAllowed();
  }

}
