<?php

/**
 * @file
 * Contains \Drupal\infinite_blocks\Plugin\Block\InfiniteSocialsBlock.
 */

namespace Drupal\infinite_blocks\Plugin\Block;

/**
 *
 * @Block(
 *   id = "infinite_blocks_modal_newsletter",
 *   admin_label = @Translation("Modal Newsletter")
 * )
 */
class InfiniteModalNewsletterBlock extends InfiniteNewsletterBlock {

  protected $theme = 'modal_newsletter';


}
