<?php

namespace Drupal\infinite_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 *
 * @Block(
 *   id = "infinite_blocks_logo",
 *   admin_label = @Translation("Theme Logo Block")
 * )
 */
class InfiniteThemeLogoBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    return array(
      '#theme' => 'logo',
      '#logo' => theme_get_setting('logo.url'),
      '#front_page' =>  \Drupal::url('<front>')
    );
  }

}
