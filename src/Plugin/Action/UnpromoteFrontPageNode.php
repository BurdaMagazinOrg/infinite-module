<?php

namespace Drupal\infinite_base\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Unpromotes a node to front page.
 *
 * @Action(
 *   id = "node_unpromote_front_page_action",
 *   label = @Translation("Unpromote selected content to front page"),
 *   type = "node"
 * )
 */
class UnpromoteFrontPageNode extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    if ($currentPromoteStates = _infinite_base_flat_promote_states($entity)) {
      $entity->field_promote_states->setValue(array_diff($currentPromoteStates, ['front_page']));
      $entity->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\node\NodeInterface $object */
    $access = $object->access('update', $account, TRUE)
      ->andif($object->field_promote_states->access('edit', $account, TRUE));
    return $return_as_object ? $access : $access->isAllowed();
  }

}
