<?php
session_start();
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
\PhpOffice\PhpSpreadsheet\Cell\Cell::setValueBinder( new \PhpOffice\PhpSpreadsheet\Cell\AdvancedValueBinder() );

require_once "connect_db.php";

$out_res = [];
$filename = tempnam(sys_get_temp_dir(), 'xls');

$csv_output = FALSE;
$xls_output = FALSE;
$global_csv_columns = "";

$call = $_POST['call'] ?? NULL;
if (isset($_GET['action'])) {
	$call = $_GET['action'];
}
$mySQLQueryName = $call.'('.basename(__DIR__).')';

$data_fields = [
	'name'	=> '', 
	'platform'	=> '', 
	'service'	=> '', 
	'owner'	=> '', 
	'contact_info'	=> '', 
	'manager'	=> '', 
	'comments'	=> '',
];


$param_error_msg = [];
$param_error_count = 0;

$param_error_msg['call_name'] = @$call;
$param_error_msg['answer'] = [];

mb_internal_encoding("UTF-8");

$pdo = connectToBase();

//allows data to be changed 
$full_access = $_SESSION['full_access'] ?? 0;

if ($call == 'doSignIn' && isset($_POST['username']) && isset($_POST['password']))
{
	$query = "/*{$mySQLQueryName}*/"."SELECT id, username, password, full_access FROM users WHERE username=:username";
 	try
 	{
		$row = $pdo->prepare($query);
		$row->execute(['username' => trim($_POST['username'])]);
		$result = $row->fetch();
		if (isset($result['id']) && password_verify($_POST['password'], $result['password']))
		{
			$_SESSION['logged_user'] = $result['id'];
			$_SESSION['full_access'] = ($result['full_access'] == '1') ? 1 : 0;
			$param_error_msg['code'] = 1;
			$param_error_msg['answer']['full_access'] = $_SESSION['full_access'];
			$out_res = ['success' => $param_error_msg];
			$unauthorized = FALSE;
		}
	}
	catch (PDOException $e) 
	{
		setSQLError($e, 'SQL error. Call '.$_POST['call']);
	}
}
elseif ($call == 'doLogOut') 
{
	$_SESSION = array();
	$unauthorized = TRUE;
}
elseif (isset($_SESSION['logged_user']) && $_SESSION['logged_user'] && $pdo)
{
	$unauthorized = FALSE;
	foreach ($data_fields as $key => $value) {
		if(isset($_POST[$key])) {
			$data_fields[$key] = removeBadSymbols($_POST[$key]);
		}
	}
	if ($call == 'doGetDevicesAll')
	{
		try
		{
			$param_error_msg['answer'] = doGetDevicesAll();
		}
		catch (PDOException $e) 
		{
			setSQLError($e, 'SQL error. Call '.$_POST['call']);
		}
		if (!$param_error_count)
		{
			$param_error_msg['code'] = 1;
			$out_res=['success' => $param_error_msg];
		}
		else
		{
			$out_res = ['error' => $param_error_msg];
		}
	}
	elseif ($call == 'doApplyDeviceSettings' && $full_access) 
	{
		try
		{
			$sql = "/*{$mySQLQueryName}*/"."UPDATE devices SET name=:name, platform=:platform, service=:service, owner=:owner, contact_info=:contact_info, manager=:manager, comments=:comments WHERE id=:id";
			$row = $pdo->prepare($sql);
			$row->execute([
				'name'		=> $data_fields['name'],
				'platform'	=> $data_fields['platform'],
				'service'	=> $data_fields['service'],
				'owner'		=> $data_fields['owner'],
				'contact_info'	=> $data_fields['contact_info'],
				'manager'	=> $data_fields['manager'],
				'comments'	=> $data_fields['comments'],
				'id'		=> $_POST['id'],
			]);
			$param_error_msg['answer'] = [
				'id'			=> (int)$_POST['id'],
				'name'			=> $data_fields['name'],
				'platform'		=> $data_fields['platform'],
				'service'		=> $data_fields['service'],
				'owner'			=> $data_fields['owner'],
				'contact_info'	=> $data_fields['contact_info'],
				'manager'		=> $data_fields['manager'],
				'comments'		=> $data_fields['comments'],
			];
		}
		catch (PDOException $e) 
		{
			setSQLError($e, 'SQL error. Call '.$_POST['call']);
		}
		if (!$param_error_count)
		{
			$param_error_msg['code'] = 1;
			$out_res=['success' => $param_error_msg];
		}
		else
		{
			$out_res = ['error' => $param_error_msg];
		}
	}
	elseif ($call == 'doAddDevice' && $full_access) 
	{
		try
		{
			$sql = "/*{$mySQLQueryName}*/"."INSERT INTO devices (name, platform, service,  owner, contact_info, manager, comments) VALUES (:name, :platform, :service, :owner, :contact_info, :manager, :comments)";
			$row = $pdo->prepare($sql);
			$row->execute([
				'name'		=> $data_fields['name'],
				'platform'	=> $data_fields['platform'],
				'service'	=> $data_fields['service'],
				'owner'		=> $data_fields['owner'],
				'contact_info'	=> $data_fields['contact_info'],
				'manager'	=> $data_fields['manager'],
				'comments'	=> $data_fields['comments'],
			]);
			$id = $pdo->lastInsertId();
			$param_error_msg['answer'] = [
				'id'			=> (int)$id,
				'name'			=> $data_fields['name'],
				'platform'		=> $data_fields['platform'],
				'service'		=> $data_fields['service'],
				'owner'			=> $data_fields['owner'],
				'contact_info'	=> $data_fields['contact_info'],
				'manager'		=> $data_fields['manager'],
				'comments'		=> $data_fields['comments'],
			];
		}
		catch (PDOException $e) 
		{
			setSQLError($e, 'SQL error. Call '.$_POST['call']);
		}
		if (!$param_error_count)
		{
			$param_error_msg['code'] = 1;
			$out_res=['success' => $param_error_msg];
		}
		else
		{
			$out_res = ['error' => $param_error_msg];
		}
	}
	elseif ($call == 'doDeleteDevice' && $full_access) 
	{
		try
		{
			$sql = "/*{$mySQLQueryName}*/"."DELETE FROM devices WHERE id=:id";
			$row = $pdo->prepare($sql);
			$row->execute([
				'id'		=> $_POST['id'],
			]);
			$id = $pdo->lastInsertId();
			$param_error_msg['answer'] = [
				'id'		=> (int)$_POST['id'],
			];
		}
		catch (PDOException $e) 
		{
			setSQLError($e, 'SQL error. Call '.$_POST['call']);
		}
		if (!$param_error_count)
		{
			$param_error_msg['code'] = 1;
			$out_res=['success' => $param_error_msg];
		}
		else
		{
			$out_res = ['error' => $param_error_msg];
		}
	}
	elseif ($call == 'doDataExport') 
	{
		$spreadsheet = new Spreadsheet();
		$sheet = $spreadsheet->getActiveSheet();
		try
		{
			$device_array = doGetDevicesAll();
		
			$headers = ["id", "name", "platform", "service", "owner", "contact_info", "manager", "comments"];
            $columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
			array_unshift($device_array, $headers);

			$row_idx = 1;
			foreach ($device_array as $rows) {
                $ceil_idx = 0;
                foreach ($rows as $ceil) {
                    $sheet->setCellValue($columns[$ceil_idx].$row_idx, $ceil);
                    $ceil_idx++;
                }
				$row_idx++;
			}
			$writer = new Xls($spreadsheet);
			$writer->save($filename);
		}
		catch (PDOException $e) 
		{
			setSQLError($e, 'SQL error. Call '.$_POST['call']);
		}
		$xls_output = TRUE;
	}
}
else
{
	$unauthorized = TRUE;
}

