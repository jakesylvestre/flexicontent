<?php
/**
 * @version 1.5 stable $Id: controller.php 1384 2012-07-15 13:10:51Z ggppdk $
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

defined( '_JEXEC' ) or die( 'Restricted access' );

jimport('joomla.application.component.controller');

/**
 * FLEXIcontent Component Controller
 *
 * @package Joomla
 * @subpackage FLEXIcontent
 * @since 1.0
 */
class FlexicontentController extends JControllerLegacy
{
	/**
	 * Constructor
	 *
	 * @since 1.0
	 */
	function __construct()
	{
		parent::__construct();
		
		// Register Extra task
		$this->registerTask( 'save_a_preview', 'save');
		$this->registerTask( 'apply', 'save');
	}
	
	
	/**
	 * Logic to create SEF urls via AJAX requests
	 *
	 * @access public
	 * @return void
	 * @since 1.0
	 */
	function getsefurl() {
		$view = JRequest::getVar('view');
		if ($view=='category') {
			$cid = (int) JRequest::getVar('cid');
			if ($cid) {
				$db = JFactory::getDBO();
				$query 	= 'SELECT CASE WHEN CHAR_LENGTH(c.alias) THEN CONCAT_WS(\':\', c.id, c.alias) ELSE c.id END as categoryslug'
					.' FROM #__categories AS c WHERE c.id = '.$cid;
				$db->setQuery( $query );
				$categoryslug = $db->loadResult();
				echo JRoute::_(FlexicontentHelperRoute::getCategoryRoute($categoryslug), false);
			}
		}
		exit;
	}
	
	
	/**
	 * Logic to save an item
	 *
	 * @access public
	 * @return void
	 * @since 1.0
	 */
	function save()
	{
		// Check for request forgeries
		JRequest::checkToken() or jexit( 'Invalid Token' );
		
		// Initialize variables
		$app     = JFactory::getApplication();
		$db      = JFactory::getDBO();
		$user    = JFactory::getUser();
		$menu    = JSite::getMenu()->getActive();
		$config  = JFactory::getConfig();
		$session = JFactory::getSession();
		$task	   = JRequest::getVar('task');
		$model   = $this->getModel(FLEXI_ITEMVIEW);
		$ctrl_task = FLEXI_J16GE ? 'task=items.' : 'controller=items&task=';
		$dolog = JComponentHelper::getParams( 'com_flexicontent' )->get('print_logging_info');
		
		// Get the COMPONENT only parameters and merge current menu item parameters
		$params = clone( JComponentHelper::getParams('com_flexicontent') );
		if ($menu) {
			$menu_params = FLEXI_J16GE ? $menu->params : new JParameter($menu->params);
			$params->merge($menu_params);
		}
		
		// Merge the type parameters
		$tparams = $model->getTypeparams();
		$tparams = FLEXI_J16GE ? new JRegistry($tparams) : new JParameter($tparams);
		$params->merge($tparams);
		
		// Get needed parameters
		$submit_redirect_url_fe = $params->get('submit_redirect_url_fe', '');
		$allowunauthorize       = $params->get('allowunauthorize', 0);
		
		// Get data from request and validate them
		if (FLEXI_J16GE) {
			// Retrieve form data these are subject to basic filtering
			$data   = JRequest::getVar('jform', array(), 'post', 'array');   // Core Fields and and item Parameters
			$custom = JRequest::getVar('custom', array(), 'post', 'array');  // Custom Fields
			$jfdata = JRequest::getVar('jfdata', array(), 'post', 'array');  // Joomfish Data
			
			// Validate Form data for core fields and for parameters
			$model->setId((int) $data['id']);   // Set data id into model in case some function tries to get a property and item gets loaded
			$form = $model->getForm();          // Do not pass any data we only want the form object in order to validate the data and not create a filled-in form
			$post = $model->validate($form, $data);
			if (!$post) JError::raiseWarning( 500, "Error while validating data: " . $model->getError() );
			
			// Some values need to be assigned after validation
			$post['attribs'] = @$data['attribs'];  // Workaround for item's template parameters being clear by validation since they are not present in item.xml
			$post['custom']  = & $custom;          // Assign array of custom field values, they are in the 'custom' form array instead of jform
			$post['jfdata']  = & $jfdata;          // Assign array of Joomfish field values, they are in the 'jfdata' form array instead of jform
		} else {
			// Retrieve form data these are subject to basic filtering
			$post = JRequest::get( 'post' );  // Core & Custom Fields and item Parameters
			
			// Some values need to be assigned after validation
			$post['text'] = JRequest::getVar( 'text', '', 'post', 'string', JREQUEST_ALLOWRAW ); // Workaround for allowing raw text field
		}
		
		// USEFULL FOR DEBUGING for J2.5 (do not remove commented code)
		//$diff_arr = array_diff_assoc ( $data, $post);
		//echo "<pre>"; print_r($diff_arr); exit();
		
		// PERFORM ACCESS CHECKS, NOTE: we need to check access again,
		// despite having checked them on edit form load, because user may have tampered with the form ... 
		$isnew = ((int) $post['id'] < 1);
		
		
		// Calculate user's privileges on current content item
		if(!$isnew) {
			if (FLEXI_J16GE) {
				$asset = 'com_content.article.' . $model->get('id');
				$canPublish = $user->authorise('core.edit.state', $asset) || ($user->authorise('core.edit.state.own', $asset) && $model->get('created_by') == $user->get('id'));
				$canEdit = $user->authorise('core.edit', $asset) || ($user->authorise('core.edit.own', $asset) && $model->get('created_by') == $user->get('id'));
				// ALTERNATIVE 1
				//$canEdit = $model->getItemAccess()->get('access-edit'); // includes privileges edit and edit-own
				// ALTERNATIVE 2
				//$rights = FlexicontentHelperPerm::checkAllItemAccess($user->get('id'), 'item', $model->get('id'));
				//$canEdit = in_array('edit', $rights) || (in_array('edit.own', $rights) && $model->get('created_by') == $user->get('id')) ;
			} else if ($user->gid >= 25) {
				$canPublish = true;
				$canEdit = true;
			} else if (FLEXI_ACCESS) {
				$rights 	= FAccess::checkAllItemAccess('com_content', 'users', $user->gmid, $model->get('id'), $model->get('catid'));
				$canPublish = in_array('publish', $rights) || (in_array('publishown', $rights) && $model->get('created_by') == $user->get('id')) ;
				$canEdit = in_array('edit', $rights) || (in_array('editown', $rights) && $model->get('created_by') == $user->get('id')) ;
			} else {
				$canPublish = $user->authorize('com_content', 'publish', 'content', 'all');
				$canEdit = $user->authorize('com_content', 'edit', 'content', 'all') || ($user->authorize('com_content', 'edit', 'content', 'own') && $model->get('created_by') == $user->get('id'));
				//$canPublish = ($user->gid >= 21);  // At least J1.5 Publisher
				//$canEdit = ($user->gid >= 20);  // At least J1.5 Editor
			}
			
			// Check if item is editable till logoff
			if ($session->has('rendered_uneditable', 'flexicontent')) {
				$rendered_uneditable = $session->get('rendered_uneditable', array(),'flexicontent');
				$canEdit = isset($rendered_uneditable[$model->get('id')]);
			}

		} else {
			if (FLEXI_J16GE) {
				$canAdd	= $user->authorize('core.create', 'com_flexicontent') && count( FlexicontentHelperPerm::getAllowedCats($user, array('core.create')) );
				// ALTERNATIVE 1
				//$canAdd = $model->getItemAccess()->get('access-create'); // includes check of creating in at least one category
				$not_authorised = !$canAdd;
				
				$canPublish	= $user->authorise('core.edit.state', 'com_flexicontent') || $user->authorise('core.edit.state.own', 'com_flexicontent');
			} else if ($user->gid >= 25) {
				$canAdd = 1;
			} else if (FLEXI_ACCESS) {
				$canAdd = FAccess::checkUserElementsAccess($user->gmid, 'submit');
				$canAdd = @$canAdd['content'] || @$canAdd['category'];
				
				$canPublishAll 		= FAccess::checkAllContentAccess('com_content','publish','users',$user->gmid,'content','all');
				$canPublishOwnAll	= FAccess::checkAllContentAccess('com_content','publishown','users',$user->gmid,'content','all');
				$canPublish	= ($user->gid < 25) ? $canPublishAll || $canPublishOwnAll : 1;
			} else {
				$canAdd	= $user->authorize('com_content', 'add', 'content', 'all');
				//$canAdd = ($user->gid >= 19);  // At least J1.5 Author
				$not_authorised = ! $canAdd;
				$canPublish	= ($user->gid >= 21);
			}
			if ( $allowunauthorize ) $canAdd = true;
		}
		
		// ... we use some strings from administrator part
		// load english language file for 'com_flexicontent' component then override with current language file
		JFactory::getLanguage()->load('com_flexicontent', JPATH_ADMINISTRATOR, 'en-GB', true);
		JFactory::getLanguage()->load('com_flexicontent', JPATH_ADMINISTRATOR, null, true);
		
		// Check for new content
		if ( ($isnew && !$canAdd) || (!$isnew && !$canEdit)) {
			$msg = JText::_( 'FLEXI_ALERTNOTAUTH' );
			if (FLEXI_J16GE) throw new Exception($msg, 403); else JError::raiseError(403, $msg);
		}
		
		
		// Get "BEFORE SAVE" categories for information mail
		$before_cats = array();
		if ( !$isnew )
		{
			$query 	= 'SELECT DISTINCT c.id, c.title FROM #__categories AS c'
				. ' LEFT JOIN #__flexicontent_cats_item_relations AS rel ON rel.catid = c.id'
				. ' WHERE rel.itemid = '.(int) $model->get('id');
			$db->setQuery( $query );
			$before_cats = $db->loadObjectList('id');
			$before_maincat = $model->get('catid');
		}
		$before_state = $model->get('state');
		
		
		// ****************************************
		// Try to store the form data into the item
		// ****************************************
		if ( ! $model->store($post) )
		{
			// Set error message about saving failed, and also the reason (=model's error message)
			$msg = JText::_( 'FLEXI_ERROR_STORING_ITEM' );
			JError::raiseWarning( 500, $msg .": " . $model->getError() );

			// Since an error occured, check if (a) the item is new and (b) was not created
			if ($isnew && !$model->get('id')) {
				$msg = '';
				$link = 'index.php?option=com_flexicontent&'.$ctrl_task.'add&id=0&typeid='.$post['type_id'].'&'. (FLEXI_J30GE ? JSession::getFormToken() : JUtility::getToken()) .'=1';
				$this->setRedirect($link, $msg);
			} else {
				$msg = '';
				$link = 'index.php?option=com_flexicontent&'.$ctrl_task.'edit&id='.$model->get('id').'&'. (FLEXI_J30GE ? JSession::getFormToken() : JUtility::getToken()) .'=1';
				$this->setRedirect($link, $msg);
			}
			
			// Saving has failed check-in and return, (above redirection will be used)
			$model->checkin();
			return;
		}
		
		
		// **************************************************
		// Check in model and get item id in case of new item
		// **************************************************
		$model->checkin();
		$post['id'] = $isnew ? (int) $model->get('id') : $post['id'];
		if ($isnew) {
			// Mark item as newly submitted, to allow to a proper "THANKS" message after final save & close operation (since user may have clicked add instead of add & close)
			$newly_submitted	= $session->get('newly_submitted', array(), 'flexicontent');
			$newly_submitted[$model->get('id')] = 1;
			$session->set('newly_submitted', $newly_submitted, 'flexicontent');
		}
		
		
		// ********************************************************************************************************************
		// First force reloading the item to make sure data are current, get a reference to it, and calculate publish privelege
		// ********************************************************************************************************************
		$item = $model->getItem($post['id'], $check_view_access=false, $no_cache=true);
		$canPublish = $model->canEditState( $item, $check_cat_perm=true );
		
		
		// ********************************************************************************************
		// Use session to detect multiple item saves to avoid sending notification EMAIL multiple times
		// ********************************************************************************************
		$is_first_save = true;
		if ($session->has('saved_fcitems', 'flexicontent')) {
			$saved_fcitems = $session->get('saved_fcitems', array(), 'flexicontent');
			$is_first_save = $isnew ? true : !isset($saved_fcitems[$model->get('id')]);
		}
		// Add item to saved items of the corresponding session array
		$saved_fcitems[$model->get('id')] = $timestamp = time();  // Current time as seconds since Unix epoc;
		$session->set('saved_fcitems', $saved_fcitems, 'flexicontent');
		
		
		// ********************************************
		// Get categories added / removed from the item
		// ********************************************
		$query 	= 'SELECT DISTINCT c.id, c.title FROM #__categories AS c'
			. ' LEFT JOIN #__flexicontent_cats_item_relations AS rel ON rel.catid = c.id'
			. ' WHERE rel.itemid = '.(int) $model->get('id');
		$db->setQuery( $query );
		$after_cats = $db->loadObjectList('id');
		if ( !$isnew ) {
			$cats_added_ids = array_diff(array_keys($after_cats), array_keys($before_cats));
			foreach($cats_added_ids as $cats_added_id) {
				$cats_added_titles[] = $after_cats[$cats_added_id]->title;
			}
			
			$cats_removed_ids = array_diff(array_keys($before_cats), array_keys($after_cats));
			foreach($cats_removed_ids as $cats_removed_id) {
				$cats_removed_titles[] = $before_cats[$cats_removed_id]->title;
			}
			$cats_altered = count($cats_added_ids) + count($cats_removed_ids);
			$after_maincat = $model->get('catid');
		}
		
		
		// *******************************************************************************************************************
		// We need to get emails to notify, from Global/item's Content Type parameters -AND- from item's categories parameters
		// *******************************************************************************************************************
		$notify_emails = array();
		if ( $is_first_save || $cats_altered || $params->get('nf_enable_debug',0) )
		{
			// Get needed flags regarding the saved items
			$approve_version = 2;
			$pending_approval_state = -3;
			$draft_state = -4;
			
			$current_version = FLEXIUtilities::getCurrentVersions($item->id, true); // Get current item version
			$last_version    = FLEXIUtilities::getLastVersions($item->id, true);    // Get last version (=latest one saved, highest version id),
			
			// $post variables vstate & state may have been (a) tampered in the form, and/or (b) altered by save procedure so better not use them
			$needs_version_reviewal     = !$isnew && ($last_version > $current_version) && !$canPublish;
			$needs_publication_approval =  $isnew && ($item->state == $pending_approval_state) && !$canPublish;
			
			$draft_from_non_publisher = $item->state==$draft_state && !$canPublish;
			
			if ($draft_from_non_publisher) {
				// Suppress notifications for draft-state items (new or existing ones), for these each author will publication approval manually via a button
				$nConf = false;
			} else {
				// Get notifications configuration and select appropriate emails for current saving case
				$nConf = $model->getNotificationsConf($params);  //echo "<pre>"; print_r($nConf); "</pre>";
			}
			
			if ($nConf)
			{
				$states_notify_new = $params->get('states_notify_new', array(1,0,(FLEXI_J16GE ? 2:-1),-3,-4,-5));
				if ( empty($states_notify_new) )						$states_notify_new = array();
				else if ( ! is_array($states_notify_new) )	$states_notify_new = !FLEXI_J16GE ? array($states_notify_new) : explode("|", $states_notify_new);
				
				$states_notify_existing = $params->get('states_notify_existing', array(1,0,(FLEXI_J16GE ? 2:-1),-3,-4,-5));
				if ( empty($states_notify_existing) )						$states_notify_existing = array();
				else if ( ! is_array($states_notify_existing) )	$states_notify_existing = !FLEXI_J16GE ? array($states_notify_existing) : explode("|", $states_notify_existing);

				$n_state_ok = in_array($item->state, $states_notify_new);
				$e_state_ok = in_array($item->state, $states_notify_existing);
				
				if ($needs_publication_approval)   $notify_emails = $nConf->emails->notify_new_pending;
				else if ($isnew && $n_state_ok)    $notify_emails = $nConf->emails->notify_new;
				else if ($isnew)                   $notify_emails = array();
				else if ($needs_version_reviewal)  $notify_emails = $nConf->emails->notify_existing_reviewal;
				else if (!$isnew && $e_state_ok)   $notify_emails = $nConf->emails->notify_existing;
				else if (!$isnew)                  $notify_emails = array();
				
				if ($needs_publication_approval)   $notify_text = $params->get('text_notify_new_pending');
				else if ($isnew)                   $notify_text = $params->get('text_notify_new');
				else if ($needs_version_reviewal)  $notify_text = $params->get('text_notify_existing_reviewal');
				else if (!$isnew)                  $notify_text = $params->get('text_notify_existing');
				//print_r($notify_emails); exit;
			}
		}
		
		
		// *********************************************************************************************************************
		// If there are emails to notify for current saving case, then send the notifications emails, but 
		// *********************************************************************************************************************
		if ( !empty($notify_emails) && count($notify_emails) ) {
			$notify_vars = new stdClass();
			$notify_vars->needs_version_reviewal     = $needs_version_reviewal;
			$notify_vars->needs_publication_approval = $needs_publication_approval;
			$notify_vars->isnew         = $isnew;
			$notify_vars->notify_emails = $notify_emails;
			$notify_vars->notify_text   = $notify_text;
			$notify_vars->before_cats   = $before_cats;
			$notify_vars->after_cats    = $after_cats;
			$notify_vars->before_state  = $before_state;
			
			$model->sendNotificationEmails($notify_vars, $params, $manual_approval_request=0);
		}
		
		
		// ***************************************************
		// CLEAN THE CACHE so that our changes appear realtime
		// ***************************************************
		if (FLEXI_J16GE) {
			$cache = FLEXIUtilities::getCache();
			$cache->clean('com_flexicontent_items');
		} else {
			$itemcache = JFactory::getCache('com_flexicontent_items');
			$itemcache->clean();
			$filtercache = JFactory::getCache('com_flexicontent_filters');
			$filtercache->clean();
		}
		
		
		// ****************************************************************************************************************************
		// Recalculate EDIT PRIVILEGE of new item. Reason for needing to do this is because we can have create permission in a category
		// and thus being able to set this category as item's main category, but then have no edit/editown permission for this category
		// ****************************************************************************************************************************
		if (FLEXI_J16GE) {
			$asset = 'com_content.article.' . $model->get('id');
			$canEdit = $user->authorise('core.edit', $asset) || ($user->authorise('core.edit.own', $asset) && $model->get('created_by') == $user->get('id'));
			// ALTERNATIVE 1
			//$canEdit = $model->getItemAccess()->get('access-edit'); // includes privileges edit and edit-own
			// ALTERNATIVE 2
			//$rights = FlexicontentHelperPerm::checkAllItemAccess($user->get('id'), 'item', $model->get('id'));
			//$canEdit = in_array('edit', $rights) || (in_array('edit.own', $rights) && $model->get('created_by') == $user->get('id')) ;
		} else if (FLEXI_ACCESS && $user->gid < 25) {
			$rights 	= FAccess::checkAllItemAccess('com_content', 'users', $user->gmid, $model->get('id'), $model->get('catid'));
			$canEdit = in_array('edit', $rights) || (in_array('editown', $rights) && $model->get('created_by') == $user->get('id')) ;
		} else {
			// This is meaningful when executed in frontend, since all backend users (managers and above) can edit items
			$canEdit = $user->authorize('com_content', 'edit', 'content', 'all') || ($user->authorize('com_content', 'edit', 'content', 'own') && $model->get('created_by') == $user->get('id'));
		}
		
		
		// *******************************************************************************************************
		// Check if user can not edit item further (due to changed main category, without edit/editown permission)
		// *******************************************************************************************************
		if (!$canEdit)
		{
			if ($isnew) {
				// Do not use parameter 'items_session_editable' for new items, to avoid breaking submitting "behavior"
				// To avoid not allow message, make sure task is cleared since user cannot edit item further
				$task = JRequest::setVar('task', '');
				// Set notice about creating an item that cannot be changed further
				$app->enqueueMessage(JText::_( 'FLEXI_CANNOT_CHANGE_FURTHER' ), 'message' );
			} else {
				if ( $params->get('items_session_editable', 0) ) {
					// Set notice for existing item being editable till logoff 
					JError::raiseNotice( 403, JText::_( 'FLEXI_CANNOT_EDIT_AFTER_LOGOFF' ) );
					// Allow item to be editable till logoff
					$session->get('rendered_uneditable', array(),'flexicontent');
					$rendered_uneditable[$model->get('id')]  = 1;
					$session->set('rendered_uneditable', $rendered_uneditable, 'flexicontent');
				} else {
					// To avoid not allow message, make sure task is cleared since user cannot edit item further
					$task = JRequest::setVar('task', '');
				}
			}
		}
		
		
		// ****************************************
		// Saving is done, decide where to redirect
		// ****************************************
		if ($task=='apply') {
			// Save and reload the item edit form
			$msg = JText::_( 'FLEXI_ITEM_SAVED' );
			$link = 'index.php?option=com_flexicontent&view='.FLEXI_ITEMVIEW.'&task=edit&id='.(int) $model->_item->id .'&'. (FLEXI_J30GE ? JSession::getFormToken() : JUtility::getToken()) .'=1';
			
			// Important pass referer back to avoid making the form itself the referer
			$referer = JRequest::getString('referer', JURI::base(), 'post');
			$return = '&return='.base64_encode( $referer );
			$link .= $return;
		} else if ($task=='save_a_preview') {
			// Save and preview the latest version
			$msg = JText::_( 'FLEXI_ITEM_SAVED' );
			$link = JRoute::_(FlexicontentHelperRoute::getItemRoute($model->_item->id.':'.$model->_item->alias, $model->_item->catid).'&preview=1', false);
		} else {
			// Get items marked as newly submitted
			$newly_submitted	= $session->get('newly_submitted', array(), 'flexicontent');
			
			if ( isset($newly_submitted[$model->get('id')]) && $submit_redirect_url_fe ) {
				// Return to a custom page after creating a new item (e.g. a thanks page)
				$link = $submit_redirect_url_fe;
				$msg = JText::_( 'FLEXI_ITEM_SAVED' );
			} else {
				// Return to the form 's referer (previous page) after item saving
				$msg = $isnew ? JText::_( 'FLEXI_THANKS_SUBMISSION' ) : JText::_( 'FLEXI_ITEM_SAVED' );
				
				// Check that referer URL is 'safe' (allowed) , e.g. not an offsite URL, otherwise for returning to HOME page
				$link = JRequest::getString('referer', JURI::base(), 'post');
				if ( ! flexicontent_html::is_safe_url($link) ) {
					if ( $dolog ) JFactory::getApplication()->enqueueMessage( 'refused redirection to possible unsafe URL: '.$link, 'notice' );
					$link = JURI::base();
				}
			}
			
			// Clear item from being marked as newly submitted
			if ( isset($newly_submitted[$model->get('id')]) ) {
				unset($newly_submitted[$model->get('id')]);
				$session->set('newly_submitted', $newly_submitted, 'flexicontent');
			}
		}
		
		$this->setRedirect($link, $msg);
	}
	
	
	/**
	 * Logic to submit item to approval
	 *
	 * @access public
	 * @return void
	 * @since 1.5
	 */
	function approval()
	{
		$cid = JRequest::getInt( 'cid', 0 );
		
		if ( !$cid ) {
			$msg = '';
			JError::raiseWarning(500, JText::_( 'FLEXI_APPROVAL_SELECT_ITEM_SUBMIT' ) );
		} else {
			// ... we use some strings from administrator part
			// load english language file for 'com_flexicontent' component then override with current language file
			JFactory::getLanguage()->load('com_flexicontent', JPATH_ADMINISTRATOR, 'en-GB', true);
			JFactory::getLanguage()->load('com_flexicontent', JPATH_ADMINISTRATOR, null, true);
			
			$model = $this->getModel( FLEXI_ITEMVIEW );
			$msg = $model->approval( array($cid) );
		}
		
		$this->setRedirect( $_SERVER['HTTP_REFERER'], $msg );
	}
	
	
	/**
	 * Display the view
	 */
	function display($cachable = false, $urlparams = false)
	{
		// Debuging message
		//JError::raiseNotice(500, 'IN display()'); // TOREMOVE
		
		// Access checking for --items-- viewing, will be handled by the items model, this is because THIS display() TASK is used by other views too
		// in future it maybe moved here to the controller, e.g. create a special task item_display() for item viewing, or insert some IF bellow
		
		if ( JRequest::getVar('layout', false) == "form" && !JRequest::getVar('task', false)) {
			// Compatibility check: Layout is form and task is not set:  this is new item submit ...
			JRequest::setVar('task', 'add');
			$this->add();
		} else {
			// Display a FLEXIcontent frontend view (category, item, favourites, etc)
			if (JFactory::getUser()->get('id')) {
				// WITHOUT CACHING (logged users)
				parent::display(false);
			} else {
				// WITH CACHING (guests)
				parent::display(true);
			}
			
		}
	}

