<?php
// UI for online updating of the GEDCOM configuration.
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

define('WT_SCRIPT_NAME', 'admin_trees_manage.php');
require './includes/session.php';
require WT_ROOT.'includes/functions/functions_edit.php';

$controller=new WT_Controller_Page();
$controller
	->restrictAccess(\WT\Auth::isAdmin())
	->setPageTitle(WT_I18N::translate('Family trees'));

// Don’t allow the user to cancel the request.  We do not want to be left
// with an incomplete transaction.
ignore_user_abort(true);

// $path is the full path to the (possibly temporary) file.
// $filename is the actual filename (no folder).
function import_gedcom_file($gedcom_id, $path, $filename) {
	// Read the file in blocks of roughly 64K.  Ensure that each block
	// contains complete gedcom records.  This will ensure we don’t split
	// multi-byte characters, as well as simplifying the code to import
	// each block.

	$file_data='';
	$fp=fopen($path, 'rb');

	WT_DB::exec("START TRANSACTION");
	WT_DB::prepare("DELETE FROM `##gedcom_chunk` WHERE gedcom_id=?")->execute(array($gedcom_id));

	while (!feof($fp)) {
		$file_data.=fread($fp, 65536);
		// There is no strrpos() function that searches for substrings :-(
		for ($pos=strlen($file_data)-1; $pos>0; --$pos) {
			if ($file_data[$pos]=='0' && ($file_data[$pos-1]=="\n" || $file_data[$pos-1]=="\r")) {
				// We’ve found the last record boundary in this chunk of data
				break;
			}
		}
		if ($pos) {
			WT_DB::prepare(
				"INSERT INTO `##gedcom_chunk` (gedcom_id, chunk_data) VALUES (?, ?)"
			)->execute(array($gedcom_id, substr($file_data, 0, $pos)));
			$file_data=substr($file_data, $pos);
		}
	}
	WT_DB::prepare(
		"INSERT INTO `##gedcom_chunk` (gedcom_id, chunk_data) VALUES (?, ?)"
	)->execute(array($gedcom_id, $file_data));

	set_gedcom_setting($gedcom_id, 'gedcom_filename', $filename);
	WT_DB::exec("COMMIT");
	fclose($fp);
}

// Process POST actions
switch (WT_Filter::post('action')) {
case 'delete':
	$gedcom_id = WT_Filter::postInteger('gedcom_id');
	if (WT_Filter::checkCsrf() && $gedcom_id) {
		WT_Tree::delete($gedcom_id);
	}
	header('Location: ' . WT_SERVER_NAME . WT_SCRIPT_PATH . WT_SCRIPT_NAME);
	exit;
case 'setdefault':
	if (WT_Filter::checkCsrf()) {
		WT_Site::preference('DEFAULT_GEDCOM', WT_Filter::post('default_ged'));
	}
	header('Location: ' . WT_SERVER_NAME . WT_SCRIPT_PATH . WT_SCRIPT_NAME);
	exit;
case 'new_tree':
	$tree_name = basename(WT_Filter::post('tree_name'));
	if (WT_Filter::checkCsrf() && $tree_name) {
		WT_Tree::create($tree_name);
	}
	header('Location: ' . WT_SERVER_NAME . WT_SCRIPT_PATH . WT_SCRIPT_NAME . '?ged=' . $tree_name);
	exit;
case 'replace_upload':
	$gedcom_id = WT_Filter::postInteger('gedcom_id');
	// Make sure the gedcom still exists
	if (WT_Filter::checkCsrf() && get_gedcom_from_id($gedcom_id)) {
		foreach ($_FILES as $FILE) {
			if ($FILE['error'] == 0 && is_readable($FILE['tmp_name'])) {
				import_gedcom_file($gedcom_id, $FILE['tmp_name'], $FILE['name']);
			}
		}
	}
	header('Location: '.WT_SERVER_NAME.WT_SCRIPT_PATH.WT_SCRIPT_NAME.'?keep_media'.$gedcom_id.'='.WT_Filter::postBool('keep_media'.$gedcom_id));
	exit;
case 'replace_import':
	$gedcom_id = WT_Filter::postInteger('gedcom_id');
	// Make sure the gedcom still exists
	if (WT_Filter::checkCsrf() && get_gedcom_from_id($gedcom_id)) {
		$tree_name = basename(WT_Filter::post('tree_name'));
		import_gedcom_file($gedcom_id, WT_DATA_DIR.$tree_name, $tree_name);
	}
	header('Location: '.WT_SERVER_NAME.WT_SCRIPT_PATH.WT_SCRIPT_NAME.'?keep_media'.$gedcom_id.'='.WT_Filter::postBool('keep_media'.$gedcom_id));
	exit;
}

