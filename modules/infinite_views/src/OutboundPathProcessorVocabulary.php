<?php

namespace Drupal\infinite_views;

use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Symfony\Component\HttpFoundation\Request;

class OutboundPathProcessorVocabulary implements OutboundPathProcessorInterface {

  function processOutbound($path, &$options = array(), Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    if (strpos($path, "/vocabulary/") === 0) {
      $path_args = explode("/", ltrim($path, "/"));

      if (!empty($path_args[2])) {
        // Get absolute URL from node alias URL.
        $alias = \Drupal::service('path.alias_manager')
          ->getAliasByPath('/' . $path_args[0] . '/' . $path_args[1]);

        return $alias . '/' . $path_args[2];
      }
    }
    return $path;
  }
}
