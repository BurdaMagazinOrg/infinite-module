<?php

namespace Drupal\infinite_flipboard\PathProcessor;

use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Symfony\Component\HttpFoundation\Request;

class OutboundPathProcessorFlipboard implements OutboundPathProcessorInterface {

  function processOutbound($path, &$options = array(), Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    if (strpos($path, '/flipboard.xml') !== FALSE && strpos($path, '/taxonomy/term/') === 0) {
      $path_args = explode("/", ltrim($path, "/"));

      if (is_numeric($path_args[2])) {
        // Get absolute URL from node alias URL.
        $alias = \Drupal::service('path.alias_manager')
          ->getAliasByPath('/taxonomy/term/' . $path_args[2]);

        if ($alias == '/') {
          return '/flipboard.xml';
        }
        else {
          return $alias . '/flipboard.xml';
        }
      }
    }
    return $path;
  }
}
