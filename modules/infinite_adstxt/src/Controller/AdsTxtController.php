<?php

namespace Drupal\infinite_adstxt\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Zend\Diactoros\Response\TextResponse;

/**
 * Class AdsTxtController.
 */
class AdsTxtController extends ControllerBase {

  public function render(){
    $settings = \Drupal::config('adstxt.settings');
    $content = $settings->get('adstxt_content');
    $response = new TextResponse($content);
    return $response;
  }

  public function access() {
    return AccessResult::allowed();
  }

}