<?php
/**
 * HTML5 Template
 * @version 1.5 stable $Id: item_html5.php 0001 2012-09-23 14:00:28Z Rehne $
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
// first define the template name
$tmpl = $this->tmpl; // for backwards compatiblity


// Prepend toc (Table of contents) before item's description (toc will usually float right)
// By prepend toc to description we make sure that it get's displayed at an appropriate place
if (isset($this->item->toc)) {
	$this->item->fields['text']->display = $this->item->toc . $this->item->fields['text']->display;
}

// Set the class for controlling number of columns in custom field blocks
switch ($this->params->get( 'columnmode', 2 )) {
	case 0: $columnmode = 'singlecol'; break;
	case 1: $columnmode = 'doublecol'; break;
	default: $columnmode = ''; break;
}

$page_classes  = '';
$page_classes .= $this->pageclass_sfx ? ' page'.$this->pageclass_sfx : '';
$page_classes .= ' fcitems fcitem'.$this->item->id;
$page_classes .= ' fctype'.$this->item->type_id;
$page_classes .= ' fcmaincat'.$this->item->catid;

$mainAreaTag = ( $this->params->get( 'show_page_heading', 1 ) && $this->params->get('page_heading') != $this->item->title && $this->params->get('show_title', 1) ) ? 'section' : 'article';
// SEO
$itemTitleHeaderLevel = ( $this->params->get( 'show_page_heading', 1 ) && $this->params->get('page_heading') != $this->item->title && $this->params->get('show_title', 1) ) ? '2' : '1'; 
?>

<?php echo '<'.$mainAreaTag; ?> id="flexicontent" class="flexicontent <?php echo $page_classes; ?>" >

	<?php echo ( ($mainAreaTag == 'section') ? '<header>' : ''); ?>
	
  <?php if ($this->item->event->beforeDisplayContent) : ?>
		<!-- BOF beforeDisplayContent -->
		<?php echo ( ($mainAreaTag == 'section') ? '<aside' : '<div'); ?> class="fc_beforeDisplayContent group">
			<?php echo $this->item->event->beforeDisplayContent; ?>
		<?php echo ( ($mainAreaTag == 'section') ? '</aside>' : '</div>'); ?>
		<!-- EOF beforeDisplayContent -->
	<?php endif; ?>
	
	<?php
	$pdfbutton = flexicontent_html::pdfbutton( $this->item, $this->params );
	$mailbutton = flexicontent_html::mailbutton( FLEXI_ITEMVIEW, $this->params, $this->item->categoryslug, $this->item->slug );
	$printbutton = flexicontent_html::printbutton( $this->print_link, $this->params );
	$editbutton = flexicontent_html::editbutton( $this->item, $this->params );
	$statebutton = flexicontent_html::statebutton( $this->item, $this->params );
	$approvalbutton = flexicontent_html::approvalbutton( $this->item, $this->params );
	if ($pdfbutton || $mailbutton || $printbutton || $editbutton || $statebutton || $approvalbutton) {
	?>
	
	<!-- BOF buttons -->
	<div class="buttons">
		<?php echo $pdfbutton; ?>
		<?php echo $mailbutton; ?>
		<?php echo $printbutton; ?>
		<?php echo $editbutton; ?>
		<?php echo $statebutton; ?>
		<?php echo $approvalbutton; ?>
	</div>
	<!-- EOF buttons -->
	<?php } ?>

	<?php if ( $this->params->get( 'show_page_heading', 1 ) && $this->params->get('page_heading') != $this->item->title ) : ?>
	<!-- BOF page title -->
	<header>
	<h1 class="componentheading">
		<?php echo $this->params->get('page_heading'); ?>
	</h1>
	</header>
	<!-- EOF page title -->
	<?php endif; ?>

	<?php echo ( ($mainAreaTag == 'section') ? '</header>' : ''); ?>
	
	<?php echo ( ($mainAreaTag == 'section') ? '<article>' : ''); ?>
	
	<?php if ($this->params->get('show_title', 1)) : ?>
	<!-- BOF item title -->
	<header class="group">
		<?php echo "<h".$itemTitleHeaderLevel; ?> class="contentheading"><span class="fc_item_title">
		<?php
		if ( mb_strlen($this->item->title, 'utf-8') > $this->params->get('title_cut_text',200) ) :
			echo mb_substr ($this->item->title, 0, $this->params->get('title_cut_text',200), 'utf-8') . ' ...';
		else :
			echo $this->item->title;
		endif;
		?>
	</span><?php echo "</h".$itemTitleHeaderLevel; ?>>
	<!-- EOF item title -->
	<?php endif; ?>
	
  <?php if ($this->item->event->afterDisplayTitle) : ?>
		<!-- BOF afterDisplayTitle -->
		<div class="fc_afterDisplayTitle group">
			<?php echo $this->item->event->afterDisplayTitle; ?>
		</div>
		<!-- EOF afterDisplayTitle -->
	<?php endif; ?>

	<?php if ((intval($this->item->modified) !=0 && $this->params->get('show_modify_date')) || ($this->params->get('show_author') && ($this->item->creator != "")) || ($this->params->get('show_create_date')) || (($this->params->get('show_modifier')) && (intval($this->item->modified) !=0))) : ?>
	<!-- BOF item basic/core info -->
	<div class="iteminfo group">
		
		<?php if (($this->params->get('show_author')) && ($this->item->creator != "")) : ?>
		<span class="createdline">
			<span class="createdby">
				<?php FlexicontentFields::getFieldDisplay($this->item, 'created_by', $values=null, $method='display'); ?>
				<?php echo JText::sprintf('FLEXI_WRITTEN_BY', $this->fields['created_by']->display); ?>
			</span>
			<?php endif; ?>
			
			<?php if (($this->params->get('show_author')) && ($this->item->creator != "") && ($this->params->get('show_create_date'))) : ?>
			::
			<?php endif; ?>
	
			<?php if ($this->params->get('show_create_date')) : ?>
			<span class="created">
				<?php FlexicontentFields::getFieldDisplay($this->item, 'created', $values=null, $method='display'); ?>
				<?php echo '['.JHTML::_('date', $this->fields['created']->value[0], JText::_('DATE_FORMAT_LC2')).']'; ?>		
			</span>
			<?php endif; ?>
		</span>
		
		<span class="modifiedline">
			<?php if (($this->params->get('show_modifier')) && ($this->item->modifier != "")) : ?>
			<span class="modifiedby">
				<?php FlexicontentFields::getFieldDisplay($this->item, 'modified_by', $values=null, $method='display'); ?>
				<?php echo JText::_('FLEXI_LAST_UPDATED').' '.JText::sprintf('FLEXI_BY', $this->fields['modified_by']->display); ?>
			</span>
			<?php endif; ?>
	
			<?php if (($this->params->get('show_modifier')) && ($this->item->modifier != "") && ($this->params->get('show_modify_date'))) : ?>
			::
			<?php endif; ?>
			
			<?php if (intval($this->item->modified) !=0 && $this->params->get('show_modify_date')) : ?>
				<span class="modified">
				<?php FlexicontentFields::getFieldDisplay($this->item, 'modified', $values=null, $method='display'); ?>
				<?php echo '['.JHTML::_('date', $this->fields['modified']->value[0], JText::_('DATE_FORMAT_LC2')).']'; ?>
				</span>
			<?php endif; ?>
		</span>
	</div>
	<!-- EOF item basic/core info -->
	<?php endif; ?>
	
	<?php if ($this->params->get('show_title', 1)) : ?>
	</header>
	<?php endif; ?>

	<?php if (($this->params->get('show_vote', 1)) || ($this->params->get('show_favs', 1)))  : ?>
	<!-- BOF item rating, favourites -->
	<aside class="itemactions group">
		<?php if ($this->params->get('show_vote', 1)) : ?>
		<span class="voting">
		<?php FlexicontentFields::getFieldDisplay($this->item, 'voting', $values=null, $method='display'); ?>
		<?php echo $this->fields['voting']->display; ?>
		</span>
		<?php endif; ?>

		<?php if ($this->params->get('show_favs', 1)) : ?>
		<span class="favourites">
			<?php FlexicontentFields::getFieldDisplay($this->item, 'favourites', $values=null, $method='display'); ?>
			<?php echo $this->fields['favourites']->display; ?>
		</span>
		<?php endif; ?>
	</aside>
	<!-- EOF item rating, favourites -->
	<?php endif; ?>
	
	
	<?php if (isset($this->item->positions['beforedescription'])) : ?>
	<!-- BOF beforedescription block -->
	<div class="customblock beforedescription group">
		<?php foreach ($this->item->positions['beforedescription'] as $field) : ?>
		<span class="element <?php echo $columnmode; ?>">
			<?php if ($field->label) : ?>
			<span class="flexi label field_<?php echo $field->name; ?>"><?php echo $field->label; ?></span>
			<?php endif; ?>
			<span class="flexi value field_<?php echo $field->name; ?><?php echo !$field->label ? ' nolabel ' : ''; ?>"><?php echo $field->display; ?></span>
		</span>
		<?php endforeach; ?>
	</div>
	<!-- EOF beforedescription block -->
	<?php endif; ?>

	<!-- BOF description block -->
	<div class="description group">
	<?php FlexicontentFields::getFieldDisplay($this->item, 'text', $values=null, $method='display'); ?>
	<?php echo JFilterOutput::ampReplace($this->fields['text']->display); ?>
	</div>
	<!-- EOF description block -->

	<?php if (isset($this->item->positions['afterdescription'])) : ?>
	<!-- BOF afterdescription block -->
	<div class="customblock afterdescription group">
		<?php foreach ($this->item->positions['afterdescription'] as $field) : ?>
		<span class="element <?php echo $columnmode; ?>">
			<?php if ($field->label) : ?>
			<span class="flexi label field_<?php echo $field->name; ?>"><?php echo $field->label; ?></span>
			<?php endif; ?>
			<span class="flexi value field_<?php echo $field->name; ?><?php echo !$field->label ? ' nolabel ' : ''; ?>"><?php echo $field->display; ?></span>
		</span>
		<?php endforeach; ?>
	</div>
	<!-- EOF afterdescription block -->
	<?php endif; ?>
	
	<?php if (($this->params->get('show_tags', 1)) || ($this->params->get('show_category', 1)))  : ?>
	<!-- BOF item categories, tags -->
	<div class="itemadditionnal group">
		<?php if ($this->params->get('show_category', 1)) : ?>
		<span class="categories">
			<?php FlexicontentFields::getFieldDisplay($this->item, 'categories', $values=null, $method='display'); ?>
			<span class="flexi label"><?php echo $this->fields['categories']->label; ?></span>
			<span class="flexi value"><i class="icon-folder-open"></i> <?php echo $this->fields['categories']->display; ?></span>
		</span>
		<?php endif; ?>

		<?php FlexicontentFields::getFieldDisplay($this->item, 'tags', $values=null, $method='display'); ?>
		<?php if ($this->params->get('show_tags', 1) && $this->fields['tags']->display) : ?>
		<span class="tags">
			<span class="flexi label"><?php echo $this->fields['tags']->label; ?></span>
			<span class="flexi value"><i class="icon-tags"></i> <?php echo $this->fields['tags']->display; ?></span>
		</span>
		<?php endif; ?>
	</div>
	<!-- EOF item categories, tags  -->
	<?php endif; ?>

	<?php if ($this->params->get('comments') && !JRequest::getVar('print')) : ?>
	<!-- BOF comments -->
	<section class="comments group">
	<?php
		if ($this->params->get('comments') == 1) :
			if (file_exists(JPATH_SITE.DS.'components'.DS.'com_jcomments'.DS.'jcomments.php')) :
				require_once(JPATH_SITE.DS.'components'.DS.'com_jcomments'.DS.'jcomments.php');
				echo JComments::showComments($this->item->id, 'com_flexicontent', $this->escape($this->item->title));
			endif;
		endif;
	
		if ($this->params->get('comments') == 2) :
			if (file_exists(JPATH_SITE.DS.'plugins'.DS.'content'.DS.'jom_comment_bot.php')) :
    			require_once(JPATH_SITE.DS.'plugins'.DS.'content'.DS.'jom_comment_bot.php');
    			echo jomcomment($this->item->id, 'com_flexicontent');
  			endif;
  		endif;
	?>
	</section>
	<!-- EOF comments -->
	<?php endif; ?>
    
    <?php echo ( ($mainAreaTag == 'section') ? '</article>' : ''); ?>

	<?php if ($this->item->event->afterDisplayContent) : ?>
	<!-- BOF afterDisplayContent -->
	<?php echo ( ($mainAreaTag == 'section') ? '<footer' : '<div'); ?> class="fc_afterDisplayContent group">
		<?php echo $this->item->event->afterDisplayContent; ?>
	<?php echo ( ($mainAreaTag == 'section') ? '</footer>' : '</div>'); ?>
	<!-- EOF afterDisplayContent -->
	<?php endif; ?>
	
<?php echo '</'.$mainAreaTag.'>'; ?>