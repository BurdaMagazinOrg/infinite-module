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
 *   id = "infinite_blocks_newsletter_large",
 *   admin_label = @Translation("Newsletter Large Block")
 * )
 */
class InfiniteNewsletterLargeBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    return array(
      '#theme' => 'newsletter_large',
      'variables' => [],
    );
  }

}
