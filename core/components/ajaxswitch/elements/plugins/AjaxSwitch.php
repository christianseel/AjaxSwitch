<?php
/**
 *
 * AjaxSwitch
 *
 * A MODX plugin that processes ajax and non-ajax requests
 *
 * @ author chsmedien, Christian Seel
 * @ version 0.2.4 - Aug 21, 2013
 * 
 * SYSTEM EVENTS:
 *	 OnWebPagePrerender
 *
**/

$processJsonFields = $modx->getOption('ajaxswitch.process_json_fields', $scriptProperties, true);
$addResourceFields = $modx->getOption('ajaxswitch.add_resource_fields', $scriptProperties, 'pagetitle,longtitle,uri');
$maxIterations= (integer) $modx->getOption('parser_max_iterations', null, 10);
$modxResourceFields = array("id","type","contentType","pagetitle","longtitle","description","alias","link_attributes","published","pub_date","unpub_date","parent","isfolder","introtext","content","richtext","template","menuindex","searchable","cacheable","createdby","createdon","editedby","editedon","deleted","deletedon","deletedby","publishedon","publishedby","menutitle","donthit","privateweb","privatemgr","content_dispo","hidemenu","class_key","context_key","content_type","uri","uri_override","hide_children_in_tree","show_in_tree","properties");

$eventName = $modx->event->name;

switch($eventName) {

	case 'OnWebPagePrerender':
		
		// check for ajax request (most ajax frameworks like jQuery and mooTools add a specific header)
		if ($_GET['ajax'] == 1 || strcasecmp('XMLHttpRequest', $_SERVER['HTTP_X_REQUESTED_WITH']) === 0) {
		// ajax request
		
			$resourceCache = $modx->cacheManager->getCacheProvider($modx->getOption('cache_resource_key', null, 'resource'));
			$cacheKey = $modx->resource->getCacheKey() . '/ajaxswitch/' . $modx->resource->get('id') . '.' . md5($addResourceFields) .  '.json';
			
			// check if resource is cacheable
			if ($modx->resource->get('cacheable')) {
				// get $fields from cache
				$fields = $resourceCache->get($cacheKey);
			}
			
			if (!$fields) { // no cached version available
				
				$fields = array();
				$resourceArray = $modx->resource->toArray();
				
				$addResourceFields = explode(',',$addResourceFields);

				foreach($addResourceFields as $key => $value) {
					if (in_array($value, $modxResourceFields)) {
						$fields[$value] = $resourceArray[$value];
					} else {
						// seems like we're looking for a TV
						$fields[$value] = $modx->resource->getTVValue($value);
					}
				}
				
				// delete content field - we're adding it later manually
				unset($fields['content']);
				
				// parse fields
				if ($processJsonFields) {
					foreach ($fields as $key => $value) {
						// Parse all cached tags
						$modx->parser->processElementTags('', $fields[$key], false, false, '[[', ']]', array(), $maxIterations);
					}
				}
				
				if ($modx->resource->get('cacheable')) {				
					// put $fields in the modx cache
					$resourceCache->set($cacheKey,$fields,0);
				}
				
				// add "uncached" value, since current output is uncached
				$fields['cached'] = 'false';
				
			} else { // cached version available
				$fields['cached'] = 'true';
			}
			
			// Parse uncached tags in all fields and content
			if ($processJsonFields) {
				foreach ($fields as $key => $value) {
					$modx->parser->processElementTags('', $fields[$key], true, true, '[[', ']]', array(), $maxIterations);
				}
			}
			
			$fields['content'] = $modx->resource->_output;
					
			$endtime= microtime();
			$endtime= explode(" ", $endtime);
			$endtime= $endtime[1] + $endtime[0];
			$tsum= round(($endtime - $modx->startTime) * 1000, 0) . " ms";
			
			$fields['content-render-time'] = $tsum;

			$modx->resource->_output = $modx->toJSON($fields);
		
		
		
		} else {
		// non-ajax request
				
			// get resource output
			$output = &$modx->resource->_output;
			
			// get template wrapper name from TV
			$templateWrapper = $modx->resource->getTVValue('ajaxswitch.templateWrapper');
			if ($templateWrapper == "") return;
			
			$output = $modx->parseChunk($templateWrapper,array('resource.content' => $output));

			// parse all non-cacheable and remove unprocessed tags
			$modx->getParser()->processElementTags('', $output, true, true, '[[', ']]', array(), $maxIterations);
		
		}
	break;
}