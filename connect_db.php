<?php

$unauthorized=TRUE;
function connectToBase()
{
	$host = 'localhost'; 
	$database = 'device_management'; 
	$user = 'viewer'; 
	$password = 'Viewer_1'; 
	try{
		$pdo = new PDO('mysql:host='.$host.';dbname='.$database.";charset=UTF8", $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
		return $pdo;
	}
	catch (PDOException $e){
		echo "errro connect to database:".$e;
		return FALSE;
	}
}
?>
