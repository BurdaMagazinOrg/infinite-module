<?php

/**
 * @file
 * Provides Drupal\infinite_base\Twig\InfiniteExtension
 */

namespace Drupal\infinite_base\Twig;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Render\Markup;
use Drupal\image\Entity\ImageStyle;
use Exception;
use Twig_Extension;
use Twig_SimpleFilter;
use Twig_SimpleFunction;
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
      new Twig_SimpleFilter('inject_no_follow', [$this, 'injectNoFollow']),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return [
      new Twig_SimpleFunction('image_style', [$this, 'imageStyle']),
    ];
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

  /**
   * Returns render array for an image styled applied to the given field.
   *
   * @param FieldItemListInterface $field
   * @param string $style_name
   * @param array $attributes
   *
   * @return array
   */
  public function imageStyle(FieldItemListInterface $field, $style_name, array $attributes = []) {
    $build = [];

    if (is_null($field)) {
      return NULL;
    }

    $field_type = $field->getFieldDefinition()->getType();
    $field_name = $field->getFieldDefinition()->getName();

    if ($field_type != 'image') {
      throw new Exception("Cannot apply image style to $field_name of type $field_type.");
    }

    if (is_null(ImageStyle::load($style_name))) {
      throw new Exception("Cannot find image style $style_name.");
    }

    foreach ($field as $item) {
      $build[] = [
        '#theme' => 'image_style',
        '#style_name' => $style_name,
        '#uri' => $item->entity->getFileUri(),
        '#width' => $item->width,
        '#height' => $item->height,
        '#alt' => $item->alt,
        '#title' => $item->title,
        '#attributes' => $attributes,
      ];
    }

    return $build;
  }

  public static function injectNoFollow($renderArray)
  {
    $html = $renderArray['#text'];
    $dom = new DOMDocument();
    $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    $x = new DOMXPath($dom);

    /** @var DOMElement $node */
    foreach($x->query("//a") as $node)
    {
      $node->setAttribute("rel","nofollow");
    }
    $html = $dom->saveHtml();
    $renderArray['#text'] = Markup::create($html);

    return $renderArray;
  }
}
