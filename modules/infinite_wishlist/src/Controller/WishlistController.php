<?php

namespace Drupal\infinite_wishlist\Controller;


use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Image\Image;
use Drupal\file\Entity\File;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class WishlistController extends ControllerBase
{
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
          '#tracking_url' => $product->product_url__uri."&subid=wishlist-".$product->product_id,
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
