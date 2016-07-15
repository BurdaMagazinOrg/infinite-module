<?php
/**
 * @file
 * Contains
 */

namespace Drupal\infinite_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\views\Views;

/**
 * Provides a 'ChannelPresenter' Block
 *
 * @Block(
 *   id = "infinite_blocks_channel_presenter",
 *   admin_label = @Translation("4 Channel Presenter"),
 * )
 */
class InfiniteChannelPresenterBlock extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {

    $view = Views::getView('infinite_channel_presenter');

    if (!$view || !$view->access('four_teasers')) {
      return [
        '#cache' => [
          'contexts' => ['url.path'],
        ]
      ];
    }

    $renderedContent = $view->render('four_teasers');

    $renderedContent['#cache'] = [
      'contexts' => ['url.path'],
      'tags' => $view->getCacheTags()
    ];

    return $renderedContent;
  }
}
