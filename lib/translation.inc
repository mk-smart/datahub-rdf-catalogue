<?php
require_once dirname(__FILE__) . '/rdf.inc';

//////////////////////////////////////////////////////////////////////////////////
function mksmart($part = ''){
	return 'http://www.mksmart.org/' . $part;
}
function datahub($part = ''){
	return 'http://datahub.mksmart.org/ns/' . $part;
}
function schema($part = ''){
	return datahub('schema/' . $part);
}
function dataset($id){
	return datahub('dataset/'. $id);
}
function catalogue($part=''){
	return datahub('catalogue/'. $part);
}
function record($id){
	return catalogue('record/'. $id);
}
function policy($id){
	return datahub('policy/' . $id);
}
function action($id){
	return datahub('action/' . $id);
}
function scope($id){
	return datahub('scope/' . $id);
}
function topic($id){
	return datahub('topic/' . $id);
}
function distribution($id){
	return datahub('dist/' . $id);
}
//////////////////////////////////////////////////////////////////////////////////
function __Policy(){
	return schema('Policy');
}
function __Service(){
	return schema('Service');
}
function __Feed(){
	return schema('Feed');
}
function __ImportPipeline(){
	return schema('ImportPipeline');
}
function __ImportPipelineComponent(){
	return schema('ImportPipelineComponent');
}
function __ImportPipelineParameter(){
	return schema('ImportPipelineParameter');
}
function _importPipeline(){
	return schema('importPipeline');
}
function _policy(){
	return schema('policy');
}
function _scope(){
	return schema('scope');
}
function _component(){
	return schema('component');
}
function _hasParameter(){
	return schema('hasParameter');
}
function _parameterOf(){
	return schema('parameterOf');
}
function _directlyFollows() {
	return schema('directlyFollows');
}
function _directlyPrecedes() {
	return schema('directlyPrecedes');
}
function _size(){
	return schema('size');
}
function _index(){
	return schema('index');
}
function _parameters(){
	return schema('parameters');
}
function _name(){
	return schema('name');
}
function _sourceLink(){
	return schema('sourceLink');
}
function _attribution(){
	return schema('attribution');
}
function _uuid(){
	return schema('uuid');
}
function _tag(){
	return schema('tag');
}
function _category(){
	return schema('category');
}
function translate_dataset($post){
	//_debug(print_r($post, TRUE));
	if(isset($post->acf->_mksdc_uuid_key)){
		$d = dataset($post->acf->_mksdc_uuid_key[0]);
	}else{
		$d = dataset($post->id);
	}
	$triples = array();
	$triples[] = u_p_u($d, rdf('type'), dcat('Dataset'));
	$triples[] = u_p_l($d, skos('prefLabel'), $post->title);
	$triples[] = u_p_ll($d, rdfs('label'), $post->title, 'en');
	$triples[] = u_p_tl($d, dct('description'), $post->content, rdf('HTML'));
	//$triples[] = u_p_tl($d, dct('issued'), str_replace(' ', 'Z', $post->date_gmt), xsd('dateTime'));
	//$triples[] = u_p_tl($d, dct('modified'), str_replace(' ', 'Z', $post->modified_gmt), xsd('dateTime'));

	if(isset($post->acf->_mksdc_link_to_source_key)){
		$triples[] = u_p_tl($d, _sourceLink() , $post->acf->_mksdc_link_to_source_key[0], xsd('anyURI'));
	}

	if(isset($post->acf->_mksdc_attribution_key)){
		$triples[] = u_p_tl($d, _attribution() , $post->acf->_mksdc_attribution_key[0], xsd('string'));
	}
	
	if(isset($post->acf->_mksdc_uuid_key[0])){
		$triples[] = u_p_tl($d, _uuid() , $post->acf->_mksdc_uuid_key[0], xsd('string'));
		$triples[] = u_p_tl($d, dct('identifier') , $post->acf->_mksdc_uuid_key[0], xsd('string'));
	}
	
	// categories
	foreach($post->categories as $cat){
		$triples[] = u_p_u($d, foaf('topic'), topic($cat->slug));
		$triples[] = u_p_u($d, _category(), topic($cat->slug));
		$triples[] = u_p_l(topic($cat->slug), rdfs('label'), $cat->name);
	}
	
	// tags
	foreach($post->taxonomies as $tag){
		if($tag->taxonomy == 'post_tag'){
			$triples[] = u_p_u($d, _tag(), topic($tag->slug));
			$triples[] = u_p_l(topic($tag->slug), rdfs('label'), $tag->name);
		}
	}
	
	// DCAT record
	if(isset($post->acf->_mksdc_uuid_key)){
		$r = record($post->acf->_mksdc_uuid_key[0]);
	}else{
		$r = record($post->id);
	}
	$triples[] = u_p_u($r, foaf('primaryTopic'), $d);
	$triples[] = u_p_u($r, rdf('type'), dcat('CatalogRecord'));
	$triples[] = u_p_l($r, skos('prefLabel'), $post->title);
	$triples[] = u_p_ll($r, rdfs('label'), $post->title, 'en');
	$triples[] = u_p_tl($r, dct('issued'), str_replace(' ', 'Z', $post->date_gmt), xsd('dateTime'));
	$triples[] = u_p_tl($r, dct('modified'), str_replace(' ', 'Z', $post->modified_gmt), xsd('dateTime'));
	$triples[] = u_p_u($r, foaf('homepage'), $post->date_gmt);
	$triples[] = u_p_u(catalogue(), dcat('record'), $r);
	
	// Collection
	// Pipeline XXX DISABLED for R1.0
	if(FALSE && isset($post->acf->_mksdc_nbcomponent_key)){
		$ip = bn('ip');
		$triples[] = u_p_b($d, _importPipeline(), $ip);
		$triples[] = b_p_u($ip, rdf('type'), __ImportPipeline());
		$triples[] = b_p_u($ip, rdf('type'), rdf('List'));
		
		$nb = $post->acf->_mksdc_nbcomponent_key[0];
		$triples[] = b_p_tl($ip, _size(), $nb, xsd('int'));
		_log("Components: " . $nb);
		$list = $ip;
		$previous = false;
		for($i=1; $i<=$nb; $i++){
			$kname = '_mksdc_component_'.$i.'_key';
			$kparam = '_mksdc_component_param_'.$i.'_key';
			$componentis = $post->acf->$kname;
			$parameteris = $post->acf->$kparam;
			if(!isset($componentis[0])) break;
			
			$componentid = $componentis [0];
			
			$cmp = bn( 'cmp' . $i );
			$triples[] = b_p_u($cmp, rdf('type'), __ImportPipelineComponent());
			$triples[] = b_p_l($cmp, _name(), $componentid);
			$triples[] = b_p_tl($cmp, _index(), $i, xsd('int'));
			$triples[] = b_p_b($list, rdf('first'), $cmp);
			// attach to the list
			if($previous === false){
				$previous = $cmp;
			} else {
				$triples[] = b_p_b($previous, rdf('rest'), $cmp);
				$previous = $cmp;
			}
			$componentparams = explode(',', $parameteris[0]);
			$cpa = bn('cpa' . $i );
			$triples[] = b_p_b($cmp, _parameters(), $cpa);
			$triples[] = b_p_u($cpa, rdf('type'), rdf('List'));
			$triples[] = b_p_tl($cpa, _size(), count($componentparams), xsd('int'));
			$vlist = $cpa;
			$pprevious = false;
			foreach($componentparams as $cindex => $cvalue) {
				$pv = bn( );
				$triples[] = b_p_l($pv, rdf('value'), $cvalue);
				$triples[] = b_p_u($pv, rdf('type'), __ImportPipelineParameter());
				$triples[] = b_p_tl($pv, _index(), ($cindex+1), xsd('int'));
				$triples[] = b_p_b($pv, _parameterOf(), $cmp);
				$triples[] = b_p_b($cmp, _hasParameter(), $pv);
				$triples[] = b_p_b($vlist, rdf('first'), $pv);
				// attach to the list
				if($pprevious === false){
					$pprevious = $pv;
				} else {
					$triples[] = b_p_b($pprevious, rdf('rest'), $pv);
					$pprevious = $pv;
				}
			}
		}
		if(isset($cmp)){
			$triples[] = b_p_u($cmp, rdf('rest'), rdf('nil'));
		}
	}
	
	// Policies
	foreach($post->policies as $pol){
// 		_debug(print_r($pol, TRUE));
		$triples[] = u_p_u($d, _policy(), policy($pol->post_name));
		$triples[] = u_p_u($d, dct('license'), policy($pol->post_name));
	}

	// If it is a feed
	if(isset($post->feed->service_id)){
		$f = distribution(substr($post->feed->service_id,1)); // remove initial /
		$triples[] = u_p_u($f, rdf('type'), dcat('Distribution'));
		$triples[] = u_p_u($f, rdf('type'), __Service());
		$triples[] = u_p_u($f, rdf('type'), __Feed());
		$triples[] = u_p_ll($f, rdf('label'), $post->post_title . ' (Service)', 'en');
		// FIXME We should add the full path of the service ...
		$triples[] = u_p_tl($f, dcat('accessURL'), $post->feed->service_id, xsd('anyURI'));
		$triples[] = u_p_u($d, dcat('distribution'), $f);
		$triples[] = u_p_tl($f, dct('issued'), str_replace(' ', 'Z', $post->feed->createFeedDate), xsd('dateTime'));
	}
	
	// and we serialize the result
	return ntriples($triples);
}

