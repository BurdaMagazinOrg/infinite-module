<?php

namespace Drupal\infinite_base\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InsertCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\views\Views;

/**
 * Defines a controller to render a single node.
 */
class LazyLoadingViewsViewController extends ControllerBase {

  public function ajaxView($nodeId, Term $channel, $page) {
    if (FALSE === is_numeric($page)) {
      throw new \Exception('Page number needs to be numeric');
    }

    $args = [
      $channel->id(),
      $nodeId,
    ];
    $view = Views::getView('infinite_taxonomy_term');
    if (is_object($view)) {
      $view->setArguments($args);
      $display = 'show_more_stream';
      $view->setDisplay($display);
      $view->setCurrentPage($page);
      // need to overwrite offset here, because it can't be changed
      // separately per display
      $view->setOffset(0);

      $view->execute($display);
      $view->hide_feed_container = true;
      $preview = $view->preview($display);
      $content = render($preview);
    }
    else {
      throw new \Exception('view not found');
    }

    $options = [];
    $response = new AjaxResponse();
    $response->addCommand(new InsertCommand('', $content, $options));
    return $response;
  }
}
