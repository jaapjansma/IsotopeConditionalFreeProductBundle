<?php

/*
 * Isotope eCommerce for Contao Open Source CMS
 *
 * Copyright (C) 2009 - 2019 terminal42 gmbh & Isotope eCommerce Workgroup
 *
 * @link       https://isotopeecommerce.org
 * @license    https://opensource.org/licenses/lgpl-3.0.html
 */

/**
 * Table tl_iso_conditional_free_product_restriction
 */
$GLOBALS['TL_DCA']['tl_iso_conditional_free_product_restriction'] = array
(

    // Config
    'config' => array
    (
        'sql' => array
        (
            'keys' => array
            (
                'id' => 'primary',
                'pid,type' => 'index',
                'type,object_id,pid' => 'index',
            )
        ),
    ),

    'fields'                        => array
    (
        'id' => array
        (
            'sql'                   => "int(10) unsigned NOT NULL auto_increment",
        ),
        'pid' => array
        (
            'foreignKey'            => 'tl_iso_conditional_free_product.name',
            'sql'                   => "int(10) unsigned NOT NULL default '0'",
            'relation'              => array('type'=>'belongsTo', 'load'=>'lazy'),
        ),
        'tstamp' => array
        (
            'sql'                   => "int(10) unsigned NOT NULL default '0'",
        ),
        'type' => array
        (
            'sql'                   => "varchar(32) NOT NULL default ''",
        ),
        'object_id' => array
        (
            'sql'                   => "int(10) unsigned NOT NULL default '0'",
        ),
    )
);
