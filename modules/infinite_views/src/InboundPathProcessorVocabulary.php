<?php

namespace Drupal\infinite_views;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Symfony\Component\HttpFoundation\Request;

class InboundPathProcessorVocabulary implements InboundPathProcessorInterface {

  protected $vocabularies = array();

  function processInbound($path, Request $request) {
    $this->getVocabularies();
    foreach ($this->vocabularies as $vocabulary) {
      // Get absolute URL from node alias URL.
      $alias = \Drupal::service('path.alias_manager')
        ->getAliasByPath('/vocabulary/' . $vocabulary);

      if (strpos($path, $alias . '/') === 0) {
        $path_args = explode("/", ltrim($path, "/"));

        if (!empty($path_args[1])) {
          return '/vocabulary/' . $vocabulary . '/' . $path_args[1];
        }
      }
    }
    return $path;
  }

  protected function getVocabularies() {
    if (empty($this->vocabularies)) {
      $this->vocabularies[] = 'all';
      $query = \Drupal::entityQuery('taxonomy_vocabulary');
      $vids = $query->execute();
      foreach($vids as $vid) {
        $this->vocabularies[] = $vid;
      }
    }
  }
}