	/**
	* Edits an item
	*
	* @access	public
	* @since	1.0
	*/
	function edit()
	{
		//JError::raiseNotice(500, 'IN edit()');   // Debuging message
		
		$view  = $this->getView(FLEXI_ITEMVIEW, 'html');   // Get/Create the view
		$model = $this->getModel(FLEXI_ITEMVIEW);   // Get/Create the model
		
		// Push the model into the view (as default)
		$view->setModel($model, true);

		// Set the layout
		$view->setLayout( JRequest::getVar('layout','form') );

		// Display the view
		$view->display();
	}
	
	/**
	* Logic to add an item
	* Deprecated in 1.5.3 stable
	*
	* @access	public
	* @since	1.0
	*/
	function add()
	{
		//JError::raiseNotice(500, 'IN ADD()');   // Debuging message
		
		$view  =  $this->getView(FLEXI_ITEMVIEW, 'html');   // Get/Create the view
		$model = $this->getModel(FLEXI_ITEMVIEW);    // Get/Create the model
		
		// Push the model into the view (as default)
		$view->setModel($model, true);
		
		// Set the layout
		$view->setLayout( JRequest::getVar('layout','form') );
		
		// Display the view
		$view->display();
	}


	/**
	* Cancels an edit item operation
	*
	* @access	public
	* @since	1.0
	*/
	function cancel()
	{
		// Check for request forgeries
		JRequest::checkToken( 'request' ) or jexit( 'Invalid Token' );		
		
		// Initialize some variables
		$user    = JFactory::getUser();
		$session = JFactory::getSession();
		$dolog = JComponentHelper::getParams( 'com_flexicontent' )->get('print_logging_info');

		// Get an item model
		$model = $this->getModel(FLEXI_ITEMVIEW);
		
		// CHECK-IN the item if user can edit
		if ($model->get('id') > 1)
		{
			if (FLEXI_J16GE) {
				$asset = 'com_content.article.' . $model->get('id');
				$canEdit = $user->authorise('core.edit', $asset) || ($user->authorise('core.edit.own', $asset) && $item->created_by == $user->get('id'));
				// ALTERNATIVE 1
				//$canEdit = $model->getItemAccess()->get('access-edit'); // includes privileges edit and edit-own
				// ALTERNATIVE 2
				//$rights = FlexicontentHelperPerm::checkAllItemAccess($user->get('id'), 'item', $model->get('id'));
				//$canEdit = in_array('edit', $rights) || (in_array('edit.own', $rights) && $model->get('created_by') == $user->get('id')) ;
			} else if ($user->gid >= 25) {
				$canEdit = true;
			} else if (FLEXI_ACCESS) {
				$rights 	= FAccess::checkAllItemAccess('com_content', 'users', $user->gmid, $model->get('id'), $model->get('catid'));
				$canEdit = in_array('edit', $rights) || (in_array('editown', $rights) && $model->get('created_by') == $user->get('id')) ;
			} else {
				$canEdit = $user->authorize('com_content', 'edit', 'content', 'all') || ($user->authorize('com_content', 'edit', 'content', 'own') && $model->get('created_by') == $user->get('id'));
			}
			
			// Check if item is editable till logoff
			if ($session->has('rendered_uneditable', 'flexicontent')) {
				$rendered_uneditable = $session->get('rendered_uneditable', array(),'flexicontent');
				$canEdit = isset($rendered_uneditable[$model->get('id')]);
			}
			
			if ($canEdit) $model->checkin();
		}
		
		// If the task was edit or cancel, we go back to the form referer
		$referer = JRequest::getString('referer', JURI::base(), 'post');
		
		// Check that referer URL is 'safe' (allowed) , e.g. not an offsite URL, otherwise for returning to HOME page
		if ( ! flexicontent_html::is_safe_url($referer) ) {
			if ( $dolog ) JFactory::getApplication()->enqueueMessage( 'refused redirection to possible unsafe URL: '.$referer, 'notice' );
			$referer = JURI::base();
		}
		
		$this->setRedirect($referer);
	}

