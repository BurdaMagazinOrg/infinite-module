<?php

namespace Drupal\infinite_adstxt\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\infinite_adstxt\Response\CacheableResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class AdsTxtController.
 */
class AdsTxtController extends ControllerBase {

  public function render(){

    $settings = \Drupal::config('infinite_adstxt.settings');
    $content = $settings->get('adstxt_content');
    $response = new CacheableResponse($content ?: '', Response::HTTP_OK, ['content-type' => 'text/plain; charset=utf-8']);
    $response->setCacheTags(['config:infinite_adstxt.settings']);
    $response->addCacheableDependency($response);

    return $response;
  }

  public function access() {
    return AccessResult::allowed();
  }

}