<?php

define('RDFCAT_DEBUG', FALSE);
if(RDFCAT_DEBUG){
	error_reporting(E_NONE);
	ini_set('display_errors',0);
}

function _debug($m){
	if(RDFCAT_DEBUG){
		error_log('DEBUG [RDFCat] ' . $m);
	}
}

function _error($m){
	error_log('ERROR [RDFCat] ');
}

function error_not_found($m) {
	header("HTTP/1.0 404 Not Found", true, 404);
	header("Content-type: text/plain; charset=utf-8"); 
	echo 'Not Found: ' .  $m . PHP_EOL; 
	exit(); 
}
function error_malformed($msg){
	header("HTTP/1.0 400 Bad request", true, 400);
	header("Content-type: text/plain; charset=utf-8");
	$echo = 'Bad request: ' . $msg ;
	error_log($echo);
	echo $echo. PHP_EOL;
	exit();
}
function error_method_not_allowed($method){
	header("HTTP/1.0 405 Method Not Allowed", true, 405);
        header("Content-type: text/plain; charset=utf-8");
        $echo = 'Method not allowed: ' . $method;
	echo $echo. PHP_EOL;
        exit(); 
}
function error_internal_server_error($msg){
        header("HTTP/1.0 500 Internal Server Error", true, 500);
        header("Content-type: text/plain; charset=utf-8");
	$echo = 'Server error: ' . $msg;
	echo $echo. PHP_EOL;
	error_log($echo);
        exit();
}
function error_forbidden($message){
	header("HTTP/1.0 403 Forbidden", true, 403);
        header("Content-type: text/plain; charset=utf-8");
        echo 'Forbidden: ' .  $message. PHP_EOL;
        exit();
}


/* REST Interface */
interface Rest {
	function doGET();
	function doPOST();
	function doPUT();
	function doDELETE();
	function doHEAD();
	function doOPTIONS();
}
abstract class AbstractRest implements Rest{

	// Security Helpers
	function allowFromIp($ip){
		if( $_SERVER['REMOTE_ADDR'] === $ip ){
			return true;
		}
		error_forbidden($ip);
	}
	
	function allowFromKey(){
		$keys = file_get_contents(dirname(__FILE__) . '/keys');
		$keys = explode("\n", $keys);
		foreach($keys as $k){
			if($k == $_SERVER['PHP_AUTH_USER']){
				return true;
			}
		}
		error_forbidden('Header: ' . $header);
	}
	

	// Input Validation Helpers
	function assertNotEmpty($paramName){
		$value = false;
		if(isset($this->parameters[$paramName])){
			$value = $this->parameters[$paramName];
		}

		if($value!==false && $value!=''){
			return $value;
		}
		error_malformed("'$paramName' is missing or empty ");
	}
	
	// Default behavior
	function doHEAD(){  error_method_not_allowed('HEAD'); }
	function doGET(){ error_method_not_allowed('GET'); }
	function doPOST(){ error_method_not_allowed('POST'); }
	function doPUT(){ error_method_not_allowed('PUT'); }
	function doDELETE(){ error_method_not_allowed('DELETE'); }	
	function doOPTIONS(){ error_method_not_allowed('OPTIONS'); }	
}
/* default Action */
class Action extends AbstractRest{}

/* Please put all the rest implementations in ./lib/ActionXxxxxx.php */
function __autoload($class_name) {
    $f = dirname(__FILE__) . '/lib/' . $class_name . '.php';
    if(file_exists($f)){
	require_once $f;
    }else{
	throw new ClassNotFoundException($class_name);
    }
}
class ClassNotFoundException extends Exception{}

// This function is for routing the HTTP request
// Requests are understood as follow:
//   /<action>[/<param>/<value>]*[?[<param>=<value>]*]
// This is routed to Rest implementation class named
//   Action<Action>
// 
function route(){
	$method = $_SERVER['REQUEST_METHOD'];
	$request = explode("/", substr(@$_SERVER['PATH_INFO'], 1));

	$pathParameters = array();
	$pathName = array_shift($request);
	for($x = 0; $x<count($request); $x+=2){	
		$v = ( isset( $request[$x+1] ) ) ? $request[$x+1] : NULL;
		$pathParameters[$request[$x]] = $v;
	}
	$params = array_merge($_GET, $_POST, $pathParameters);
	$actionName =  ucfirst($pathName);
	$action = 'Action' . $actionName;	

	try{
		$exists = class_exists($action);
	}catch(ClassNotFoundException $e){
		error_not_found($actionName);
	}
	if($exists && in_array( 'Rest', class_implements($action) ) ){
		$a = new $action;
		$m = 'do' . $method;
		if(method_exists($a, $m)){ 
			$a->parameters = $params;
			$a->headers = getallheaders();
			try{
				$a->$m();
			}catch(Exception $e){
				error_internal_server_error($e->getMessage());
				throw $e;
			}
			// after executed, we must be sure nothing else happen. 
			exit(); 
		}else{
			 error_method_not_allowed($method);
		} 
	}else{
		error_not_found("Not found: " . $request[0]);
	}
	
	
}


function start(){
	route();
}

start();

