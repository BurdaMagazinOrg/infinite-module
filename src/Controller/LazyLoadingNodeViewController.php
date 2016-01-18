<?php

/**
 * @file
 * Contains \Drupal\node\Controller\NodeViewController.
 */

namespace Drupal\infinite_base\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InsertCommand;
use Drupal\Core\Entity\EntityInterface;
use Drupal\node\Controller\NodeViewController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Defines a controller to render a single node.
 */
class LazyLoadingNodeViewController extends NodeViewController {

  /**
   * {@inheritdoc}
   */
  public function ajaxView(EntityInterface $node, $js = 'nojs', $view_mode = 'lazyloading', $langcode = NULL) {
    $build = parent::view($node, $view_mode, $langcode);
    $build['#cache']['keys'][] = 'lazyloading';
    $build['#cache']['tags'][] = 'lazyloading';
    $lazyloading_node = render($build);

    if ($js == 'ajax') {
      $options = array();
      $response = new AjaxResponse();
      $response->addCommand(new InsertCommand('',$lazyloading_node, $options));
    } else {
      $response = new Response($lazyloading_node);
      $response->headers->set('X-Robots-Tag', 'noindex, follow');  // added to reduce duplicate content without ajax. for ajax requests this is added in InfiniteEventSubscriber.
    }

    return $response;
  }
}
