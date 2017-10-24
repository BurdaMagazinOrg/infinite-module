<?php

namespace Drupal\infinite_wishlist\Controller;


use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class InfiniteWishlistController extends ControllerBase
{
  public function show(Request $request)
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
            '<li><a href="%s" target="_blank">%s</a></li>',
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