<?php

namespace Drupal\infinite_base\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Promotes a node to FB instant articles.
 *
 * @Action(
 *   id = "node_promote_fb_instant_articles_action",
 *   label = @Translation("Promote selected content to FB instant articles"),
 *   type = "node"
 * )
 */
class PromoteFBInstantArticlesNode extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    if ($currentPromoteStates = _infinite_base_flat_promote_states($entity)) {
      $entity->field_promote_states->setValue(array_merge($currentPromoteStates, ['fb_instant_articles']));
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
