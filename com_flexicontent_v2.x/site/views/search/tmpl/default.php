<?php defined('_JEXEC') or die('Restricted access'); ?>

<div id='flexicontent'>
	
<?php if ( $this->params->get( 'show_page_title', 1 ) ) : ?>
<div class="componentheading<?php echo $this->escape($this->params->get('pageclass_sfx')); ?>">
	<?php echo $this->params->get( 'page_title' ); ?>
</div>
<?php endif; ?>

<?php 

if (!JRequest::getVar('print',0)) {
	echo flexicontent_html::printbutton( $this->print_link, $this->params );
}
?>

<?php if (!JRequest::getVar('print',0)) echo $this->loadTemplate('form'); ?>
<?php if(!$this->error && count($this->results) > 0) :
	echo $this->loadTemplate('results');
else :
	echo $this->loadTemplate('error');
endif; ?>

</div>