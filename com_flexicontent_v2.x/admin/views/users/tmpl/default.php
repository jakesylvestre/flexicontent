<?php defined('_JEXEC') or die('Restricted access'); ?>

<script language="javascript" type="text/javascript">

function fetchcounter()
{
	var url = "index.php?option=com_flexicontent&controller=items&task=getorphans&format=raw";
	var ajax = new Ajax(url, {
		method: 'get',
		update: $('count'),
		onComplete:function(v) {
			if(v==0)
				if(confirm("<?php echo JText::_( 'FLEXI_ITEMS_REFRESH_CONFIRM',true ); ?>"))
					location.href = 'index.php?option=com_flexicontent&view=items';
		}
	});
	ajax.request();
}

// the function overloads joomla standard event
function submitform(pressbutton)
{
	form = document.adminForm;
	// If formvalidator activated
	if( pressbutton == 'remove' ) {
		var answer = confirm('<?php echo JText::_( 'FLEXI_ITEMS_DELETE_CONFIRM',true ); ?>')
		if (!answer){
			new Event(e).stop();
			return;
		} else {
			// Store the button task into the form
			if (pressbutton) {
				form.task.value=pressbutton;
			}

			// Execute onsubmit
			if (typeof form.onsubmit == "function") {
				form.onsubmit();
			}
			// Submit the form
			form.submit();
		}
	} else {
		// Store the button task into the form
		if (pressbutton) {
			form.task.value=pressbutton;
		}

		// Execute onsubmit
		if (typeof form.onsubmit == "function") {
			form.onsubmit();
		}
		// Submit the form
		form.submit();
	}
}

// delete active filter
function delFilter(name)
{
	var myForm = $('adminForm');
	if ($(name).type=='checkbox')
		$(name).checked = '';
	else
		$(name).setProperty('value', '');
}

function delAllFilters() {
	delFilter('search'); delFilter('filter_itemscount');
	delFilter('filter_logged'); delFilter('filter_usergrp');
	delFilter('startdate'); delFilter('enddate');
	delFilter('filter_id');
}

window.addEvent('domready', function(){
	var startdate	= $('startdate');
	var enddate 	= $('enddate');
	if(MooTools.version>="1.2.4") {
		var sdate = startdate.value;
		var edate = enddate.value;
	}else{
		var sdate = startdate.getValue();
		var edate = enddate.getValue();
	}
	if (sdate == '') {
		startdate.setProperty('value', '<?php echo JText::_( 'FLEXI_FROM',true ); ?>');
	}
	if (edate == '') {
		enddate.setProperty('value', '<?php echo JText::_( 'FLEXI_TO',true ); ?>');
	}
	$('startdate').addEvent('focus', function() {
		if (sdate == '<?php echo JText::_( 'FLEXI_FROM',true ); ?>') {
			startdate.setProperty('value', '');
		}
	});
	$('enddate').addEvent('focus', function() {
		if (edate == '<?php echo JText::_( 'FLEXI_TO',true ); ?>') {
			enddate.setProperty('value', '');
		}
	});
	$('startdate').addEvent('blur', function() {
		if (sdate == '') {
			startdate.setProperty('value', '<?php echo JText::_( 'FLEXI_FROM',true ); ?>');
		}
	});
	$('enddate').addEvent('blur', function() {
		if (edate == '') {
			enddate.setProperty('value', '<?php echo JText::_( 'FLEXI_TO',true ); ?>');
		}
	});

/*
	$('show_filters').setStyle('display', 'none');
	$('hide_filters').addEvent('click', function() {
		$('filterline').setStyle('display', 'none');
		$('show_filters').setStyle('display', '');
		$('hide_filters').setStyle('display', 'none');
	});
	$('show_filters').addEvent('click', function() {
		$('filterline').setStyle('display', '');
		$('show_filters').setStyle('display', 'none');
		$('hide_filters').setStyle('display', '');
	});
*/
});
</script>

