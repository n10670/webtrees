<?php
// This is a default viewer for non-standard records (e.g. SUBM, SUBN, _LOC)
// that have no dedicated page of their own.
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

define('WT_SCRIPT_NAME', 'gedrecord.php');
require './includes/session.php';
require_once WT_ROOT.'includes/functions/functions_print_lists.php';

$controller = new WT_Controller_GedcomRecord(WT_GedcomRecord::getInstance(WT_Filter::get('pid', WT_REGEX_XREF)));

// If this record has its own viewer, then use it.
if ($controller->record && strpos(WT_SCRIPT_NAME, $controller->record->getRawUrl()) !== false) {
	Zend_Session::writeClose();
	header('Location: ' . WT_SERVER_NAME . WT_SCRIPT_PATH . $obj->getRawUrl());
	exit;
}

if ($controller->record && $controller->record->canShow()) {
	$controller->pageHeader();
	if ($controller->record->isOld()) {
		if (WT_USER_CAN_ACCEPT) {
			echo
				'<p class="ui-state-highlight">',
				/* I18N: %1$s is “accept”, %2$s is “reject”.  These are links. */ WT_I18N::translate(
					'This repository has been deleted.  You should review the deletion and then %1$s or %2$s it.',
					'<a href="#" onclick="accept_changes(\''.$controller->record->getXref().'\');">' . WT_I18N::translate_c('You should review the deletion and then accept or reject it.', 'accept') . '</a>',
					'<a href="#" onclick="reject_changes(\''.$controller->record->getXref().'\');">' . WT_I18N::translate_c('You should review the deletion and then accept or reject it.', 'reject') . '</a>'
				),
				' ', help_link('pending_changes'),
				'</p>';
		} elseif (WT_USER_CAN_EDIT) {
			echo
				'<p class="ui-state-highlight">',
				WT_I18N::translate('This record has been deleted.  The deletion will need to be reviewed by a moderator.'),
				' ', help_link('pending_changes'),
				'</p>';
		}
	} elseif ($controller->record->isNew()) {
		if (WT_USER_CAN_ACCEPT) {
			echo
				'<p class="ui-state-highlight">',
				/* I18N: %1$s is “accept”, %2$s is “reject”.  These are links. */ WT_I18N::translate(
					'This record has been edited.  You should review the changes and then %1$s or %2$s them.',
					'<a href="#" onclick="accept_changes(\''.$controller->record->getXref().'\');">' . WT_I18N::translate_c('You should review the changes and then accept or reject them.', 'accept') . '</a>',
					'<a href="#" onclick="reject_changes(\''.$controller->record->getXref().'\');">' . WT_I18N::translate_c('You should review the changes and then accept or reject them.', 'reject') . '</a>'
				),
				' ', help_link('pending_changes'),
				'</p>';
		} elseif (WT_USER_CAN_EDIT) {
			echo
				'<p class="ui-state-highlight">',
				WT_I18N::translate('This record has been edited.  The changes need to be reviewed by a moderator.'),
				' ', help_link('pending_changes'),
				'</p>';
		}
	}
} else {
	header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found');
	$controller->pageHeader();
	echo '<p class="ui-state-error">', WT_I18N::translate('This record does not exist or you do not have permission to view it.'), '</p>';
	exit;
}

$controller
	->addInlineJavascript('function show_gedcom_record() {window.open("gedrecord.php?pid=' . $controller->record->getXref() . '", "_blank", edit_window_specs);}')
	->addInlineJavascript('jQuery("#record-tabs").tabs();')
	->addInlineJavascript('jQuery("#record-tabs").css("visibility", "visible");');

?>

<div id="record-details">
	<h2><?php echo $controller->record->getFullName(); ?></h2>
	<div id="record-tabs">
		<ul>
			<li><a href="#record-edit"><span><?php echo WT_I18N::translate('Details'); ?></span></a></li>
		</ul>
		<div id="record-edit">
			<table class="facts_table">
				<?php
					foreach ($controller->record->getFacts() as $fact) {
						print_fact($fact, $controller->record);
					}
				?>

			</table>
		</div>
	</div>
</div>
