<?php
/**
 * Header for Minimal theme
 *
 * webtrees: Web based Family History software
 * Copyright (C) 2010 webtrees development team.
 *
 * Derived from PhpGedView
 * Copyright (C) 2002 to 2009  PGV Development Team.  All rights reserved.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @package webtrees
 * @subpackage Themes
 * @version $Id$
 */

if (!defined('WT_WEBTREES')) {
	header('HTTP/1.0 403 Forbidden');
	exit;
}

echo
	'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">',
	'<html xmlns="http://www.w3.org/1999/xhtml" ', WT_I18N::html_markup(), '>',
	'<head>',
	'<meta http-equiv="Content-type" content="text/html;charset=UTF-8" />',
	'<title>', htmlspecialchars($title), '</title>',
	'<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />';

if (!empty($LINK_CANONICAL)) {
	echo '<link rel="canonical" href="', $LINK_CANONICAL, '" />';
}
if (!empty($META_DESCRIPTION)) {
	echo '<meta name="description" content="', htmlspecialchars($META_DESCRIPTION), '" />';
}
echo '<meta name="robots" content="', $META_ROBOTS, '" />';
if (!empty($META_GENERATOR)) {
	echo '<meta name="generator" content="', $META_GENERATOR, '" />';
}

echo
	'<link type="text/css" href="js/jquery/css/jquery-ui.custom.css" rel="Stylesheet" />',
	'<link rel="stylesheet" href="', $stylesheet, '" type="text/css" media="all" />';

if ((!empty($rtl_stylesheet))&&($TEXT_DIRECTION=="rtl")) {
	echo '<link rel="stylesheet" href="', $rtl_stylesheet, '" type="text/css" media="all" />';
}

switch ($BROWSERTYPE) {
//case 'chrome': uncomment when chrome.css file needs to be added, or add others as needed
case 'msie':
	echo '<link type="text/css" rel="stylesheet" href="', WT_THEME_DIR, $BROWSERTYPE, '.css" />';
	break;
}

// Additional css files required (Only if Lightbox installed)
if (WT_USE_LIGHTBOX) {
	if ($TEXT_DIRECTION=='rtl') {
		echo '<link rel="stylesheet" href="', WT_MODULES_DIR, 'lightbox/css/clearbox_music_RTL.css" type="text/css" />';
		echo '<link rel="stylesheet" href="', WT_MODULES_DIR, 'lightbox/css/album_page_RTL_ff.css" type="text/css" media="screen" />';
	} else {
		echo '<link rel="stylesheet" href="', WT_MODULES_DIR, 'lightbox/css/clearbox_music.css" type="text/css" />';
		echo '<link rel="stylesheet" href="', WT_MODULES_DIR, 'lightbox/css/album_page.css" type="text/css" media="screen" />';
	}
}

echo
	'<link type="text/css" href="', WT_THEME_DIR, 'modules.css" rel="Stylesheet" />',
	'<link rel="stylesheet" href="', $print_stylesheet, '" type="text/css" media="print" />',
	$javascript,
	'</head>',
	'<body id="body">';

// begin header section
if ($view!='simple') {
	echo 
	'<div id="header" class="', $TEXT_DIRECTION, '">',
	'<span class="title">';
	print_gedcom_title_link(TRUE);
	echo 
	'</span>',
	
	'<span class="hlogin">';
	if (WT_USER_ID) {
		echo '<a href="edituser.php" class="link">', WT_I18N::translate('Logged in as '), ' ', getUserFullName(WT_USER_ID), '</a> | ', logout_link();
	} elseif (empty($SEARCH_SPIDER)) {
		echo login_link();
	}
	echo
	'</span>';
	if (empty($SEARCH_SPIDER)) { 
		echo 
		'<span class="htheme">';
		$menu=WT_MenuBar::getThemeMenu();
		if ($menu) {
			echo $menu->getMenuAsDropdown();
		}
		$menu=WT_MenuBar::getLanguageMenu();
		if ($menu) {
			echo $menu->getMenuAsDropdown();
		}
		echo
		'</span>',
		'<div class="hsearch">';
		if (empty($SEARCH_SPIDER)) {
			echo 
			'<form style="display:inline;" action="search.php" method="get">',
			'<input type="hidden" name="action" value="general" />',
			'<input type="hidden" name="topsearch" value="yes" />',
			'<input type="text" name="query" size="15" value="', WT_I18N::translate('Search'), '" onfocus="if (this.value==\'', WT_I18N::translate('Search'), '\') this.value=\'\'; focusHandler();" onblur="if (this.value==\'\') this.value=\'', WT_I18N::translate('Search'), '\';" />',
			'<input type="submit" name="search" value=" &gt; " />',
			'</form>';
		}
		print_favorite_selector();
	}
	echo 
	'</div></div>';
	echo "\n";
//  begin top links section
	echo 
	'<div id="topMenu">', 
	'<ul class="makeMenu">'; 

		$menu=WT_MenuBar::getGedcomMenu();
		if ($menu) {
			$menu->addIcon(null);
			echo $menu->getMenuAsList();
		}
		$menu=WT_MenuBar::getMyPageMenu();
		if ($menu) {
			echo $menu->getMenuAsList();
		}
		$menu=WT_MenuBar::getChartsMenu();
		if ($menu) {
			$menu->addIcon(null);
			echo $menu->getMenuAsList();
		}
		$menu=WT_MenuBar::getListsMenu();
		if ($menu) {
			$menu->addIcon(null);
			echo $menu->getMenuAsList();
		}
		$menu=WT_MenuBar::getCalendarMenu();
		if ($menu) {
			$menu->addIcon(null);
			echo $menu->getMenuAsList();
		}
		$menu=WT_MenuBar::getReportsMenu();
		if ($menu) {
			$menu->addIcon(null);
			echo $menu->getMenuAsList();
		}
		$menu=WT_MenuBar::getSearchMenu();
		if ($menu) {
			$menu->addIcon(null);
			echo $menu->getMenuAsList();
		}
		$menus=WT_MenuBar::getModuleMenus();
		foreach ($menus as $menu) {
			if ($menu) {
				$menu->addIcon(null);
			echo $menu->getMenuAsList();
			}
		}
		$menu=WT_MenuBar::getHelpMenu();
		if ($menu) {
			$menu->addIcon(null);
			echo $menu->getMenuAsList();
		}
	echo 
	'</ul>',
	'</div>',
'<img src="', $WT_IMAGES['hline'], '" width="100%" height="3" alt="" />';
} 
?>
<!-- end menu section -->
<!-- begin content section -->
<div id="content">
