<?php
$GLOBALS["records"] = array();
$GLOBALS["metadata"] = array();
$GLOBALS["region"] = 'sa-east-1';

function GetToken($key, $secret) 
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
	$return = curl_exec($ch);
	curl_close($ch);
	sleep(0.05);
	$retjson = json_decode($return);
	return $retjson->access_token;
}

function GetAllMetadata($domain, $token)
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
		echo 'GetMetadataRecord Error:' . curl_error($ch);
	}
	curl_close ($ch);
	sleep(0.05);
	return json_decode($result);
}

$GLOBALS["cachemetadata"] = null;
function GetMetadataRecord($domain, $classoid, $token)
{
	if (isset($GLOBALS["metadata"][$classoid]) && $GLOBALS["metadata"][$classoid] != null)
		return $GLOBALS["metadata"][$classoid];

	$json = null;
	if ($GLOBALS["cachemetadata"] == null)
	{
		$json = GetAllMetadata($domain, $token);
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
						$metadatalist = GetMetadataRecordList($json, $meta->ListEntityName);
						foreach ($metadatalist as $metaitem)
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

function GetMetadataRecordList($metadata, $metaid)
{
	if (isset($GLOBALS["metadatalist"][$metaid]) && $GLOBALS["metadatalist"][$metaid] != null)
		return $GLOBALS["metadatalist"][$metaid];

	$props = array();
	foreach ($metadata as $item)
	{
		if ($item->Oid == $metaid)
		{
			foreach ($item->Properties as $prop)
			{
				if (isset($prop->Text) && strlen($prop->Text) > 0)
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

function GetRecordList($domain, $classoid, $uid, $listentityoid, $token)
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
		echo 'GetRecordList Error:' . curl_error($ch);
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
	$headers[] = 'Referer: https://app.heflo.com/Workspace/Home';
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

	$data = array();

	$metadata = GetMetadataRecord($domain, $classoid, $token);
	
	foreach ($metadata as $meta)
	{
		if (strrpos($meta->Type, "Venki.") !== false)
		{
			if (isset($ret->{$meta->Uid.'Oid'}))
				$data[$meta->Text] = $ret->{$meta->Uid.'Oid'};
		}
		else if (strrpos($meta->Type, "Heflo.Custom") !== false)
		{
			if (isset($ret->{$meta->Uid.'Oid'}))
			{
				$uidint = $ret->{$meta->Uid.'Oid'};
				$classoidint = str_replace("Heflo.Custom.ce_", "", $meta->Type);
				if ($uidint != null)
					$data[$meta->Text] = GetRecord( $uidint, $classoidint, $domain, $token, $withmetadata);
			}
		}
		else if ($meta->Type != "HEFLO.RecordList")
		{
			if (isset($ret->{$meta->Uid}))
				$data[$meta->Text] = $ret->{$meta->Uid};
		}
		else {
			$data[$meta->Text] = array();
			$datalist = GetRecordList($domain, $classoid, $ret->Oid, $meta->ListEntityName, $token);
			foreach ($datalist as $record)
			{
				$rec = new stdClass();
				if (isset($record->Oid))
					$rec->Oid = $record->Oid;
				foreach ($meta->Items as $metaitem)
				{
					if (strrpos($metaitem->Type, "Heflo.Custom") !== false)
					{
						$uidint = $record->{$metaitem->Uid.'Oid'};
						$classoidint = str_replace("Heflo.Custom.ce_", "", $metaitem->Type);
						$rec->{$metaitem->Text} = GetRecord($uidint, $classoidint, $domain, $token, $withmetadata);
					}
					else if ($metaitem->Type != "HEFLO.RecordList")
					{
						$rec->{$metaitem->Text} = $record->{$metaitem->Uid};
					}
				}
				array_push($data[$meta->Text], $rec);
			}
		}
	}
	if ($withmetadata)
		$data["metadata"] = $metadata;
	$GLOBALS["records"]["$uid|$classoid"] = $data;

	return $data;
}

function GetWorkitem($workitemnumber, $domain, $token, $withmetadata)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, 'https://'. $GLOBALS['region'] .'-prod-data.heflo.com/WorkItem?$filter=Number%20eq%20'. $workitemnumber .'&$selectCustom=true');
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
	
	$data = array();
	$vars = get_object_vars ($ret);
	foreach($vars as $key=>$value) {
		if (strpos($key, 'cp_') === false)
			$data[$key] = $ret->{$key}; 
	}

	$metadata = GetMetadataRecord($domain, "WorkItem", $token);
	
	foreach ($metadata as $meta)
	{
		if (strrpos($meta->Type, "Venki.") !== false)
		{
			if (isset($ret->{$meta->Uid.'Oid'}))
				$data[$meta->Text] = $ret->{$meta->Uid.'Oid'};
		}
		else if (strrpos($meta->Type, "Heflo.Custom") !== false)
		{
			if (isset($ret->{$meta->Uid.'Oid'}))
			{
				$uidint = $ret->{$meta->Uid.'Oid'};
				$classoidint = str_replace("Heflo.Custom.ce_", "", $meta->Type);
				if ($uidint != null)
					$data[$meta->Text] = GetRecord( $uidint, $classoidint, $domain, $token, $withmetadata);
			}
		}
		else if ($meta->Type != "HEFLO.RecordList")
		{
			if (isset($ret->{$meta->Uid}))
				$data[$meta->Text] = $ret->{$meta->Uid};
		}
		else {
			$data[$meta->Text] = array();
			$datalist = GetRecordList($domain, null, $ret->Oid, $meta->ListEntityName, $token);
			foreach ($datalist as $record)
			{
				$rec = new stdClass();
				if (isset($record->Oid))
					$rec->Oid = $record->Oid;
				foreach ($meta->Items as $metaitem)
				{
					if (strrpos($metaitem->Type, "Heflo.Custom") !== false)
					{
						$uidint = $record->{$metaitem->Uid.'Oid'};
						$classoidint = str_replace("Heflo.Custom.ce_", "", $metaitem->Type);
						$rec->{$metaitem->Text} = GetRecord($uidint, $classoidint, $domain, $token, $withmetadata);
					}
					else if ($metaitem->Type != "HEFLO.RecordList")
					{
						$rec->{$metaitem->Text} = $record->{$metaitem->Uid};
					}
				}
				array_push($data[$meta->Text], $rec);
			}
		}
	}
	if ($withmetadata)
		$data["metadata"] = $metadata;

	return $data;
}
?>
