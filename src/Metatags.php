<?php

namespace Drupal\infinite_base;

use Drupal\node\Entity\Node;

class Metatags {
  public function injectGooglebotNews(Node $node) {
    if (false === $node->hasField('field_meta_tags')) {
      return $node;
    }

    // If node is native or advertorial set googlebot news value
    $metaTagsField = $node->get('field_meta_tags')->first();

    $clearValue = true;
    if ($metaTagsField) {
      $metaTagsValue = unserialize($metaTagsField->getValue()['value']);

      if (
        $node->hasField('field_meta_tags') &&
        $node->hasField('field_sponsor_type') &&
        $node->get('field_sponsor_type')->first() &&
        in_array($node->get('field_sponsor_type')->first()->value, ['native', 'advertorial', 'deal'])
      ) {
        $this->setGooglebotNewsMetatagValue($node, $metaTagsValue);
        $clearValue = false;
      }
    } else {
      $metaTagsValue = [];
    }

    // If node is ecommerce landing page set googlebot news value
    if($this->nodeIsEcommerceLandingPage($node)) {
      $this->setGooglebotNewsMetatagValue($node, $metaTagsValue);
      $clearValue = false;
    }

    if ($clearValue) {
      $this->setGooglebotNewsMetatagValue($node, $metaTagsValue, '');
    }

    return $node;
  }

  protected function nodeIsEcommerceLandingPage(Node $node) : bool {
    if($node->hasField('field_promote_states')) {
      $promoteStatesField = $node->get('field_promote_states')->first();
      if ($promoteStatesField) {
        $values = $promoteStatesField->getValue();
        if (is_array($values)) {
          return in_array('landing', $values);
        }
      }
    }

    return false;
  }

  /**
   * @param \Drupal\node\Entity\Node $node
   * @param array $metaTagsValue
   * @param string $defaultValue
   */
  private function setGooglebotNewsMetatagValue(
    Node $node,
    array $metaTagsValue,
    string $defaultValue = 'noindex, nofollow'
  ): void {
    $metaTagsValue['metatag_googlebot_news'] = $defaultValue;
    $node->set('field_meta_tags', serialize($metaTagsValue));
  }
}
