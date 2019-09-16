<?php
header("Content-Type: application/json; charset=utf-8");
include "hefloutils.php";

$json = file_get_contents('php://input');
$obj  = json_decode($json, true);

$token = GetToken($_GET['login'],$_GET['pwd']);
$returnmetadata = false;
if (isset($_GET['withmetadata']))
    $returnmetadata = $_GET['withmetadata'] == 'true';

$wi = GetWorkitem($_GET['workitem'], $_GET['domain'], $token, $returnmetadata);
$jsonheflo = new StdClass();
$jsonheflo->data = $wi;
echo json_encode($jsonheflo);
?>
