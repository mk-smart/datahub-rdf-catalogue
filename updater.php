<?php 
//if (php_sapi_name() !== 'cli') exit(1);

define('DATAREG_HOME', 'http://www.mksmart.org/datareg/');
define('ENDPOINT', 'http://localhost:4444/catalogue/data');
// define('PROXY','wwwcache.open.ac.uk:80');

include 'lib/translation.inc';
global $argv;

function arg($index){
	global $argv;
	if(array_key_exists($index, $argv)){
		$arg = $argv[$index];
	}else{
		throw new Exception("Missing argument: " . $index);
	}
	return $arg;
}
function downloadJson($p){
	$ch = curl_init();
	$url = DATAREG_HOME . '?p=' . $p . '&json=1';
	curl_setopt($ch, CURLOPT_URL,   $url );
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt ($ch, CURLOPT_TIMEOUT, 60);
	$json = curl_exec ($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close ($ch);
	if(strpos("$httpCode", '2') !== 0){
		throw new Exception("Cannot download json. HTTP " . $httpCode . ' '. strpos($httpCode, '2')  . ' '. $json);
	}
	$o = json_decode($json);
	if(!isset($o->post)){
		throw new Exception("Bad Json response: \nURL: $url \nResponse: " . $json ."\n");
	}
	return $o;
}
function getGraphName($type, $p){
	if($type == 'mksdc-datasets'){
		return dataset($p);
	}else if($type == 'mksdc-policies'){
		return policy($p);
	}
}
function put_ntriples($ntriples, $graphname) {
	/** use a max of 256KB of RAM before going to disk */
	$fp = fopen('php://temp/maxmemory:256000', 'w');
	if (!$fp) {
		throw new Exception('Could not open temp memory data! PUT failed.');
	}
	fwrite($fp, $ntriples);
	fseek($fp, 0);
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,   ENDPOINT . '?graph=' . urlencode($graphname)  );
	curl_setopt($ch, CURLOPT_PUT, 1);
	//	curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/plain; charset=utf-8'));
	curl_setopt($ch, CURLOPT_INFILE, $fp); // file pointer
	curl_setopt($ch, CURLOPT_INFILESIZE, strlen($ntriples));
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$out = curl_exec ($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close ($ch);
	if(strpos($httpCode, '2') !== 0){
		throw new Exception("Cannot PUT dataset. HTTP " . $httpCode . ' ' . $out . "\n" . "=====\n$body\n=====");
	}
}

function put_json($json, $type, $id){
	$rdf = translate($json->post);	
	put_ntriples($rdf, $type, $id);
}
function put($type, $p){
	$json = downloadJson($p);
	$rdf = translate($json->post); // from lib/translation.inc
	
	// PUT
	$body = $rdf;
	put_raw($body, $type, $p);
}
function delete($type, $p){
	// Not implemente yet
	throw new Exception ("Not implemented yet :(");
}
function updater($action, $type, $p){
	if($action == 'put'){
		put($type, $p);
	}else if ($action == 'delete'){
		delete($type, $p);
	}else{
		throw new Exception("Unsupported action: " . $action);
	}
}
function main(){
	try {
		echo 'Disabled';
		//echo updater(arg(1), arg(2), arg(3));
		exit(0);
	} catch (Exception $e) {
		echo $e->getMessage();
		echo "\n";
		echo $e->getTraceAsString();
		echo "\n";
		exit(1);
	}
}
if (php_sapi_name() === 'cli'){
	main();
}
