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

namespace Krabo\IsotopeConditionalFreeProductBundle\Backend;

use Contao\Backend;
use Contao\Controller;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\Database;
use Contao\Image;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;

class IsotopeConditionalFreeProduct extends Backend {
  public function __construct()
  {
    parent::__construct();
    $this->import('BackendUser', 'User');
  }


  /**
   * Load product restrictions from linked table
   */
  public function loadRestrictions($varValue, $dc)
  {
    $varValue = Database::getInstance()->execute("SELECT object_id FROM tl_iso_conditional_free_product_restriction WHERE pid={$dc->activeRecord->id} AND type='{$dc->field}'")->fetchEach('object_id');

    if (!empty($GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['csv'])) {
      $varValue = implode($GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['csv'], $varValue);
    }

    return $varValue;
  }

  /**
   * Save product restrictions to linked table. Only update what necessary to prevent the IDs from increasing on every save_callback
   */
  public function saveRestrictions($varValue, $dc)
  {
    if (!empty($GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['csv'])) {
      $arrNew = explode($GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['csv'], $varValue);
    } else {
      $arrNew = StringUtil::deserialize($varValue);
    }

    if (!\is_array($arrNew) || empty($arrNew)) {
      Database::getInstance()->query("DELETE FROM tl_iso_conditional_free_product_restriction WHERE pid={$dc->activeRecord->id} AND type='{$dc->field}'");

    } else {
      $arrOld = Database::getInstance()->execute("SELECT object_id FROM tl_iso_conditional_free_product_restriction WHERE pid={$dc->activeRecord->id} AND type='{$dc->field}'")->fetchEach('object_id');

      $arrInsert = array_diff($arrNew, $arrOld);
      $arrDelete = array_diff($arrOld, $arrNew);

      if (!empty($arrDelete)) {
        Database::getInstance()->query("DELETE FROM tl_iso_conditional_free_product_restriction WHERE pid={$dc->activeRecord->id} AND type='{$dc->field}' AND object_id IN (" . implode(',', $arrDelete) . ")");
      }

      if (!empty($arrInsert)) {
        $time = time();
        Database::getInstance()->query("INSERT INTO tl_iso_conditional_free_product_restriction (pid,tstamp,type,object_id) VALUES ({$dc->id}, $time, '{$dc->field}', " . implode("), ({$dc->id}, $time, '{$dc->field}', ", $arrInsert) . ")");
      }
    }

    return '';
  }

  public function toggleIcon($row, $href, $label, $title, $icon, $attributes)
  {
    if (\strlen(Input::get('tid'))) {
      $this->toggleVisibility(Input::get('tid'), (Input::get('state') == 1));
      Controller::redirect($this->getReferer());
    }

    // Check permissions AFTER checking the tid, so hacking attempts are logged
    if (!$this->User->isAdmin && !$this->User->hasAccess('tl_iso_conditional_free_product::enabled', 'alexf')) {
      return Image::getHtml($icon, $label) . ' ';
    }

    $href .= '&amp;tid=' . $row['id'] . '&amp;state=' . ($row['enabled'] ? '' : 1);

    if (!$row['enabled']) {
      $icon = 'invisible.svg';
    }

    return '<a href="' . $this->addToUrl($href) . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label) . '</a> ';
  }

  public function toggleVisibility($intId, $blnVisible)
  {
    // Check permissions to publish
    if (!$this->User->isAdmin && !$this->User->hasAccess('tl_iso_conditional_free_product::enabled', 'alexf')) {
      throw new AccessDeniedException('Not enough permissions to enable/disable rule ID "' . $intId . '"');
    }
    // Trigger the save_callback
    if (\is_array($GLOBALS['TL_DCA']['tl_iso_conditional_free_product']['fields']['enabled']['save_callback'])) {
      foreach ($GLOBALS['TL_DCA']['tl_iso_conditional_free_product']['fields']['enabled']['save_callback'] as $callback) {
        $objCallback = System::importStatic($callback[0]);
        $blnVisible = $objCallback->{$callback[1]}($blnVisible, $this);
      }
    }
    // Update the database
    Database::getInstance()->prepare("UPDATE tl_iso_conditional_free_product SET tstamp=" . time() . ", enabled='" . ($blnVisible ? 1 : '') . "' WHERE id=?")->execute($intId);
  }

}