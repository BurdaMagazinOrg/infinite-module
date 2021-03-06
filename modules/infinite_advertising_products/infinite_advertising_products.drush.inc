<?php

use Drupal\Core\Entity\EntityInterface;
use Drush\Log\LogLevel;

/**
 * Implements hook_drush_command().
 */
function infinite_advertising_products_drush_command() {

  $items = [];

  $items['advertising-products-reimport'] = [
    'description' => 'Transliterate the filenames of old image files',
    'callback' => 'drush_infinite_advertising_products_reimport',
    'arguments' => [
      'pid' => 'The nummeric id of the product to reimport or product type for bulk reimport',
      'chunksize' => 'The count of elements to process at once (default: 50)',
      'batchsize' => 'The count of elements to process at all (default: 0 for all)',
    ],
    'options' => array(
      'delay' => 'How many millisoconds shlould be waited between each reimpor.',
    ),
    'aliases' => ['apreimp'],
    'examples' => [
      'drush apreimp tracdelight 10 500"' => 'Reimport 500 advertising trecdelight products by importing 10 products at once.'
    ],
  ];

  $items['advertising-products-fix-images'] = [
    'description' => 'Reimports products that have images matching "advert"',
    'callback' => 'drush_infinite_advertising_products_fix_images',
    'arguments' => [
      'pid' => 'The nummeric id of the product to reimport or product type for bulk reimport',
      'chunksize' => 'The count of elements to process at once (default: 50)',
      'batchsize' => 'The count of elements to process at all (default: 0 for all)',
    ],
    'options' => array(
      'delay' => 'How many millisoconds shlould be waited between each reimpor.',
    ),
    'aliases' => ['apfiximg'],
    'examples' => [
      'drush apfiximg tracdelight 10 500"' => 'Reimport 500 advertising trecdelight products by importing 10 products at once.'
    ],
  ];

  return $items;
}

function drush_infinite_advertising_products_reimport($pid = 'tracdelight', $chunksize = 50, $batchsize = 0){
  $advProdEntityTypeManager = \Drupal::entityTypeManager()->getStorage('advertising_product');
  $delay = drush_get_option('delay');

  if(is_numeric($pid) && $pid != '004'){
    $product = $advProdEntityTypeManager->load($pid);
    if($product) {
      $product = _infinite_advertising_products_reimport($product);
      if($product) {
        drush_log('Product reimported', LogLevel::SUCCESS);
      }
      else {
        drush_log('Product not reimported', LogLevel::WARNING);
      }
    }
    else {
      drush_set_error('product not found', dt('The product whth ID "!id" could not be found.', ['!id' => $pid]));
    }
  }
  else {
    if(strpos($pid,'advertising_product_') === 0) {
      $bundle = $pid;
    }
    else {
      $bundle = 'advertising_product_' . $pid;
    }
    $def = \Drupal::service('entity_type.bundle.info')->getBundleInfo('advertising_product');

    if(isset($def[$bundle])){
      $query = \Drupal::entityQuery('advertising_product')
        ->condition('type', $bundle)
        ->condition('status', TRUE);

      if($batchsize) {
        $query->range(0, $batchsize);
      }

      $result = $query->execute();

      $success = 0;
      $error = 0;

      drush_print(dt('Processing !count items.', ['!count' => count($result)]));

      while(!empty($result)){
        $chunk = array_splice($result, 0, $chunksize);

        $products = $advProdEntityTypeManager->loadMultiple($chunk);

        foreach ($products as $product) {
          $ret = _infinite_advertising_products_reimport($product);
          if($ret === FALSE) {
            drush_log(dt('Product !id not reimported', ['!id' => $product->id()]), LogLevel::WARNING);
            $error++;
          }
          else {
            drush_log(dt('Product !id reimported', ['!id' => $product->id()]), LogLevel::SUCCESS);
            $success++;
          }
          usleep($delay * 1000);
        }

        $rows = [
          ['success:', $success],
          ['error:', $error],
          ['total:', $success + $error],
        ];

        drush_print_table($rows);
      }

    }
    else {
      drush_set_error('product bundle not found', dt('The advertising product type "!bundle" not found.', ['!bundle' => $bundle]));
    }

  }


}


function drush_infinite_advertising_products_fix_images($pid = 'tracdelight', $chunksize = 50, $batchsize = 0){
  $advProdEntityTypeManager = \Drupal::entityTypeManager()->getStorage('advertising_product');
  $delay = drush_get_option('delay');

  if(is_numeric($pid) && $pid != '004'){
    $product = $advProdEntityTypeManager->load($pid);
    if($product) {
      $product = _infinite_advertising_products_reimport($product);
      if($product) {
        drush_log('Product reimported', LogLevel::SUCCESS);
      }
      else {
        drush_log('Product not reimported', LogLevel::WARNING);
      }
    }
    else {
      drush_set_error('product not found', dt('The product whth ID "!id" could not be found.', ['!id' => $pid]));
    }
  }
  else {
    if(strpos($pid,'advertising_product_') === 0) {
      $bundle = $pid;
    }
    else {
      $bundle = 'advertising_product_' . $pid;
    }
    $def = \Drupal::service('entity_type.bundle.info')->getBundleInfo('advertising_product');

    if(isset($def[$bundle])){
      $query = \Drupal::entityQuery('advertising_product')
        ->condition('type', $bundle)
        ->condition('status', TRUE)
        ->condition('product_image.target_id', NULL, 'IS NOT NULL')
        ->condition('product_image.target_id.entity.filename', '%' . db_like('advert') . '%', 'LIKE');

      if($batchsize) {
        $query->range(0, $batchsize);
      }

      $result = $query->execute();

      $success = 0;
      $error = 0;

      drush_print(dt('Processing !count items.', ['!count' => count($result)]));

      while(!empty($result)){
        $chunk = array_splice($result, 0, $chunksize);

        $products = $advProdEntityTypeManager->loadMultiple($chunk);

        foreach ($products as $product) {
          $ret = _infinite_advertising_products_reimport($product);
          if($ret === FALSE) {
            drush_log(dt('Product !id not reimported', ['!id' => $product->id()]), LogLevel::WARNING);
            $error++;
          }
          else {
            drush_log(dt('Product !id reimported', ['!id' => $product->id()]), LogLevel::SUCCESS);
            $success++;
          }
          usleep($delay * 1000);
        }

        $rows = [
          ['success:', $success],
          ['error:', $error],
          ['total:', $success + $error],
        ];

        drush_print_table($rows);
      }

    }
    else {
      drush_set_error('product bundle not found', dt('The advertising product type "!bundle" not found.', ['!bundle' => $bundle]));
    }

  }
}


function _infinite_advertising_products_reimport(EntityInterface $product) {

  /** @var \Drupal\advertising_products\AdvertisingProductsProviderManager $providerManager */
  $providerManager = \Drupal::service('plugin.manager.advertising_products.provider');

  try {
    /** @var \Drupal\advertising_products\AdvertisingProductsProviderInterface $provider */
    $provider = $providerManager->createInstance($product->product_provider->value);
    $product_id = $product->product_id->value;

    return $provider->fetchProductOnTheFly($product_id, $product->id());
  }
  catch (Exception $e){
    $message_prefix = dt('!type !id', ['!type' => $product->bundle(), '!id' => $product->id()]);
    $message = $message_prefix . ': ' . $e->getMessage();
    drush_set_error('ERROR', $message);
    return FALSE;
  }

}
