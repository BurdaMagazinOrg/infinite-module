<?php

/**
 * @file Contains code for custom drush commands for odoscope
 */

use Drupal\Core\Queue\RequeueException;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\views\Views;
use Drupal\views\ViewExecutable;
use Drupal\views_data_export\Plugin\views\display;
use Drupal\views_data_export\Plugin\views\display\DataExport;


/**
 * Implements hook_drush_command().
 */
function infinite_odoscope_drush_command() {
  $items = [];
  $items['odoscope-queue'] = [
    'description' => 'Works on the odoscope queue',
  ];
  return $items;
}


/**
 * Implements drush_{module}_{command}.
 */
function drush_infinite_odoscope_odoscope_queue() {
  $queueFactory = \Drupal::service('queue');

  $OdoscopeQueues = ['OdoscopeUpdater'];

  foreach ($OdoscopeQueues as $queue_name) {
    // Make sure every queue exists. There is no harm in trying to recreate
    // an existing queue.
    $queueFactory->get($queue_name)->createQueue();
    $queue = $queueFactory->get($queue_name);
    // Claim all items from the queue.
    if ($itemcount = $queue->numberOfItems()) {
      $claims_update = $claims_create = $claims_delete = [];
      $args_create = $args_update = $args_delete = [];
      $args_create[0] = 'create';
      $args_create[1] = [];
      $args_update[0] = 'update';
      $args_update[1] = [];
      $args_delete[0] = 'delete';
      $args_delete[1] = [];

      for ($i = 1; $i <= $itemcount; $i++) {
        $claim = $queue->claimItem();
        switch ($claim->data->action) {
          case 'update':
            $claims_update[] = $claim;
            $args_update[1][] = $claim->data->nid;
            break;
          case 'create':
            $claims_create[] = $claim;
            $args_create[1][] = $claim->data->nid;
            break;
          case 'delete':
            $claims_delete[] = $claim;
            $args_delete[1][] = $claim->data->nid;
            break;
        }
      }
    }
    if (count($claims_create)) {
      _odoscope_infinite_execute_view($args_create);
    }
    if (count($claims_update)) {
      _odoscope_infinite_execute_view($args_update);
    }
    if (count($claims_delete)) {
      _odoscope_infinite_execute_view($args_delete);
    }
  }
}

function _odoscope_infinite_execute_view($args) {
  $views_args = [$args[0]];
  $views_args[] = implode('+', $args[1]);
  $view = Views::getView('odoscope_article_export');
  $view->setDisplay('data_update');
  $view->setArguments($views_args);
  $view->get_total_rows = TRUE;
  $export_limit = $view->getDisplay()->getOption('export_limit');

  $view->build();
  $count_query = clone $view->query;
  $total_rows = $count_query->query()->countQuery()->execute()->fetchField();
  $view->execute();
}
