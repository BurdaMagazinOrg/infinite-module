<?php

/**
 * @file Contains the implementations of own drush commands
 */

use \Drupal\file\Entity\File;

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

