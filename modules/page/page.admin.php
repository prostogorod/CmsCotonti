<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=admin
[END_COT_EXT]
==================== */

/**
 * Pages manager & Queue of pages
 *
 * @package Cotonti
 * @version 0.7.0
 * @author Neocrome, Cotonti Team
 * @copyright Copyright (c) Cotonti Team 2008-2011
 * @license BSD
 */

(defined('COT_CODE') && defined('COT_ADMIN')) or die('Wrong URL.');

list($usr['auth_read'], $usr['auth_write'], $usr['isadmin']) = cot_auth('page', 'any');
cot_block($usr['isadmin']);

$t = new XTemplate(cot_tplfile('page.admin', 'module'));

require_once cot_incfile('page', 'module');
require_once cot_incfile('users', 'module');

$adminpath[] = array(cot_url('admin', 'm=page'), $L['Pages']);
$adminhelp = $L['adm_help_page'];

$id = cot_import('id', 'G', 'INT');

list($pg, $d) = cot_import_pagenav('d', $cfg['maxrowsperpage']);

$sorttype = cot_import('sorttype', 'R', 'ALP');
$sorttype = empty($sorttype) ? 'id' : $sorttype;
$sort_type = array(
	'id' => $L['Id'],
	'type' => $L['Type'],
	'key' => $L['Key'],
	'title' => $L['Title'],
	'desc' => $L['Description'],
	'text' => $L['Body'],
	'author' => $L['Author'],
	'ownerid' => $L['Owner'],
	'date' => $L['Date'],
	'begin' => $L['Begin'],
	'expire' => $L['Expire'],
	'rating' => $L['Rating'],
	'count' => $L['Hits'],
	'comcount' => $L['Comments'],//TODO: if comments plug not instaled this row generated error
	'file' => $L['adm_fileyesno'],
	'url' => $L['adm_fileurl'],
	'size' => $L['adm_filesize'],
	'filecount' => $L['adm_filecount']
);
$sqlsorttype = 'page_'.$sorttype;

$sortway = cot_import('sortway', 'R', 'ALP');
$sortway = empty($sortway) ? 'desc' : $sortway;
$sort_way = array(
	'asc' => $L['Ascending'],
	'desc' => $L['Descending']
);
$sqlsortway = $sortway;

$filter = cot_import('filter', 'R', 'ALP');
$filter = empty($filter) ? 'valqueue' : $filter;
$filter_type = array(
	'all' => $L['All'],
	'valqueue' => $L['adm_valqueue'],
	'validated' => $L['adm_validated']
);
if ($filter == 'all')
{
	$sqlwhere = "1 ";
}
elseif ($filter == 'valqueue')
{
	$sqlwhere = "page_state=1 ";
}
elseif ($filter == 'validated')
{
	$sqlwhere = "page_state<>1 ";
}

/* === Hook  === */
foreach (cot_getextplugins('page.admin.first') as $pl)
{
	include $pl;
}
/* ===== */

