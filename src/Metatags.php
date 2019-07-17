<?php

namespace Drupal\infinite_base;

use Drupal\node\Entity\Node;

class Metatags {
  public static function injectGooglebotNews(Node $node) {
    if (false === $node->hasField('field_meta_tags')) {
      return $node;
    }

    $metaTagsValue = unserialize($node->get('field_meta_tags')
      ->first()
      ->getValue()['value']);
    if (in_array($node->get('field_sponsor_type')->first()->value, ['native', 'advertorial'])) {
      $metaTagsValue['metatag_googlebot_news'] = 'noindex, nofollow';
      $node->set('field_meta_tags', serialize($metaTagsValue));
    }

    return $node;
  }
}
