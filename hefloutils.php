<?php
$GLOBALS["records"] = array();
$GLOBALS["metadata"] = array();

function ObtemToken($key, $secret) 
{
	$headers = array(
			'Accept' => 'application/json, text/javascript, */*; q=0.01',
			'Accept-Language' => 'pt-BR', 'Referer' => 'https://app.heflo.com/Workspace/Home',
			'Content-Type' => 'application/x-www-form-urlencoded',
			'Origin' => 'https://app.heflo.com',
			'Connection' => 'keep-alive'
	);
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
	curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials&client_id=$key&client_secret=$secret");
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_URL, 'https://auth.heflo.com/token');
	$retorno = curl_exec($ch);
	curl_close($ch);
	sleep(0.05);
	$retjson = json_decode($retorno);
	return $retjson->access_token;
}

function ObtemTodoMetadata($domain, $token)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, 'https://auth.heflo.com/odata/Class/DataServiceControllers.GetCustomMetadata?buildFullMetadata=false');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
	curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
	
	$headers = array();
	$headers[] = 'User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:66.0) Gecko/20100101 Firefox/66.0';
	$headers[] = 'Accept: */*';
	$headers[] = 'Accept-Language: pt-BR';
	$headers[] = 'Referer: https://app.heflo.com/Workspace/Home';
	$headers[] = 'Authorization: Bearer '. $token;
	$headers[] = 'Currentdomain: '. $domain;
	$headers[] = 'Origin: https://app.heflo.com';
	$headers[] = 'Connection: keep-alive';
	$headers[] = 'Cache-Control: max-age=0';
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	
	$result = curl_exec($ch);
	if (curl_errno($ch)) {
		echo 'ObtemMetadadosCadastro Error:' . curl_error($ch);
	}
	curl_close ($ch);
	sleep(0.05);
	return json_decode($result);
}

$GLOBALS["cachemetadata"] = null;
function ObtemMetadadosCadastro($domain, $classoid, $token)
{
	if (isset($GLOBALS["metadata"][$classoid]) && $GLOBALS["metadata"][$classoid] != null)
		return $GLOBALS["metadata"][$classoid];

	$json = null;
	if ($GLOBALS["cachemetadata"] == null)
	{
		$json = ObtemTodoMetadata($domain, $token);
		$GLOBALS["cachemetadata"] = $json;
	}
	else
		$json = $GLOBALS["cachemetadata"];

	$props = array();
	foreach ($json as $item)
	{
		if ($item->Oid == $classoid || $item->Text == $classoid || $item->Name == $classoid)
		{
			foreach ($item->Properties as $prop)
			{
				if (isset($prop->Text) && strlen($prop->Text) > 0)
				{
					$meta = new stdClass();
					$meta->Text = $prop->Text;
					$meta->Uid = $prop->Name;
					$meta->Type = $prop->Type;
					$meta->ListEntityName = null;
					if ($prop->Type == "HEFLO.RecordList")
					{
						$meta->ListEntityName = $prop->ListEntityName;
						$meta->Items = array();
						$metadadoslist = ObtemMetadadosListaRegistros($json, $meta->ListEntityName);
						foreach ($metadadoslist as $metaitem)
						{
							$metaprop = new stdClass();
							$metaprop->Text = $metaitem->Text;
							$metaprop->Uid = $metaitem->Uid;
							$metaprop->Type = $metaitem->Type;
							array_push($meta->Items, $metaprop);
						}
					}
					array_push($props, $meta);
				}
				else if ($prop->Name == "Oid")
				{
					$meta = new stdClass();
					$meta->Text = $prop->Name;
					$meta->Uid = $prop->Name;
					$meta->Type = $prop->Type;
					array_push($props, $meta);
				}
			}
			break;
		}
	}
	$GLOBALS["metadata"][$classoid] = $props;
	return $props;
}

function ObtemMetadadosListaRegistros($metadados, $metaid)
{
	if (isset($GLOBALS["metadatalist"][$metaid]) && $GLOBALS["metadatalist"][$metaid] != null)
		return $GLOBALS["metadatalist"][$metaid];

	$props = array();
	foreach ($metadados as $item)
	{
		if ($item->Oid == $metaid)
		{
			foreach ($item->Properties as $prop)
			{
				if (isset($prop->Text) && strlen($prop->Text) > 0) // && strlen($prop->AssociatedClassOid) > 0)
				{
					$meta = new stdClass();
					$meta->Text = $prop->Text;
					$meta->Uid = $prop->Name;
					$meta->Type = $prop->Type;
					array_push($props, $meta);
				}
				
			}
			break;
		}
	}
	$GLOBALS["metadatalist"][$metaid] = $props;
	return $props;
}

