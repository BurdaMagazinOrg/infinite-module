<?php

use Drupal\Core\Entity\EntityInterface;

/**
 * Implements hook_drush_command().
 */
function infinite_advertising_products_drush_command() {

  $items = [];

  $items['advertising-products-reimport'] = [
    'description' => 'Transliterate the filenames of old image files',
    'callback' => 'drush_infinite_advertising_products_reimport',
    'arguments' => [
      'pid' => 'The nummeric id of the product to reimport (default: all)',
      'chunksize' => 'The count of elements to process at once (default: 50)',
      'batchsize' => 'The count of elements to process at all (default: 500)',
    ],
    'aliases' => ['apreimp'],
    'examples' => [
      'drush apreimp trecdelight 10 500"' => 'Reimport 500 advertising trecdelight products by importing 10 products at once.'
    ],
  ];

  return $items;
}

function drush_infinite_advertising_products_reimport($pid = 'tracdelight', $chunksize = 50, $batchsize = 500){
  $advProdEntityTypeManager = \Drupal::entityTypeManager()->getStorage('advertising_product');

  if(is_numeric($pid)){
    $product = $advProdEntityTypeManager->load($pid);
    if($product) {
      _infinite_advertising_products_reimport($product);
    }
    else {
      drush_set_error('product not found', dt('The product whth ID "!id" could not be found.', ['!id' => $pid]));
    }
  }
  else {
    $bundle = 'advertising_product_' . $pid;
    $def = \Drupal::service('entity_type.bundle.info')->getBundleInfo('advertising_product');

    if(isset($def[$bundle])){
      $result = \Drupal::entityQuery('advertising_product')
        ->condition('type', $bundle)
        ->condition('status', TRUE)
        ->notExists('field_product_category_txt')
        ->range(0, $batchsize)
        ->execute();

      $success = 0;
      $error = 0;

      while(!empty($result)){
        $chunk = array_splice($result, 0, $chunksize);

        $products = $advProdEntityTypeManager->loadMultiple($chunk);

        foreach ($products as $product) {
          $ret = _infinite_advertising_products_reimport($product);
          if($ret === FALSE) {
            $error++;
          }
          else {
            $success++;
          }
        }

        $rows = [
          ['success', $success],
          ['error', $error],
          ['total', $success + $error],
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

  /** @var \Drupal\advertising_products\AdvertisingProductsProviderInterface $provider */
  $provider = $providerManager->createInstance($product->product_provider->value);
  $product_id = $product->product_id->value;

  try {
    $product_data = $provider->queryProduct($product_id);
    return $provider->saveProduct($product_data, $product->id());
  }
  catch (Exception $e){
    drush_set_error('an error accoured', $e->getMessage());
    return FALSE;
  }

}

