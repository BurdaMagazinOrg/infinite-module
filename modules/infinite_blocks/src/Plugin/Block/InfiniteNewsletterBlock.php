<?php

/**
 * @file
 * Contains \Drupal\infinite_blocks\Plugin\Block\InfiniteSocialsBlock.
 */

namespace Drupal\infinite_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 *
 * @Block(
 *   id = "infinite_blocks_newsletter",
 *   admin_label = @Translation("Newsletter Block")
 * )
 */
class InfiniteNewsletterBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    return array(
      '#theme' => 'newsletter',
      'variables' => [],
    );
  }

}
