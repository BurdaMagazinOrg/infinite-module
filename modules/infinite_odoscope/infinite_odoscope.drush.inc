<?php

/**
 * @file Contains code for custom drush commands for odoscope
 */

use Drupal\Core\Queue\RequeueException;
use Drupal\Core\Queue\SuspendQueueException;


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

  $OdoscopeQueues = [
    'infinite_odoscope_create',
    'infinite_odoscope_update',
    'infinite_odoscope_delete',
  ];
  $OdoscopeQueues = ['OdoscopeUpdater'];

  foreach ($OdoscopeQueues as $queue_name) {
    // Make sure every queue exists. There is no harm in trying to recreate
    // an existing queue.
    $queueFactory->get($queue_name)->createQueue();
    $queue = $queueFactory->get($queue_name);
    // Claim zero or more items from the queue.
    if ($itemcount = $queue->numberOfItems()) {
      var_dump($itemcount);
      $claims = [];
      for ($i = 1; $i <= $itemcount; $i++) {
        $claim = $queue->claimItem();
        $claims[] = $claim;
      }
      foreach ($claims as $claim) {
        $queue->deleteItem($claim);
      }
    }
  }

}
