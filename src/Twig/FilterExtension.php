<?php

namespace Drupal\infinite_base\Twig;

use Drupal\Core\Template\TwigExtension;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Render\Renderer_Interface;

class FilterExtension extends \Twig_Extension {

  public function getFilters() {
    return array(
      new \Twig_SimpleFilter('plain_text', array($this, 'plainText')),
    );
  }

  public function getName() {
    return 'filter_extension';
  }

  public static function plainText($value) {
    $element = render($value);
    $element = strip_tags($element);
    $element = html_entity_decode($element);
    return $element;
  }
}