$controller->pageHeader();

echo '<h2>', WT_I18N::translate('Manage family trees'), '</h2>';

// Process GET actions
switch (WT_Filter::get('action')) {
case 'uploadform':
case 'importform':
	$gedcom_id=WT_Filter::getInteger('gedcom_id');
	$gedcom_name=get_gedcom_from_id($gedcom_id);
	// Check it exists
	if (!$gedcom_name) {
		break;
	}
	echo '<p>', WT_I18N::translate('This will delete all the genealogical data from <b>%s</b> and replace it with data from another GEDCOM.', $gedcom_name), '</p>';
	// the javascript in the next line strips any path associated with the file before comparing it to the current GEDCOM name (both Chrome and IE8 include c:\fakepath\ in the filename).
	$previous_gedcom_filename=get_gedcom_setting($gedcom_id, 'gedcom_filename');
	echo '<form name="replaceform" method="post" enctype="multipart/form-data" action="', WT_SCRIPT_NAME, '" onsubmit="var newfile = document.replaceform.tree_name.value; newfile = newfile.substr(newfile.lastIndexOf(\'\\\\\')+1); if (newfile!=\'', WT_Filter::escapeHtml($previous_gedcom_filename), '\' && \'\' != \'', WT_Filter::escapeHtml($previous_gedcom_filename), '\') return confirm(\'', WT_Filter::escapeHtml(WT_I18N::translate('You have selected a GEDCOM with a different name.  Is this correct?')), '\'); else return true;">';
	echo '<input type="hidden" name="gedcom_id" value="', $gedcom_id, '">';
	echo WT_Filter::getCsrf();
	if (WT_Filter::get('action')=='uploadform') {
		echo '<input type="hidden" name="action" value="replace_upload">';
		echo '<input type="file" name="tree_name">';
	} else {
		echo '<input type="hidden" name="action" value="replace_import">';
		$d=opendir(WT_DATA_DIR);
		$files=array();
		while (($f=readdir($d))!==false) {
			if (!is_dir(WT_DATA_DIR.$f) && is_readable(WT_DATA_DIR.$f)) {
				$fp=fopen(WT_DATA_DIR.$f, 'rb');
				$header=fread($fp, 64);
				fclose($fp);
				if (preg_match('/^('.WT_UTF8_BOM.')?0 *HEAD/', $header)) {
					$files[]=$f;
				}
			}
		}
		if ($files) {
			sort($files);
			echo WT_DATA_DIR, '<select name="tree_name">';
			foreach ($files as $file) {
				echo '<option value="', WT_Filter::escapeHtml($file), '"';
				if ($file==$previous_gedcom_filename) {
					echo ' selected="selected"';
				}
				echo'>', WT_Filter::escapeHtml($file), '</option>';
			}
			echo '</select>';
		} else {
			echo '<p>', WT_I18N::translate('No GEDCOM files found.  You need to copy files to the <b>%s</b> directory on your server.', WT_DATA_DIR);
			echo '</form>';
			exit;
		}
	}
	echo '<br><br><input type="checkbox" name="keep_media', $gedcom_id, '" value="1">';
	echo WT_I18N::translate('If you have created media objects in webtrees, and have edited your gedcom off-line using a program that deletes media objects, then check this box to merge the current media objects with the new GEDCOM.');
	echo '<br><br><input type="submit" value="', WT_I18N::translate('continue'), '">';
	echo '</form>';
	exit;
}

?>
<div class="panel-group" id="accordion">
	<?php foreach (WT_Tree::GetAll() as $tree): ?>
	<?php if (\WT\Auth::isManager($tree)): ?>
	<div class="panel panel-default">
		<div class="panel-heading">
			<h3 class="panel-title">
				<a data-toggle="collapse" data-parent="#accordion" href="#tree-<?php echo $tree->tree_id; ?>">
					<?php echo WT_Filter::escapeHtml($tree->tree_name); ?> — <?php echo WT_Filter::escapeHtml($tree->tree_title); ?>
				</a>
			</h3>
		</div>
		<div id="tree-<?php echo $tree->tree_id; ?>" class="panel-collapse collapse<?php echo $tree->tree_id === WT_GED_ID ? ' in' : ''; ?>">
			<div class="panel-body">
