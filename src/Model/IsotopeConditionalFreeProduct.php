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
use Isotope\Model\ProductCategory;
use Isotope\Model\ProductCollectionItem;

class IsotopeConditionalFreeProduct extends Model {

  protected static $strTable = "tl_iso_conditional_free_product";

  public static function findForCart():? Collection
  {
    return static::findByConditions();
  }

  protected static function findByConditions($arrProcedures = [], $arrValues = [], $arrProducts = [], $blnIncludeVariants = false, $arrAttributeData = []):? Collection
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


    // Product restrictions
    if (!\is_array($arrProducts)) {
      $arrProducts = Isotope::getCart()->getItems();
    }

    if (!empty($arrProducts)) {
      $arrProductIds = [0];
      $arrVariantIds = [0];
      $arrAttributes = [];
      $arrTypes = [0];

      // Prepare product attribute condition
      $objAttributeRules = Database::getInstance()->execute("SELECT attributeName, attributeCondition FROM " . static::$strTable . " WHERE enabled='1' AND productRestrictions='attribute' AND attributeName!='' GROUP BY attributeName, attributeCondition");
      while ($objAttributeRules->next()) {
        $arrAttributes[] = array
        (
          'attribute' => $objAttributeRules->attributeName,
          'condition' => $objAttributeRules->attributeCondition,
          'values'    => [],
        );
      }

      foreach ($arrProducts as $objProduct) {
        if ($objProduct instanceof ProductCollectionItem) {
          if (!$objProduct->hasProduct()) {
            continue;
          }

          $objProduct = $objProduct->getProduct();
        }

        $arrProductIds[] = (int) $objProduct->getProductId();
        $arrVariantIds[] = (int) $objProduct->{$objProduct->getPk()};
        $arrTypes[] = (int) $objProduct->type;

        if ($objProduct->isVariant()) {
          $arrVariantIds[] = (int) $objProduct->pid;
        }

        if ($blnIncludeVariants && $objProduct->hasVariants()) {
          $arrVariantIds = array_merge($arrVariantIds, $objProduct->getVariantIds());
        }

        $arrOptions = $objProduct->getOptions();
        foreach ($arrAttributes as $k => $restriction) {
          $varValue = null;

          if (isset($arrAttributeData[$restriction['attribute']])) {
            $varValue = $arrAttributeData[$restriction['attribute']];
          } elseif (isset($arrOptions[$restriction['attribute']])) {
            $varValue = $arrOptions[$restriction['attribute']];
          } else {
            $varValue = $objProduct->{$restriction['attribute']};
          }

          if (!\is_null($varValue)) {
            $arrAttributes[$k]['values'][] = \is_array($varValue) ? serialize($varValue) : $varValue;
          }
        }
      }

      $arrProductIds = array_unique($arrProductIds);
      $arrVariantIds = array_unique($arrVariantIds);

      $arrRestrictions = array("productRestrictions='none'");
      $arrRestrictions[] = "(productRestrictions='producttypes' AND productCondition='1' AND (SELECT COUNT(*) FROM tl_iso_rule_restriction WHERE pid=r.id AND type='producttypes' AND object_id IN (" . implode(',', $arrTypes) . "))>0)";
      $arrRestrictions[] = "(productRestrictions='producttypes' AND productCondition='0' AND (SELECT COUNT(*) FROM tl_iso_rule_restriction WHERE pid=r.id AND type='producttypes' AND NOT object_id IN (" . implode(',', $arrTypes) . "))>0)";
      $arrRestrictions[] = "(productRestrictions='products' AND productCondition='1' AND (SELECT COUNT(*) FROM tl_iso_rule_restriction WHERE pid=r.id AND type='products' AND object_id IN (" . implode(',', $arrProductIds) . "))>0)";
      $arrRestrictions[] = "(productRestrictions='products' AND productCondition='0' AND (SELECT COUNT(*) FROM tl_iso_rule_restriction WHERE pid=r.id AND type='products' AND object_id NOT IN (" . implode(',', $arrProductIds) . "))>0)";
      $arrRestrictions[] = "(productRestrictions='variants' AND productCondition='1' AND (SELECT COUNT(*) FROM tl_iso_rule_restriction WHERE pid=r.id AND type='variants' AND object_id IN (" . implode(',', $arrVariantIds) . "))>0)";
      $arrRestrictions[] = "(productRestrictions='variants' AND productCondition='0' AND (SELECT COUNT(*) FROM tl_iso_rule_restriction WHERE pid=r.id AND type='variants' AND object_id NOT IN (" . implode(',', $arrVariantIds) . "))>0)";
      $arrRestrictions[] = "(productRestrictions='pages' AND productCondition='1' AND (SELECT COUNT(*) FROM tl_iso_rule_restriction WHERE pid=r.id AND type='pages' AND object_id IN (SELECT page_id FROM " . ProductCategory::getTable() . " WHERE pid IN (" . implode(',', $arrProductIds) . ")))>0)";
      $arrRestrictions[] = "(productRestrictions='pages' AND productCondition='0' AND (SELECT COUNT(*) FROM tl_iso_rule_restriction WHERE pid=r.id AND type='pages' AND object_id NOT IN (SELECT page_id FROM " . ProductCategory::getTable() . " WHERE pid IN (" . implode(',', $arrProductIds) . ")))>0)";

      foreach ($arrAttributes as $restriction) {
        if (empty($restriction['values'])) {
          continue;
        }

        $strRestriction = "(productRestrictions='attribute' AND attributeName='" . $restriction['attribute'] . "' AND attributeCondition='" . $restriction['condition'] . "' ";

        switch ($restriction['condition']) {
          case 'eq':
            $strRestriction .= sprintf(
              "AND attributeValue IN (%s)",
              implode(', ', array_fill(0, \count($restriction['values']), '?'))
            );

            $arrValues = array_merge($arrValues, $restriction['values']);
            break;

          // We cannot handle this as `attributeValue NOT IN (...)`, since we want to handle the rule if at
          // least one of the products in the cart does not equal the value. So if exactly one product (value)
          // is in the cart, it might not match. Otherwise it always matches at least one of the cart products.
          case 'neq':
            if (1 === \count($restriction['values'])) {
              $strRestriction .= 'AND attributeValue = ?';
              $arrValues = array_merge($arrValues, $restriction['values']);
            }
            break;

          case 'lt':
          case 'gt':
          case 'elt':
          case 'egt':
            $arrOR = array();
            foreach ($restriction['values'] as $value) {
              $arrOR[] = sprintf(
                'attributeValue %s%s ?',
                (('lt' === $restriction['condition'] || 'elt' === $restriction['condition']) ? '>' : '<'),
                (('elt' === $restriction['condition'] || 'egt' === $restriction['condition']) ? '=' : '')
              );
              $arrValues[] = $value;
            }
            $strRestriction .= 'AND (' . implode(' OR ', $arrOR) . ')';
            break;

          case 'starts':
          case 'ends':
          case 'contains':
            $arrOR = array();
            foreach ($restriction['values'] as $value) {
              $arrOR[] = sprintf(
                "? LIKE CONCAT(%sattributeValue%s)",
                (('ends' === $restriction['condition'] || 'contains' === $restriction['condition']) ? "'%', " : ''),
                (('starts' === $restriction['condition'] || 'contains' === $restriction['condition']) ? ", '%'" : '')
              );
              $arrValues[] = $value;
            }
            $strRestriction .= 'AND (' . implode(' OR ', $arrOR) . ')';
            break;

          default:
            throw new \InvalidArgumentException(
              sprintf('Unknown rule condition "%s"', $restriction['condition'])
            );
        }

        $arrRestrictions[] = $strRestriction . ')';
      }

      $arrProcedures[] = '(' . implode(' OR ', $arrRestrictions) . ')';
    }

    $objResult = Database::getInstance()
      ->prepare('SELECT * FROM ' . static::$strTable . ' r WHERE ' . implode(' AND ', $arrProcedures))
      ->execute($arrValues)
    ;

    if ($objResult->numRows) {
      return Collection::createFromDbResult($objResult, static::$strTable);
    }

    return null;
  }
}