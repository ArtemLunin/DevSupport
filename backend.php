<?php
session_start();
require_once "connect_db.php";

$shield_new_line = function($value) {
    // if ((strpos($value,"\n") !== FALSE) || ((strpos($value,"\r\n") !== FALSE)))
	// {
		$value = '"'.$value.'"';
	// }
	return $value;
};

$out_res = [];

$csv_output = FALSE;
$global_csv_columns = "";
$csv_rows_delimiter = "\n";

$call = $_POST['call'] ?? NULL;
if (isset($_GET['action'])) {
	$call = $_GET['action'];
}
$mySQLQueryName = $call.'('.basename(__DIR__).')';

$param_error_msg = [];
$param_error_count = 0;

$param_error_msg['call_name'] = @$call;
$param_error_msg['answer'] = [];

mb_internal_encoding("UTF-8");

$pdo = connectToBase();

if ($call == 'doSignIn' && isset($_POST['username']) && isset($_POST['password']))
{
	$query = "/*{$mySQLQueryName}*/"."SELECT id, username, password FROM users WHERE username=:username";
 	try
 	{
		$row = $pdo->prepare($query);
		$row->execute(['username' => trim($_POST['username'])]);
		$result = $row->fetch();
		if (isset($result['id']) && password_verify($_POST['password'], $result['password']))
		{
			$_SESSION['logged_user'] = $result['id'];
			$param_error_msg['code'] = 1;
			$param_error_msg['answer'] = 1;
			$out_res=['success' => $param_error_msg];
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
	elseif ($call == 'doApplyDeviceSettings') 
	{
		try
		{
			$sql = "/*{$mySQLQueryName}*/"."UPDATE devices SET name=:name, platform=:platform, service=:service, owner=:owner, contact_info=:contact_info, manager=:manager, comments=:comments WHERE id=:id";
			$row = $pdo->prepare($sql);
			$row->execute([
				'name'		=> $_POST['name'],
				'platform'	=> $_POST['platform'],
				'service'	=> $_POST['service'],
				'owner'		=> $_POST['owner'],
				'contact_info'	=> $_POST['contact_info'],
				'manager'	=> $_POST['manager'],
				'comments'	=> $_POST['comments'],
				'id'		=> $_POST['id'],
			]);
			$param_error_msg['answer'] = [
				'id'			=> (int)$_POST['id'],
				'name'			=> $_POST['name'],
				'platform'		=> $_POST['platform'],
				'service'		=> $_POST['service'],
				'owner'			=> $_POST['owner'],
				'contact_info'	=> $_POST['contact_info'],
				'manager'		=> $_POST['manager'],
				'comments'		=> $_POST['comments'],
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
	elseif ($call == 'doAddDevice') 
	{
		try
		{
			$sql = "/*{$mySQLQueryName}*/"."INSERT INTO devices (name, platform, service,  owner, contact_info, manager, comments) VALUES (:name, :platform, :service, :owner, :contact_info, :manager, :comments)";
			$row = $pdo->prepare($sql);
			$row->execute([
				'name'		=> $_POST['name'],
				'platform'	=> $_POST['platform'],
				'service'	=> $_POST['service'],
				'owner'		=> $_POST['owner'],
				'contact_info'	=> $_POST['contact_info'],
				'manager'	=> $_POST['manager'],
				'comments'	=> $_POST['comments'],
			]);
			$id = $pdo->lastInsertId();
			$param_error_msg['answer'] = [
				'id'			=> (int)$id,
				'name'			=> $_POST['name'],
				'platform'		=> $_POST['platform'],
				'service'		=> $_POST['service'],
				'owner'			=> $_POST['owner'],
				'contact_info'	=> $_POST['contact_info'],
				'manager'		=> $_POST['manager'],
				'comments'		=> $_POST['comments'],
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
	elseif ($call == 'doDeleteDevice') 
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
	elseif ($call == 'doDataExport') {
		try
		{
			$device_array = doGetDevicesAll();
			$csv_output = "";
			foreach ($device_array as $value) {
				
				$csv_output .= implode(';', array_map($shield_new_line, $value)).$csv_rows_delimiter;
			}
			$csv_output = $global_csv_columns.$csv_output;
		}
		catch (PDOException $e) 
		{
			setSQLError($e, 'SQL error. Call '.$_POST['call']);
		}
	}
}
else
{
	$unauthorized = TRUE;
}

if (!$unauthorized) {
	if ($csv_output !== FALSE) {
		// $filename = 'httpfile.zip';
  		$mimetype = 'text/csv';
  		// $data = file_get_contents($filename);
  		$size = strlen($csv_output);
  		header('Content-Disposition: attachment; filename= data.csv');
  		header('Content-Length: '.$size);
  		header('Content-Type: text/csv');
  		echo $csv_output;
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
	global $pdo, $mySQLQueryName, $global_csv_columns, $csv_rows_delimiter;
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
				'name'			=> $row_res['name'],
				'platform'		=> $row_res['platform'],
				'service'		=> $row_res['service'],
				'owner'			=> $row_res['owner'],
				'contact_info'	=> $row_res['contact_info'],
				'manager'		=> $row_res['manager'],
				'comments'		=> $row_res['comments'],
			];
		}
	}
	$global_csv_columns = "id;name;platform;service;owner;contact_info;manager;comments".$csv_rows_delimiter;
	return $device_list;
}

?>