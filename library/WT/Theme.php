<?php namespace WT;

// webtrees: Web based Family History software
// Copyright (C) 2014 webtrees development team
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

use WT_Tree;
use Zend_Session;

/**
 * Class Theme - static interface to the currently selected theme
 */
class Theme {

	/**
	 * The selected theme.  Should be set during bootstrap.
	 *
	 * @var \WT\Theme_Base
	 */
	private static $theme;

	/**
	 *
	 * @param \WT\Theme_Base $theme
	 */
	public static function setTheme(Theme_Base $theme) {
		self::$theme = $theme;
	}

	/**
	 *
	 */
	public static function getThemes() {
	}

	/**
	 * Wrapper function to pass all calls to Theme::func() to $this->theme->func()
	 *
	 * This allows us to easily call theme functions from anywhere in the code.
	 */
	public static function __callStatic($func, $args) {
		return call_user_func_array(self::$theme->$func, $args);
	}
}