	/**
	 * Method of the voting without AJAX. Exists for compatibility reasons, since it can be called by Joomla's content vote plugin.
	 *
	 * @access public
	 * @since 1.0
	 */
	function vote()
	{
		$id = JRequest::getInt('id', 0);
		$cid = JRequest::getInt('cid', 0);
		$url = JRequest::getString('url', '');
		$dolog = JComponentHelper::getParams( 'com_flexicontent' )->get('print_logging_info');
		
		// Check that the pased URL variable is 'safe' (allowed) , e.g. not an offsite URL, otherwise for returning to HOME page
		if ( ! $url || ! flexicontent_html::is_safe_url($url) ) {
			if ( $dolog ) JFactory::getApplication()->enqueueMessage( 'refused redirection to possible unsafe URL: '.$url, 'notice' );
			$url = JRoute::_('index.php?view='.FLEXI_ITEMVIEW.'&cid='.$cid.'&id='.$id);
		}
		
		// Finally store the vote
		JRequest::setVar('no_ajax', 1);
		$this->ajaxvote();
		
		$msg = '';
		$this->setRedirect($url, $msg );
	}

	/**
	 *  Ajax favourites
	 *
	 * @access public
	 * @since 1.0
	 */
	function ajaxfav()
	{
		$user  = JFactory::getUser();
		$db    = JFactory::getDBO();
		$model = $this->getModel(FLEXI_ITEMVIEW);
		$id    = JRequest::getInt('id', 0);
		
		if (!$user->get('id'))
		{
			echo 'login';
		}
		else
		{
			$isfav = $model->getFavoured();

			if ($isfav)
			{
				$model->removefav();
				$favs 	= $model->getFavourites();
				if ($favs == 0) {
					echo 'removed';
				} else {
					echo '-'.$favs;
				}
			}
			else
			{
				$model->addfav();
				$favs 	= $model->getFavourites();
				if ($favs == 0) {
					echo 'added';
				} else {
					echo '+'.$favs;
				}
			}
		}
	}

