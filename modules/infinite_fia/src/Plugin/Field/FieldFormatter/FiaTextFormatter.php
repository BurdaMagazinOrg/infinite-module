<?php

/**
 * @file
 * Contains Drupal\infinite_fia\Plugin\Field\FieldFormatter\FiaTextFormatter.
 */

namespace Drupal\infinite_fia\Plugin\Field\FieldFormatter;

use Drupal\text\Plugin\Field\FieldFormatter\TextDefaultFormatter;
use Drupal\Core\Field\FieldItemListInterface;


/**
 * Plugin implementation of the 'fia_text_formatter' formatter.
 *
 * This formatter rewrites urls to be fully qualified by prepending the base
 * url.
 *
 * @FieldFormatter(
 *   id = "fia_text",
 *   label = @Translation("FIA text"),
 *   field_types = {
 *     "text",
 *     "text_long",
 *     "text_with_summary",
 *   }
 * )
 */
class FiaTextFormatter extends TextDefaultFormatter {
  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode = NULL) {
    $elements = parent::viewElements($items, $langcode);

    foreach ($elements as $delta => $element) {
      if ($element['#type'] == 'processed_text') {
        $elements[$delta]['#text'] = $this->completeURLs($element['#text']);
      }
    }
    return $elements;
  }

  public function completeURLs($text) {
    $base_url = $GLOBALS['base_url'];
    $text = str_replace('src="/', 'src="' . $base_url . '/', $text);
    $text = str_replace('href="/', 'href="' . $base_url . '/', $text);
    return $text;
  }
}
