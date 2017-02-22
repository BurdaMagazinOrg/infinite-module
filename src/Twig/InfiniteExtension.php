<?php

/**
 * @file
 * Provides Drupal\infinite_base\Twig\InfiniteExtension
 */

namespace Drupal\infinite_base\Twig;

use Twig_Extension;
use Twig_SimpleFilter;
use function render;

/**
 * A Twig extension for infinite themes.
 */
class InfiniteExtension extends Twig_Extension {

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    return array(
      new Twig_SimpleFilter('plain_text', [$this, 'plainText']),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'infinite_base.twig_extension';
  }

  /**
   * Returns a plain text representation of the given value.
   *
   * @param mixed $value
   *
   * @return string
   */
  public static function plainText($value) {
    return html_entity_decode(strip_tags(render($value)), ENT_QUOTES);
  }
}