	/**
	 *  Method for voting (ajax)
	 *
	 * @TODO move the query part to the item model
	 * @access public
	 * @since 1.5
	 */
	public function ajaxvote()
	{
		$app  = JFactory::getApplication();
		$user = JFactory::getUser();
		$db   = JFactory::getDBO();
		$session = JFactory::getSession();
		
		$no_ajax			= JRequest::getInt('no_ajax');
		$user_rating	= JRequest::getInt('user_rating');
		$cid 			= JRequest::getInt('cid');
		$xid 			= JRequest::getVar('xid');
		
		if ($no_ajax) {
			// Joomla 's content plugin uses 'id' HTTP request variable
			$cid = JRequest::getInt('id');
		}
		
		$result	= new JObject;

		if (($user_rating >= 1) and ($user_rating <= 5))
		{
			// Check: item id exists in our voting logging SESSION (array) variable 
			$votestamp = $session->get('votestamp', array(),'flexicontent');
			if ( !isset($votestamp[$cid]) || !is_array($votestamp[$cid]) )
			{
				$votestamp[$cid] = array();
			}
			$votecheck = isset($votestamp[$cid][$xid]);
			
			// Set: the current item id, in our voting logging SESSION (array) variable  
			$votestamp[$cid][$xid] = 1;
			$session->set('votestamp', $votestamp, 'flexicontent');
			
			// Setup variables used in the db queries
			$currip = ( phpversion() <= '4.2.1' ? @getenv( 'REMOTE_ADDR' ) : $_SERVER['REMOTE_ADDR'] );
			$currip_quoted = $db->Quote( $currip );
			$dbtbl = !(int)$xid ? '#__content_rating' : '#__flexicontent_items_extravote';  // Choose db table to store vote (normal or extra)
			$and_extra_id = (int)$xid ? ' AND field_id = '.(int)$xid : '';     // second part is for defining the vote type in case of extra vote
			
			// Retreive last vote for the given item
			$query = ' SELECT *'
				. ' FROM '.$dbtbl.' AS a '
				. ' WHERE content_id = '.(int)$cid.' '.$and_extra_id;
			
			$db->setQuery( $query );
			$votesdb = $db->loadObject();
			
			if ( !$votesdb )
			{
				// Voting record does not exist for this item, accept user's vote and insert new voting record in the db
				$query = ' INSERT '.$dbtbl
					. ' SET content_id = '.(int)$cid.', '
					. '  lastip = '.$currip_quoted.', '
					. '  rating_sum = '.(int)$user_rating.', '
					. '  rating_count = 1 '
					. ( (int)$xid ? ', field_id = '.(int)$xid : '' );
					
				$db->setQuery( $query );
				$db->query() or die( $db->stderr() );
				$result->ratingcount = 1;
				$result->htmlrating = '(' . $result->ratingcount .' '. JText::_( 'FLEXI_VOTE' ) . ')';
			}
			else
			{
				// Voting record exists for this item, check if user has already voted
				
				// NOTE: it is not so good way to check using ip, since 2 users may have same IP,
				// but for compatibility with standard joomla and for stronger security we will do it
				if ( !$votecheck && $currip!=$votesdb->lastip ) 
				{
					// vote accepted update DB
					$query = " UPDATE ".$dbtbl
					. ' SET rating_count = rating_count + 1, '
					. '  rating_sum = rating_sum + '.(int)$user_rating.', '
					. '  lastip = '.$currip_quoted
					. ' WHERE content_id = '.(int)$cid.' '.$and_extra_id;
					
					$db->setQuery( $query );
					$db->query() or die( $db->stderr() );
					$result->ratingcount = $votesdb->rating_count + 1;
					$result->htmlrating = '(' . $result->ratingcount .' '. JText::_( 'FLEXI_VOTES' ) . ')';
				} 
				else 
				{
					// vote rejected
					// avoid setting percentage ... since it may confuse the user because someone from same ip may have voted and
					// despite telling user that she/he has voted already, user will see a change in the percentage of highlighted stars
					//$result->percentage = ( $votesdb->rating_sum / $votesdb->rating_count ) * 20;
					$result->htmlrating = '(' . $votesdb->rating_count .' '. JText::_( 'FLEXI_VOTES' ) . ')';
					$result->html = JText::_( 'FLEXI_YOU_HAVE_ALREADY_VOTED' );
					if ($no_ajax) {
						$app->enqueueMessage( $result->html, 'notice' );
						return;
					} else {
						echo json_encode($result);
						exit();
					}
				}
			}
			$result->percentage = ( ((isset($votesdb->rating_sum) ? $votesdb->rating_sum : 0) + (int)$user_rating) / $result->ratingcount ) * 20;
			$result->html = JText::_( 'FLEXI_THANK_YOU_FOR_VOTING' );
			if ($no_ajax) {
				$app->enqueueMessage( $result->html, 'notice' );
				return;
			} else {
				echo json_encode($result);
				exit();
			}
		}
	}