if (!$unauthorized) {
	if ($csv_output !== FALSE) {
  		$mimetype = 'text/csv';
  		$size = strlen($csv_output);
  		header('Content-Disposition: attachment; filename=data.csv');
  		header('Content-Length: '.$size);
  		header('Content-Type: text/csv');
  		echo $csv_output;
	}
	elseif ($xls_output !== FALSE){
		header("Content-Type: application/vnd.ms-excel; charset=utf-8");
		header("Content-Disposition: attachment; filename=data.xls");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: private",false);
		$handle = fopen($filename, "r");
		$contents = fread($handle, filesize($filename));
		echo $contents;
	}
	else {
		header('Content-type: application/json');
		echo json_encode($out_res);
	}
}
else
{
	header($_SERVER["SERVER_PROTOCOL"]." 401 Unauthorized");
	echo "401 Unauthorized";
}

function setSQLError($pdo_exception, $error_text)
{
	setError('sql', 'Database error, see error log apache');
	$error_txt_info = $error_text.' Text: '.$pdo_exception->getMessage().', file: '.$pdo_exception->getFile().', line: '.$pdo_exception->getLine();
	errorLog($error_txt_info, 1);
}

function errorLog($error_message, $debug_mode = 1)
{
	if ($debug_mode === 1)
		error_log($error_message);
	return TRUE;
}
function doGetDevicesAll()
{
	global $pdo, $mySQLQueryName, $global_csv_columns;
	$device_list = [];
	$sql = "/*{$mySQLQueryName}*/"."SELECT id, name, platform, service, owner, contact_info, manager, comments FROM devices";
	$row = $pdo->prepare($sql);
	$row->execute();
	if($table_res = $row->fetchall())
	{
		foreach ($table_res as $row_res)
		{
			$device_list[] = [
				'id'			=> (int)$row_res['id'],
				'name'			=> removeBadSymbols($row_res['name']),
				'platform'		=> removeBadSymbols($row_res['platform']),
				'service'		=> removeBadSymbols($row_res['service']),
				'owner'			=> removeBadSymbols($row_res['owner']),
				'contact_info'	=> removeBadSymbols($row_res['contact_info']),
				'manager'		=> removeBadSymbols($row_res['manager']),
				'comments'		=> removeBadSymbols($row_res['comments']),
			];
		}
	}
	return $device_list;
}

function removeBadSymbols($str)
{
	return str_replace(["\"","'","\t"]," ", $str);
}

?>