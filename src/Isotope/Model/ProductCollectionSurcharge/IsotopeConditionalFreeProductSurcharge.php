<?php
/**
 * Copyright (C) 2025  Jaap Jansma (jaap.jansma@civicoop.org)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace Krabo\IsotopeConditionalFreeProductBundle\Isotope\Model\ProductCollectionSurcharge;

use Isotope\Interfaces\IsotopeProductCollection;
use Isotope\Interfaces\IsotopeProductCollectionSurcharge;
use Isotope\Model\Product;
use Isotope\Model\ProductCollectionSurcharge;
use Krabo\IsotopeConditionalFreeProductBundle\Model\IsotopeConditionalFreeProduct;

class IsotopeConditionalFreeProductSurcharge extends ProductCollectionSurcharge implements IsotopeProductCollectionSurcharge {

  public $quantity;

  public $checked;

  public static function addToCollection(IsotopeConditionalFreeProduct $objFreeProduct, IsotopeProductCollection $objCollection, array $arrSurcharges): array
  {
    // Cart subtotal
    if (($objFreeProduct->minSubtotal > 0 && $objCollection->getSubtotal() < $objFreeProduct->minSubtotal) || ($objFreeProduct->maxSubtotal > 0 && $objCollection->getSubtotal() > $objFreeProduct->maxSubtotal)) {
      return $arrSurcharges;
    }

    $qty = 1;
    if ($objFreeProduct->applyTo == 'product') {
      $qty = $objCollection->sumItemsQuantity();
    }
    $objIsoProduct = null;
    if ($objFreeProduct->product_id) {
      $objIsoProduct = Product::findByPk($objFreeProduct->product_id);
    }

    if (!$objIsoProduct) {
      return $arrSurcharges;
    }

    if (!$objCollection->freeProducts) {
      $objCollection->freeProducts = [];
    }
    $freeProductsSettings = $objCollection->freeProducts;

    $checked = static::isFreeProductInCart($objCollection, $objFreeProduct);
    $objSurcharge = new static();
    $objSurcharge->source_id = $objFreeProduct->id;
    $objSurcharge->label = $objIsoProduct->getName();
    $objSurcharge->quantity = $qty;
    $objSurcharge->checked = $checked;
    $objSurcharge->before_tax = true;
    $objSurcharge->addToTotal = false;
    $objSurcharge->rowClass = $checked ? 'checked' : 'unchecked';
    $freeProductsSettings[$objFreeProduct->id] = [
      'checked' => $checked,
      'qty' => $qty,
    ];

    $objCollection->freeProducts = $freeProductsSettings;

    $arrSurcharges[] = $objSurcharge;

    return $arrSurcharges;
  }

  public function hasTax()
  {
    return true;
  }

  public static function isFreeProductInCart(IsotopeProductCollection $productCollection, IsotopeConditionalFreeProduct $objFreeProduct): bool {
    if (!isset($productCollection->freeProducts)) {
      $productCollection->freeProducts = array();
    }
    $checked = true;
    if (isset($productCollection->freeProducts[$objFreeProduct->id]['checked'])) {
      $checked = (bool) $productCollection->freeProducts[$objFreeProduct->id]['checked'];
    }
    return $checked;
  }

}