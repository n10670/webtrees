<?php namespace WT\Theme;

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

/**
 * The Base theme provides simple/default behaviour.
 */
class Base {
	/**
	 * Get the user-friendly name for this theme.
	 *
	 * Implementations of this function may want to translate the name.
	 *
	 * @return string
	 */
	public function getName() {
		return 'THEME NAME HERE';
	}

	/**
	 * @param string $head_title The text to put in <head><title></title></head>
	 * @param string $extra_html Additional content for the header.
	 *
	 * @return string
	 */
	public function getHtmlHead($head_title, $extra_html='') {
		return
			$this->getHtmlHeadMeta() .
			$this->getHtmlHeadTitle($head_title) .
			$this->getHtmlHeadStyle() .
			$this->getHtmlHeadScript() .
			$extra_html;
	}

	/**
	 * Generate the meta tags for the HTML header.
	 *
	 * @return string
	 */
	public function getHtmlHeadMeta() {
		return '';
	}

	/**
	 * Generate the Javascript links for the HTML header.
	 *
	 * @return string
	 */
	public function getHtmlHeadScript() {
		return '';
	}

	/**
	 * Generate the CSS links for the HTML header.
	 *
	 * @return string
	 */
	public function getHtmlHeadScript() {
		return '';
	}

	/**
	 * Generate the title element for the HTML header.
	 *
	 * @param string $head_title The text for the page title
	 *
	 * @return string
	 */
	public function getHtmlHeadTitle($head_title) {
		return '<title>' . WT_Filter::escapeHtml(strip_tags($head_title)) . ' - webtrees</title>';
	}
}