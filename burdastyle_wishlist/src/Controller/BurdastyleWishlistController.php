<?php

namespace Drupal\burdastyle_wishlist\Controller;


use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Image\Image;
use Drupal\file\Entity\File;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class BurdastyleWishlistController extends ControllerBase
{
  public function show()
  {
    $build = array(
      '#theme' => 'burdastyle_wishlist_page',
    );
    return $build;
  }


  public function loadProducts(Request $request)
  {
    $productIds = [];
    $products = [];
    $wishlist = json_decode($request->get('wishlist'), true);
    foreach ($wishlist as $wishlistItem) {
      $productIds[] = $wishlistItem['productId'];
    }

    if (false === empty($productIds)) {
      $query = Database::getConnection()
        ->select('advertising_product')
        ->fields('advertising_product')
        ->condition('product_id', $productIds, 'IN')
        ->execute();

      foreach ($query->fetchAll() as $product) {
        $imageFile = File::load($product->product_image__target_id);
        $fileUri = $imageFile->getFileUri();
        /** @var \Drupal\Core\Image\Image $image */
        $image = \Drupal::service('image.factory')->get($fileUri);
        $variables = array(
          'style_name' => 'thumbnail',
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
          '#theme' => 'burdastyle_wishlist_item',
          '#product' => $product,
          '#image' => [
            '#theme' => 'image_style',
            '#width' => $variables['width'],
            '#height' => $variables['height'],
            '#style_name' => $variables['style_name'],
            '#uri' => $variables['uri'],
          ],
        ];
        $products[] = [
          'productId' => $product->product_id,
          'markup' => \Drupal::service('renderer')->renderPlain($build),
        ];
      }
    }


    header('Content-type: application/json');
    return new JsonResponse([
      'products' => $products,
    ]);
  }
}