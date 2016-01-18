<?php

/**
 * Contains \Drupal\infinite_media\Plugin\MediaEntity\Type\InfiniteImage.
 */

namespace Drupal\infinite_media\Plugin\MediaEntity\Type;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Config\Config;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\media_entity\MediaBundleInterface;
use Drupal\media_entity\MediaInterface;
use Drupal\media_entity\MediaTypeBase;
use Drupal\media_entity\MediaTypeInterface;
use Drupal\file\FileInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides generic media type.
 *
 * @MediaType(
 *   id = "infinite_image",
 *   label = @Translation("Infinite image media"),
 *   description = @Translation("Infinite image media type.")
 * )
 */
class InfiniteImage extends MediaTypeBase {

  /**
   * {@inheritdoc}
   */
  public function providedFields() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getField(MediaInterface$media, $name) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(MediaBundleInterface $bundle) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function validate(MediaInterface $media) { }

  /**
   * {@inheritdoc}
   */
  public function thumbnail(MediaInterface $media) {
    $source_field = 'field_image';

    /** @var \Drupal\file\FileInterface $file */
    $file = $this->entityManager->getStorage('file')->load($media->{$source_field}->target_id);

    if (!$file) {
      return $this->config->get('icon_base') . '/generic.png';
    }

    return $file->getFileUri();
  }

}
