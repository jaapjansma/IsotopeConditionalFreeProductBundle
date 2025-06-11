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

namespace Krabo\IsotopeConditionalFreeProductBundle\Model;

use Contao\Database;
use Contao\Model;
use Contao\Model\Collection;
use Isotope\Isotope;
use Isotope\Model\ProductCollectionItem;

class IsotopeConditionalFreeProduct extends Model {

  protected static $strTable = "tl_iso_conditional_free_product";

  public static function findForCart():? Collection
  {
    // Only enabled rules
    $arrProcedures[] = "enabled='1'";

    // Date & Time restrictions
    $date = date('Y-m-d H:i:s');
    $time = date('H:i:s');
    $arrProcedures[] = "(startDate='' OR startDate <= UNIX_TIMESTAMP('$date'))";
    $arrProcedures[] = "(endDate='' OR endDate >= UNIX_TIMESTAMP('$date'))";
    $arrProcedures[] = "(startTime='' OR startTime <= UNIX_TIMESTAMP('1970-01-01 $time'))";
    $arrProcedures[] = "(endTime='' OR endTime >= UNIX_TIMESTAMP('1970-01-01 $time'))";
    $arrProducts = Isotope::getCart()->getItems();
    if (!empty($arrProducts)) {
      $arrProductIds = [0];
      $arrTypes = [0];

      foreach ($arrProducts as $objProduct) {
        if ($objProduct instanceof ProductCollectionItem) {
          if (!$objProduct->hasProduct()) {
            continue;
          }

          $objProduct = $objProduct->getProduct();
        }
        $arrProductIds[] = (int) $objProduct->getProductId();
        $arrTypes[] = (int) $objProduct->type;
      }

      $arrProductIds = array_unique($arrProductIds);

      $arrRestrictions = array("productRestrictions='none'");
      $arrRestrictions[] = "(productRestrictions='producttypes' AND (SELECT COUNT(*) FROM tl_iso_conditional_free_product_restriction WHERE pid=r.id AND type='productRestrictions_types' AND object_id IN (" . implode(',', $arrTypes) . "))>0)";
      $arrRestrictions[] = "(productRestrictions='products' AND (SELECT COUNT(*) FROM tl_iso_conditional_free_product_restriction WHERE pid=r.id AND type='productRestrictions_products' AND object_id IN (" . implode(',', $arrProductIds) . "))>0)";
      $arrProcedures[] = '(' . implode(' OR ', $arrRestrictions) . ')';
    }

    $objResult = Database::getInstance()
      ->prepare('SELECT * FROM ' . static::$strTable . ' r WHERE ' . implode(' AND ', $arrProcedures))
      ->execute()
    ;

    if ($objResult->numRows) {
      return Collection::createFromDbResult($objResult, static::$strTable);
    }

    return null;
  }

  public function findRestrictedIds(): array {
    $return = [];
    $arrProcedures[] = "pid = ?";
    $arrValues[] = $this->id;
    if ($this->productRestrictions == 'none') {
      return $return;
    } elseif ($this->productRestrictions == 'producttypes') {
      $arrProcedures[] = "type = 'productRestrictions_types'";
    } elseif ($this->productRestrictions == 'products') {
      $arrProcedures[] = "type = 'productRestrictions_products'";
    }

    $objResult = Database::getInstance()
      ->prepare('SELECT object_id FROM tl_iso_conditional_free_product_restriction WHERE ' . implode(' AND ', $arrProcedures))
      ->execute($arrValues)
    ;
    return $objResult->fetchEach('object_id');
  }
}