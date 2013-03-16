<?php
/**
 * @version 1.5 stable $Id$
 * @package Joomla
 * @subpackage FLEXIcontent
 * @copyright (C) 2009 Emmanuel Danan - www.vistamedia.fr
 * @license GNU/GPL v2
 * 
 * FLEXIcontent is a derivative work of the excellent QuickFAQ component
 * @copyright (C) 2008 Christoph Lukes
 * see www.schlu.net for more information
 *
 * FLEXIcontent is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport('joomla.application.component.model');

/**
 * FLEXIcontent Component Model
 *
 * @package Joomla
 * @subpackage FLEXIcontent
 * @since		1.0
 */
class FlexicontentModelTags extends JModelLegacy
{
	/**
	 * Current Tag properties
	 *
	 * @var mixed
	 */
	var $_tag = null;
	
	/**
	 * Item list data
	 *
	 * @var mixed
	 */
	var $_data = null;
	
	/**
	 * Items list total
	 *
	 * @var integer
	 */
	var $_total = null;
	
	/**
	 * Tags view parameters via menu item or via tag cloud module or ... via global configuration selected menu item
	 *
	 * @var object
	 */
	var $_params = null;
	
	/**
	 * Constructor
	 *
	 * @since 1.0
	 */
	function __construct()
	{
		parent::__construct();
		
		// Set id and load parameters
		$id = JRequest::getInt('id', 0);		
		$this->setId((int)$id);
		
		$params = & $this->_params;
		
		// Set the pagination variables into state (We get them from http request OR use default tags view parameters)
		$limit = JRequest::getVar('limit') ? JRequest::getVar('limit') : $params->get('limit');
		$limitstart = JRequest::getInt('limitstart');

		$this->setState('limit', $limit);
		$this->setState('limitstart', $limitstart);
		
		// Get the filter request variables
		$this->setState('filter_order', 'i.title');
		$this->setState('filter_order_dir', 'ASC');
	}
	
	/**
	 * Method to set the tag id
	 *
	 * @access	public
	 * @param	int	tag ID number
	 */
	function setId($id)
	{
		// Set new category ID, wipe member variables and load parameters
		$this->_id      = $id;
		$this->_tag     = null;
		$this->_data    = null;
		$this->_total   = null;
		$this->_params  = null;
		$this->_loadParams();
	}

	/**
	 * Overridden get method to get properties from the tag
	 *
	 * @access	public
	 * @param	string	$property	The name of the property
	 * @param	mixed	$value		The value of the property to set
	 * @return 	mixed 				The value of the property
	 * @since	1.5
	 */
	function get($property, $default=null)
	{
		if ( $this->_tag || $this->_tag = $this->getTag() ) {
			if(isset($this->_tag->$property)) {
				return $this->_tag->$property;
			}
		}
		return $default;
	}
	
