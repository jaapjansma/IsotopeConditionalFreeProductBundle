<?php
/**
 * Copyright (C) 2024  Jaap Jansma (jaap.jansma@civicoop.org)
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

\Contao\System::loadLanguageFile('tl_iso_conditional_free_product');

$GLOBALS['TL_DCA']['tl_iso_conditional_free_product'] = array
(
  // Config
  'config' => array
  (
    'dataContainer'               => 'Table',
    'ctable'                      => array('tl_iso_conditional_free_product_restriction'),
    'sql' => array
    (
      'keys' => array
      (
        'id' => 'primary'
      )
    )
  ),

  // List
  'list' => array
  (
    'sorting' => array
    (
      'mode'                    => 1,
      'fields'                  => array('name'),
      'flag'                    => 11,
      'panelLayout'             => 'sort,filter,search,limit'
    ),
    'label' => array
    (
      'showColumns'             => true,
      'fields'                  => array('name'),
    ),
    'global_operations' => array
    (
      'all' => array
      (
        'href'                => 'act=select',
        'class'               => 'header_edit_all',
        'attributes'          => 'onclick="Backend.getScrollOffset()" accesskey="e"'
      )
    ),
    'operations' => array
    (
      'toggle' => array
      (
        'icon'                      => 'visible.svg',
        'attributes'                => 'onclick="Backend.getScrollOffset(); return AjaxRequest.toggleVisibility(this, %s);"',
        'button_callback'           => array('\Krabo\IsotopeConditionalFreeProductBundle\Backend\IsotopeConditionalFreeProduct', 'toggleIcon'),
      ),
      'edit' => array
      (
        'href'                => 'act=edit',
        'icon'                => 'edit.svg',
      ),
      'delete' => array
      (
        'href'                => 'act=delete',
        'icon'                => 'delete.svg',
        'attributes'          => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['tl_iso_conditional_free_product']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"',
      ),
    )
  ),

  // Palettes
  'palettes' => array
  (
    '__selector__'                      => array('productRestrictions'),
    'default'                     => 'name,applyTo,product_id;{limit_legend},productRestrictions,minItemQuantity,maxItemQuantity,minSubtotal,maxSubtotal;{datim_legend:hide},startDate,endDate,startTime,endTime;{enabled_legend},enabled'
  ),

  // Subpalettes
  'subpalettes' => array
  (
    'productRestrictions_producttypes'  => 'productRestrictions_types',
    'productRestrictions_products'      => 'productRestrictions_products',
  ),

  // Fields
  'fields' => array
  (
    'id' => array
    (
      'sql'                     => "int(10) unsigned NOT NULL auto_increment"
    ),
    'tstamp' => array
    (
      'sql'                     => "int(10) unsigned NOT NULL default 0"
    ),
    'name' => array
    (
      'search'                  => true,
      'inputType'               => 'text',
      'eval'                    => array('mandatory'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
      'sql'                     => "varchar(255) NOT NULL default ''"
    ),
    'applyTo' => array
    (
      'filter'                  => true,
      'inputType'               => 'radio',
      'options'                 => ['cart', 'product'],
      'reference'               => $GLOBALS['TL_LANG']['tl_iso_conditional_free_product']['applyToOptions'],
      'eval'                    => array('mandatory'=>true, 'tl_class'=>'w50'),
      'sql'                     => "varchar(255) NOT NULL default 'cart'"
    ),
    'product_id'     => array
    (
      'exclude'                       => true,
      'inputType'                     => 'tableLookup',
      'eval' => array
      (
        'mandatory'                 => true,
        'doNotSaveEmpty'            => true,
        'tl_class'                  => 'clr',
        'foreignTable'              => 'tl_iso_product',
        'fieldType'                 => 'radio',
        'listFields'                => array(\Isotope\Model\ProductType::getTable().'.name', 'name', 'sku'),
        'joins'                     => array
        (
          \Isotope\Model\ProductType::getTable() => array
          (
            'type' => 'LEFT JOIN',
            'jkey' => 'id',
            'fkey' => 'type',
          ),
        ),
        'searchFields'              => array('name', 'alias', 'sku', 'description'),
        'customLabels'              => array
        (
          $GLOBALS['TL_DCA'][\Isotope\Model\Product::getTable()]['fields']['type']['label'][0],
          $GLOBALS['TL_DCA'][\Isotope\Model\Product::getTable()]['fields']['name']['label'][0],
          $GLOBALS['TL_DCA'][\Isotope\Model\Product::getTable()]['fields']['sku']['label'][0],
        ),
        'sqlWhere'                  => 'pid=0',
        'searchLabel'               => 'Search products',
      ),
      'sql'                   => "int(10) unsigned NOT NULL default '0'",
    ),
    'minItemQuantity' => array
    (
      'exclude'                       => true,
      'inputType'                     => 'text',
      'eval'                          => array('maxlength'=>10, 'rgxp'=>'digit', 'tl_class'=>'w50'),
      'sql'                           => "int(10) unsigned NOT NULL default '0'",
    ),
    'maxItemQuantity' => array
    (
      'exclude'                       => true,
      'inputType'                     => 'text',
      'eval'                          => array('maxlength'=>10, 'rgxp'=>'digit', 'tl_class'=>'w50'),
      'sql'                           => "int(10) unsigned NOT NULL default '0'",
    ),
    'minSubtotal' => array
    (
      'exclude'                       => true,
      'inputType'                     => 'text',
      'eval'                          => array('maxlength'=>10, 'rgxp'=>'digit', 'tl_class'=>'w50'),
      'sql'                           => "int(10) unsigned NOT NULL default '0'",
    ),
    'maxSubtotal' => array
    (
      'exclude'                       => true,
      'inputType'                     => 'text',
      'eval'                          => array('maxlength'=>10, 'rgxp'=>'digit', 'tl_class'=>'w50'),
      'sql'                           => "int(10) unsigned NOT NULL default '0'",
    ),
    'productRestrictions' => array
    (
      'inputType'                     => 'radio',
      'default'                       => 'none',
      'exclude'                       => true,
      'filter'                        => true,
      'options'                       => array('none', 'producttypes', 'products'),
      'reference'                     => &$GLOBALS['TL_LANG']['tl_iso_conditional_free_product']['productRestrictions_options'],
      'eval'                          => array('submitOnChange'=>true, 'tl_class'=>'clr w50 w50h'),
      'sql'                           => "varchar(32) NOT NULL default ''",
    ),
    'productRestrictions_types' => array
    (
      'exclude'                       => true,
      'inputType'                     => 'checkbox',
      'foreignKey'                    => \Isotope\Model\ProductType::getTable().'.name',
      'eval'                          => array('mandatory'=>true, 'multiple'=>true, 'doNotSaveEmpty'=>true, 'tl_class'=>'clr'),
      'load_callback' => array
      (
        array('\Krabo\IsotopeConditionalFreeProductBundle\Backend\IsotopeConditionalFreeProduct', 'loadRestrictions'),
      ),
      'save_callback' => array
      (
        array('\Krabo\IsotopeConditionalFreeProductBundle\Backend\IsotopeConditionalFreeProduct', 'saveRestrictions'),
      ),
    ),
    'productRestrictions_products'     => array
    (
      'exclude'                       => true,
      'inputType'                     => 'tableLookup',
      'eval' => array
      (
        'mandatory'                 => true,
        'doNotSaveEmpty'            => true,
        'tl_class'                  => 'clr',
        'foreignTable'              => 'tl_iso_product',
        'fieldType'                 => 'checkbox',
        'listFields'                => array(\Isotope\Model\ProductType::getTable().'.name', 'name', 'sku'),
        'joins'                     => array
        (
          \Isotope\Model\ProductType::getTable() => array
          (
            'type' => 'LEFT JOIN',
            'jkey' => 'id',
            'fkey' => 'type',
          ),
        ),
        'searchFields'              => array('name', 'alias', 'sku', 'description'),
        'customLabels'              => array
        (
          $GLOBALS['TL_DCA'][\Isotope\Model\Product::getTable()]['fields']['type']['label'][0],
          $GLOBALS['TL_DCA'][\Isotope\Model\Product::getTable()]['fields']['name']['label'][0],
          $GLOBALS['TL_DCA'][\Isotope\Model\Product::getTable()]['fields']['sku']['label'][0],
        ),
        'sqlWhere'                  => 'pid=0',
        'searchLabel'               => 'Search products',
      ),
      'load_callback' => array
      (
        array('\Krabo\IsotopeConditionalFreeProductBundle\Backend\IsotopeConditionalFreeProduct', 'loadRestrictions'),
      ),
      'save_callback' => array
      (
        array('\Krabo\IsotopeConditionalFreeProductBundle\Backend\IsotopeConditionalFreeProduct', 'saveRestrictions'),
      ),
    ),
    'startDate' => array
    (
      'exclude'                       => true,
      'inputType'                     => 'text',
      'eval'                          => array('rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 wizard'),
      'sql'                           => "varchar(10) NOT NULL default ''",
    ),
    'endDate' => array
    (
      'exclude'                       => true,
      'inputType'                     => 'text',
      'eval'                          => array('rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 wizard'),
      'sql'                           => "varchar(10) NOT NULL default ''",
    ),
    'startTime' => array
    (
      'exclude'                       => true,
      'inputType'                     => 'text',
      'eval'                          => array('rgxp'=>'time', 'datepicker'=>true, 'tl_class'=>'w50 wizard'),
      'sql'                           => "varchar(10) NOT NULL default ''",
    ),
    'endTime' => array
    (
      'exclude'                       => true,
      'inputType'                     => 'text',
      'eval'                          => array('rgxp'=>'time', 'datepicker'=>true, 'tl_class'=>'w50 wizard'),
      'sql'                           => "varchar(10) NOT NULL default ''",
    ),
    'enabled'    => array
    (
      'exclude'                       => true,
      'inputType'                     => 'checkbox',
      'filter'                        => true,
      'eval'                          => array('doNotCopy'=>true, 'tl_class'=>'w50'),
      'sql'                           => "char(1) NOT NULL default ''",
    ),
  )
);