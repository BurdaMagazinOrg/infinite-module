<?php

/**
 * @file
 * Contains \Drupal\infinite_base\Plugin\Filter\FilterInternalLinks.
 */

namespace Drupal\infinite_base\Plugin\Filter;

use Drupal\Core\Url;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Replaces system path with URL alias in internal links.
 *
 * @Filter(
 *   id = "filter_internal_links",
 *   title = @Translation("Replace system paths with URL aliases"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
 *   weight = 10
 * )
 */
class FilterInternalLinks extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    return new FilterProcessResult($this->filterInternalLinks($text));
  }

  /**
   * Replaces system path with URL alias in internal links.
   *
   * @param string $text
   *   A text
   *
   * @return string
   *   The processed text
   */
  public function filterInternalLinks($text) {
    $replace = function ($matches) {
      if ($matches[2][0] != '/') {
        return $matches[0];
      }

      try {
        $url = Url::fromUserInput($matches[2]);
        $replace = 'href="' . $url->toString() . '"';
        return preg_replace('%href="([^"]+?)"%', $replace, $matches[0]);
      }
      catch (\InvalidArgumentException $e) {
        return $matches[0];
      }
    };
    return preg_replace_callback('%<a([^>]*?href="([^"]+?)"[^>]*?)>%i', $replace, $text);
  }
}