if ($a == 'validate')
{
	cot_check_xg();

	/* === Hook  === */
	foreach (cot_getextplugins('page.admin.validate') as $pl)
	{
		include $pl;
	}
	/* ===== */

	$sql_page = $db->query("SELECT page_cat FROM $db_pages WHERE page_id=$id");
	if ($row = $sql_page->fetch())
	{
		$usr['isadmin_local'] = cot_auth('page', $row['page_cat'], 'A');
		cot_block($usr['isadmin_local']);

		$sql_page = $db->update($db_pages, array('page_state' => 0), "page_id=$id");
		$sql_page = $db->query("UPDATE $db_structure SET structure_count=structure_count+1 WHERE structure_code=".$db->quote($row['page_cat']));

		cot_log($L['Page'].' #'.$id.' - '.$L['adm_queue_validated'], 'adm');

		if ($cache)
		{
			if ($cfg['cache_page'])
			{
				$cache->page->clear('page/' . str_replace('.', '/', $cot_cat[$row['page_cat']]['path']));
			}
			if ($cfg['cache_index'])
			{
				$cache->page->clear('index');
			}
		}

		cot_message('#'.$id.' - '.$L['adm_queue_validated']);
	}
	else
	{
		cot_die();
	}
}
elseif ($a == 'unvalidate')
{
	cot_check_xg();

	/* === Hook  === */
	foreach (cot_getextplugins('page.admin.unvalidate') as $pl)
	{
		include $pl;
	}
	/* ===== */

	$sql_page = $db->query("SELECT page_cat FROM $db_pages WHERE page_id=$id");
	if ($row = $sql_page->fetch())
	{
		$usr['isadmin_local'] = cot_auth('page', $row['page_cat'], 'A');
		cot_block($usr['isadmin_local']);

		$sql_page = $db->update($db_pages, array('page_state' => 1), "page_id=$id");
		$sql_page = $db->query("UPDATE $db_structure SET structure_count=structure_count-1 WHERE structure_code=".$db->quote($row['page_cat']));

		cot_log($L['Page'].' #'.$id.' - '.$L['adm_queue_unvalidated'], 'adm');

		if ($cache)
		{
			if ($cfg['cache_page'])
			{
				$cache->page->clear('page/' . str_replace('.', '/', $cot_cat[$row['page_cat']]['path']));
			}
			if ($cfg['cache_index'])
			{
				$cache->page->clear('index');
			}
		}

		cot_message('#'.$id.' - '.$L['adm_queue_unvalidated']);
	}
	else
	{
		cot_die();
	}
}
elseif ($a == 'delete')
{
	cot_check_xg();

	/* === Hook  === */
	foreach (cot_getextplugins('page.admin.delete') as $pl)
	{
		include $pl;
	}
	/* ===== */

	$sql_page = $db->query("SELECT * FROM $db_pages WHERE page_id=$id LIMIT 1");
	if ($row = $sql_page->fetch())
	{
		if ($row['page_state'] != 1)
		{
			$sql_page = $db->query("UPDATE $db_structure SET structure_count=structure_count-1 WHERE structure_code=".$db->quote($row['page_cat']));
		}

		$sql_page = $db->delete($db_pages, "page_id=$id");

		cot_log($L['Page'].' #'.$id.' - '.$L['Deleted'], 'adm');

		/* === Hook === */
		foreach (cot_getextplugins('page.admin.delete.done') as $pl)
		{
			include $pl;
		}
		/* ===== */

		if ($cache)
		{
			if ($cfg['cache_page'])
			{
				$cache->page->clear('page/' . str_replace('.', '/', $cot_cat[$row['page_cat']]['path']));
			}
			if ($cfg['cache_index'])
			{
				$cache->page->clear('index');
			}
		}

		cot_message('#'.$id.' - '.$L['adm_queue_deleted']);
	}
	else
	{
		cot_die();
	}
}
elseif ($a == 'update_cheked')
{
	$paction = cot_import('paction', 'P', 'TXT');

	if ($paction == $L['Validate'] && is_array($_POST['s']))
	{
		cot_check_xp();
		$s = cot_import('s', 'P', 'ARR');

		$perelik = '';
		$notfoundet = '';
		foreach ($s as $i => $k)
		{
			if ($s[$i] == '1' || $s[$i] == 'on')
			{
				/* === Hook  === */
				foreach (cot_getextplugins('page.admin.cheked_validate') as $pl)
				{
					include $pl;
				}
				/* ===== */

				$sql_page = $db->query("SELECT * FROM $db_pages WHERE page_id=".(int)$i);
				if ($row = $sql_page->fetch())
				{
					$id = $row['page_id'];
					$usr['isadmin_local'] = cot_auth('page', $row['page_cat'], 'A');
					cot_block($usr['isadmin_local']);

					$sql_page = $db->update($db_pages, array('page_state' => 0), "page_id=$id");
					$sql_page = $db->query("UPDATE $db_structure SET structure_count=structure_count+1 WHERE structure_code=".$db->quote($row['page_cat']));

					cot_log($L['Page'].' #'.$id.' - '.$L['adm_queue_validated'], 'adm');

					if ($cache && $cfg['cache_page'])
					{
						$cache->page->clear('page/' . str_replace('.', '/', $cot_cat[$row['page_cat']]['path']));
					}

					$perelik .= '#'.$id.', ';
				}
				else
				{
					$notfoundet .= '#'.$id.' - '.$L['Error'].'<br  />';
				}
			}
		}

		if ($cache && $cfg['cache_index'])
		{
			$cache->page->clear('index');
		}

		if (!empty($perelik))
		{
			cot_message($notfoundet.$perelik.' - '.$L['adm_queue_validated']);
		}
	}
	elseif ($paction == $L['Delete'] && is_array($_POST['s']))
	{
		cot_check_xp();
		$s = cot_import('s', 'P', 'ARR');

		$perelik = '';
		$notfoundet = '';
		foreach ($s as $i => $k)
		{
			if ($s[$i] == '1' || $s[$i] == 'on')
			{
				/* === Hook  === */
				foreach (cot_getextplugins('page.admin.cheked_delete') as $pl)
				{
					include $pl;
				}
				/* ===== */

				$sql_page = $db->query("SELECT * FROM $db_pages WHERE page_id=".(int)$i." LIMIT 1");
				if ($row = $sql_page->fetch())
				{
					$id = $row['page_id'];
					if ($row['page_state'] != 1)
					{
						$sql_page = $db->query("UPDATE $db_structure SET structure_count=structure_count-1 WHERE structure_code='".$db->quote($row['page_cat']));
					}

					$sql_page = $db->delete($db_pages, "page_id=$id");

					cot_log($L['Page'].' #'.$id.' - '.$L['Deleted'],'adm');

					if ($cache && $cfg['cache_page'])
					{
						$cache->page->clear('page/' . str_replace('.', '/', $cot_cat[$row['page_cat']]['path']));
					}

					/* === Hook === */
					foreach (cot_getextplugins('page.admin.delete.done') as $pl)
					{
						include $pl;
					}
					/* ===== */
					$perelik .= '#'.$id.', ';
				}
				else
				{
					$notfoundet .= '#'.$id.' - '.$L['Error'].'<br  />';
				}
			}
		}

		if ($cache && $cfg['cache_index'])
		{
			$cache->page->clear('index');
		}

		if (!empty($perelik))
		{
			cot_message($notfoundet.$perelik.' - '.$L['adm_queue_deleted']);
		}
	}
}

