<?php

/**
 * @file Contains the implementations of own drush commands
 */

use \Drupal\file\Entity\File;

use Drupal\Core\Database\Database;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\taxonomy\TermInterface;

function infinite_base_drush_command(){
  $items = [];

  $items['image-files-transliteration'] = [
    'description' => 'Transliterate the filenames of old image files',
    'arguments' => [
      'fid' => 'The nummeric id of the file entity or all to process all files (default: all)',
      'batchsize' => 'The count of elements to process at once (default: 500)',
      'baseurl' => 'The base url to be uses for stage file proxy functionality',
    ],
    'options' => array(
      'baseurl' => 'The base url to be uses for stage file proxy functionality',
    ),
    'aliases' => ['ift'],
    'examples' => [
      'drush ift all 500 --baseurl="http://example.com"' => 'Transliterate all image files starting from 0 in 500 items chunks'
    ],
  ];

  $items['migrate-taxonomy-seo-data'] = [
    'description' => 'Migrate taxonomy SEO data to new fields. Run only once!',
  ];

  return $items;
}

function drush_infinite_base_image_files_transliteration($fid = FALSE, $batchsize = 500){

  $baseurl = drush_get_option('baseurl', FALSE);

  if(is_numeric($fid)){
    return _infinite_base_file_transliteration($fid, $baseurl);
  }

  do {

    $select = Drupal::database()->select('file_managed', 'fm');
    $select->fields('fm', ['fid', 'uri', 'created']);
    $select->condition('uri', '^.+://([^/]+/)*[[:alnum:]_-]+(\.[[:alnum:]_-]+)?$', 'NOT REGEXP');
    $select->range(0, $batchsize);

    $result = $select->execute();
    $rows = $result->fetchAllAssoc('fid');
    $count = count($rows);
    drush_print("Processing $count items ...");

    $fids = array_keys($rows);

    foreach ($fids as $fid) {
      _infinite_base_file_transliteration($fid, $baseurl);
    }

  }
  while(count($rows) == $batchsize);

  return;
}

function drush_infinite_base_migrate_taxonomy_seo_data() {
  $val = \Drupal::state()->get('infinite_base.term_seo_migrate');
  if ($val) {
    drush_set_error(dt("Taxonomy terms SEO info already migrated."));
    exit;
  }
  /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
  $entity_type_manager = \Drupal::entityTypeManager();
  /** @var \Drupal\Core\Entity\EntityStorageInterface $taxonomy_term_storage */
  $taxonomy_term_storage = $entity_type_manager->getStorage('taxonomy_term');

  // We get all terms regardless of vocabulary, this avoids hardcoding those.
  $tids = $taxonomy_term_storage->getQuery()
    ->accessCheck(FALSE)
    ->execute();

  $chunks = array_chunk(array_values($tids), 50);
  $operations = [];
  foreach ($chunks as $chunk) {
    $operations[] = [
      '_drush_infinite_base_migrate_taxonomy_seo_data_batch_op',
      [
        $chunk,
        $taxonomy_term_storage,
      ],
    ];
  }
  $batch = [
    'operations' => $operations,
    'title' => t('Process all taxonomy terms'),
    'init_message' => t('Initializing'),
    'error_message' => t('An error occurred'),
    'finished' => '_drush_infinite_base_migrate_taxonomy_seo_data_finished',
  ];

  batch_set($batch);
  $batch =& batch_get();
  $batch['progressive'] = FALSE;

  // Start processing.
  drush_backend_batch_process();
}

/**
 * Perform unpublish/delete on every taxonomy term in batch.
 *
 * @param array $tids
 *   List of taxonomy ids.
 * @param  $storage
 *   Taxonomy term storage service.
 *
 */
