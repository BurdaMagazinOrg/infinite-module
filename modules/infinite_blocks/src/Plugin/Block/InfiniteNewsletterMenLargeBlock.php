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
 *   id = "infinite_blocks_newsletter_men_large",
 *   admin_label = @Translation("Newsletter Large Men Block")
 * )
 */
class InfiniteNewsletterMenLargeBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    return array(
      '#theme' => 'newsletter_men_large',
      'variables' => [],
    );
  }

}