<?php
// The third row shows an optional progress bar and a list of maintenance options
$importing=WT_DB::prepare(
	"SELECT 1 FROM `##gedcom_chunk` WHERE gedcom_id=? AND imported=0 LIMIT 1"
)->execute(array($tree->tree_id))->fetchOne();
if ($importing) {
	$in_progress=WT_DB::prepare(
		"SELECT 1 FROM `##gedcom_chunk` WHERE gedcom_id=? AND imported=1 LIMIT 1"
	)->execute(array($tree->tree_id))->fetchOne();
		?>
	<div id="import<?php echo $tree->tree_id; ?>" class="col-xs-12">
		<div class="progress">
			<?php if ($in_progress): ?>
			<?php echo WT_I18N::translate('Calculating…'); ?>
			<?php else: ?>
			<?php echo WT_I18N::translate('Deleting old genealogy data…'); ?>
			<?php endif; ?>
		</div>
	</div>
	<?php
	$controller->addInlineJavascript(
	'jQuery("#import'.$tree->tree_id.'").load("import.php?gedcom_id='.$tree->tree_id.'&keep_media'.$tree->tree_id.'='.WT_Filter::get('keep_media'.$tree->tree_id).'");'
	);
}
?>

			<div class="row<?php echo $importing ? ' hidden' : ''; ?>" id="actions<?php echo $tree->tree_id; ?>">
				<div class="col-sm-6 col-md-3">
					<h4>
						<?php echo WT_I18N::translate('Family tree'); ?>
					</h4>
					<ul class="fa-ul">
						<!-- PREFERENCES -->
						<li>
							<i class="fa fa-li fa-cogs"></i>
							<a href="admin_trees_config.php?ged=<?php echo WT_Filter::escapeHtml($tree->tree_name); ?>">
								<?php echo WT_I18N::translate('Preferences'); ?>
							</a>
						</li>
						<!-- PRIVACY -->
						<li>
							<i class="fa fa-li fa-lock"></i>
							<a href="admin_trees_privacy.php?ged=<?php echo WT_Filter::escapeHtml($tree->tree_name); ?>">
								<?php echo WT_I18N::translate('Privacy'); ?>
							</a>
						</li>
						<!-- VIEW -->
						<li>
							<i class="fa fa-li fa-folder-open-o"></i>
							<a href="index.php?ged=<?php echo WT_Filter::escapeHtml($tree->tree_name); ?>">
								<?php echo WT_I18N::translate('View'); ?>
							</a>
						</li>
						<!-- SET AS DEFAULT -->
						<?php if (count(WT_Tree::getAll()) > 1): ?>
						<li>
							<i class="fa fa-li fa-star"></i>
							<?php if ($tree->tree_name == WT_Site::preference('DEFAULT_GEDCOM')): ?>
							<?php echo WT_I18N::translate('Default family tree'); ?>
							<?php else: ?>
							<a href="#" onclick="document.defaultform<?php echo $tree->tree_id; ?>.submit();">
								<?php echo WT_I18N::translate('Set as default'); ?>
							</a>
							<form name="defaultform<?php echo $tree->tree_id; ?>" method="POST" action="admin_trees_manage.php">
								<input type="hidden" name="action" value="setdefault">
								<input type="hidden" name="default_ged" value="<?php echo WT_Filter::escapeHtml($tree->tree_name); ?>">
								<?php echo WT_Filter::getCsrf(); ?>
							</form>
							<?php endif; ?>
						</li>
						<?php endif; ?>
						<!-- MERGE -->
						<?php if (count(WT_Tree::getAll()) > 1): ?>
						<li>
							<i class="fa fa-li fa-code-fork"></i>
							<a href="admin_trees_merge.php?ged=<?php echo WT_Filter::escapeHtml($tree->tree_name); ?>">
								<?php echo WT_I18N::translate('Merge'); ?>
							</a>
						</li>
						<?php endif; ?>
						<!-- DELETE -->
						<li>
							<i class="fa fa-li fa-trash-o"></i>
							<a href="#" onclick="if (confirm('<?php echo WT_Filter::escapeJs(WT_I18N::translate('Are you sure you want to delete “%s”?', $tree->tree_name)); ?>')) document.delete_form<?php echo $tree->tree_id; ?>.submit(); return false;">
								<?php echo WT_I18N::translate('Delete'); ?>
							</a>
							<form name="delete_form<?php echo $tree->tree_id; ?>" method="POST" action="admin_trees_manage.php">
								<input type="hidden" name="action" value="delete">
								<input type="hidden" name="gedcom_id" value="<?php echo $tree->tree_id; ?>">
								<?php echo WT_Filter::getCsrf(); ?>
							</form>
						</li>
					<ul>
				</div>
				<div class="col-sm-6 col-md-3">
					<h4>
						<?php echo /* I18N: Individuals, sources, dates, places, etc. */ WT_I18N::translate('Genealogy data'); ?>
					</h4>
					<ul class="fa-ul">
						<!-- MERGE -->
						<li>
							<i class="fa fa-li fa-code-fork"></i>
							<a href="admin_site_merge.php?ged=<?php echo WT_Filter::escapeHtml($tree->tree_name); ?>">
								<?php echo WT_I18N::translate('Merge records'); ?>
							</a>
						</li>
						<!-- UPDATE PLACE NAMES -->
						<li>
							<i class="fa fa-li fa-map-marker"></i>
							<a href="admin_trees_places.php?ged=<?php echo WT_Filter::escapeHtml($tree->tree_name); ?>">
								<?php echo WT_I18N::translate('Update place names'); ?>
							</a>
						</li>
						<!-- CHECK FOR ERRORS -->
						<li>
							<i class="fa fa-li fa-check"></i>
							<a href="admin_trees_check.php?ged=<?php echo WT_Filter::escapeHtml($tree->tree_name); ?>">
								<?php echo WT_I18N::translate('Check for errors'); ?>
							</a>
						</li>
						<!-- RENUMBER -->
						<li>
							<i class="fa fa-li fa-sort-numeric-asc"></i>
							<a href="admin_trees_renumber.php?ged=<?php echo WT_Filter::escapeHtml($tree->tree_name); ?>">
								<?php echo WT_I18N::translate('Renumber'); ?>
							</a>
						</li>
						<!-- CHANGES -->
						<li>
							<i class="fa fa-li fa-th-list"></i>
							<a href="admin_site_change.php?gedc=<?php echo WT_Filter::escapeHtml($tree->tree_name); ?>">
								<?php echo WT_I18N::translate('Changes log'); ?>
							</a>
						</li>
					</ul>
				</div>
				<div class="clearfix visible-sm-block"></div>
				<div class="col-sm-6 col-md-3">
					<h4>
						<?php echo WT_I18N::translate('Add unlinked records'); ?>
					</h4>
					<ul class="fa-ul">
						<!-- UNLINKED INDIVIDUAL -->
						<li>
							<i class="fa fa-li fa-user"></i>
							<a href="#" onclick="add_unlinked_indi(); return false;">
								<?php echo WT_I18N::translate('Individual'); ?>
							</a>
						</li>
						<!-- UNLINKED SOURCE -->
						<li>
							<i class="fa fa-li fa-book"></i>
							<a href="#" onclick="addnewsource(''); return false;">
								<?php echo WT_I18N::translate('Source'); ?>
							</a>
						</li>
						<!-- UNLINKED REPOSITORY -->
						<li>
							<i class="fa fa-li fa-university"></i>
							<a href="#" onclick="addnewrepository(''); return false;">
								<?php echo WT_I18N::translate('Repository'); ?>
							</a>
						</li>
						<!-- UNLINKED MEDIA OBJECT -->
						<li>
							<i class="fa fa-li fa-photo"></i>
							<a href="#" onclick="window.open('addmedia.php?action=showmediaform', '_blank', edit_window_specs); return false;">
								<?php echo WT_I18N::translate('Media object'); ?>
							</a>
						</li>
						<!-- UNLINKED NOTE -->
						<li>
							<i class="fa fa-li fa-paragraph"></i>
							<a href="#" onclick="addnewnote(''); return false;">
								<?php echo WT_I18N::translate('Shared note'); ?>
							</a>
						</li>
					</ul>
				</div>
				<div class="col-sm-6 col-md-3">
					<h4>
						<?php echo WT_I18N::translate('GEDCOM file'); ?>
					</h4>
					<ul class="fa-ul">
						<!-- DOWNLOAD -->
						<li>
							<i class="fa fa-li fa-download"></i>
							<a href="admin_trees_download.php?ged=<?php echo WT_Filter::escapeHtml($tree->tree_name); ?>">
								<?php echo WT_I18N::translate('Download'); ?>
							</a>
							<?php echo help_link('download_gedcom'); ?>
						</li>
						<!-- UPLOAD -->
						<li>
							<i class="fa fa-li fa-upload"></i>
							<a href="admin_trees_manage.php?action=uploadform&amp;gedcom_id=<?php echo $tree->tree_id; ?>">
								<?php echo WT_I18N::translate('Upload'); ?>
							</a>
							<?php echo help_link('upload_gedcom'); ?>

						</li>
						<!-- EXPORT -->
						<li>
							<i class="fa fa-li fa-file-text"></i>
							<a href="#" onclick="return modalDialog('admin_trees_export.php?ged=<?php echo WT_Filter::escapeHtml($tree->tree_name); ?>', '<?php echo WT_I18N::translate('Export'); ?>');"
							>
								<?php echo WT_I18N::translate('Export'); ?>
							</a>
							<?php echo help_link('export_gedcom'); ?>
						</li>
						<!-- IMPORT -->
						<li>
							<i class="fa fa-li fa-file-text-o"></i>
							<a href="admin_trees_manage.php?action=importform&amp;gedcom_id=<?php echo $tree->tree_id; ?>">
								<?php echo WT_I18N::translate('Import'); ?>
							</a>
							<?php echo help_link('import_gedcom'); ?>
						</li>
				</div>
			</div></div>
		</div>
	</div>
	<?php endif; ?>
	<?php endforeach; ?>
