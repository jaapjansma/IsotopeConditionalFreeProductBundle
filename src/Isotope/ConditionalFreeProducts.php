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

namespace Krabo\IsotopeConditionalFreeProductBundle\Isotope;

use Contao\Controller;
use Contao\FrontendTemplate;
use Contao\Input;
use Isotope\Interfaces\IsotopeProductCollection;
use Isotope\Model\ProductCollection;
use Isotope\Model\ProductCollection\Cart;
use Isotope\Model\ProductCollection\Order;
use Krabo\IsotopeConditionalFreeProductBundle\Isotope\Model\ProductCollectionSurcharge\IsotopeConditionalFreeProductSurcharge;
use Krabo\IsotopeConditionalFreeProductBundle\Model\IsotopeConditionalFreeProduct;

class ConditionalFreeProducts extends Controller {

  /**
   * Current object instance (Singleton)
   * @var ConditionalFreeProducts|null
   */
  protected static ?ConditionalFreeProducts $objInstance = null;

  /**
   * Prevent cloning of the object (Singleton)
   */
  private function __clone() {}


  /**
   * Prevent direct instantiation (Singleton)
   */
  protected function __construct()
  {
    parent::__construct();
  }

  /**
   * Instantiate the singleton if necessary and return it
   * @return ConditionalFreeProducts
   */
  public static function getInstance(): ConditionalFreeProducts
  {
    if (static::$objInstance === null) {
      static::$objInstance = new ConditionalFreeProducts();
    }
    return static::$objInstance;
  }

  public function findSurcharges(IsotopeProductCollection $objCollection): array
  {
    $objCart = $objCollection;

    // The checkout review pages shows an order, but we need the cart
    // Only the cart contains coupons etc.
    if ($objCollection instanceof Order) {
      $objCart = $objCollection->getRelated('source_collection_id');
    }

    // Rules should only be applied to Cart, not any other product collection
    if (!($objCart instanceof Cart)) {
      return array();
    }

    $arrSurcharges = array();
    $objFreeProducts = IsotopeConditionalFreeProduct::findForCart();
    if (null !== $objFreeProducts) {
      foreach ($objFreeProducts as $objFreeProduct) {
        $arrSurcharges = IsotopeConditionalFreeProductSurcharge::addToCollection($objFreeProduct, $objCollection, $arrSurcharges);
      }
    }
    return $arrSurcharges;
  }

  public function addCollectionToTemplate(FrontendTemplate $objTemplate, array $arrItems, ProductCollection $productCollection, array $arrConfig) {
    $objTemplate->freeProducts = [];
    if ($productCollection instanceof Cart) {
      if (!isset($productCollection->freeProducts)) {
        $productCollection->freeProducts = array();
      }
      $freeProductsSettings = $productCollection->freeProducts;
      $arrFreeProducts = [];
      foreach($productCollection->getSurcharges() as $objSurcharge) {
        if ($objSurcharge instanceof IsotopeConditionalFreeProductSurcharge) {
          if (!isset($freeProductsSettings[$objSurcharge->source_id])) {
            $freeProductsSettings[$objSurcharge->source_id] = [
              'checked' => 1,
            ];
          }
          if (Input::post('FORM_SUBMIT') == $objTemplate->formId && $objTemplate->isEditable) {
            $isChecked = false;
            $freeProducts = Input::post('freeProduct');
            if (isset($freeProducts[$objSurcharge->source_id])) {
              $isChecked = true;
            }
            $freeProductsSettings[$objSurcharge->source_id]['checked'] = $isChecked ? 1 : 0;
          }
          $arrFreeProducts[] = [
            'id' => $objSurcharge->source_id,
            'label' => $objSurcharge->label,
            'quantity' => $objSurcharge->quantity,
            'checked' => $freeProductsSettings[$objSurcharge->source_id]['checked'],
          ];
        }
        $productCollection->freeProducts = $freeProductsSettings;
      }
      $objTemplate->freeProducts = $arrFreeProducts;
    }
  }

  public function transferFreeProducts(ProductCollection $oldCollection, ProductCollection $newCollection) {
    $newCollection->freeProducts = $oldCollection->freeProducts;
  }

}