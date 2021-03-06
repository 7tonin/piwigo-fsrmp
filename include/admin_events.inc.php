<?php
defined('FSRMP_PATH') or die('Hacking attempt!');

/**
 * admin plugins menu link
 */
function fsrmp_get_admin_plugin_menu_links($menu)
{
  $menu[] = array(
    'NAME' => l10n('Fsrmp'),
    'URL' => FSRMP_ADMIN,
    );

  return $menu;
}

function fsrmp_get_batch_manager_prefilters($prefilters)
{
	global $conf;
  
	if (in_array('f1', $conf['fsrmp']['enabled_filters'])) {
		$prefilters[] = array(
			'ID' => 'fsrmp1', 
			'NAME' => l10n('Modified since less than %d %s', $conf['fsrmp']['nb1'], l10n($conf['fsrmp']['unit1']).((1<$conf['fsrmp']['nb1'])?'s':'') )
		);
	}
	if (in_array('f2', $conf['fsrmp']['enabled_filters'])) {
		$prefilters[] = array(
			'ID' => 'fsrmp2', 
			'NAME' => l10n('Modified since less than %d %s', $conf['fsrmp']['nb2'], l10n($conf['fsrmp']['unit2']).((1<$conf['fsrmp']['nb2'])?'s':'') )
		);
	}
	if (in_array('f3', $conf['fsrmp']['enabled_filters'])) {
		$prefilters[] = array(
			'ID' => 'fsrmp3', 
			'NAME' => l10n('Modified since previous bm.metadata (%d files), since less than about %d %s', 
				$conf['fsrmp']['batch_manager_metadata']['pictures_nb'],
				fsrmp_duration(), 
				l10n('mmin').'s'
			)
		);
	}
	return $prefilters;
}

function fsrmp_perform_batch_manager_prefilters($filter_sets, $prefilter)
{
	global $conf;
  
	if ($prefilter==="fsrmp1") {
// 			$filter = "-mmin -60";
		$filter = sprintf('-%s -%d', $conf['fsrmp']['unit1'], $conf['fsrmp']['nb1']);
	}
	else if ($prefilter==="fsrmp2") {
// 			$filter = "-mtime -1";
		$filter = sprintf('-%s -%d', $conf['fsrmp']['unit2'], $conf['fsrmp']['nb2']);
	}
	else if ($prefilter==="fsrmp3") {
// 			$filter = "-mmin -16573";
		$filter = sprintf('-%s -%d', 'mmin', fsrmp_duration());
	}
		
	if(isset($filter)) {
		// Looking into galleries/ dir
		$cmd = 'find -L '.PHPWG_ROOT_PATH.'galleries'.' -type f '.$filter.'' ;
		if(exec($cmd, $output)) {
			$list_g = $output ;
		}
		else {
			$list_g = array() ;
		}
		
		// Looking into upload/ dir
		$cmd = 'find -L '.PHPWG_ROOT_PATH.'upload'.' -type f '.$filter.'' ;
		if(exec($cmd, $output)) {
			$list_u = $output ;
		}
		else {
			$list_u = array() ;
		}
		
		$list = array_merge($list_g, $list_u);
		
		if ( !empty($list) )
		{
            $in_list = "('".implode("', '", $list)."')" ;
			$query = "SELECT id FROM ".IMAGES_TABLE." WHERE path IN ".$in_list;
			$filter_sets[] = array_from_query($query, 'id');
		}
	}
	return $filter_sets;
}

/*
string $action : 'metadata'
array $collection : array (0 => '94094', 1 => '92953', ... , 18 => '64904', 19 => '64903', ),
*/
function fsrmp_element_set_global_action($action, $collection) {
	global $conf;

	if('metadata'==$action) {
		$conf['fsrmp']['batch_manager_metadata']['latest'] = time();
		$conf['fsrmp']['batch_manager_metadata']['pictures_nb'] = count($collection);
		conf_update_param('fsrmp', $conf['fsrmp']);
	}
}

/*
Returns duration since latest bm.metadata update. Default unit is minute, but if 'mtime' is given it's day. 
string $interval : 'mmin' ou 'mtime' (minutes or days)
*/
function fsrmp_duration($interval = 'mmin') {
	global $conf;
	switch($interval) {
	case 'mmin' : $seconds_interval = 60 ; break ;
	case 'mtime' : $seconds_interval = 86400 ; break ;
	default : $seconds_interval = 60 ; 
	}
	// one more second to make sure no file will be forgotten
	return ceil((1+time()-$conf['fsrmp']['batch_manager_metadata']['latest'])/$seconds_interval) ;
}

/*
Detects if the file is a pwg_representative (of a video)
Try to return represented file path (video file)
string $file : '/absolute/path/to/file'
*/
function fsrmp_pwg_representative_to_original($file) {
	global $conf;
	if('pwg_representative' == basename(dirname($file))) {
		$original = pathinfo($file, PATHINFO_FILENAME) ;
		$original_path = dirname(dirname($file)) ;
		foreach($conf['file_ext'] as $ext) {
			if (file_exists($original_path.'/'.$original.'.'.$ext)) {
				return $original_path.'/'.$original.'.'.$ext ;
			}
		}
	}
	return $file ;
}