</div>

<?php if (!WT_Tree::getAll()): ?>
	<p>
		<?php echo WT_I18N::translate('Welcome to webtrees.'); ?>
		<?php echo WT_I18N::translate('Before you can continue, you must create a family tree.'); ?>
	</p>
<?php endif; ?>

<hr>
<h2>
	<?php echo WT_I18N::translate('Create a new family tree'); ?>
</h2>

<p>
	<?php echo WT_I18N::translate('This option creates a new family tree.  The name you give it will be used to generate URLs and filenames, so you should choose something short, simple, and avoid punctuation.'); ?>
</p>
<p>
	<?php echo WT_I18N::translate('After creating the family tree, you will be able to upload or import data from a GEDCOM file.'); ?>
</p>

	<form role="form" class="form-horizontal" method="POST" action="admin_trees_manage.php">
	<?php echo WT_Filter::getCsrf(); ?>
	<input type="hidden" name="action" value="new_tree">
	<div class="form-group">
		<label for="tree_name" class="col-sm-2 control-label"><?php echo WT_I18N::translate('Family tree name'); ?></label>
		<div class="col-sm-10">
			<input type="text" class="form-control" name="tree_name" id="tree_name" placeholder="<?php echo WT_I18N::translate('Family tree name'); ?>">
		</div>
	</div>
	<div class="form-group">
		<label for="ged_title" class="col-sm-2 control-label"><?php echo WT_I18N::translate('Family tree title'); ?></label>
		<div class="col-sm-10">
			<input type="text" class="form-control" name="tree_title" id="ged_title" placeholder="<?php echo WT_I18N::translate('Family tree title'); ?>">
		</div>
	</div>
	<div class="form-group">
		<div class="col-sm-offset-2 col-sm-10">
			<button type="submit" class="btn btn-primary">
				<?php echo /* I18N: Button label */ WT_I18N::translate('create'); ?>
			</button>
		</div>
	</div>