function _drush_infinite_base_migrate_taxonomy_seo_data_batch_op(array $tids, $storage) {
  $taxonomy_terms = $storage->loadMultiple($tids);
  $search = [
    '[term:title]',
    '[term:description]'
  ];
  $replace = [
    '[term:field_seo_title:value]',
    '[term:field_seo_description:value]'
  ];
  foreach ($taxonomy_terms as $term) {
    // In case the vocabulary does not have the new fields ignore.
    $field_definitions = $term->getFieldDefinitions();
    if (!isset($field_definitions['field_seo_title'])) {
      continue;
    }
    $meta = FALSE;
    // Check for meta info
    if (isset($term->field_meta_tags->value) && strlen($term->field_meta_tags->value) > 2) {
      $meta = unserialize($term->field_meta_tags->value);
    }
    // We try to update the tokens in the fields that we know may use them. If
    // there is no successfull replacement in the "default" meta tags, we
    // update the new fields from those and put the new tokens into the meta
    // tags.
    // Otherwise, we update the fields from the name and description fields
    // and leave the meta tags alone.
    if ($meta) {
      if (isset($meta['title'])) {
        $count = 0;
        $meta['title'] = str_replace($search, $replace, $meta['title'], $count);
        if ($count === 0) {
          $term->set('field_seo_title', $meta['title']);
          $meta['title'] = '[term:field_seo_title:value]';
        }
        else {
          $term->set('field_seo_title', $term->get('name')->value);
        }
      }
      else {
        $term->set('field_seo_title', $term->get('name')->value);
      }
      if (isset($meta['description'])) {
        $count = 0;
        $meta['description'] = str_replace($search, $replace, $meta['description'], $count);
        if ($count === 0) {
          $term->set('field_seo_description', $meta['description']);
          $meta['description'] = '[term:field_seo_description:value]';
        }
        else {
          $term->set('field_seo_description', strip_tags($term->get('description')->value));
        }
      }
      else {
        $term->set('field_seo_description', strip_tags($term->get('description')->value));
      }
      if (isset($meta['og_title'])) {
        $meta['og_title'] = str_replace($search, $replace, $meta['og_title']);
      }
      if (isset($meta['og_description'])) {
        $meta['og_description'] = str_replace($search, $replace, $meta['og_description']);
      }
      if (isset($meta['twitter_cards_title'])) {
        $meta['twitter_cards_title'] = str_replace($search, $replace, $meta['twitter_cards_title']);
      }
      // put the new values back
      $term->set('field_meta_tags', serialize($meta));
    }
    // no meta info, just set fields.
    else {
      $term->set('field_seo_title', $term->get('name')->value);
      $term->set('field_seo_description', strip_tags($term->get('description')->value));
    }
    // We would like to call term->save() here, but the nexx module does some
    // time consuming stuff in its taxonomy_term_update hook which may make
    // the update fail.
    Database::getConnection()->insert('taxonomy_term__field_seo_title')
      ->fields([
          'bundle' => $term->bundle(),
          'deleted' => 0,
          'entity_id' => $term->id(),
          'revision_id' => $term->id(),
          'langcode' => 'de',
          'delta' => 0,
          'field_seo_title_value' => $term->get('field_seo_title')->value,
        ])
      ->execute();
    Database::getConnection()->insert('taxonomy_term__field_seo_description')
      ->fields([
          'bundle' => $term->bundle(),
          'deleted' => 0,
          'entity_id' => $term->id(),
          'revision_id' => $term->id(),
          'langcode' => 'de',
          'delta' => 0,
          'field_seo_description_value' => $term->get('field_seo_description')->value,
        ])
      ->execute();
    if ($meta) {
      Database::getConnection()->update('taxonomy_term__field_meta_tags')
        ->fields([
            'field_meta_tags_value' => $term->field_meta_tags->value,
          ])
        ->condition('entity_id', $term->id())
        ->execute();
    }
  }
  drush_log(dt('Batch finished...'), "ok");
}

/**
 * Perform this function after all batches are done.
 *
 * @param bool $success
 *   Indicate that the batch API tasks were all completed successfully.
 * @param array $results
 *   An array of all the results that were updated in update_do_one().
 * @param array $operations
 *   A list of all the operations that had not been completed by the batch
 *   API.
 */
function _drush_infinite_base_migrate_taxonomy_seo_data_finished($success, array $results, array $operations) {
  \Drupal::state()->set('infinite_base.term_seo_migrate', 1);
  drush_log(dt('Operation finished'), "ok");
}

/**
 * Helper function to transliterate an image file name by
 * the file entity id
 *
 * @param $fid
 *  id of the file entity
 *
 * @return bool
 */
function _infinite_base_file_transliteration($fid, $baseurl = false) {
  $file = file_load($fid);
  if(!$file){
    return FALSE;
  }

  $uri_old = $file->getFileUri();

  $values = [
    'uid' => $file->get('uid'),
    'status' => $file->get('status'),
    'filename' => $file->getFilename(),
    'uri' => $uri_old,
    'filesize' => $file->getSize(),
    'filemime' => $file->getMimeType()
  ];
  $file_new = File::create($values);

  $basename = basename($uri_old);

  $pattern = '/(.+:\/\/)(([^\/]*\/)*)'.preg_quote($basename).'/';
  $uri_new = preg_replace($pattern, '$1${2}'.$file_new->getFilename(), $uri_old);

  if($uri_old != $uri_new) {

    $move = TRUE;
    if(is_string($baseurl) && strlen($baseurl)){
      $GLOBALS['base_url'] = $baseurl;
    }
    $url = file_create_url($uri_old);
    if (!file_exists($uri_old)) {
      $ch = curl_init($url);
      $move = curl_exec($ch);
      curl_close($ch);
    }

    if ($move) {
      file_move($file, $uri_new);
    }

  }
  else {
    return FALSE;
  }

  return FALSE;
}

