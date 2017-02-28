<?php

namespace Drupal\infinite_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;

/**
 *
 * @Block(
 *   id = "infinite_blocks_header_media",
 *   admin_label = @Translation("Header Media Block")
 * )
 */
class InfiniteHeaderMediaBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $entity = NULL;
    if ($node = \Drupal::request()->attributes->get('node')) {
      $revision = NULL;
      if ($revision = \Drupal::request()->attributes->get('node_revision')) {
        $storage = \Drupal::entityTypeManager()->getStorage('node');
        $revision = $storage->loadRevision($revision);
      }
      $entity = $revision ? $revision : $node;
    }
    else if ($term = \Drupal::request()->attributes->get('taxonomy_term')) {
      /* @var Term $term */
      $entity = $term;
    }
    else if ($user = \Drupal::request()->attributes->get('user')) {
      /* @var Term $term */
      $entity = $user;
    }

    $header_media = $title = NULL;
    $cache = ['contexts' => ['url.path']];
    if (isset($entity)) {

      $cache['tags'] = $entity->getCacheTags();

      if ($entity->hasField('field_header_media') && !$entity->get('field_header_media')->isEmpty()) {

        if ($media = $entity->get('field_header_media')->entity) {
          $header_media = \Drupal::entityManager()
            ->getViewBuilder('media')
            ->view($media, 'header');

          $title = $entity->label();
        }
      }
      if ($entity->hasField('field_header_title') && !$entity->get('field_header_title')->isEmpty()) {
        $title = $entity->get('field_header_title')->value;
      }
    }

    if (!empty($header_media) || !empty($title)) {
      return array(
        '#theme' => 'header_media',
        '#header_media' => $header_media,
        '#header_title' => $title,
        '#cache' => $cache,
      );
    }
    return ['#cache' => $cache]; // needed to avoid global caching of block without header media.
  }
}
