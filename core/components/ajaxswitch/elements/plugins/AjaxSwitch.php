<?php
/**
 *
 * AjaxSwitch
 *
 * A MODX plugin that processes ajax and non-ajax requests
 *
 * @ author chsmedien, Christian Seel
 * @ version 0.2.1 - Feb 27, 2013
 * 
 * SYSTEM EVENTS:
 *	 OnWebPagePrerender
 *
**/

$parseJsonFields = $modx->getOption('ajaxmanager.parse_json_fields', $scriptProperties, true);
$maxIterations= (integer) $modx->getOption('parser_max_iterations', null, 10);
$eventName = $modx->event->name;

switch($eventName) {

	case 'OnWebPagePrerender':
		switch($_GET['ajax']) {
		
			// ajax request
			case 'true':
				$resourceCache = $modx->cacheManager->getCacheProvider($modx->getOption('cache_resource_key', null, 'resource'));
				$cacheKey = $modx->resource->getCacheKey() . '/ajaxmanager/' . $modx->resource->get('id') . '.ajax';
				
				// check if resource is cacheable
				if ($modx->resource->get('cacheable')) {
					// get $fields from cache
					$fields = $resourceCache->get($cacheKey);
				}
				
				if ($fields) { // cached version available
				
					$fields['cached'] = 'true';
				
				} else { // no cached version available
				
					//$fields = $modx->resource->toArray();
					$fields = array();
					$fields['sitetitle'] = $modx->resource->getTVValue('sitetitle');
					$fields['uri'] = $modx->resource->get('uri');
					
					// parse fields
					if ($parseJsonFields) {
						foreach ($fields as $key => $value) {
							// Parse all cached tags
							$modx->parser->processElementTags('', $fields[$key], false, false, '[[', ']]', array(), $maxIterations);
						}
					}
									
					// put $fields in the modx cache
					$resourceCache->set($cacheKey,$fields,0);
					
					// add "uncached" value, since current output is uncached
					$fields['cached'] = 'false';
				}
			   
				// Parse uncached tags in all fields and content
				if ($parseJsonFields) {
					foreach ($fields as $key => $value) {
						$modx->parser->processElementTags('', $fields[$key], true, true, '[[', ']]', array(), $maxIterations);
					}
				}
				
				$fields['content'] = $modx->resource->_output;
						
				$mtime= microtime();
				$mtime= explode(" ", $mtime);
				$mtime= $mtime[1] + $mtime[0];
				$tsum= round(($mtime - $modx->startTime) * 1000, 0) . " ms";
				
				$fields['content-render-time'] = $tsum;

				$modx->resource->_output = $modx->toJSON($fields);
			break;
			
			
			
			// non-ajax request
			default:
				
				// get resource output
				$output = &$modx->resource->_output;
				
				// get template wrapper name from TV
				$templateWrapper = $modx->resource->getTVValue('templateWrapper');
				if ($templateWrapper == "") return;
				
				$output = $modx->parseChunk($templateWrapper,array('resource.content' => $output));
 
				// parse all non-cacheable and remove unprocessed tags
				$modx->getParser()->processElementTags('', $output, true, true, '[[', ']]', array(), $maxIterations);
			
		}
	break;
}