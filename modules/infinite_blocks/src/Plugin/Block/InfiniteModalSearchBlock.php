<?php

namespace Drupal\infinite_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 *
 * @Block(
 *   id = "infinite_blocks_modal_search",
 *   admin_label = @Translation("Modal Search")
 * )
 */
class InfiniteModalSearchBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    return array(
      '#theme' => 'modal_search',
      '#front_page' =>  \Drupal::url('<front>'),
      'variables' => [],
    );
  }

}
