<?php 
require_once dirname(__FILE__) . '/../arc2-2.2.4/ARC2.php';
///////////////////////////////////////////////////////////////////////////////////

function dcat($ln){ return 'http://www.w3.org/ns/dcat#' . $ln; }
function dctype($ln){ return 'http://purl.org/dc/dcmitype/' . $ln; }
function dct($ln){ return 'http://purl.org/dc/terms/' . $ln; }
function foaf($ln){ return 'http://xmlns.com/foaf/0.1/' . $ln; }
function rdf($ln){ return 'http://www.w3.org/1999/02/22-rdf-syntax-ns#' . $ln; }
function rdfs($ln){ return 'http://www.w3.org/2000/01/rdf-schema#' . $ln; }
function skos($ln){ return 'http://www.w3.org/2004/02/skos/core#' . $ln; }
function vcard($ln){ return 'http://www.w3.org/2006/vcard/ns#' . $ln; }
function xsd($ln){ return 'http://www.w3.org/2001/XMLSchema#' . $ln; }
function odrl($ln){ return 'http://www.w3.org/ns/odrl/2/' . $ln; }

global $___nb___;
$___nb___ = 0;
function bn($localid = null){
	global $___nb___;
	if($localid != null){
		return "_:" . $localid;
	}else{
		$___nb___++;
		return "_:bn" . $___nb___;
	}
}
function b_p_b($s, $p, $o){
	return array(
			's' => $s,
			'p' => $p,
			'o' => $o,
			's_type' => 'bnode',
			'o_type' => 'bnode'
	);
}
function b_p_u($s, $p, $o){
	return array(
			's' => $s,
			'p' => $p,
			'o' => $o,
			's_type' => 'bnode',
			'o_type' => 'uri'
	);
}
function b_p_l($s, $p, $l){
	return array(
			's' => $s,
			'p' => $p,
			'o' => $l,
			's_type' => 'bnode',
			'o_type' => 'literal'
	);
}
function b_p_tl($s, $p, $l, $t){
	return array(
			's' => $s,
			'p' => $p,
			'o' => $l,
			's_type' => 'bnode',
			'o_type' => 'literal',
			'o_datatype' => $t
	);
}
function b_p_ll($s, $p, $l, $lang){
	return array(
			's' => $s,
			'p' => $p,
			'o' => $l,
			's_type' => 'bnode',
			'o_type' => 'literal',
			'o_lang' => $lang
	);
}
function u_p_b($s, $p, $o){
	return array(
			's' => $s,
			'p' => $p,
			'o' => $o,
			's_type' => 'uri',
			'o_type' => 'bnode'
	);
}
function u_p_u($s, $p, $o){
	return array(
			's' => $s,
			'p' => $p,
			'o' => $o,
			's_type' => 'uri',
			'o_type' => 'uri'
	);
}
function u_p_l($s, $p, $l){
	return array(
			's' => $s,
			'p' => $p,
			'o' => $l,
			's_type' => 'uri',
			'o_type' => 'literal'
	);
}
function u_p_tl($s, $p, $l, $t){
	return array(
			's' => $s,
			'p' => $p,
			'o' => $l,
			's_type' => 'uri',
			'o_type' => 'literal',
			'o_datatype' => $t
	);
}
function u_p_ll($s, $p, $l, $lang){
	return array(
			's' => $s,
			'p' => $p,
			'o' => $l,
			's_type' => 'uri',
			'o_type' => 'literal',
			'o_lang' => $lang
	);
}
function ntriples(array $triples){
	$ser = ARC2::getNTriplesSerializer();
	$doc = $ser->getSerializedTriples($triples);
	return $doc;
}