function translate_policy($post){
	//_debug(print_r($post, TRUE));
	$p = policy($post->post_name);
	$triples = array();
	$triples[] = u_p_u($p, rdf('type'), __Policy());
	$triples[] = u_p_u($p, rdf('type'), odrl('Set'));
	$triples[] = u_p_l($p, skos('prefLabel'), $post->title);
	$triples[] = u_p_ll($p, rdfs('label'), $post->title, 'en');
	$triples[] = u_p_tl($p, dct('description'), $post->content, rdf('HTML'));
	$triples[] = u_p_tl($p, dct('issued'), str_replace(' ', 'Z', $post->date_gmt), xsd('dateTime'));
	$triples[] = u_p_tl($p, dct('modified'), str_replace(' ', 'Z', $post->modified_gmt), xsd('dateTime'));

	foreach($post->taxonomies as $c){
		if(isset($c->taxonomy)){
			$t = $c->taxonomy;
			// if permission
			if($t == 'mksdc-permissions'){
				$action = action($c->slug);
				$px = bn();
				$triples[] = u_p_b($p, odrl('permission'), $px);
				$triples[] = b_p_u($px, rdf('type'), odrl('Permission'));
				$triples[] = b_p_u($px, odrl('action'), $action);
			}else 
			// if requirement
			if($t == 'mksdc-requirements'){
				$action = action($c->slug);
				$px = bn();
				$triples[] = u_p_b($p, odrl('requirement'), $px);
				$triples[] = b_p_u($px, rdf('type'), odrl('Requirement'));
				$triples[] = b_p_u($px, odrl('action'), $action);
			}else			
			// if prohibition
			if($t == 'mksdc-prohibitions'){
				$action = action($c->slug);
				$px = bn();
				$triples[] = u_p_b($p, odrl('prohibition'), $px);
				$triples[] = b_p_u($px, rdf('type'), odrl('Prohibition'));
				$triples[] = b_p_u($px, odrl('action'), $action);
			}else
			// if scope
			if($t == 'mksdc-policy-scope'){
				$scope = scope($c->slug);
				$triples[] = u_p_u($p, _scope(), $scope);
			}		
		}
	}
	
	// and we serialize the result
	return ntriples($triples);
}


function translate( $post){
	if($post->type === 'mksdc-datasets'){
		return translate_dataset($post);
	}else if($post->type === 'mksdc-policies'){
		return translate_policy($post);
	}else{
		throw new Exception("Unsupported type: " . $post->type);
	}
}