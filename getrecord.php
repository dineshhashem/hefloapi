<?php
header("Content-Type: application/json; charset=utf-8");
include "hefloutils.php";

$json = file_get_contents('php://input');
$obj  = json_decode($json, true);

$token = ObtemToken($_GET['login'],$_GET['pwd']);
$returnmetadata = false;
if (isset($_GET['withmetadata']))
    $returnmetadata = $_GET['withmetadata'] == 'true';

$mensagemerro = null;
$classoid = null;
if (isset($_GET['classoid']))
    $classoid = $_GET['classoid'];
else if (isset($_GET['name']))
{
    $metadados = ObtemTodoMetadata($_GET['domain'], $token);
    $arr = array();
    foreach ($metadados as $item)
	{
		if ($item->Text == $_GET['name'])
		{
            array_push($arr, $item->Oid);
        }
    }
    if (count($arr) == 1)
        $classoid = $arr[0];
    else if (count($arr) == 0)
        $mensagemerro = "Não foi possível encontrar uma classe chamada ".  $_GET['name'];
    else if (count($arr) > 1)
        $mensagemerro = "Existe mais de uma classe ".  $_GET['name'] .". Opte por fornecer o parâmetro classoid";
}

if ($mensagemerro == null)
{
    $rec = GetRecord($_GET['oid'], $classoid, $_GET['domain'], $token, $returnmetadata);
    $jsonheflo = new StdClass();
    $jsonheflo->data = $rec;
    echo json_encode($jsonheflo);
}
else
{
    $jsonheflo->erro = $mensagemerro;
    echo json_encode($jsonheflo);
}
?>
