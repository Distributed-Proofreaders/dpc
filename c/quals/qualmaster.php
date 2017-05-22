<?
/**
 * Created by PhpStorm.
 * User: don
 * Date: 7/7/2015
 * Time: 11:22 AM
 */
ini_set("display_errors", true);
error_reporting(E_ALL);

$relPath="./../pinc/";
include_once $relPath.'dpinit.php';

$rows = $dpdb->SqlRows("
	SELECT p.id, phase, project_name, language, COUNT(1) pagecount
	FROM qual_projects p
	JOIN qual_pages qp ON p.id = qp.projectid
	WHERE p.state = 'T'
	GROUP BY p.id");

$tbl = new DpTable();
$tbl->AddColumn("<ID", "id");
$tbl->AddColumn("<Name", "project_name");
$tbl->AddColumn("<Phase", "phase");
$tbl->AddColumn("<Language", "language");
$tbl->AddColumn(">Pages", "pagecount");
$tbl->SetRows($rows);

$tbl->EchoTable();


