<?php
// Base controller for all GedcomRecord controllers
//
// webtrees: Web based Family History software
// Copyright (C) 2014 webtrees development team.
//
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA

if (!defined('WT_WEBTREES')) {
	header('HTTP/1.0 403 Forbidden');
	exit;
}

require_once WT_ROOT.'includes/functions/functions_print_facts.php';
require_once WT_ROOT.'includes/functions/functions_import.php';

class WT_Controller_GedcomRecord extends WT_Controller_Page {
	public $record; // individual, source, repository, etc.

	public function __construct(WT_GedcomRecord $record = null) {
		$this->record = $record;

		// Automatically fix broken links
		if ($this->record && $this->record->canEdit()) {
			$broken_links = false;
			foreach ($this->record->getFacts('HUSB|WIFE|CHIL|FAMS|FAMC|REPO') as $fact) {
				if (!$fact->isOld() && $fact->getTarget() === null) {
					$this->record->deleteFact($fact->getFactId(), false);
					WT_FlashMessages::addMessage(/* I18N: %s are names of records, such as sources, repositories or individuals */ WT_I18N::translate('The link from “%1$s” to “%2$s” has been deleted.', $this->record->getFullName(), $fact->getValue()));
					$broken_links = true;
				}
			}
			foreach ($this->record->getFacts('NOTE|SOUR|OBJE') as $fact) {
				// These can be links or inline.  Only delete links.
				if (!$fact->isOld() && $fact->getTarget() === null && preg_match('/^@.*@$/', $fact->getValue())) {
					$this->record->deleteFact($fact->getFactId(), false);
					WT_FlashMessages::addMessage(/* I18N: %s are names of records, such as sources, repositories or individuals */ WT_I18N::translate('The link from “%1$s” to “%2$s” has been deleted.', $this->record->getFullName(), $fact->getValue()));
					$broken_links = true;
				}
			}
			if ($broken_links) {
				// Reload the updated record
				$this->record = WT_GedcomRecord::getInstance($this->record->getXref());
			}
		}

		parent::__construct();

		// We want robots to index this page
		$this->setMetaRobots('index,follow');

		// Set a page title
		if ($this->record) {
			$this->setCanonicalUrl($this->record->getHtmlUrl());
			if ($this->record->canShowName()) {
				// e.g. "John Doe" or "1881 Census of Wales"
				$this->setPageTitle($this->record->getFullName());
			} else {
				// e.g. "Individual" or "Source"
				$record = $this->record;
				$this->setPageTitle(WT_Gedcom_Tag::getLabel($record::RECORD_TYPE));
			}
		} else {
			// No such record
			$this->setPageTitle(WT_I18N::translate('Private'));
		}
	}
	
	/**
	* get edit menu
	*/
	function getEditMenu() {
		$SHOW_GEDCOM_RECORD = get_gedcom_setting(WT_GED_ID, 'SHOW_GEDCOM_RECORD');

		if (!$this->record || $this->record->isOld()) {
			return null;
		}

		// edit menu
		$menu = new WT_Menu(WT_I18N::translate('Edit'), '#', 'menu-record');

		// What behaviour shall we give the main menu?  If we leave it blank, the framework
		// will copy the first submenu - which may be edit-raw or delete.
		$menu->addOnclick("return false;");

		// delete
		if (WT_USER_CAN_EDIT) {
			$submenu = new WT_Menu(WT_I18N::translate('Delete'), '#', 'menu-record-del');
			$submenu->addOnclick("return delete_repository('" . WT_I18N::translate('Are you sure you want to delete “%s”?', strip_tags($this->record->getFullName()))."', '".$this->record->getXref()."');");
			$menu->addSubmenu($submenu);
		}

		// edit raw
		if (WT_USER_IS_ADMIN || WT_USER_CAN_EDIT && $SHOW_GEDCOM_RECORD) {
			$submenu = new WT_Menu(WT_I18N::translate('Edit raw GEDCOM'), '#', 'menu-record-editraw');
			$submenu->addOnclick("return edit_raw('" . $this->record->getXref() . "');");
			$menu->addSubmenu($submenu);
		}

		// add to favorites
		if (array_key_exists('user_favorites', WT_Module::getActiveModules())) {
			$submenu = new WT_Menu(
				/* I18N: Menu option.  Add [the current page] to the list of favorites */ WT_I18N::translate('Add to favorites'),
				'#',
				'menu-record-addfav'
			);
			$submenu->addOnclick("jQuery.post('module.php?mod=user_favorites&amp;mod_action=menu-add-favorite',{xref:'".$this->record->getXref()."'},function(){location.reload();})");
			$menu->addSubmenu($submenu);
		}

		//-- get the link for the first submenu and set it as the link for the main menu
		if (isset($menu->submenus[0])) {
			$link = $menu->submenus[0]->onclick;
			$menu->addOnclick($link);
		}
		return $menu;
	}
}
