<?php

namespace Drupal\infinite_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 *
 * @Block(
 *   id = "infinite_blocks_logo_header",
 *   admin_label = @Translation("Theme Logo Header Block")
 * )
 */
class InfiniteThemeLogoHeaderBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    return array(
      '#theme' => 'logo_header',
      '#logo' => theme_get_setting('logo.url'),
      '#front_page' =>  \Drupal::url('<front>')
    );
  }

}
