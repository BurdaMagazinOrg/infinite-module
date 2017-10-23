<?php

namespace Drupal\infinite_wishlist\Controller;


use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class InfiniteWishlistController extends ControllerBase
{
  public function show(Request $request)
  {
    $productIds = [];
    $products = [];
    $wishlist = $request->get('wishlist');
    foreach ($wishlist as $wishlistItem) {
      $productIds[] = $wishlistItem['productId'];
    }
    $query = \Drupal\Core\Database\Database::getConnection()->query('select * from advertising_product
    where product_id IN (:productIds)',[
    ':productIds[]' => $productIds,
    ]);
    $query->execute();

    var_dump($query->fetchAll());
    die;


    foreach ($wishlist as $wishlistItem) {
      $products[] = [
        'productId' => $wishlistItem['productId'],
        'markup' => '<li>some product</li>'
      ];
    }
    return new JsonResponse([
      'products' => $products
    ]);
  }
}