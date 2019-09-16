<?php
header("Content-Type: application/json; charset=utf-8");
include "hefloutils.php";

$json = file_get_contents('php://input');
$obj  = json_decode($json, true);

$token = GetToken($_GET['login'],$_GET['pwd']);
$returnmetadata = false;
if (isset($_GET['withmetadata']))
    $returnmetadata = $_GET['withmetadata'] == 'true';

$error = null;
$classoid = null;
if (isset($_GET['classoid']))
    $classoid = $_GET['classoid'];
else if (isset($_GET['name']))
{
    $metadata = GetAllMetadata($_GET['domain'], $token);
    $arr = array();
    foreach ($metadata as $item)
	{
		if ($item->Text == $_GET['name'])
            array_push($arr, $item->Oid);
    }
    
    if (count($arr) == 1)
        $classoid = $arr[0];
    else if (count($arr) == 0)
        $error = "Could not find a class named '".  $_GET['name'] ."'";
    else if (count($arr) > 1)
        $error = "There is more than one class named ".  $_GET['name'] ."'. Choose to provide the parameter classoid.";
}

if ($error == null)
{
    $rec = GetRecord($_GET['oid'], $classoid, $_GET['domain'], $token, $returnmetadata);
    $jsonheflo = new StdClass();
    $jsonheflo->data = $rec;
    echo json_encode($jsonheflo);
}
else
{
    $jsonheflo->error = $error;
    echo json_encode($jsonheflo);
}
?>
