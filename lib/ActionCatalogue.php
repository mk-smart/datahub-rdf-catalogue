<?php
include_once dirname(__FILE__) . '/../updater.php';
include_once dirname(__FILE__) . '/jsonld.php';

class ActionCatalogue extends AbstractRest {
	private $_config;
	private $_service;
	
	function __construct(){
		$this->_config = parse_ini_file(dirname(__FILE__) . '/../config.ini', true);
		$this->_service = $this->_config['fuseki']['service'];
	}
	
	function doGET(){

		$FROM = "";
		if(isset($this->parameters['graph'])){
			$FROM = ' FROM <' . $this->parameters['graph'] . '>';
		}

		$accept = $this->headers['Accept'];
		
		if(isset($this->parameters['uri'])){
			$uri = $this->parameters['uri'];
			$query = "DESCRIBE <$uri> ?S ?P ?O $FROM WHERE { { <$uri> ?P ?O } UNION { ?S ?P <$uri> } }";
		}else{
			$query = "DESCRIBE ?S ?P ?O $FROM WHERE { ?S ?P ?O }";
		}
		//		$query = "SELECT DISTINCT ?G WHERE {GRAPH ?G { [] ?p []} }";

		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $this->_service . '/query?query=' . urlencode( $query ) );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		@curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 1 ); // This raise a notice... value used is 2)
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Accept: ' . $accept ) );
		$server_output = curl_exec ($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if(strpos($http_code, '2') === 0){
			// OK
			header("HTTP/1.1 " . $http_code, true, $http_code);
			header('Expires: 0');
			header('X-SPARQL-Query: ' . $query);
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
			header('Content-Description: File Transfer');
			header("Content-Transfer-Encoding: binary");
			header("Content-Type: " . curl_getinfo( $ch, CURLINFO_CONTENT_TYPE)."");
			header("Content-Length: " . curl_getinfo( $ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD)."");
			curl_close ($ch);
			print $server_output;
			exit(0); // Die happy
		}else{
			// Any other is a failure
			error_internal_server_error("$http_code \nResponse was: " . $server_output);
		}
	}

	function doPOST(){
		// FIXME This should be done at apache level probably...
		$this->allowFromKey();
		
// 		$p = $this->assertNotEmpty('p');
		$action = $this->assertNotEmpty('action');
		$graph = $this->assertNotEmpty('graph');
// 		$type = $this->assertNotEmpty('type');

		if( $action == 'put' ){
			$json = $this->assertNotEmpty('json');
			$json = json_decode( base64_decode($json) );
			switch(json_last_error())
	        {
	            case JSON_ERROR_DEPTH:
	                $error =  ' - Maximum stack depth exceeded';
	                break;
	            case JSON_ERROR_CTRL_CHAR:
	                $error = ' - Unexpected control character found';
	                break;
	            case JSON_ERROR_SYNTAX:
	                $error = ' - Syntax error, malformed JSON';
	                break;
	            case JSON_ERROR_NONE:
	            default:
	                $error = '';
	        }
        	
	        if($error) _error ( 'Broken JSON: ' . $error );
        	
	        $ntriples = jsonld_normalize($json, array('format' => 'application/nquads'));
	        $this->parameters['data'] = $ntriples;
	        //put_ntriples($ntriples, $graph);
			//$output = $ntriples;
			//header("HTTP/1.1 " . 200, true, 200);
			return $this->_doPUT();
		} else if( $action == 'delete' ) {
			return $this->doDELETE();
		}

		//
		exit(0); // Die happy
	}
	
	/**
	 * The real PUT would take a file as input...
	 * @throws Exception
	 */
	function _doPUT(){
		
		$graph = $this->assertNotEmpty('graph');
		$data = $this->assertNotEmpty('data');
		_debug('PUT graph ' . $graph);
		/** use a max of 256KB of RAM before going to disk */
		$fp = fopen('php://temp/maxmemory:256000', 'w');
		if (!$fp) {
			throw new Exception('Could not open temp memory data! PUT failed.');
		}
		fwrite($fp, $data);
		fseek($fp, 0);
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,   ENDPOINT . '?graph=' . urlencode($graph)  );
		curl_setopt($ch, CURLOPT_PUT, 1);
		//	curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/plain; charset=utf-8'));
		curl_setopt($ch, CURLOPT_INFILE, $fp); // file pointer
		curl_setopt($ch, CURLOPT_INFILESIZE, strlen($data));
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$out = curl_exec ($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close ($ch);
		if(strpos($httpCode, '2') !== 0){
			throw new Exception("Cannot PUT dataset. HTTP " . $httpCode . ' ' . $out );
		}
	}

	// This deletes a graph in the catalogue
	function doDELETE(){
		$this->allowFromKey();
		$graph = $this->assertNotEmpty('graph');
		_debug('DELETE graph ' . $graph);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,'http://localhost:4444/catalogue/data?graph=' . urlencode($graph));
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE"); #curl_setopt($ch, CURLOPT_DELETE, 1);
		$server_output = curl_exec ($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close ($ch);
		#error_log("Executed Catalogue::DELETE . Response: " . $http_code, 0);
		if(strpos($http_code, '2') === 0){
			// OK
			header("HTTP/1.1 " . $http_code, true, $http_code);
			exit(0); // Die happy
		}else{
			// Any other is a failure
			error_internal_server_error("Response was: " . $server_output);
		}
	}
}