	/**
	 * Get the new tags and outputs html (ajax)
	 *
	 * @TODO cleanup this mess
	 * @access public
	 * @since 1.0
	 */
	function getajaxtags()
	{
		$user = JFactory::getUser();

		if (!$user->authorize('com_flexicontent', 'newtags')) {
			return;
		}

		$id 	= JRequest::getInt('id', 0);
		$model 	= $this->getModel(FLEXI_ITEMVIEW);
		$tags 	= $model->getAlltags();

		$used = null;

		if ($id) {
			$used = $model->getUsedtagsIds($id);
		}
		if(!is_array($used)){
			$used = array();
		}

		$rsp = '';
		$n = count($tags);
		for( $i = 0, $n; $i < $n; $i++ ){
			$tag = $tags[$i];

			if( ( $i % 5 ) == 0 ){
				if( $i != 0 ){
					$rsp .= '</div>';
				}
				$rsp .=  '<div class="qf_tagline">';
			}
			$rsp .=  '<span class="qf_tag"><span class="qf_tagidbox"><input type="checkbox" name="tag[]" value="'.$tag->id.'"' . (in_array($tag->id, $used) ? 'checked="checked"' : '') . ' /></span>'.$tag->name.'</span>';
		}
		$rsp .= '</div>';
		$rsp .= '<div class="clear"></div>';
		$rsp .= '<div class="qf_addtag">';
		$rsp .= '<label for="addtags">'.JText::_( 'FLEXI_ADD_TAG' ).'</label>';
		$rsp .= '<input type="text" id="tagname" class="inputbox" size="30" />';
		$rsp .=	'<input type="button" class="button" value="'.JText::_( 'FLEXI_ADD' ).'" onclick="addtag()" />';
		$rsp .= '</div>';

		echo $rsp;
	}