function ObtemDadosListaRegistros($domain, $classoid, $uid, $listentityoid, $token)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "https://auth.heflo.com/odata/CustomProperty/DataServiceControllers.GetListData?classOid=$classoid&instanceOid=$uid&entityOid=$listentityoid&count=true");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
	curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
	
	$headers = array();
	$headers[] = 'User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:66.0) Gecko/20100101 Firefox/66.0';
	$headers[] = 'Accept: application/json';
	$headers[] = 'Accept-Language: pt-BR';
	$headers[] = 'Referer: https://app.heflo.com/Workspace/companies';
	$headers[] = 'Content-Type: application/json';
	$headers[] = 'Authorization: Bearer '. $token;
	$headers[] = 'Gettestobjects: false';
	$headers[] = 'Currentdomain: '. $domain;
	$headers[] = 'Type: GET';
	$headers[] = 'Odata-Version: 4.0';
	$headers[] = "X-Domain-$domain: true";
	$headers[] = 'Origin: https://app.heflo.com';
	$headers[] = 'Connection: keep-alive';
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	
	$result = curl_exec($ch);
	if (curl_errno($ch)) {
		echo 'ObtemDadosListaRegistros Error:' . curl_error($ch);
	}
	curl_close ($ch);
	sleep(0.05);
	$json = json_decode($result);
	return $json->value;
}

function GetRecord($uid, $classoid, $domain, $token, $withmetadata)
{
	if (isset($GLOBALS["records"]["$uid|$classoid"]) && $GLOBALS["records"]["$uid|$classoid"] != null)
		return $GLOBALS["records"]["$uid|$classoid"];

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "https://auth.heflo.com/odata/CustomProperty('$uid')/DataServiceControllers.GetEntityData?classOid=$classoid");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
	curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	
	$headers = array();
	$headers[] = 'User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:66.0) Gecko/20100101 Firefox/66.0';
	$headers[] = 'Accept: application/json';
	$headers[] = 'Accept-Language: pt-BR';
	$headers[] = 'Referer: https://app.heflo.com/Workspace/companies';
	$headers[] = 'Content-Type: application/json';
	$headers[] = 'Authorization: Bearer '. $token;
	$headers[] = 'Currentdomain: '. $domain;
	$headers[] = 'Odata-Version: 4.0';
	$headers[] = 'Origin: https://app.heflo.com';
	$headers[] = 'Connection: keep-alive';
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	
	$result = curl_exec($ch);
	if (curl_errno($ch)) {
		echo 'GetRecord Error:' . curl_error($ch);
	}
	curl_close ($ch);
	sleep(0.05);
	$ret = json_decode($result);
	
	if (isset($ret->error) && $ret->error != null)
	{
		return null;
	}

	$dados = array();

	$metadados = ObtemMetadadosCadastro($domain, $classoid, $token);
	
	foreach ($metadados as $meta)
	{
		if (strrpos($meta->Type, "Venki.") !== false)
		{
			if (isset($ret->{$meta->Uid.'Oid'}))
				$dados[$meta->Text] = $ret->{$meta->Uid.'Oid'};
		}
		else if (strrpos($meta->Type, "Heflo.Custom") !== false)
		{
			if (isset($ret->{$meta->Uid.'Oid'}))
			{
				$uidint = $ret->{$meta->Uid.'Oid'};
				$classoidint = str_replace("Heflo.Custom.ce_", "", $meta->Type);
				if ($uidint != null)
					$dados[$meta->Text] = GetRecord( $uidint, $classoidint, $domain, $token, $withmetadata);
			}
		}
		else if ($meta->Type != "HEFLO.RecordList")
		{
			if (isset($ret->{$meta->Uid}))
				$dados[$meta->Text] = $ret->{$meta->Uid};
		}
		else {
			$dados[$meta->Text] = array();
			$dadoslista = ObtemDadosListaRegistros($domain, $classoid, $ret->Oid, $meta->ListEntityName, $token);
			foreach ($dadoslista as $registro)
			{
				$reg = new stdClass();
				if (isset($registro->Oid))
					$reg->Oid = $registro->Oid;
				foreach ($meta->Items as $metaitem)
				{
					if (strrpos($metaitem->Type, "Heflo.Custom") !== false)
					{
						$uidint = $registro->{$metaitem->Uid.'Oid'};
						$classoidint = str_replace("Heflo.Custom.ce_", "", $metaitem->Type);
						$reg->{$metaitem->Text} = GetRecord($uidint, $classoidint, $domain, $token, $withmetadata);
					}
					else if ($metaitem->Type != "HEFLO.RecordList")
					{
						$reg->{$metaitem->Text} = $registro->{$metaitem->Uid};
					}
				}
				array_push($dados[$meta->Text], $reg);
			}
		}
	}
	if ($withmetadata)
		$dados["metadata"] = $metadados;
	$GLOBALS["records"]["$uid|$classoid"] = $dados;

	return $dados;
}

