<?php

namespace Drupal\infinite_base\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\node\Entity\Node;

/**
 *
 * @Block(
 *   id = "infinite_base_lazy_loading_simple",
 *   admin_label = @Translation("Lazy Loading Simple")
 * )
 */
class InfiniteLazyLoadingSimple extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    if ($node = \Drupal::request()->attributes->get('node')) {
      /* @var Node $node */
      $query = \Drupal::entityQuery('node')
        ->condition('created', $node->getCreatedTime(), '<')
        ->condition('type', 'article')
        ->condition('status', 1)
        ->condition('promote', 1)
        ->condition('nid', $node->id(), '!=')
        ->sort('created', 'DESC')
        ->range(0, 1);

      // Filter content promoted to front or channel page.
      // todo: add field / value exists checks.
      //  $promoteOptions = $query->orConditionGroup()
      //    ->condition('promote', 1)
      //    ->condition('promote_channel', 1);
      //  $group = $query->andConditionGroup()
      //    ->condition($promoteOptions);
      //  $query->condition($group);
      //
      // Perhaps we could use above code later again.

      $next_nid = $query->execute();

      if (!empty($next_nid)) {
        $page = 1;
        if (\Drupal::request()->query->has('page')) {
          $page = \Drupal::request()->query->getInt('page') + 1;
        }

        $next_nid = array_shift($next_nid);
        $lazy_loading_url = '/lazyloading/node/' . $next_nid . '/nojs?page=' . $page;

        return array(
          '#theme' => 'lazy_loading',
          '#lazy_loading_url' => $lazy_loading_url,
          '#attached' => array(
            'library' => array(
              'core/drupal.ajax',
            ),
          ),
          '#cache' => [
            'tags' => [
              'node:' . $node->id(),
              'node:' . $next_nid,
            ],
            'contexts' => ['url.path'],
          ],
        );
      } else {
        return array(
          '#cache' => [
            'tags' => [
              'node:' . $node->id(),
            ],
            'contexts' => ['url.path'],
          ],
        );
      }


    }
    return;
  }
}