	/**
	 *  Add new Tag from item screen
	 *
	 * @access public
	 * @since 1.0
	 */
	function addtagx()
	{

		$user = JFactory::getUser();

		$name 	= JRequest::getString('name', '');

		if ($user->authorize('com_flexicontent', 'newtags')) {
			$model 	= $this->getModel(FLEXI_ITEMVIEW);
			$model->addtag($name);
		}
		return;
	}
	
	/**
	 *  Add new Tag from item screen
	 *
	 */
	function addtag() {
		// Check for request forgeries
		JRequest::checkToken('request') or jexit( 'Invalid Token' );

		$name 	= JRequest::getString('name', '');
		$model 	= $this->getModel('tags');
		$array = JRequest::getVar('cid',  0, '', 'array');
		$cid = (int)$array[0];
		$model->setId($cid);
		if($cid==0) {
			// Add the new tag and output it so that it gets loaded by the form
			$result = $model->addtag($name);
			if($result)
				echo $model->_tag->id."|".$model->_tag->name;
		} else {
			// Since an id was given, just output the loaded tag, instead of adding a new one
			$id = $model->get('id');
			$name = $model->get('name');
			echo $id."|".$name;
		}
		exit;
	}

	/**
	 * Add favourite
	 * deprecated to ajax favs 
	 *
	 * @access public
	 * @since 1.0
	 */
	function addfavourite()
	{
		$cid 	= JRequest::getInt('cid', 0);
		$id 	= JRequest::getInt('id', 0);

		$model 	= $this->getModel(FLEXI_ITEMVIEW);
		if ($model->addfav()) {
			$msg = JText::_( 'FLEXI_FAVOURITE_ADDED' );
		} else {
			$msg = JText::_( 'FLEXI_FAVOURITE_NOT_ADDED' ).': '.$model->getError();
			if (FLEXI_J16GE) throw new Exception($msg, 500); else JError::raiseError(500, $msg);
		}
		
		$cache = JFactory::getCache('com_flexicontent');
		$cache->clean();

		$this->setRedirect(JRoute::_('index.php?view='.FLEXI_ITEMVIEW.'&cid='.$cid.'&id='. $id, false), $msg );
	}

	/**
	 * Remove favourite
	 * deprecated to ajax favs
	 *
	 * @access public
	 * @since 1.0
	 */
	function removefavourite()
	{
		$cid 	= JRequest::getInt('cid', 0);
		$id 	= JRequest::getInt('id', 0);

		$model 	= $this->getModel(FLEXI_ITEMVIEW);
		if ($model->removefav()) {
			$msg = JText::_( 'FLEXI_FAVOURITE_REMOVED' );
		} else {
			$msg = JText::_( 'FLEXI_FAVOURITE_NOT_REMOVED' ).': '.$model->getError();
			if (FLEXI_J16GE) throw new Exception($msg, 500); else JError::raiseError(500, $msg);
		}
		
		$cache = JFactory::getCache('com_flexicontent');
		$cache->clean();
		
		if ($cid) {
			$this->setRedirect(JRoute::_('index.php?view='.FLEXI_ITEMVIEW.'&cid='.$cid.'&id='. $id, false), $msg );
		} else {
			$this->setRedirect(JRoute::_('index.php?view=favourites', false), $msg );
		}
	}

