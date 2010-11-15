<?php
/**
* @version		$Id: flexicontent_categories.php 171 2010-03-20 00:44:02Z emmanuel.danan $
* @package		Joomla.Framework
* @subpackage	Table
* @copyright	Copyright (C) 2005 - 2008 Open Source Matters. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
* Joomla! is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/

// Check to ensure this file is within the rest of the framework
defined('_JEXEC') or die('Restricted access');

/**
 * Category table
 *
 * @package 	Joomla.Framework
 * @subpackage	Table
 * @since		1.0
 */jimport('joomla.database.tablenested');
class flexicontent_categories extends JTableNested{
	/** @var int Primary key */
	var $id				= null;
	/** @var int */
	var $asset_id			= null;
	var $parent_id			= null;
	var $lft			= null;
	var $rgt			= null;
	var $level			= null;
	/** @var string */
	var $path			= null;
	var $extension=null;
	/** @var string The menu title for the category (a short name)*/
	var $title			= null;
	/** @var string The the alias for the category*/
	var $alias			= null;
	var $note			= null;
	/** @var string */
	var $description		= null;
	/** @var boolean */
	var $published			= null;
	/** @var boolean */
	var $checked_out		= 0;
	/** @var time */
	var $checked_out_time	= 0;
	/** @var int */
	//var $ordering			= null;
	/** @var int */
	var $access			= null;
	/** @var string */
	var $params			= null;
	var $metadesc			= null;
	var $metakey			= null;
	var $metadata			= null;
	var $created_user_id		= null;
	var $created_time		= null;
	var $modified_user_id		= null;
	var $modified_time		= null;
	var $hits			= null;
	var $language			= null;

	/**
	* @param database A database connector object
	*/
	function flexicontent_categories(& $db) {
		parent::__construct('#__categories', 'id', $db);
	}
	
	/**
	 * Method to compute the default name of the asset.
	 * The default name is in the form `table_name.id`
	 * where id is the value of the primary key of the table.
	 *
	 * @return	string
	 * @since	1.6
	 */
	protected function _getAssetName() {
		$k = $this->_tbl_key;
		return 'flexicontent.category.'.(int) $this->$k;
	}

	/**
	 * Overloaded check function
	 *
	 * @access public
	 * @return boolean
	 * @see JTable::check
	 * @since 1.5
	 */
	function check() {
		// check for valid name
		if (trim( $this->title ) == '') {
			$this->setError(JText::sprintf( 'must contain a title', JText::_( 'FLEXI_Category' ) ));
			return false;
		}

		/*// check for existing name
		$query = 'SELECT id'
			. ' FROM #__categories '
			. ' WHERE title = '.$this->_db->Quote($this->title)
			. ' AND lft>=' . FLEXI_CATEGORY_LFT . ' AND rgt<=' . FLEXI_CATEGORY_RGT
			;
		$this->_db->setQuery( $query );
		$xid = intval( $this->_db->loadResult() );
		if ($xid && ($xid != intval( $this->id )) ) {
			$this->_error = JText::sprintf( 'WARNNAMETRYAGAIN', JText::_( 'FLEXI_Category' ) );
			return false;
		}*/

		if(empty($this->alias)) {
			$this->alias = $this->title;
		}
		$this->alias = JFilterOutput::stringURLSafe($this->alias);
		if(trim(str_replace('-','',$this->alias)) == '') {
			$datenow =& JFactory::getDate();
			$this->alias = $datenow->toFormat("%Y-%m-%d-%H-%M-%S");
		}
		return true;
	}
}
