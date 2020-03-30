<?php

/**
 * @file Contains code for custom drush commands for odoscope
 */

namespace Drupal\infinite_odoscope\Commands;


use Drupal\Core\Queue\RequeueException;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\Core\Url;
use Drupal\Core\Session\UserSession;
use Drupal\Core\Session\AccountSwitcherInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Output\ConsoleOutput;
use Drupal\views\Views;
use Drupal\views\ViewExecutable;
use Drupal\views_data_export\Plugin\views\display;
use Drupal\views_data_export\Plugin\views\display\DataExport;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class InfiniteOdoscopeCommands extends DrushCommands {

  /**
   * The account switcher service.
   *
   * @var \Drupal\Core\Session\AccountSwitcherInterface
   */
  protected $accountSwitcher;

  /**
   * InfiniteOdoscopeCommands constructor.
   *
   * @param \Drupal\Core\Session\AccountSwitcherInterface $account_switcher
   *   The account switching service.
   */
  public function __construct(AccountSwitcherInterface $account_switcher) {
    $this->accountSwitcher = $account_switcher;
  }
  
  /**
   * Fill queue for odoscope.
   *
   *
   * @command infinite:odoscope-queue
   * @aliases infinite-odoscope-queue
   */
  public function drush_infinite_odoscope_odoscope_queue() {
    $this->accountSwitcher->switchTo(new UserSession(['uid' => 1]));

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
      $csv_data = $csv_header = [];
      if (count($claims_delete)) {
        foreach ($args_delete as $nid) {
          $row = [];
          // add ID and Publlished columns
          $row[] = $nid;
          $row[] = 0;
          // Fill the rest with empty columns.
          $csv_data[] = $row + array_fill(2, 15,'');
        }
      }
      if (count($claims_create)) {
        // execute the view
        $this->_odoscope_infinite_execute_view($args_create);
        // open the generated file. This sadly depends on executing drush with
        // -u 1 because the filename starts with the user ID.
        $fids = \Drupal::entityQuery('file')
              ->condition('status', FILE_STATUS_PERMANENT, '<>')
              ->condition('created', REQUEST_TIME - 120, '>')
              ->condition('filemime', 'text/csv')
              ->condition('filename', 'article-update.csv')
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
            \Drupal::service('slack.slack_service')->sendMessage($message);
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
            if ($rowcount == 0) {
              $csv_header[] = $data;
            }
            else {
              $csv_data[] = $data;
            }
            $rowcount++;
          }
          fclose($handle);
        }
      }
      // Combine both sets of data into a new csv file.
      if (count($csv_data)) {
        // in case the header wasn't read from the view above
        if (!$csv_header) {
          // hardcoded headers from the view
          $csv_header[] = explode(',', "ID,Published,Promoted_to_home,Promoted_to_channel,Title,Channel,Channel-ID,Author-ID,Author-Name,Created,Image,Thumbnail,Image-Text,Tag-IDs,Tag-Names,URL,Base64");
        }
        // We save the data to a tmp csv file so we do not need to to csv stuff ourselves
        $handle = fopen('compress.zlib://temporary://odoscope_update.csv.gz', 'w');
        if ($handle) {
          foreach ($csv_header as $fields) {
            fputcsv($handle, $fields);
          }
          foreach ($csv_data as $fields) {
            fputcsv($handle, $fields);
          }
          fclose($handle);
        }
        else {
          watchdog_exception('infinite_odoscope', 'Could not open temporary file');
        }
        $contents = file_get_contents('temporary://odoscope_update.csv.gz');
        if (!$contents) {
          watchdog_exception('infinite_odoscope', 'Empty temporary file');
        }
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
    $this->accountSwitcher->switchBack();
  }


  /**
   * Execute a view using code from drush_views_data_export
   */
  public function _odoscope_infinite_execute_view($args) {
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

    // Get view exposed input which is the query string parameters from url.
    $query_parameters = $view->getExposedInput();
    // Remove the file format parameter from the query string.
    if (array_key_exists('_format', $query_parameters)) {
      unset($query_parameters['_format']);
    }

    // Get view exposed input which is the query string parameters from url.
    $query_parameters = $view->getExposedInput();
    // Remove the file format parameter from the query string.
    if (array_key_exists('_format', $query_parameters)) {
      unset($query_parameters['_format']);
    }

    // Check where to redirect the user after the batch finishes.
    // Defaults to the <front> route.
    $redirect_url = Url::fromRoute('<front>');

    // Get options set in views display configuration.
    $custom_redirect = $view->getDisplay()->getOption('custom_redirect_path');
    $redirect_to_display = $view->getDisplay()->getOption('redirect_to_display');

    // Check if the url query string should be added to the redirect URL.
    $include_query_params = $view->display_handler->getOption('include_query_params');

    if ($custom_redirect) {
      $redirect_path = $view->display_handler->getOption('redirect_path');
      if (isset($redirect_path)) {
        // Replace tokens in the redirect_path.
        $token_service = \Drupal::token();
        $redirect_path = $token_service->replace($redirect_path, ['view' => $view]);

        if ($include_query_params) {
          $redirect_url = Url::fromUserInput(trim($redirect_path), ['query' => $query_parameters]);
        }
        else {
          $redirect_url = Url::fromUserInput(trim($redirect_path));
        }
      }
    }
    elseif (isset($redirect_to_display) && $redirect_to_display !== 'none') {
      // Get views display URL.
      $display_route = $view->getUrl([], $redirect_to_display)->getRouteName();
      if ($include_query_params) {
        $redirect_url = Url::fromRoute($display_route, [], ['query' => $query_parameters]);
      }
      else {
        $redirect_url = Url::fromRoute($display_route);
      }
    }

    $batch_definition = [
      'operations' => [
        [
          ['\Drupal\views_data_export\Plugin\views\display\DataExport', '\Drupal\views_data_export\Plugin\views\display\DataExport::processBatch'],
          [
            $view->id(),
            $view->current_display,
            $view->args,
            $view->getExposedInput(),
            $total_rows,
            $query_parameters,
            $redirect_url->toString(),
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
}
