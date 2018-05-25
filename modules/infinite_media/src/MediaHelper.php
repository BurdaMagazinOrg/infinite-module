<?php

namespace Drupal\infinite_media;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Url;
use Drupal\image\Entity\ImageStyle;

class MediaHelper {

  /**
   * @param \Drupal\Core\Entity\ContentEntityBase $entity
   * @param $fieldName
   * @param null $imageStyle
   * @param string $imageFieldName
   *
   * @return string
   */
  public static function getImageUrlFromMediaReference(
    ContentEntityBase $entity,
    $fieldName,
    $imageStyle = null,
    $imageFieldName = 'field_image'
  ): string {
    if ($entity->hasField($fieldName) &&
      !empty($entity->get($fieldName)->entity) &&  // todo: check why some media entity reference seems to be empty here after isEmpty() check? example: node 6001
      $entity->get($fieldName)->entity->hasField($imageFieldName) &&
      !$entity->get($fieldName)->entity->field_image->isEmpty()
    ) {

      if ($imageStyle) {
        $imageUri = $entity->get($fieldName)->entity->field_image->entity->getFileUri();
        $image = ImageStyle::load($imageStyle);
        if (is_object($image)) {
          $url = $image->buildUrl($imageUri);
          return $url;
        }
      } else {
        /** @var \Drupal\file\Entity\File $file */
        $file = $entity->get($fieldName)->entity->field_image->entity;
        return $file->url();
      }
    }

    return '';
  }
}
