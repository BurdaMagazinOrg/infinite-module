<?php
namespace Drupal\infinite_adstxt\Response;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Cache\CacheableResponseTrait;
use Symfony\Component\HttpFoundation\Response;

class CacheableResponse extends Response implements CacheableResponseInterface, CacheableDependencyInterface {

  use CacheableResponseTrait;

  protected $cacheTags;

  public function setCacheTags(array $cacheTags) {
    $this->cacheTags = $cacheTags;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return ['config:infinite_adstxt.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return -1;
  }

}