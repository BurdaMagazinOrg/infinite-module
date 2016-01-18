<?php

/**
 * @file
 * Contains \Drupal\infinite_article\Plugin\Action\PromoteHomePresenterNode.
 */

namespace Drupal\infinite_base\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Promotes a node to channel presenter.
 *
 * @Action(
 *   id = "node_promote_channel_presenter_action",
 *   label = @Translation("Promote selected content to channel presenter"),
 *   type = "node"
 * )
 */
class PromoteChannelPresenterNode extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    if ($entity->hasField('promote_channel_presenter')) {
      $entity->set('promote_channel_presenter', 1);
      $entity->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\node\NodeInterface $object */
    $access = $object->access('update', $account, TRUE)
      ->andif($object->promote->access('edit', $account, TRUE));
    return $return_as_object ? $access : $access->isAllowed();
  }

}
