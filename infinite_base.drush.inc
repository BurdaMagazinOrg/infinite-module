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
      'fid' => 'The nummeric id of the file entity',
    ],
    'aliases' => ['ift'],
  ];

  return $items;
}

function drush_infinite_base_image_files_transliteration($fid = FALSE){

  if(is_numeric($fid)){
    $fids = [$fid];
  }
  else {
    $select = Drupal::database()->select('file_managed', 'fm');
    $select->fields('fm', ['fid', 'uri', 'created']);
    $select->condition('uri', '^.+://([^/]+/)*[[:alnum:]_-]+\..+$', 'NOT REGEXP');
    $result = $select->execute();
    $rows = $result->fetchAllAssoc('fid');
    $fids = array_keys($rows);
  }

  foreach ($fids as $fid) {

    $file = file_load($fid);
    $uri_old = $file->getFileUri();

    $values = array(
      'uid' => $file->get('uid'),
      'status' => $file->get('status'),
      'filename' => $file->getFilename(),
      'uri' => $uri_old,
      'filesize' => $file->getSize(),
      'filemime' => $file->getMimeType()
    );
    $file_new = File::create($values);

    $basename = basename($uri_old);

    $uri_new = str_replace($basename, $file_new->getFilename(), $uri_old);

    $update = Drupal::database()->update('file_managed');
    $update->fields([
      'uri' => $uri_new,
      'filename' => $file_new->getFilename()
    ]);
    $update->condition('fid', $file->id());
    $update->execute();

    $move = TRUE;
    $url = file_create_url($uri_old);
    if(!file_exists($uri_old)) {
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_HEADER, 0);
      $move = curl_exec($ch);
      curl_close($ch);
    }

    if($move){
      file_unmanaged_move($uri_old, $uri_new);
    }

  }

  return;
}