$totalitems = $db->query("SELECT COUNT(*) FROM $db_pages WHERE ".$sqlwhere)->fetchColumn();
$pagenav = cot_pagenav('admin', 'm=page&sorttype='.$sorttype.'&sortway='.$sortway.'&filter='.$filter, $d, $totalitems, $cfg['maxrowsperpage'], 'd', '', $cfg['jquery'] && $cfg['turnajax']);

$sql_page = $db->query("SELECT p.*, u.user_name
	FROM $db_pages as p
	LEFT JOIN $db_users AS u ON u.user_id=p.page_ownerid
	WHERE $sqlwhere
		ORDER BY $sqlsorttype $sqlsortway
		LIMIT $d, ".$cfg['maxrowsperpage']);

$ii = 0;
/* === Hook - Part1 : Set === */
$extp = cot_getextplugins('page.admin.loop');
/* ===== */
while ($row = $sql_page->fetch())
{
	if ($row['page_type'] == 0)
	{
		$page_type = 'BBcode';
	}
	elseif ($row['page_type'] == 1)
	{
		$page_type = 'HTML';
	}
	elseif ($row['page_type'] == 2)
	{
		$page_type = 'PHP';
	}
	$page_urlp = empty($row['page_alias']) ? 'id='.$row['page_id'] : 'al='.$row['page_alias'];
	$row['page_begin_noformat'] = $row['page_begin'];
	$row['page_pageurl'] = cot_url('page', $page_urlp);
	$catpath = cot_structure_buildpath('page', $row['page_cat']);
	$row['page_fulltitle'] = $catpath.' '.$cfg['separator'].' <a href="'.$row['page_pageurl'].'">'.htmlspecialchars($row['page_title']).'</a>';
	$sql_page_subcount = $db->query("SELECT SUM(structure_count) FROM $db_structure WHERE structure_path LIKE '".$db->prep($cot_cat[$row["page_cat"]]['rpath'])."%' ");
	$sub_count = $sql_page_subcount->fetchColumn();
	$row['page_file'] = intval($row['page_file']);
	$t->assign(cot_generate_pagetags($row, 'ADMIN_PAGE_', 200));
	$t->assign(array(
		'ADMIN_PAGE_ID_URL' => cot_url('page', 'id='.$row['page_id']),
		'ADMIN_PAGE_TYPE' => $page_type,
		'ADMIN_PAGE_OWNER' => cot_build_user($row['page_ownerid'], htmlspecialchars($row['user_name'])),
		'ADMIN_PAGE_FILE_BOOL' => $row['page_file'],
		'ADMIN_PAGE_URL_FOR_VALIDATED' => cot_url('admin', 'm=page&a=validate&id='.$row['page_id'].'&d='.$d.'&'.cot_xg()),
		'ADMIN_PAGE_URL_FOR_DELETED' => cot_url('admin', 'm=page&a=delete&id='.$row['page_id'].'&d='.$d.'&'.cot_xg()),
		'ADMIN_PAGE_URL_FOR_EDIT' => cot_url('page', 'm=edit&id='.$row['page_id']),
		'ADMIN_PAGE_ODDEVEN' => cot_build_oddeven($ii),
		'ADMIN_PAGE_CAT_COUNT' => $sub_count
	));
	$t->assign(cot_generate_usertags($row['page_ownerid'], 'ADMIN_PAGE_OWNER_'), htmlspecialchars($row['user_name']));

	/* === Hook - Part2 : Include === */
	foreach ($extp as $pl)
	{
		include $pl;
	}
	/* ===== */

	$t->parse('MAIN.PAGE_ROW');
	$ii++;
}

$is_row_empty = ($sql_page->rowCount() == 0) ? true : false ;

$totaldbpages = $db->countRows($db_pages);
$sql_page_queued = $db->query("SELECT COUNT(*) FROM $db_pages WHERE page_state=1");
$sys['pagesqueued'] = $sql_page_queued->fetchColumn();

$t->assign(array(
	'ADMIN_PAGE_URL_CONFIG' => cot_url('admin', 'm=config&n=edit&o=module&p=page'),
	'ADMIN_PAGE_URL_ADD' => cot_url('page', 'm=add'),
	'ADMIN_PAGE_URL_EXTRAFIELDS' => cot_url('admin', 'm=extrafields&n=page'),
	'ADMIN_PAGE_URL_STRUCTURE' => cot_url('admin', 'm=structure&n=page'),
	'ADMIN_PAGE_FORM_URL' => cot_url('admin', 'm=page&a=update_cheked&sorttype='.$sorttype.'&sortway='.$sortway.'&filter='.$filter.'&d='.$d),
	'ADMIN_PAGE_ORDER' => cot_selectbox($sorttype, 'sorttype', array_keys($sort_type), array_values($sort_type), false),
	'ADMIN_PAGE_WAY' => cot_selectbox($sortway, 'sortway', array_keys($sort_way), array_values($sort_way), false),
	'ADMIN_PAGE_FILTER' => cot_selectbox($filter, 'filter', array_keys($filter_type), array_values($filter_type), false),
	'ADMIN_PAGE_TOTALDBPAGES' => $totaldbpages,
	'ADMIN_PAGE_PAGINATION_PREV' => $pagenav['prev'],
	'ADMIN_PAGE_PAGNAV' => $pagenav['main'],
	'ADMIN_PAGE_PAGINATION_NEXT' => $pagenav['next'],
	'ADMIN_PAGE_TOTALITEMS' => $totalitems,
	'ADMIN_PAGE_ON_PAGE' => $ii
));

cot_display_messages($t);

/* === Hook  === */
foreach (cot_getextplugins('page.admin.tags') as $pl)
{
	include $pl;
}
/* ===== */

$t->parse('MAIN');
$adminmain = $t->text('MAIN');

?>