</form>

	<!-- display link to PGV-WT transfer wizard on first visit to this page, before any GEDCOM is loaded -->
<?php if (count(WT_Tree::GetAll()) === 0 && count(\WT\User::all()) === 1): ?>
<hr>
<h2>
	<?php echo WT_I18N::translate('PhpGedView to webtrees transfer wizard'); ?>
</h2>
<div>
	<p>
		<?php echo WT_I18N::translate('The PGV to webtrees wizard is an automated process to assist administrators make the move from a PGV installation to a new webtrees one. It will transfer all PGV GEDCOM and other database information directly to your new webtrees database. The following requirements are necessary:'); ?>
	</p>
	<ul>
		<li>
			<?php echo WT_I18N::translate('webtrees’ database must be on the same server as PGV’s'); ?>
		</li>
		<li>
			<?php echo WT_I18N::translate('PGV must be version 4.2.3, or any SVN up to #6973'); ?>
		</li>
		<li>
			<?php echo WT_I18N::translate('All changes in PGV must be accepted'); ?>
		</li>
		<li>
			<?php echo WT_I18N::translate('You must export your latest GEDCOM data'); ?>
		</li>
		<li>
			<?php echo WT_I18N::translate('The current webtrees admin username must be the same as an existing PGV admin username'); ?>
		</li>
		<li>
			<?php echo WT_I18N::translate('All existing PGV users must have distinct email addresses'); ?>
		</li>
	</ul>
	<p>
		<?php echo WT_I18N::translate('<b>Important note:</b> The transfer wizard is not able to assist with moving media items. You will need to set up and move or copy your media configuration and objects separately after the transfer wizard is finished.'); ?>
	</p>
	<p>
		<a href="admin_pgv_to_wt.php">
			<?php echo WT_I18N::translate('Click here for PhpGedView to webtrees transfer wizard'); ?>
		</a>
	</p>
</div>
<?php endif; ?>





<?php
echo help_link('default_gedcom'), help_link('add_new_gedcom');