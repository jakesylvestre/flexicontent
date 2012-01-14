<?php
/**
 * @version 1.5 stable $Id: default.php 183 2009-11-18 10:30:48Z vistamedia $
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

// ensures the PHP version is correct
if (version_compare(PHP_VERSION, '5.0.0', '<'))
{
	echo '<div class="fc-error">';
	echo JText::_( 'FLEXI_UPGRADE_PHP' ) . '<br />';
	echo '</div>';
	return false;
}
?>
<form action="index.php" method="post" name="adminForm">
	<table cellspacing="0" cellpadding="0" border="0" width="100%">
		<tr>
			<td valign="top">
			<table class="adminlist">
				<tr>
					<td>
						<div id="cpanel">
						<?php
						if ((!$this->existfields) || (!$this->existtype) || (!$this->allplgpublish) || (!$this->existlang) || (!$this->existversions) || (!$this->existversionsdata) || (!$this->oldbetafiles) || (!$this->nooldfieldsdata) || ($this->missingversion))
						{
							echo '<div class="fc-error">';
							echo JText::_( 'FLEXI_DO_POSTINSTALL' );
							echo '</div>';
						}
						else if (!$this->existmenu || !$this->existcat || !$this->params->get('flexi_section'))
						{
							echo '<div class="fc-error">';
							if (!$this->params->get('flexi_section') || $this->params->get('flexi_section') == 0)	echo JText::_( 'FLEXI_NO_SECTION_CHOOSEN' ) . '<br />';
							else if (!$this->existcat)	echo JText::_( 'FLEXI_NO_CATEGORIES_CREATED' );
							else if (!$this->existmenu)	echo JText::_( 'FLEXI_NO_MENU_CREATED' );
							echo '</div>';
						}

						global $option;

						$link = 'index.php?option='.$option.'&amp;view=items';
						FlexicontentViewFlexicontent::quickiconButton( $link, 'icon-48-items.png', JText::_( 'FLEXI_ITEMS' ) );
						if ($this->CanAdd)
						{
							$link = 'index.php?option='.$option.'&amp;view=item';
							FlexicontentViewFlexicontent::quickiconButton( $link, 'icon-48-item-add.png', JText::_( 'FLEXI_NEW_ITEM' ) );
						}
						
						if ($this->CanCats || $this->CanAddCats)
						{
							if ($this->CanCats)
							{
								$link = 'index.php?option='.$option.'&amp;view=categories';
								FlexicontentViewFlexicontent::quickiconButton( $link, 'icon-48-categories.png', JText::_( 'FLEXI_CATEGORIES' ) );
							}
							$link = 'index.php?option='.$option.'&amp;view=category';
							FlexicontentViewFlexicontent::quickiconButton( $link, 'icon-48-category-add.png', JText::_( 'FLEXI_NEW_CATEGORY' ) );
						}
						
						if ($this->CanTypes)
						{
							$link = 'index.php?option='.$option.'&amp;view=types';
							FlexicontentViewFlexicontent::quickiconButton( $link, 'icon-48-types.png', JText::_( 'FLEXI_TYPES' ) );
							$link = 'index.php?option='.$option.'&amp;view=type';
							FlexicontentViewFlexicontent::quickiconButton( $link, 'icon-48-type-add.png', JText::_( 'FLEXI_NEW_TYPE' ) );
						}
						
						if ($this->CanFields)
						{
							$link = 'index.php?option='.$option.'&amp;view=fields';
							FlexicontentViewFlexicontent::quickiconButton( $link, 'icon-48-fields.png', JText::_( 'FLEXI_FIELDS' ) );
							$link = 'index.php?option='.$option.'&amp;view=field';
							FlexicontentViewFlexicontent::quickiconButton( $link, 'icon-48-field-add.png', JText::_( 'FLEXI_NEW_FIELD' ) );
						}

						if ($this->CanTags)
						{
							$link = 'index.php?option='.$option.'&amp;view=tags';
							FlexicontentViewFlexicontent::quickiconButton( $link, 'icon-48-tags.png', JText::_( 'FLEXI_TAGS' ) );
							$link = 'index.php?option='.$option.'&amp;view=tag';
							FlexicontentViewFlexicontent::quickiconButton( $link, 'icon-48-tag-add.png', JText::_( 'FLEXI_NEW_TAG' ) );
						}

						if ($this->CanArchives)
						{
							$link = 'index.php?option='.$option.'&amp;view=archive';
							FlexicontentViewFlexicontent::quickiconButton( $link, 'icon-48-archive.png', JText::_( 'FLEXI_ARCHIVE' ) );
						}

						if ($this->CanFiles)
						{
							$link = 'index.php?option='.$option.'&amp;view=filemanager';
							FlexicontentViewFlexicontent::quickiconButton( $link, 'icon-48-file.png', JText::_( 'FLEXI_FILEMANAGER' ) );
						}
						
						if ($this->CanTemplates)
						{
							$link = 'index.php?option='.$option.'&amp;view=templates';
							FlexicontentViewFlexicontent::quickiconButton( $link, 'icon-48-editcss.png', JText::_( 'FLEXI_TEMPLATES' ) );
						}

						if ($this->CanStats)
						{
							$link = 'index.php?option='.$option.'&amp;view=stats';
							FlexicontentViewFlexicontent::quickiconButton( $link, 'icon-48-stats.png', JText::_( 'FLEXI_STATISTICS' ) );
						}

						if ($this->CanPlugins)
						{
							$link = 'index.php?option=com_plugins&amp;filter_type=flexicontent_fields&amp;tmpl=component';
							FlexicontentViewFlexicontent::quickiconButton( $link, 'icon-48-plugins.png', JText::_( 'FLEXI_PLUGINS' ), 1 );
						}
												
						if ($this->params->get('comments') == 1)
						{
							if ($this->CanComments)
							{
								$link = 'index.php?option=com_jcomments&amp;task=view&amp;fog=com_flexicontent&amp;tmpl=component';
								FlexicontentViewFlexicontent::quickiconButton( $link, 'icon-48-comments.png', JText::_( 'FLEXI_COMMENTS' ), 1 );
							}
						}
						
						if (FLEXI_ACCESS)
						{
							if ($this->CanRights)
							{
								$link = 'index.php?option=com_flexiaccess';
								FlexicontentViewFlexicontent::quickiconButton( $link, 'icon-48-editacl.png', JText::_( 'FLEXI_EDIT_ACL' ) );
							}
						}
						
						if ($this->params->get('support_url'))
						{
							$link = $this->params->get('support_url');
							FlexicontentViewFlexicontent::quickiconButton( $link, 'icon-48-support.png', JText::_( 'FLEXI_SUPPORT' ), 1 );
						}
						?>
						</div>
					</td>
				</tr>
			</table>
			</td>
			<td valign="top" width="400px" style="padding: 7px 0 0 5px">
			<?php
			echo $this->pane->startPane( 'stat-pane' );
			
			if ((!$this->existfields) || (!$this->existtype) || (!$this->allplgpublish) || (!$this->existlang) || (!$this->existversions) || (!$this->existversionsdata) || (!$this->oldbetafiles) || (!$this->nooldfieldsdata) || ($this->missingversion))
			{
				$title = JText::_( 'FLEXI_POST_INSTALL' );
				echo $this->pane->startPanel( $title, 'postinstall' );
				echo $this->loadTemplate('postinstall');
				echo $this->pane->endPanel();
			}

			$title = JText::_( 'FLEXI_UNAPPROVED' );
			echo $this->pane->startPanel( $title, 'unapproved' );

				?>
				<table class="adminlist">
				<?php
					$k = 0;
					$n = count($this->unapproved);
					for ($i=0, $n; $i < $n; $i++) {
					$row = $this->unapproved[$i];
					if (FLEXI_ACCESS) {
						$user =& JFactory::getUser();
						$rights = FAccess::checkAllItemAccess('com_content', 'users', $user->gmid, $row->id, $row->catid);
						$canEdit 		= in_array('edit', $rights) || ($user->gid >= 24);
						$canEditOwn		= (in_array('editown', $rights) && ($row->created_by == $user->id)) || ($user->gid >= 24);
					} else {
						$canEdit	= 1;
						$canEditOwn	= 1;
					}
					$link 		= 'index.php?option=com_flexicontent&amp;controller=items&amp;task=edit&amp;cid[]='. $row->id;
				?>
					<tr>
						<td>
						<?php
						if ((!$canEdit) && (!$canEditOwn)) {
							echo htmlspecialchars($row->title, ENT_QUOTES, 'UTF-8');
						} else {
						?>
							<span class="editlinktip hasTip" title="<?php echo JText::_( 'FLEXI_EDIT_ITEM' );?>::<?php echo $row->title; ?>">
								<a href="<?php echo $link; ?>">
									<?php echo htmlspecialchars($row->title, ENT_QUOTES, 'UTF-8'); ?>
								</a>
							</span>
						<?php
						}
						?>
						</td>
					</tr>
					<?php $k = 1 - $k; } ?>
				</table>
				<?php
				$title = JText::_( 'FLEXI_TO_WRITE' );
				echo $this->pane->endPanel();
				echo $this->pane->startPanel( $title, 'openquest' );

				?>
				<table class="adminlist">
				<?php
					$k = 0;
					$n = count($this->openquest);
					for ($i=0, $n; $i < $n; $i++) {
					$row = $this->openquest[$i];
					if (FLEXI_ACCESS) {
						$user =& JFactory::getUser();
						$rights = FAccess::checkAllItemAccess('com_content', 'users', $user->gmid, $row->id, $row->catid);
						$canEdit 		= in_array('edit', $rights) || ($user->gid >= 24);
						$canEditOwn		= (in_array('editown', $rights) && ($row->created_by == $user->id)) || ($user->gid >= 24);
					} else {
						$canEdit	= 1;
						$canEditOwn	= 1;
					}
					$link 		= 'index.php?option=com_flexicontent&amp;controller=items&amp;task=edit&amp;cid[]='. $row->id;
				?>
					<tr>
						<td>
						<?php
						if ((!$canEdit) && (!$canEditOwn)) {
							echo htmlspecialchars($row->title, ENT_QUOTES, 'UTF-8');
						} else {
						?>
							<span class="editlinktip hasTip" title="<?php echo JText::_( 'FLEXI_EDIT_ITEM' );?>::<?php echo $row->title; ?>">
								<a href="<?php echo $link; ?>">
									<?php echo htmlspecialchars($row->title, ENT_QUOTES, 'UTF-8'); ?>
								</a>
							</span>
						<?php
						}
						?>
						</td>
					</tr>
					<?php $k = 1 - $k; } ?>
				</table>
				<?php
				$title = JText::_( 'FLEXI_IN_PROGRESS' );
				echo $this->pane->endPanel();
				echo $this->pane->startPanel( $title, 'inprogress' );

				?>
				<table class="adminlist">
				<?php
					$k = 0;
					$n = count($this->inprogress);
					for ($i=0, $n; $i < $n; $i++) {
					$row = $this->inprogress[$i];
					if (FLEXI_ACCESS) {
						$user =& JFactory::getUser();
						$rights = FAccess::checkAllItemAccess('com_content', 'users', $user->gmid, $row->id, $row->catid);
						$canEdit 		= in_array('edit', $rights) || ($user->gid >= 24);
						$canEditOwn		= (in_array('editown', $rights) && ($row->created_by == $user->id)) || ($user->gid >= 24);
					} else {
						$canEdit	= 1;
						$canEditOwn	= 1;
					}
					$link 		= 'index.php?option=com_flexicontent&amp;controller=items&amp;task=edit&amp;cid[]='. $row->id;
				?>
					<tr>
						<td>
						<?php
						if ((!$canEdit) && (!$canEditOwn)) {
							echo htmlspecialchars($row->title, ENT_QUOTES, 'UTF-8');
						} else {
						?>
							<span class="editlinktip hasTip" title="<?php echo JText::_( 'FLEXI_EDIT_ITEM' );?>::<?php echo $row->title; ?>">
								<a href="<?php echo $link; ?>">
									<?php echo htmlspecialchars($row->title, ENT_QUOTES, 'UTF-8'); ?>
								</a>
							</span>
						<?php
						}
						?>
						</td>
					</tr>
					<?php $k = 1 - $k; } ?>
				</table>
				<?php
				echo $this->pane->endPanel();
				echo $this->pane->endPane();
				?>
				<div class="credits">
					<?php echo JHTML::_('image', 'administrator/components/com_flexicontent/assets/images/logo.png', 'FLEXIcontent' ); ?>
					<p><a href="http://www.flexicontent.org" target="_blank">FLEXIcontent</a> version <?php echo FLEXI_VERSION . ' ' . FLEXI_RELEASE; ?><br />released under the GNU/GPL licence</p>
					<p>Copyright &copy; 2009-2010 / Emmanuel Danan<br />
					<a class="hasTip" href="http://www.vistamedia.fr" target="_blank" title="vistamedia::Professionnal Joomla! developpement and Integration">www.vistamedia.fr</a> - <a class="hasTip" href="http://www.joomla.fr" target="_blank" title="Joomla.fr::The official french support portal">www.joomla.fr</a></p>
					<p>Logo and icons : Greg Berthelot<br />
					<a class="hasTip" href="http://www.artefact-design.com" target="_blank" title="Artefact Design::Professionnal Joomla! Integration">www.artefact-design.com</a></p>
				</div>
			</td>
		</tr>
	</table>
	<input type="hidden" name="option" value="com_flexicontent" />
	<input type="hidden" name="controller" value="" />
	<input type="hidden" name="view" value="" />
	<input type="hidden" name="task" value="" />
	<?php echo JHTML::_( 'form.token' ); ?>
</form>