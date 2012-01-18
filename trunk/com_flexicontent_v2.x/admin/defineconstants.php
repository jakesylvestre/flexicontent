<?php
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

// Joomla version variables
if (!defined('FLEXI_J16GE'))			define('FLEXI_J16GE'			, 1 );
//jimport( 'joomla.version' );  $jversion = new JVersion;
//define('FLEXI_J16GE', version_compare( $jversion->getShortVersion(), '1.6.0', 'ge' ) );

// Set file manager paths
$params =& JComponentHelper::getParams('com_flexicontent');
define('COM_FLEXICONTENT_FILEPATH',    JPATH_ROOT.DS.$params->get('file_path', 'components/com_flexicontent/uploads'));
define('COM_FLEXICONTENT_MEDIAPATH',   JPATH_ROOT.DS.$params->get('media_path', 'components/com_flexicontent/medias'));

// Set the media manager paths definitions
$view = JRequest::getCmd('view',null);
$popup_upload = JRequest::getCmd('pop_up',null);
$path = "fleximedia_path";
if(substr(strtolower($view),0,6) == "images" || $popup_upload == 1) $path = "image_path";
define('COM_FLEXIMEDIA_BASE',    JPath::clean(JPATH_ROOT.DS.$params->get($path, 'images'.DS.'stories')));
define('COM_FLEXIMEDIA_BASEURL', JURI::root().$params->get($path, 'images/stories'));

// J1.5 Section or J1.7 category type
if (!FLEXI_J16GE) {
	if (!defined('FLEXI_SECTION'))				define('FLEXI_SECTION', $params->get('flexi_section'));
	if (!defined('FLEXI_CAT_EXTENSION'))	define('FLEXI_CAT_EXTENSION', '');
} else {
	if (!defined('FLEXI_SECTION'))				define('FLEXI_SECTION', 0);
	if (!defined('FLEXI_CAT_EXTENSION')) {
		define('FLEXI_CAT_EXTENSION', $params->get('flexi_cat_extension','com_content'));
		$db = &JFactory::getDBO();
		$query = "SELECT lft,rgt FROM #__categories WHERE id=1 ";
		$db->setQuery($query);
		$obj = $db->loadObject();
		if (!defined('FLEXI_LFT_CATEGORY'))	define('FLEXI_LFT_CATEGORY', $obj->lft);
		if (!defined('FLEXI_RGT_CATEGORY'))	define('FLEXI_RGT_CATEGORY', $obj->rgt);
	}
}

// Define configuration constants
if (!defined('FLEXI_ACCESS')) 		define('FLEXI_ACCESS'			, (JPluginHelper::isEnabled('system', 'flexiaccess') && version_compare(PHP_VERSION, '5.0.0', '>')) ? 1 : 0);
if (!defined('FLEXI_CACHE')) 			define('FLEXI_CACHE'			, $params->get('advcache', 1));
if (!defined('FLEXI_CACHE_TIME'))	define('FLEXI_CACHE_TIME'	, $params->get('advcache_time', 3600));
if (!defined('FLEXI_GC'))					define('FLEXI_GC'					, $params->get('purge_gc', 1));
if (!defined('FLEXI_FISH'))				define('FLEXI_FISH'				, ($params->get('flexi_fish', 0) && (JPluginHelper::isEnabled('system', 'jfdatabase'))) ? 1 : 0);
if (!defined('FLEXI_ONDEMAND'))		define('FLEXI_ONDEMAND'		, 0 );
if (!defined('FLEXI_ITEMVIEW'))		define('FLEXI_ITEMVIEW'		, 'item' );

// Version constants
define('FLEXI_VERSION',	'2.0');
define('FLEXI_RELEASE',	'preRC3 (r1110)');
?>