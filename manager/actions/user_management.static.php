<?php
if(IN_MANAGER_MODE != "true") {
	die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
}
if(!$modx->hasPermission('edit_user')) {
	$modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

// initialize page view state - the $_PAGE object
$modx->manager->initPageViewState();

// get and save search string
if($_REQUEST['op'] == 'reset') {
	$query = '';
	$_PAGE['vs']['search'] = '';
} else {
	$query = isset($_REQUEST['search']) ? $_REQUEST['search'] : $_PAGE['vs']['search'];
	$sqlQuery = $modx->db->escape($query);
	$_PAGE['vs']['search'] = $query;
}

// get & save listmode
$listmode = isset($_REQUEST['listmode']) ? $_REQUEST['listmode'] : $_PAGE['vs']['lm'];
$_PAGE['vs']['lm'] = $listmode;


// context menu
include_once MODX_MANAGER_PATH . "includes/controls/contextmenu.php";
$cm = new ContextMenu("cntxm", 150);
$cm->addItem($_lang["edit"], "js:menuAction(1)", $_style["actions_edit"], (!$modx->hasPermission('edit_user') ? 1 : 0));
$cm->addItem($_lang["delete"], "js:menuAction(2)", $_style["actions_delete"], (!$modx->hasPermission('delete_user') ? 1 : 0));
echo $cm->render();

?>
<script language="JavaScript" type="text/javascript">
	function searchResource() {
		document.resource.op.value = "srch";
		document.resource.submit();
	};

	function resetSearch() {
		document.resource.search.value = '';
		document.resource.op.value = "reset";
		document.resource.submit();
	};

	function changeListMode() {
		var m = parseInt(document.resource.listmode.value) ? 1 : 0;
		if(m) document.resource.listmode.value = 0;
		else document.resource.listmode.value = 1;
		document.resource.submit();
	};

	var selectedItem;
	var contextm = <?= $cm->getClientScriptObject() ?>;

	function showContentMenu(id, e) {
		selectedItem = id;
		contextm.style.left = (e.pageX || (e.clientX + (document.documentElement.scrollLeft || document.body.scrollLeft))) + "px";
		contextm.style.top = (e.pageY || (e.clientY + (document.documentElement.scrollTop || document.body.scrollTop))) + "px";
		contextm.style.visibility = "visible";
		e.cancelBubble = true;
		return false;
	};

	function menuAction(a) {
		var id = selectedItem;
		switch(a) {
			case 1:		// edit
				window.location.href = 'index.php?a=12&id=' + id;
				break;
			case 2:		// delete
				if(confirm("<?= $_lang['confirm_delete_user'] ?>") === true) {
					window.location.href = 'index.php?a=33&id=' + id;
				}
				break;
		}
	}

	document.addEvent('click', function() {
		contextm.style.visibility = "hidden";
	});

	document.addEventListener('DOMContentLoaded', function() {
		var h1help = document.querySelector('h1 > .help');
		h1help.onclick = function() {
			document.querySelector('.element-edit-message').classList.toggle('show')
		}
	});

</script>
<form name="resource" method="post">
	<input type="hidden" name="id" value="<?= $id ?>" />
	<input type="hidden" name="listmode" value="<?= $listmode ?>" />
	<input type="hidden" name="op" value="" />

	<h1>
		<i class="fa fa fa-user"></i><?= $_lang['user_management_title'] ?><i class="fa fa-question-circle help"></i>
	</h1>

	<div class="container element-edit-message">
		<div class="alert alert-info"><?= $_lang['user_management_msg'] ?></div>
	</div>

	<div class="tab-page">
		<div class="container container-body">
			<div class="searchbar form-group">
				<div class="input-group">
					<div class="input-group-btn">
						<a class="btn btn-success btn-sm" href="index.php?a=11"><i class="<?= $_style["actions_new"] ?> hide4desktop"></i> <span><?= $_lang['new_user'] ?></span></a>
					</div>
					<input class="form-control form-control-sm float-xs-right" name="search" type="text" size="50" value="<?= $query ?>" placeholder="<?= $_lang["search"] ?>" />
					<div class="input-group-btn">
						<a class="btn btn-secondary btn-sm" href="javascript:;" title="<?= $_lang["search"] ?>" onclick="searchResource();return false;"><?= $_lang['go'] ?></a>
						<a class="btn btn-secondary btn-sm" href="javascript:;" title="<?= $_lang["reset"] ?>" onclick="resetSearch();return false;"><i class="fa fa-refresh"></i></a>
						<a class="btn btn-secondary btn-sm" href="javascript:;" title="<?= $_lang["list_mode"] ?>" onclick="changeListMode();return false;"><i class="fa fa-table"></i></a>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="table-responsive">
					<?php
					$where = "";
					if(!$modx->hasPermission('save_role')) {
						$where .= (empty($where) ? "" : " AND ") . "mua.role != 1";
					}
					if(!empty($sqlQuery)) {
						$where .= (empty($where) ? "" : " AND ") . "((mu.username LIKE '{$sqlQuery}%') OR (mua.fullname LIKE '%{$sqlQuery}%') OR (mua.email LIKE '{$sqlQuery}%'))";
					}
					$ds = $modx->db->select("mu.id, mu.username, rname.name AS role, mua.fullname, mua.email, ELT(mua.gender, '{$_lang['user_male']}', '{$_lang['user_female']}', '{$_lang['user_other']}') AS gender, IF(mua.blocked,'{$_lang['yes']}','-') as blocked, mua.thislogin", $modx->getFullTableName('manager_users') . " AS mu 
			INNER JOIN " . $modx->getFullTableName('user_attributes') . " AS mua ON mua.internalKey=mu.id 
			LEFT JOIN " . $modx->getFullTableName('user_roles') . " AS rname ON mua.role=rname.id", $where, 'mua.blocked ASC, mua.thislogin DESC');
					include_once MODX_MANAGER_PATH . "includes/controls/datagrid.class.php";
					$grd = new DataGrid('', $ds, $modx->config['number_of_results']); // set page size to 0 t show all items
					$grd->noRecordMsg = $_lang["no_records_found"];
					$grd->cssClass = "table data";
					$grd->columnHeaderClass = "tableHeader";
					$grd->itemClass = "tableItem";
					$grd->altItemClass = "tableAltItem";
					$grd->fields = "id,username,fullname,role,email,gender,blocked,thislogin";
					$grd->columns = implode(',', array(
						$_lang["icon"],
						$_lang["name"],
						$_lang["user_full_name"],
						$_lang['role'],
						$_lang["email"],
						$_lang["user_gender"],
						$_lang["user_block"],
						$_lang["login_button"]
					));
					$grd->colWidths = "1%,,,,,,1%,1%";
					$grd->colAligns = "center,,,,,center,center,right' nowrap='nowrap";
					$grd->colTypes = implode('||', array(
						'template:<a class="gridRowIcon" href="javascript:;" onclick="return showContentMenu([+id+],event);" title="' . $_lang['click_to_context'] . '"><i class="' . $_style['icons_user'] . '"></i></a>',
						'template:<a href="index.php?a=12&id=[+id+]" title="' . $_lang['click_to_edit_title'] . '">[+value+]</a>',
						'template:[+fullname+]',
						'template:[+role+]',
						'template:[+email+]',
						'template:[+gender+]',
						'template:[+blocked+]',
						'date: ' . $modx->toDateFormat('[+thislogin+]', 'formatOnly') . ' %H:%M'
					));
					if($listmode == '1') {
						$grd->pageSize = 0;
					}
					if($_REQUEST['op'] == 'reset') {
						$grd->pageNumber = 1;
					}
					// render grid
					echo $grd->render();
					?>
				</div>
			</div>
		</div>
	</div>
</form>
