<?php

/**
 * @file
 * Contains \Drupal\infinite_article\Plugin\Action\PromoteHomePresenterNode.
 */

namespace Drupal\infinite_base\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Promotes a node to front presenter.
 *
 * @Action(
 *   id = "node_promote_front_presenter_action",
 *   label = @Translation("Promote selected content to front presenter"),
 *   type = "node"
 * )
 */
class PromoteFrontPresenterNode extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    if ($entity->hasField('promote_front_presenter')) {
      $entity->set('promote_front_presenter', 1);
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