	/**
	 * Overridden set method to pass properties on to the tag
	 *
	 * @access	public
	 * @param	string	$property	The name of the property
	 * @param	mixed	$value		The value of the property to set
	 * @return	boolean	True on success
	 * @since	1.5
	 */
	function set( $property, $value=null )
	{
		if ( $this->_tag || $this->_tag = $this->getTag() ) {
			$this->_tag->$property = $value;
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * Method to get Data
	 *
	 * @access public
	 * @return object
	 */
	function getData()
	{
		// Lets load the content if it doesn't already exist
		if (empty($this->_data))
		{		
			$query = $this->_buildQuery();
			$this->_data = $this->_getList( $query, $this->getState('limitstart'), $this->getState('limit') );
			if ( $this->_db->getErrorNum() ) {
				$jAp=& JFactory::getApplication();
				$jAp->enqueueMessage(nl2br($query."\n".$this->_db->getErrorMsg()."\n"),'error');
			}
		}
		
		return $this->_data;
	}
	
	/**
	 * Total nr of Items
	 *
	 * @access public
	 * @return integer
	 */
	function getTotal()
	{
		// Lets load the total nr if it doesn't already exist
		if (empty($this->_total))
		{
			$query = $this->_buildQuery();
			$this->_total = $this->_getListCount($query);
		}

		return $this->_total;
	}
	
	/**
	 * Method to build the query
	 *
	 * @access public
	 * @return string
	 */
	function _buildQuery()
	{   	
		$user		= & JFactory::getUser();
		$params = & $this->_params;
		
		// image for an image field
		/*$use_image    = (int)$params->get('use_image', 1);
		$image_source = $params->get('image_source');

		// EXTRA select and join for special fields: --image--
		if ($use_image && $image_source) {
			$select_image = ' img.value AS image,';
			$join_image   = '	LEFT JOIN #__flexicontent_fields_item_relations AS img'
				. '	ON ( i.id = img.item_id AND img.valueorder = 1 AND img.field_id = '.$image_source.' )';
		} else {
			$select_image	= '';
			$join_image		= '';
		}*/
		
		// show unauthorized items
		$show_noauth = $params->get('show_noauth', 0);
		
		// Select only items user has access to if he is not allowed to show unauthorized items
		if (!$show_noauth) {
			if (FLEXI_J16GE) {
				$aid_arr = $user->getAuthorisedViewLevels();
				$aid_list = implode(",", $aid_arr);
				$andaccess = ' AND i.access IN ('.$aid_list.') AND mc.access IN ('.$aid_list.')';
				$joinaccess	= '';
			} else {
				$aid = (int) $user->get('aid');
				if (FLEXI_ACCESS) {
					$joinaccess  = ' LEFT JOIN #__flexiaccess_acl AS gc ON mc.id = gc.axo AND gc.aco = "read" AND gc.axosection = "category"';
					$joinaccess .= ' LEFT JOIN #__flexiaccess_acl AS gi ON i.id = gi.axo AND gi.aco = "read" AND gi.axosection = "item"';
					$andaccess	 = ' AND (gc.aro IN ( '.$user->gmid.' ) OR mc.access <= '. $aid . ')';
					$andaccess  .= ' AND (gi.aro IN ( '.$user->gmid.' ) OR i.access <= '. $aid . ')';
				} else {
					$joinaccess	 = '';
					$andaccess   = ' AND mc.access <= '.$aid;
					$andaccess  .= ' AND i.access <= '.$aid;
				}
			}
		} else {
			$joinaccess	 = '';
			$andaccess   = '';
		}

		// Get the WHERE and ORDER BY clauses for the query
		$where		= $this->_buildItemWhere();
		$orderby	= $this->_buildItemOrderBy();
		
		// Add sort items by custom field.
		$field_item = '';
		if ($params->get('orderbycustomfieldid', 0) != 0) {
			$field_item = ' LEFT JOIN #__flexicontent_fields_item_relations AS f ON f.item_id = i.id AND f.field_id='.(int)$params->get('orderbycustomfieldid', 0);
		}

		$query = 'SELECT i.id, i.*, ie.*, '
			//.$select_image
			. ' CASE WHEN CHAR_LENGTH(i.alias) THEN CONCAT_WS(\':\', i.id, i.alias) ELSE i.id END as slug,'
			. ' CASE WHEN CHAR_LENGTH(c.alias) THEN CONCAT_WS(\':\', c.id, c.alias) ELSE c.id END as categoryslug'
			. ' FROM #__content AS i'
			. ' LEFT JOIN #__flexicontent_items_ext AS ie ON ie.item_id = i.id'
			. ' INNER JOIN #__flexicontent_tags_item_relations AS tag ON tag.itemid = i.id'
			. ' LEFT JOIN #__flexicontent_cats_item_relations AS rel ON rel.itemid = i.id'
			. ' LEFT JOIN #__categories AS c ON c.id = rel.catid'
			. ' LEFT JOIN #__categories AS mc ON mc.id = i.catid'
			. ' LEFT JOIN #__users AS u ON u.id = i.created_by'
			//. $join_image
			. $field_item
			. $joinaccess
			. $where
			. $andaccess
			. ' GROUP BY i.id'
			. $orderby
			;
		return $query;
	}
	
	
	/**
	 * Build the order clause
	 *
	 * @access private
	 * @return string
	 */
	function _buildItemOrderBy()
	{
		$request_var = $this->_params->get('orderby_override') ? 'orderby' : '';
		$default_order = $this->getState('filter_order');
		$default_order_dir = $this->getState('filter_order_dir');
		
		// Precedence: $request_var ==> $order ==> $config_param ==> $default_order
		return flexicontent_db::buildItemOrderBy(
			$this->_params,
			$order='', $request_var, $config_param='orderby',
			$item_tbl_alias = 'i', $relcat_tbl_alias = 'rel',
			$default_order, $default_order_dir
		);
	}
	
	
	/**
	 * Method to build the WHERE clause
	 *
	 * @access private
	 * @return string
	 */
	function _buildItemWhere( )
	{
		$mainframe =& JFactory::getApplication();
		$params = & $this->_params;
		$user		= & JFactory::getUser();
		$db =& JFactory::getDBO();
		
		// Date-Times are stored as UTC, we should use current UTC time to compare and not user time (requestTime),
		//  thus the items are published globally at the time the author specified in his/her local clock
		//$now		= $mainframe->get('requestTime');
		$now			= FLEXI_J16GE ? JFactory::getDate()->toSql() : JFactory::getDate()->toMySQL();
		$nullDate	= $db->getNullDate();

		// First thing we need to do is to select only the requested TAGGED items
		$where = ' WHERE tag.tid = '.$this->_id;
		
		// User current language
		$lang = flexicontent_html::getUserCurrentLang();
		$filtertag  = $params->get('filtertag', 0);
		
		// Filter the tag view with the active language
		if ((FLEXI_FISH || FLEXI_J16GE) && $filtertag) {
			$where .= ' AND ( ie.language LIKE ' . $this->_db->Quote( $lang .'%' ) . (FLEXI_J16GE ? ' OR ie.language="*" ' : '') . ' ) ';
		}
		
		// Get privilege to view non viewable items (upublished, archived, trashed, expired, scheduled).
		// NOTE:  ACL view level is checked at a different place
		if ( FLEXI_J16GE )
			$ignoreState = $user->authorise('flexicontent.ignoreviewstate', 'com_flexicontent');
		else if (FLEXI_ACCESS)
			$ignoreState = ($user->gid < 25) ? FAccess::checkComponentAccess('com_flexicontent', 'ignoreviewstate', 'users', $user->gmid) : 1;
		else
			$ignoreState = $user->gid  > 19;  // author has 19 and editor has 20
		
		if (!$ignoreState) {
			// Limit by publication state. Exception: when displaying personal user items or items modified by the user
			$where .= ' AND ( i.state IN (1, -5) OR i.created_by = '.$user->id.' )';   //.' OR ( i.modified_by = '.$user->id.' AND i.modified_by != 0 ) )';
			
			// Limit by publish up/down dates. Exception: when displaying personal user items or items modified by the user
			$where .= ' AND ( ( i.publish_up = '.$this->_db->Quote($nullDate).' OR i.publish_up <= '.$this->_db->Quote($now).' ) OR i.created_by = '.$user->id.' )';       //.' OR ( i.modified_by = '.$user->id.' AND i.modified_by != 0 ) )';
			$where .= ' AND ( ( i.publish_down = '.$this->_db->Quote($nullDate).' OR i.publish_down >= '.$this->_db->Quote($now).' ) OR i.created_by = '.$user->id.' )';   //.' OR ( i.modified_by = '.$user->id.' AND i.modified_by != 0 ) )';
		}
		
		$where .= !FLEXI_J16GE ? ' AND i.sectionid = ' . FLEXI_SECTION : '';

		/*
		 * If we have a filter, and this is enabled... lets tack the AND clause
		 * for the filter onto the WHERE clause of the item query.
		 */
		if ($params->get('use_search'))
		{
			$filter 		= JRequest::getString('filter', '', 'request');

			if ($filter)
			{
				$where .= ' AND MATCH (ie.search_index) AGAINST ('.$this->_db->Quote( $this->_db->getEscaped( $filter, true ), false ).' IN BOOLEAN MODE)';
			}
		}
		return $where;
	}
	
	
	/**
	 * Method to load parameters
	 *
	 * @access	private
	 * @return	void
	 * @since	1.5
	 */
	function _loadParams()
	{
		if (!empty($this->_params)) return;
		
		$mainframe =& JFactory::getApplication();

		// Retrieve menu parameters
		$menu = JSite::getMenu()->getActive();
		if ($menu) {
			$menuParams = FLEXI_J16GE ? new JRegistry($menu->params) : new JParameter($menu->params);
		}
		
		// a. Get the COMPONENT only parameters, NOTE: we will merge the menu parameters later selectively
		$flexi = JComponentHelper::getComponent('com_flexicontent');
		$params = FLEXI_J16GE ? new JRegistry($flexi->params) : new JParameter($flexi->params);
		
		// Merge current menu item (could be tags specific or the Globally Configured Default Tag Menu Item)
		$params->merge($menuParams);
		
		/*
		// a. Get the PAGE/COMPONENT parameters (WARNING: merges current menu item parameters in J1.5 but not in J1.6+)
		$params = clone($mainframe->getParams('com_flexicontent'));
		
		// In J1.6+ the above function does not merge current menu item parameters, it behaves like JComponentHelper::getParams('com_flexicontent') was called
		if (FLEXI_J16GE && $menu) {
			$params->merge($menuParams);
		}
		*/
		
		// IF module id is in the HTTP request then get this (tags cloud) module configuration and override current configuration
		if ( JRequest::getInt('module', 0 ) )
		{
			// Higher Priority: prefer module configuration requested in URL
			jimport( 'joomla.application.module.helper' );
			jimport( 'joomla.html.parameter' );
			$params = FLEXI_J16GE ? new JRegistry() : new JParameter('');
			
			// load by module name, not used
			//$module_name = JRequest::getInt('module', 0 );
			//$module = & JModuleHelper::getModule('mymodulename');
			
			// load by module id
			$module_id = JRequest::getInt('module', 0 );
			$module = & JTable::getInstance ( 'Module', 'JTable' );
			
			if ( !$module->load($module_id) ) {
				JError::raiseNotice ( 500, $module->getError() );
			} else {
				$moduleParams = FLEXI_J16GE ? new JRegistry($module->params) : new JParameter($module->params);
			}
			$params->merge($moduleParams);
		}
		
		$this->_params = & $params;
	}
	
	
	/**
	 * Method to load the Tag data
	 *
	 * @access public
	 * @return object
	 */
	function getTag()
	{
		//get categories
		$query = 'SELECT t.name, t.id,'
				. ' CASE WHEN CHAR_LENGTH(t.alias) THEN CONCAT_WS(\':\', t.id, t.alias) ELSE t.id END as slug'
				. ' FROM #__flexicontent_tags AS t'
				. ' WHERE t.id = '.(int)$this->_id
				. ' AND t.published = 1'
				;

		$this->_db->setQuery($query);
		$this->_tag = $this->_db->loadObject();       // Execute query to load tag properties
		if ( $this->_tag ) {
			$this->_tag->parameters = & $this->_params;   // Assign tag parameters ( already load by setId() )
    }
    
		return $this->_tag;
	}
	
		
	/**
	 * Method to store the tag
	 *
	 * @access	public
	 * @return	boolean	True on success
	 * @since	1.0
	 */
	function storetag($data)
	{
		$row  =& $this->getTable('flexicontent_tags', '');

		// bind it to the table
		if (!$row->bind($data)) {
			$msg = $this->_db->getErrorMsg();
			if (FLEXI_J16GE) throw new Exception($msg, 500); else JError::raiseError(500, $msg);
		}

		// Make sure the data is valid
		if (!$row->check()) {
			$this->setError($row->getError());
			return false;
		}

		// Store it in the db
		if (!$row->store()) {
			$msg = $this->_db->getErrorMsg();
			if (FLEXI_J16GE) throw new Exception($msg, 500); else JError::raiseError(500, $msg);
		}
		$this->_tag = &$row;
		return $row->id;
	}
	
	/**
	 * Method to add a tag
	 *
	 * @access	public
	 * @return	boolean	True on success
	 * @since	1.0
	 */
	function addtag($name) {
		$obj = new stdClass();
		$obj->name	 	= $name;
		$obj->published	= 1;

		if($this->storetag($obj)) {
			return true;
		}
		return false;
	}
}
?>