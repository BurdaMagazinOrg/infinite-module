<?php

namespace Drupal\infinite_flipboard\PathProcessor;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Symfony\Component\HttpFoundation\Request;

class InboundPathProcessorFlipboard implements InboundPathProcessorInterface {

  function processInbound($path, Request $request) {
    if (strpos($path, '/flipboard.xml') !== FALSE) {
      $path_args = explode("/", ltrim($path, "/"));
      array_pop($path_args);
      $alias = '/' . implode('/', $path_args);

      $new_path = \Drupal::service('path.alias_manager')
          ->getPathByAlias($alias);

      $path_args = explode("/", ltrim($new_path, "/"));

      if ($path_args[0] == 'taxonomy' && $path_args[1] == 'term' && is_numeric($path_args[2])) {
        return '/taxonomy/term/' . $path_args[2] . '/flipboard.xml';
      }
    }
    return $path;
  }
}