<div class="flexicontent">
<form action="index.php?option=com_flexicontent&controller=users&view=users" method="post" name="adminForm">
	
	<table class="adminlist" cellpadding="1">
		<thead>
			<tr>
				<th class="center" style="width:24px;">
					<?php echo JText::_( 'NUM' ); ?>
				</th>
				<th class="center" style="width:24px;">
					<input type="checkbox" name="toggle" value="" onclick="<?php echo FLEXI_J30GE ? 'Joomla.checkAll(this);' : 'checkAll('.count( $this->items).');'; ?>" />
				</th>
				<th class="left">
					<?php echo JHTML::_('grid.sort',   'Name', 'a.name', @$this->lists['order_Dir'], @$this->lists['order'] ); ?>
					<?php if ($this->search) : ?>
					<span class="hasTip filterdel" title="<?php echo JText::_('FLEXI_REMOVE_THIS_FILTER_DESC') ?>">
						<img src="components/com_flexicontent/assets/images/bullet_delete.png" alt="<?php echo JText::_('FLEXI_REMOVE_THIS_FILTER') ?>" onclick="delFilter('search');document.adminForm.submit();" />
					</span>
					<?php endif; ?>
				</th>
				<th class="center" >
					<?php echo JHTML::_('grid.sort',   'FLEXI_ITEMS', 'itemscount', @$this->lists['order_Dir'], @$this->lists['order'] ); ?>
					<?php if ($this->filter_itemscount) : ?>
					<span class="hasTip filterdel" title="<?php echo JText::_('FLEXI_REMOVE_THIS_FILTER_DESC') ?>">
						<img src="components/com_flexicontent/assets/images/bullet_delete.png" alt="<?php echo JText::_('FLEXI_REMOVE_THIS_FILTER') ?>" onclick="delFilter('filter_itemscount');document.adminForm.submit();" />
					</span>
					<?php endif; ?>
				</th>
				<th class="left" >
					<?php echo JHTML::_('grid.sort',   'Username', 'a.username', @$this->lists['order_Dir'], @$this->lists['order'] ); ?>
				</th>
				<th class="center" nowrap="nowrap">
					<?php echo JHTML::_('grid.sort',   'Logged In', 'loggedin', @$this->lists['order_Dir'], @$this->lists['order'] ); ?>
					<?php if ($this->filter_logged) : ?>
					<span class="hasTip filterdel" title="<?php echo JText::_('FLEXI_REMOVE_THIS_FILTER_DESC') ?>">
						<img src="components/com_flexicontent/assets/images/bullet_delete.png" alt="<?php echo JText::_('FLEXI_REMOVE_THIS_FILTER') ?>" onclick="delFilter('filter_logged');document.adminForm.submit();" />
					</span>
					<?php endif; ?>
				</th>
				<th class="center" nowrap="nowrap">
					<?php echo JHTML::_('grid.sort',   'Enabled', 'a.block', @$this->lists['order_Dir'], @$this->lists['order'] ); ?>
				</th>
				<th class="center">
					<?php echo FLEXI_J16GE ? JText::_( 'FLEXI_USERGROUPS' ) : JHTML::_('grid.sort',   'Group', 'groupname', @$this->lists['order_Dir'], @$this->lists['order'] ); ?>
					<?php if ($this->filter_usergrp) : ?>
					<span class="hasTip filterdel" title="<?php echo JText::_('FLEXI_REMOVE_THIS_FILTER_DESC') ?>">
						<img src="components/com_flexicontent/assets/images/bullet_delete.png" alt="<?php echo JText::_('FLEXI_REMOVE_THIS_FILTER') ?>" onclick="delFilter('filter_usergrp');document.adminForm.submit();" />
					</span>
					<?php endif; ?>
				</th>
				<th class="left">
					<?php echo JHTML::_('grid.sort',   'E-Mail', 'a.email', @$this->lists['order_Dir'], @$this->lists['order'] ); ?>
				</th>
				<th width="110" class="center">
					<?php echo JHTML::_('grid.sort',   'Registered', 'a.registerDate', @$this->lists['order_Dir'], @$this->lists['order'] ); ?>
					<?php
					if ($this->date == '1') :
						if (($this->startdate && ($this->startdate != JText::_('FLEXI_FROM'))) || ($this->enddate && ($this->startdate != JText::_('FLEXI_TO')))) :
					?>
					<span class="hasTip filterdel" title="<?php echo JText::_('FLEXI_REMOVE_THIS_FILTER_DESC') ?>">
						<img src="components/com_flexicontent/assets/images/bullet_delete.png" alt="<?php echo JText::_('FLEXI_REMOVE_THIS_FILTER') ?>" onclick="delFilter('startdate');delFilter('enddate');document.adminForm.submit();" />
					</span>
					<?php
						endif;
					endif;
					?>
				</th>
				<th width="110" class="center">
					<?php echo JHTML::_('grid.sort',   'Last Visit', 'a.lastvisitDate', @$this->lists['order_Dir'], @$this->lists['order'] ); ?>
					<?php
					if ($this->date == '2') :
						if (($this->startdate && ($this->startdate != JText::_('FLEXI_FROM'))) || ($this->enddate && ($this->startdate != JText::_('FLEXI_TO')))) :
					?>
					<span class="hasTip filterdel" title="<?php echo JText::_('FLEXI_REMOVE_THIS_FILTER_DESC') ?>">
						<img src="components/com_flexicontent/assets/images/bullet_delete.png" alt="<?php echo JText::_('FLEXI_REMOVE_THIS_FILTER') ?>" onclick="delFilter('startdate');delFilter('enddate');document.adminForm.submit();" />
					</span>
					<?php
						endif;
					endif;
					?>
				</th>
				<th class="center" nowrap="nowrap">
					<?php echo JHTML::_('grid.sort',   'ID', 'a.id', @$this->lists['order_Dir'], @$this->lists['order'] ); ?>
					<?php if ($this->filter_id) : ?>
					<span class="hasTip filterdel" title="<?php echo JText::_('FLEXI_REMOVE_THIS_FILTER_DESC') ?>">
						<img src="components/com_flexicontent/assets/images/bullet_delete.png" alt="<?php echo JText::_('FLEXI_REMOVE_THIS_FILTER') ?>" onclick="delFilter('filter_id');document.adminForm.submit();" />
					</span>
					<?php endif; ?>
				</th>
			</tr>

			<tr id="filterline">
				<td class="left col_title" colspan="3">
					<label class="label"><?php echo JText::_( 'FLEXI_SEARCH' ); ?></label>
					<input type="text" name="search" id="search" value="<?php echo htmlspecialchars($this->lists['search']);?>" class="text_area" style='width:140px;' onchange="document.adminForm.submit();" />
				</td>
				<td class="left col_itemscount">
					<?php echo $this->lists['filter_itemscount']; ?>
				</td>
				<td class="left"></td>
				<td class="left col_logged">
					<?php echo $this->lists['filter_logged']; ?>
				</td>
				<td class="left"></td>
				<td class="left col_usergrp">
					<?php echo $this->lists['filter_usergrp']; ?>
				</td>
				<td class="left"></td>
				<td class="left col_registered col_visited" colspan="2">
					<span class="radio"><?php echo $this->lists['date']; ?></span>
					<?php echo $this->lists['startdate']; ?>&nbsp;&nbsp;<?php echo $this->lists['enddate']; ?>
				</td>
				<td class="left col_id">
					<input type="text" name="filter_id" id="filter_id" value="<?php echo $this->lists['filter_id']; ?>" class="inputbox" />
				</td>
			</tr>

			<tr>
				<td colspan="12" class="filterbuttons">
					<div id="fc-filter-buttons">
						<input type="submit" class="fc_button fcsimple" onclick="this.form.submit();" value="<?php echo JText::_( 'FLEXI_APPLY_FILTERS' ); ?>" />
						<input type="button" class="fc_button fcsimple" onclick="delAllFilters();this.form.submit();" value="<?php echo JText::_( 'FLEXI_RESET_FILTERS' ); ?>" />
					</div>
				
					<div class="limit" style="display: inline-block; margin-left: 24px;">
						<?php echo JText::_(FLEXI_J16GE ? 'JGLOBAL_DISPLAY_NUM' : 'DISPLAY NUM') . $this->pagination->getLimitBox(); ?>
					</div>
					
					<span class="fc_item_total_data fc_nice_box" style="margin-right:10px;" >
						<?php echo @$this->resultsCounter ? $this->resultsCounter : $this->pagination->getResultsCounter(); // custom Results Counter ?>
					</span>
					
					<span class="fc_pages_counter">
						<?php echo $this->pagination->getPagesCounter(); ?>
					</span>
					
					<div class='fc_mini_note_box' style='display: inline-block; float:right; clear:both!important;'>
					<?php
					if (FLEXI_J16GE) {
						$tz_string = JFactory::getApplication()->getCfg('offset');
						$tz = new DateTimeZone( $tz_string );
						$tz_offset = $tz->getOffset(new JDate()) / 3600;
						$tz_info =  $tz_offset > 0 ? ' UTC +'.$tz_offset : ' UTC '.$tz_offset;
						$tz_info .= ' ('.$tz_string.')';
						echo JText::sprintf( 'FLEXI_DATES_IN_USER_TIMEZONE_NOTE', '', $tz_info);
					} else {
						$tz_offset = JFactory::getApplication()->getCfg('offset');
						$tz_info =  ($tz_offset > 0) ? ' UTC +'. $tz_offset : ' UTC '. $tz_offset;
						echo JText::sprintf( 'FLEXI_DATES_IN_SITE_TIMEZONE_NOTE', '', $tz_info );
					}
					?>
					</div>

	<!--
					<span style="float:right;">
						<input type="button" class="button" onclick="delAllFilters();this.form.submit();" value="<?php echo JText::_( 'FLEXI_RESET_FILTERS' ); ?>" />
						<input type="button" class="button submitbutton" onclick="this.form.submit();" value="<?php echo JText::_( 'FLEXI_APPLY_FILTERS' ); ?>" />
						
						<input type="button" class="button" id="hide_filters" value="<?php echo JText::_( 'FLEXI_HIDE_FILTERS' ); ?>" />
						<input type="button" class="button" id="show_filters" value="<?php echo JText::_( 'FLEXI_DISPLAY_FILTERS' ); ?>" />
					</span>
	-->
				</td>
			</tr>


		</thead>
		<tfoot>
			<tr>
				<td colspan="12">
					<?php echo $this->pagination->getListFooter(); ?>
				</td>
			</tr>
		</tfoot>
		<tbody>
		<?php
			$k = 0;
			for ($i=0, $n=count( $this->items ); $i < $n; $i++)
			{
				$row 	=& $this->items[$i];
				if (FLEXI_J16GE) {
					$row->groupname = array();
					foreach($row->usergroups as $row_ugrp_id) {
						$row->groupname[] = $this->usergroups[$row_ugrp_id]->title;
					}
					$row->groupname = implode(', ', $row->groupname);
				}

				$img_path  = '../components/com_flexicontent/assets/images/';
				$tick_img  = $img_path . 'tick.png';
				$block_img = $img_path . ($row->block ? 'publish_x.png' : 'tick.png');
				$task_block= (FLEXI_J16GE ? 'users.' : '') . ($row->block ? 'unblock' : 'block');
				$users_task = FLEXI_J16GE ? 'task=users.' : 'controller=users&amp;task=';
				$alt   = $row->block ? JText::_( 'Enabled' ) : JText::_( 'Blocked' );
				$link  = 'index.php?option=com_flexicontent&amp;controller=users&amp;view=user&amp;'.$users_task.'edit&amp;cid[]='. $row->id. '';

				if ($row->lastvisitDate == "0000-00-00 00:00:00") {
					$lvisit = JText::_( 'Never' );
				} else {
					$lvisit	= JHTML::_('date', $row->lastvisitDate, FLEXI_J16GE ? 'Y-m-d H:i:s' : '%Y-%m-%d %H:%M:%S');
				}
				$registered	= JHTML::_('date', $row->registerDate, FLEXI_J16GE ? 'Y-m-d H:i:s' : '%Y-%m-%d %H:%M:%S');

				if ($row->itemscount) {
					$itemslink 	= 'index.php?option=com_flexicontent&amp;view=items&amp;filter_authors='. $row->id;
					$itemscount = '[<a onclick="delAllFilters();"  href="'.$itemslink.'">'.$row->itemscount.'</a>]';
				} else {
					$itemscount = '['.$row->itemscount.']';
				}
			?>
			<tr class="<?php echo "row$k"; ?>">
				<td class="center">
					<?php echo $i+1+$this->pagination->limitstart;?>
				</td>
				<td class="center">
					<?php echo JHTML::_('grid.id', $i, $row->id ); ?>
				</td>
				<td class="col_title">
					<a href="<?php echo $link; ?>">
						<?php echo $row->name; ?></a>
				</td>
				<td align="center" class="col_itemscount">
					<?php echo $itemscount; ?>
				</td>
				<td>
					<!-- <a class="modal" rel="{handler: 'iframe', size: {x: 800, y: 500}, onClose: function() {alert('hello');} }" href="<?php echo $link; ?>"> -->
					<?php echo $row->username; ?>
					<!-- </a> -->
				</td>
				<td align="center" class="col_logged">
					<?php echo $row->loggedin ? '<img src="'.$tick_img.'" width="16" height="16" border="0" alt="" />': ''; ?>
				</td>
				<td align="center">
					<a href="javascript:void(0);" onclick="return listItemTask('cb<?php echo $i;?>','<?php echo $task_block;?>')">
						<img src="images/<?php echo $block_img;?>" width="16" height="16" border="0" alt="<?php echo $alt; ?>" /></a>
				</td>
				<td align="center" class="col_usergrp">
					<?php echo JText::_( $row->groupname ); ?>
				</td>
				<td align="left">
					<a href="mailto:<?php echo $row->email; ?>">
						<?php echo $row->email; ?></a>
				</td>
				<td align="center" nowrap="nowrap" class="col_registered">
					<?php echo $registered; ?>
				</td>
				<td align="center" nowrap="nowrap" class="col_visited">
					<?php echo $lvisit; ?>
				</td>
				<td class="left col_id">
					<?php echo $row->id; ?>
				</td>
			</tr>
			<?php
				$k = 1 - $k;
				}
			?>
		</tbody>
	</table>

	<input type="hidden" name="boxchecked" value="0" />
	<input type="hidden" name="option" value="com_flexicontent" />
	<input type="hidden" name="controller" value="users" />
	<input type="hidden" name="view" value="users" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="filter_order" value="<?php echo $this->lists['order']; ?>" />
	<input type="hidden" name="filter_order_Dir" value="<?php echo $this->lists['order_Dir']; ?>" />
	<?php echo JHTML::_( 'form.token' ); ?>
</form>
</div>