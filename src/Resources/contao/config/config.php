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

$GLOBALS['TL_MODELS'][\Krabo\IsotopeConditionalFreeProductBundle\Model\IsotopeConditionalFreeProduct::getTable()] = 'Krabo\IsotopeConditionalFreeProductBundle\Model\IsotopeConditionalFreeProduct';

$GLOBALS['ISO_MOD']['product']['conditional_free_products'] = [
  'tables'        => array(\Krabo\IsotopeConditionalFreeProductBundle\Model\IsotopeConditionalFreeProduct::getTable()),
  'icon'          => 'system/modules/isotope/assets/images/setup-related_categories.png',
];

\Isotope\Model\ProductCollectionSurcharge::registerModelType('iso_conditional_free_product', 'Krabo\IsotopeConditionalFreeProductBundle\Isotope\Model\ProductCollectionSurcharge\IsotopeConditionalFreeProductSurcharge');

array_unshift($GLOBALS['ISO_HOOKS']['findSurchargesForCollection'], array('Krabo\IsotopeConditionalFreeProductBundle\Isotope\ConditionalFreeProducts', 'findSurcharges'));
$GLOBALS['ISO_HOOKS']['addCollectionToTemplate'][]  = array('Krabo\IsotopeConditionalFreeProductBundle\Isotope\ConditionalFreeProducts', 'addCollectionToTemplate');
$GLOBALS['ISO_HOOKS']['copiedCollectionItems'][]        = array('Krabo\IsotopeConditionalFreeProductBundle\Isotope\ConditionalFreeProducts', 'transferFreeProducts');