	/**
	 * Logic to change the state of an item
	 *
	 * @access public
	 * @return void
	 * @since 1.0
	 */
	function setitemstate()
	{
		flexicontent_html::setitemstate($this);
	}
	
	
	/**
	 * Download logic
	 *
	 * @access public
	 * @since 1.0
	 */
	function download()
	{
		// Import and Initialize some joomla API variables
		jimport('joomla.filesystem.file');
		$app   = JFactory::getApplication();
		$db    = JFactory::getDBO();
		$user  = JFactory::getUser();
		
		// Get HTTP REQUEST variables
		$fieldid   = JRequest::getInt( 'fid', 0 );
		$contentid = JRequest::getInt( 'cid', 0 );
		$fileid    = JRequest::getInt( 'id', 0 );
		
		
		// **************************************************
		// Create and Execute SQL query to retrieve file info
		// **************************************************
		
		// Create JOIN + AND clauses for checking Access
		$joinaccess = $andaccess = $joinaccess2 = $andaccess2 = '';
		$this->_createFieldItemAccessClause( $joinaccess, $andaccess, $joinaccess2, $andaccess2);
		
		// Extra access CLAUSEs for given file (this is conmbined with the above CLAUSEs for field and item access)
		if (FLEXI_J16GE) {
			$aid_arr = $user->getAuthorisedViewLevels();
			$aid_list = implode(",", $aid_arr);
			$andaccess  .= ' AND f.access IN (0,'.$aid_list.')';
		} else {
			$aid = (int) $user->get('aid');
			if (FLEXI_ACCESS) {
				$joinaccess .= ' LEFT JOIN #__flexiaccess_acl AS gf ON f.id = gf.axo AND gf.aco = "read" AND gf.axosection = "file"';
				$andaccess  .= ' AND (f.access IS NULL OR gf.aro IN ( '.$user->gmid.' ) OR f.access <= '. $aid . ')';
			} else {
				$andaccess  .= ' AND (f.access IS NULL OR f.access <= '.$aid .')';
			}
		}
		
		$query  = 'SELECT f.id, f.filename, f.secure, f.url'
				.' FROM #__flexicontent_fields_item_relations AS rel'
				.' LEFT JOIN #__flexicontent_files AS f ON f.id = rel.value'
				.' LEFT JOIN #__flexicontent_fields AS fi ON fi.id = rel.field_id'
				.' LEFT JOIN #__content AS i ON i.id = rel.item_id'
				.' LEFT JOIN #__categories AS c ON c.id = i.catid'
				.' LEFT JOIN #__flexicontent_items_ext AS ie ON ie.item_id = i.id'
				.' LEFT JOIN #__flexicontent_types AS ty ON ie.type_id = ty.id'
				. $joinaccess
				. $joinaccess2
				.' WHERE rel.item_id = ' . (int)$contentid
				.' AND rel.field_id = ' . (int)$fieldid
				.' AND f.id = ' . (int)$fileid
				.' AND f.published= 1'
				. $andaccess
				. $andaccess2
				;
		$db->setQuery($query);
		$file = $db->loadObject();
		if ($db->getErrorNum())  {
			JFactory::getApplication()->enqueueMessage(__FUNCTION__.'(): SQL QUERY ERROR:<br/>'.nl2br($db->getErrorMsg()),'error');
			exit;
		}
		
		
		// ************************************************************************
		// Check for user not having the required Access Level (empty query result)
		// ************************************************************************
		
		if ( empty($file) ) {
			$msg = !$db->getErrorNum() ? JText::_( 'FLEXI_ALERTNOTAUTH' ) : __FUNCTION__.'(): SQL QUERY ERROR:<br/>'.nl2br($db->getErrorMsg()) ;
			$app->enqueueMessage($msg,'error');
			$this->setRedirect('index.php', '');
			return;
		}
		
		// ****************************************************
		// (for non-URL) Create file path and check file exists
		// ****************************************************
		
		if ( !$file->url ) {
			$basePath = $file->secure ? COM_FLEXICONTENT_FILEPATH : COM_FLEXICONTENT_MEDIAPATH;
			$abspath = str_replace(DS, '/', JPath::clean($basePath.DS.$file->filename));
			if ( !JFile::exists($abspath) ) {
				$msg = JText::_( 'FLEXI_REQUESTED_FILE_DOES_NOT_EXIST_ANYMORE' );
				$link = 'index.php';
				$this->setRedirect($link, $msg);
				return;
			}
		}
		
		
		// **********************
		// Increment hits counter
		// **********************
		
		$filetable = JTable::getInstance('flexicontent_files', '');
		$filetable->hit($fileid);
		
		
		// **************************
		// Special case file is a URL
		// **************************
		
		if ($file->url) {
			// redirect to the file download link
			@header("Location: ".$file->filename."");
			$app->close();
		}
		
		
		// *****************************************
		// Output an appropriate Content-Type header
		// *****************************************
		
		// Get filesize and extension
		$size = filesize($abspath);
		$ext  = strtolower(JFile::getExt($file->filename));
		
		// * Required for IE, otherwise Content-disposition is ignored
		if (ini_get('zlib.output_compression')) {
			ini_set('zlib.output_compression', 'Off');
		}

		switch( $ext )
		{
			case "pdf":
				$ctype = "application/pdf";
				break;
			case "exe":
				$ctype="application/octet-stream";
				break;
			case "rar":
			case "zip":
				$ctype = "application/zip";
				break;
			case "txt":
				$ctype = "text/plain";
				break;
			case "doc":
				$ctype = "application/msword";
				break;
			case "xls":
				$ctype = "application/vnd.ms-excel";
				break;
			case "ppt":
				$ctype = "application/vnd.ms-powerpoint";
				break;
			case "gif":
				$ctype = "image/gif";
				break;
			case "png":
				$ctype = "image/png";
				break;
			case "jpeg":
			case "jpg":
				$ctype = "image/jpg";
				break;
			case "mp3":
				$ctype = "audio/mpeg";
				break;
			default:
				$ctype = "application/force-download";
		}
/*		
		JResponse::setHeader('Pragma', 'public');
		JResponse::setHeader('Expires', 0);
		JResponse::setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0');
		JResponse::setHeader('Cache-Control', 'private', false);
		JResponse::setHeader('Content-Type', $ctype);
		JResponse::setHeader('Content-Disposition', 'attachment; filename="'.$file.'";');
		JResponse::setHeader('Content-Transfer-Encoding', 'binary');
		JResponse::setHeader('Content-Length', $size);
*/
		header("Pragma: public"); // required
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: private",false); // required for certain browsers
		header("Content-Type: $ctype");
		//quotes to allow spaces in filenames
		header("Content-Disposition: attachment; filename=\"".$file->filename."\";" );
		header("Content-Transfer-Encoding: binary");
		header("Content-Length: ".$size);
		
		
		// *******************************
		// Finally read file and output it
		// *******************************
		
		readfile($abspath);
		$app->close();
	}
	
	
	/**
	 * External link logic
	 *
	 * @access public
	 * @since 1.5
	 */
	function weblink()
	{
		// Import and Initialize some joomla API variables
		$app   = JFactory::getApplication();
		$db    = JFactory::getDBO();
		$user  = JFactory::getUser();
		
		// Get HTTP REQUEST variables
		$fieldid   = JRequest::getInt( 'fid', 0 );
		$contentid = JRequest::getInt( 'cid', 0 );
		$order     = JRequest::getInt( 'ord', 0 );
		
		
		// **************************************************
		// Create and Execute SQL query to retrieve file info
		// **************************************************
		
		// Create JOIN + AND clauses for checking Access
		$joinaccess = $andaccess = $joinaccess2 = $andaccess2 = '';
		$this->_createFieldItemAccessClause( $joinaccess, $andaccess, $joinaccess2, $andaccess2);
		
		$query  = 'SELECT value'
				.' FROM #__flexicontent_fields_item_relations AS rel'
				.' LEFT JOIN #__flexicontent_fields AS fi ON fi.id = rel.field_id'
				.' LEFT JOIN #__content AS i ON i.id = rel.item_id'
				.' LEFT JOIN #__categories AS c ON c.id = i.catid'
				.' LEFT JOIN #__flexicontent_items_ext AS ie ON ie.item_id = i.id'
				.' LEFT JOIN #__flexicontent_types AS ty ON ie.type_id = ty.id'
				. $joinaccess
				. $joinaccess2
				.' WHERE rel.item_id = ' . (int)$contentid
				.' AND rel.field_id = ' . (int)$fieldid
				.' AND rel.valueorder = ' . (int)$order
				. $andaccess
				. $andaccess2
				;
		$db->setQuery($query);
		$link = $db->loadResult();
		
		
		// ************************************************************************
		// Check for user not having the required Access Level (empty query result)
		// ************************************************************************
		
		if ( empty($link) ) {
			$msg = !$db->getErrorNum() ? JText::_( 'FLEXI_ALERTNOTAUTH' ) : __FUNCTION__.'(): SQL QUERY ERROR:<br/>'.nl2br($db->getErrorMsg()) ;
			$app->enqueueMessage($msg,'error');
			$this->setRedirect('index.php', '');
			return;
		}
		
		
		// **********************
		// Increment hits counter
		// **********************
		
		// recover the link array (url|title|hits)
		$link = unserialize($link);
		
		// get the url from the array
		$url = $link['link'];
		
		// update the hit count
		$link['hits'] = (int)$link['hits'] + 1;
		$value = serialize($link);
		
		// update the array in the DB
		$query 	= 'UPDATE #__flexicontent_fields_item_relations'
				.' SET value = ' . $db->Quote($value)
				.' WHERE item_id = ' . (int)$contentid
				.' AND field_id = ' . (int)$fieldid
				.' AND valueorder = ' . (int)$order
				;
		$db->setQuery($query);
		if (!$db->query()) {
			return JError::raiseWarning( 500, $db->getError() );
		}
		
		
		// ***************************
		// Finally redirect to the URL
		// ***************************
		
		@header("Location: ".$url."","target=blank");
		$app->close();
	}
	
	
	// Private common method to create join + and-where SQL CLAUSEs, for checking access of field - item pair(s), IN FUTURE maybe moved
	function _createFieldItemAccessClause( &$joinaccess='', &$andaccess='', &$joinaccess2='', &$andaccess2='')
	{
		$user  = JFactory::getUser();
		if (FLEXI_J16GE) {
			$aid_arr = $user->getAuthorisedViewLevels();
			$aid_list = implode(",", $aid_arr);
			// is the field available
			$andaccess  .= ' AND fi.access IN (0,'.$aid_list.')';
			// is the item available
			$andaccess2 .= ' AND ty.access IN (0,'.$aid_list.')';
			$andaccess2 .= ' AND  c.access IN (0,'.$aid_list.')';
			$andaccess2 .= ' AND  i.access IN (0,'.$aid_list.')';
		} else {
			$aid = (int) $user->get('aid');
			if (FLEXI_ACCESS) {
				// is the field available
				$joinaccess .= ' LEFT JOIN #__flexiaccess_acl AS gfi ON fi.id = gfi.axo AND gfi.aco = "read" AND gfi.axosection = "field"';
				$andaccess  .= ' AND (gfi.aro IN ( '.$user->gmid.' ) OR fi.access <= '. $aid . ')';
				// is the item available
				$joinaccess2 .= ' LEFT JOIN #__flexiaccess_acl AS gt ON ty.id = gt.axo AND gt.aco = "read" AND gt.axosection = "type"';
				$joinaccess2 .= ' LEFT JOIN #__flexiaccess_acl AS gc ON  c.id = gc.axo AND gc.aco = "read" AND gc.axosection = "category"';
				$joinaccess2 .= ' LEFT JOIN #__flexiaccess_acl AS gi ON  i.id = gi.axo AND gi.aco = "read" AND gi.axosection = "item"';
				$andaccess2  .= ' AND (gt.aro IN ( '.$user->gmid.' ) OR ty.access <= '. $aid . ')';
				$andaccess2  .= ' AND (gc.aro IN ( '.$user->gmid.' ) OR  c.access <= '. $aid . ')';
				$andaccess2  .= ' AND (gi.aro IN ( '.$user->gmid.' ) OR  i.access <= '. $aid . ')';
			} else {
				// is the field available
				$andaccess  .= ' AND fi.access <= '.$aid ;
				// is the item available
				$andaccess2 .= ' AND ty.access <= '.$aid ;
				$andaccess2 .= ' AND  c.access <= '.$aid ;
				$andaccess2 .= ' AND  i.access <= '.$aid ;
			}
		}
		return;
	}
	
	
	/**
	 * Method to fetch the tags form
	 * 
	 * @since 1.5
	 */
	function viewtags() {
		// Check for request forgeries
		JRequest::checkToken('request') or jexit( 'Invalid Token' );

		$user = JFactory::getUser();
		if (FLEXI_J16GE) {
			$CanUseTags = FlexicontentHelperPerm::getPerm()->CanUseTags;
		} else if (FLEXI_ACCESS) {
			$CanUseTags = ($user->gid < 25) ? FAccess::checkComponentAccess('com_flexicontent', 'usetags', 'users', $user->gmid) : 1;
		} else {
			$CanUseTags = 1;
		}

		if($CanUseTags) {
			//header('Content-type: application/json');
			@ob_end_clean();
			header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
			header("Cache-Control: no-cache");
			header("Pragma: no-cache");
			//header("Content-type:text/json");
			$model 		=  $this->getModel(FLEXI_ITEMVIEW);
			$tagobjs 	=  $model->gettags(JRequest::getVar('q'));
			$array = array();
			echo "[";
			foreach($tagobjs as $tag) {
				$array[] = "{\"id\":\"".$tag->id."\",\"name\":\"".$tag->name."\"}";
			}
			echo implode(",", $array);
			echo "]";
			exit;
		}
	}
	
	function search()
	{
		// Strip characteres that will cause errors
		$badchars = array('#','>','<','\\'); 
		$searchword = trim(str_replace($badchars, '', JRequest::getString('searchword', null, 'post')));
		
		// If searchword is enclosed in double quotes, then strip quotes and do exact phrase matching
		if (substr($searchword,0,1) == '"' && substr($searchword, -1) == '"') { 
			$searchword = substr($searchword,1,-1);
			JRequest::setVar('searchphrase', 'exact');
			JRequest::setVar('searchword', $searchword);
		}
		
		// If no current menu itemid, then set it using the first menu item that points to the search view
		if (!JRequest::getVar('Itemid', 0)) {
			$menus = JSite::getMenu();
			$items = $menus->getItems('link', 'index.php?option=com_flexicontent&view=search');
	
			if(isset($items[0])) {
				JRequest::setVar('Itemid', $items[0]->id);
			}
		}
		
		$model = $this->getModel(FLEXI_ITEMVIEW);
		$view  = $this->getView('search', 'html');
		$view->setModel($model);
		
		JRequest::setVar('view', 'search');
		parent::display(true);
	}
	
	
	function doPlgAct() {
		FLEXIUtilities::doPlgAct();
	}
}
?>
