<?php

/**
 * @file Contains code for custom drush commands for odoscope
 */

use Drupal\Core\Queue\RequeueException;
use Drupal\Core\Queue\SuspendQueueException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Output\ConsoleOutput;
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
    'description' => 'Works on the odoscope queue. Requires to be run with -u 1.',
  ];
  return $items;
}


/**
 * Implements drush_{module}_{command}.
 */
function drush_infinite_odoscope_odoscope_queue() {
  $uid = \Drupal::currentUser()->id();
  if ($uid != 1) {
    return 'This command requires the -u 1 flag';
  }
  $queueFactory = \Drupal::service('queue');

  $queue_name = 'OdoscopeUpdater';

  // Make sure the queue exists. There is no harm in trying to recreate
  // an existing queue.
  $queueFactory->get($queue_name)->createQueue();
  $queue = $queueFactory->get($queue_name);
  // Claim all items from the queue.
  if ($itemcount = $queue->numberOfItems()) {
    $claims_create = $claims_delete = [];
    $args_create = $args_delete = [];

    for ($i = 1; $i <= $itemcount; $i++) {
      $claim = $queue->claimItem();
      switch ($claim->data->action) {
        case 'create':
        case 'update':
          $claims_create[] = $claim;
          $args_create[] = $claim->data->nid;
          break;
        case 'delete':
          $claims_delete[] = $claim;
          $args_delete[] = $claim->data->nid;
          break;
      }
    }
    // hardcoded headers from the view
    $csv_data[] = explode(',', "ID,Published,Promoted to home,Promoted to channel,Title,Channel,Channel-ID,Author-ID,Author-Name,Created,Image,Thumbnail,Image-Text,Tag-IDs,Tag-Names,URL,Base64");
    if (count($claims_delete)) {
      foreach ($args_delete as $nid) {
        $row = [];
        // add ID and Publlished columns
        $row[] = $nid;
        $row[] = 0;
        // Fill the rest with empty columns.
        $csv_data[] = $row + array_fill(2, 13,'');
      }
    }
    if (count($claims_create)) {
      // execute the view
      _odoscope_infinite_execute_view($args_create);
      // open the generated file. This sadly depends on executing drush with
      // -u 1 because the filename starts with the user ID.
      $fids = Drupal::entityQuery('file')
        ->condition('status', FILE_STATUS_PERMANENT, '<>')
        ->condition('created', REQUEST_TIME - 120, '>')
        ->condition('filemime', 'text/csv')
        ->condition('uri', db_like('private://') . '%' . db_like('article-update.csv'), 'LIKE')
        ->sort('created', 'DESC')
        ->range(0, 1)
        ->execute();

      $files = entity_load_multiple('file', $fids);
      $filecount = count($files);
      switch ($filecount) {
        case 0:
          // No file found, alert slack
          $ah_env = isset($_ENV['AH_SITE_ENVIRONMENT']) ? $_ENV['AH_SITE_ENVIRONMENT'] : '';
          $message = t('No file created by odoscope view or file not found on @env.', ['@env' => $ah_env]);
          Drupal::service('slack.slack_service')->sendMessage($message);
          break;
        case 1:
          $file = reset($files);
          break;
        default:
          // can't happen
          break;
      }

      $handle = fopen($file->getFileUri(), 'r');
      $rowcount = 0;
      if ($handle) {
        while (($data = fgetcsv($handle)) !== FALSE) {
          if ($rowcount > 0) {
            $csv_data[] = $data;
          }
          $rowcount++;
        }
        fclose($handle);
      }
    }

    // Combine both sets of data into a new csv file.
    if (count($csv_data) > 1) {
      $handle = fopen('compress.zlib://temporary://odoscope_update.csv.gz', 'w');
      foreach ($csv_data as $fields) {
        fputcsv($handle, $fields);
      }
      fclose($handle);
      $contents = file_get_contents('temporary://odoscope_update.csv.gz');
      $client = \Drupal::httpClient();
      $date_format = 'Y-m-d\TH-i-s';
      try {
        $config = \Drupal::config('infinite_odoscope.settings');
        $odoscope_user = $config->get('odoscope_user');
        $odoscope_pass = $config->get('odoscope_pass');
        $odoscope_url = $config->get('odoscope_url');
        $date = date($date_format);
        $request = $client->post("$odoscope_url",
                   [
                     'auth' => ["$odoscope_user","$odoscope_pass"],
                     'multipart' => [
                       [
                         'name'     => 'file',
                         'contents' => $contents,
                         'filename' => "update-{$date}.csv.gz",
                       ]
                     ]
                   ]);
        $reply = $request->getBody()->getContents();
        $status = $request->getStatusCode();
        \Drupal::logger('infinite_odoscope')->notice('Sent CSV update and got resonse @r with apache code @a', ['@r' => $reply, '@a' => $status]);
      }
      catch (RequestException $e) {
        watchdog_exception('infinite_odoscope', $e->getMessage());
      }
      finally {
        // we delete the claimed queue items when we get 200
        if ($status == 200) {
          foreach ($claims_create as $claim) {
            $queue->deleteItem($claim);
          }
          foreach ($claims_delete as $claim) {
            $queue->deleteItem($claim);
          }
          // We save the processed CSV-Data
          $dir = 'private://odoscope-archive';
          file_prepare_directory($dir, FILE_CREATE_DIRECTORY);
          $contents = file_save_data($contents, $dir . '/' . date($date_format) . '.csv.gz');
        }
      }
    }
  }
}

/**
 * Execute a view using code from drush_views_data_export
 */
function _odoscope_infinite_execute_view($args) {
  $views_args[] = implode('+', $args);
  $view = Views::getView('odoscope_article_export');
  $view->setDisplay('data_update');
  $view->setArguments($views_args);

  /* this is all copied from drush_views_data_export */
  $view->get_total_rows = TRUE;
  $export_limit = $view->getDisplay()->getOption('export_limit');

  $view->build();
  $count_query = clone $view->query;
  $total_rows = $count_query->query()->countQuery()->execute()->fetchField();
  // Don't load and instantiate so many entities.
  $view->query->setLimit(1);
  $view->execute();

  // If export limit is set and the number of rows is greater than the
  // limit, then set the total to limit.
  if ($export_limit && $export_limit < $total_rows) {
    $total_rows = $export_limit;
  }

  $batch_definition = [
    'operations' => [
      [
        ['Drupal\views_data_export\Plugin\views\display\DataExport', 'Drupal\views_data_export\Plugin\views\display\DataExport::processBatch'],
        [
          $view->id(),
          $view->current_display,
          $view->args,
          $view->getExposedInput(),
          $total_rows,
          ],
      ],
    ],
    'title' => t('Exporting data...'),
    'progressive' => TRUE,
    'progress_message' => '@percentage% complete. Time elapsed: @elapsed',
    'finished' => ['DataExport', 'DataExport::finishBatch'],
  ];

  batch_set($batch_definition);

  drush_backend_batch_process();
}
