<?php

namespace Drupal\infinite_wishlist\Controller;


use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Render\Markup;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class InfiniteWishlistController extends ControllerBase
{
  public function show()
  {
    $build = array(
      '#type' => 'markup',
      '#markup' => Markup::create('<div id="wishlist-page"></div>'),
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
        $products[] = [
          'productId' => $product->product_id,
          'markup' => sprintf(
            '<a href="%s" target="_blank">%s</a>',
            $product->product_url__uri,
            $product->product_name
          ),
        ];
      }
    }

    return new JsonResponse([
      'products' => $products
    ]);
  }
}