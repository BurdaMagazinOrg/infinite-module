<?php

namespace Drupal\infinite_wishlist\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class WishlistController extends ControllerBase {
  public function show()
  {
    $build = array(
      '#theme' => 'infinite_wishlist_page',
    );
    return $build;
  }


  public function loadProducts(Request $request)
  {
    $uuids = [];
    $products = [];
    $wishlist = json_decode($request->get('wishlist'), true);
    foreach ($wishlist as $wishlistItem) {
      $uuids[] = $wishlistItem['uuid'];
    }

    // Get products from ProductDB by UUID.
    if ($productFetcher = \Drupal::service('productdb_client.productdb_client_fetcher')) {
      $uuidsFromProductDB = [];
      foreach ($uuids as $uuid) {
        if ($productsFromProductDB = $productFetcher->fetchProductJson($uuid)) {
          $build = [
            '#theme' => 'infinite_wishlist_productdb_item',
            '#product' => $productsFromProductDB['data'],
            '#product_name' => $productsFromProductDB['data']['attributes']['title'],
            '#image' => [
              '#theme' => 'image',
              '#uri' => $this->getThumbnailUrl($productsFromProductDB)->getUri(),
              '#alt' => $productsFromProductDB['data']['attributes']['title'],
            ],
            '#provider' => explode("_", $productsFromProductDB['data']['attributes']['provider'])[0],
          ];
          $products[] = [
            'productId' => $productsFromProductDB['data']['attributes']['provider_identifier'],
            'uuid' => $productsFromProductDB['data']['id'],
            'name' => $productsFromProductDB['data']['attributes']['title'],
            'price' => $productsFromProductDB['data']['attributes']['price']['number'],
            'currency' => $productsFromProductDB['data']['attributes']['price']['currency_code'],
            'brand' => $productsFromProductDB['data']['attributes']['brand'],
            'category' => NULL,
            'markup' => \Drupal::service('renderer')->renderPlain($build),
          ];
          $uuidsFromProductDB[] = $uuid;
        };
      }
      $uuids = array_diff($uuids, $uuidsFromProductDB);
    }

    // Get non-ProductDB products by UUID.
    if (false === empty($uuids)) {
      $query = Database::getConnection()
        ->select('advertising_product');
      $query->leftJoin('advertising_product__field_product_category_txt', null, 'advertising_product__field_product_category_txt.entity_id=advertising_product.id');
      $query = $query->fields('advertising_product');
      $query = $query->fields('advertising_product__field_product_category_txt');
      $query = $query->condition('uuid', $uuids, 'IN')
        ->execute();

      foreach ($query->fetchAll() as $product) {
        $imageFile = File::load($product->product_image__target_id);
        if (!$imageFile) {
          continue;
        }
        $fileUri = $imageFile->getFileUri();
        /** @var \Drupal\Core\Image\Image $image */
        $image = \Drupal::service('image.factory')->get($fileUri);
        $variables = array(
          'style_name' => 'wishlist_item',
          'uri' => $fileUri,
        );

        if ($image->isValid()) {
          $variables['width'] = $image->getWidth();
          $variables['height'] = $image->getHeight();
        }
        else {
          $variables['width'] = $variables['height'] = NULL;
        }

        $build = [
          '#theme' => 'infinite_wishlist_item',
          '#product' => $product,
          '#product_name_without_brand' => $this->removeLeadingBrandFromProductName($product->product_name, $product->product_brand),
          '#image' => [
            '#theme' => 'responsive_image',
            '#width' => $variables['width'],
            '#height' => $variables['height'],
            '#responsive_image_style_id' => $variables['style_name'],
            '#uri' => $variables['uri'],
          ],
          '#provider' => explode("_", $product->product_provider)[0],
        ];
        $products[] = [
          'productId' => $product->product_id,
          'uuid' => $product->uuid,
          'name' => $product->product_name,
          'price' => $product->product_price,
          'currency' => $product->product_currency,
          'brand' => $product->product_brand,
          'category' => $product->field_product_category_txt_value,
          'markup' => \Drupal::service('renderer')->renderPlain($build),
        ];
      }
    }


    header('Content-type: application/json');
    return new JsonResponse([
      'products' => $products,
    ]);
  }

  /**
   * Get thumbnail image url of ProductDB product.
   *
   * @param $product
   *
   * @return Url|null
   *   Absolute thumbnail image URL of ProductDB product or NULL.
   */
  protected function getThumbnailUrl($product) {
    if (!isset($product['included']['0']['attributes']['uri']['url'])) {
      return NULL;
    }
    $url = $product['included']['0']['attributes']['uri']['url'];
    $url = str_replace('/files/', '/files/styles/thumbnail/public/', $url);

    $product_db_url = $this->config('productdb_client.settings')
      ->get('product_db_url');
    return Url::fromUri($product_db_url . '/' . $url);
  }

  protected function removeLeadingBrandFromProductName($name, $brand)
  {
    if (empty($brand)) {
      return $name;
    }
    $brandRemoved = false;
    if (0 === strpos(strtolower($name), strtolower($brand))) {
      $brandRemoved = true;
      $name = trim(substr($name, strlen($brand)));
    }
    if ($brandRemoved) {
      $name = ltrim($name, " \t\n\r\0\x0B-:;®");
    }
    return $name;
  }
}