function GetWorkitem($workitemnumber, $domain, $token, $withmetadata)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, 'https://sa-east-1-prod-data.heflo.com/WorkItem?$filter=Number%20eq%20'. $workitemnumber .'&$selectCustom=true');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
	curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
 
	$headers = array();
	$headers[] = 'User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:69.0) Gecko/20100101 Firefox/69.0';
	$headers[] = 'Accept: application/json';
	$headers[] = 'Accept-Language: pt-BR';
	$headers[] = 'Content-Type: application/json';
	$headers[] = 'Authorization: Bearer '. $token;
	$headers[] = 'Currentdomain: '.$domain;
	$headers[] = 'Odata-Version: 4.0';
	$headers[] = 'Origin: https://app.heflo.com';
	$headers[] = 'Connection: keep-alive';
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	
	$result = curl_exec($ch);
	if (curl_errno($ch)) {
		echo 'ObtemWorkitem Error:' . curl_error($ch);
	}
	curl_close ($ch);
	$ret = json_decode($result);
	if (isset($ret->error) && $ret->error != null)
		return null;
		
	$ret = $ret->value[0];
	
	$dados = array();
	$vars = get_object_vars ($ret);
	foreach($vars as $key=>$value) {
		if (strpos($key, 'cp_') === false)
			$dados[$key] = $ret->{$key}; 
	}

	$metadados = ObtemMetadadosCadastro($domain, "WorkItem", $token);
	
	foreach ($metadados as $meta)
	{
		if (strrpos($meta->Type, "Venki.") !== false)
		{
			if (isset($ret->{$meta->Uid.'Oid'}))
				$dados[$meta->Text] = $ret->{$meta->Uid.'Oid'};
		}
		else if (strrpos($meta->Type, "Heflo.Custom") !== false)
		{
			if (isset($ret->{$meta->Uid.'Oid'}))
			{
				$uidint = $ret->{$meta->Uid.'Oid'};
				$classoidint = str_replace("Heflo.Custom.ce_", "", $meta->Type);
				if ($uidint != null)
					$dados[$meta->Text] = GetRecord( $uidint, $classoidint, $domain, $token, $withmetadata);
			}
		}
		else if ($meta->Type != "HEFLO.RecordList")
		{
			if (isset($ret->{$meta->Uid}))
				$dados[$meta->Text] = $ret->{$meta->Uid};
		}
		else {
			$dados[$meta->Text] = array();
			$dadoslista = ObtemDadosListaRegistros($domain, null, $ret->Oid, $meta->ListEntityName, $token);
			foreach ($dadoslista as $registro)
			{
				$reg = new stdClass();
				foreach ($meta->Items as $metaitem)
				{
					if (strrpos($metaitem->Type, "Heflo.Custom") !== false)
					{
						$uidint = $registro->{$metaitem->Uid.'Oid'};
						$classoidint = str_replace("Heflo.Custom.ce_", "", $metaitem->Type);
						$reg->{$metaitem->Text} = GetRecord($uidint, $classoidint, $domain, $token, $withmetadata);
					}
					else if ($metaitem->Type != "HEFLO.RecordList")
					{
						$reg->{$metaitem->Text} = $registro->{$metaitem->Uid};
					}
				}
				array_push($dados[$meta->Text], $reg);
			}
		}
	}
	if ($withmetadata)
		$dados["metadata"] = $metadados;

	return $dados;
}
?>