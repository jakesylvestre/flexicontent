<?php
/**
 * @version 1.5 stable $Id: category_alpha.php 171 2010-03-20 00:44:02Z emmanuel.danan $
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

function utf8ord($char) {
	$i = 0;
	$number = '';
	while (isset($char{$i})) {
		$number.= ord($char{$i});
		++$i;
	}
	return $number;
}

$show_alpha = $this->params->get('show_alpha',1);
if ($show_alpha == 1) {
	// Language Default
	$alphacharacters = JTEXT::_("FLEXI_ALPHA_INDEX_CHARACTERS");
	$groups = explode("|", $alphacharacters);
	$groupcssclasses = explode("|", JTEXT::_("FLEXI_ALPHA_INDEX_CSSCLASSES"));
	$alphacharsep = JTEXT::_("FLEXI_ALPHA_INDEX_SEPARATOR");
} else {  // $show_alpha == 2
	// Custom setting
	$alphacharacters = $this->params->get('alphacharacters', "a,b,c,d,e,f,g,h,i,j,k,l,m,n,o,p,q,r,s,t,u,v,w,y,z|0,1,2,3,4,5,6,7,8,9");
	$groups = explode("|", $alphacharacters);
	$groupcssclasses = explode("|", $this->params->get('alphagrpcssclasses'));
	$alphacharsep = $this->params->get('alphacharseparator','');
}
$alphaskipempty = $this->params->get('alphaskipempty',0);

// a. Trim classes names
foreach ($groupcssclasses as $i => $grpcssclass) {
	$groupcssclasses[$i] = trim($grpcssclass);
}
// b. Check for empty first value, means initial string was empty ... and set empty array
if ($groupcssclasses[0]=='') $groupcssclasses = array();
// c. Set missing classes to class 'letters'
for($i=count($groupcssclasses); $i<count($groups); $i++) {
	$groupcssclasses[$i] = 'letters';
}
?>

<div id="fc_alpha">
	<?php
	$flag = true;
	$i=0;
	foreach($groups as $group) {
		$letters = explode(",", $group);
	?>
	<div class="<?php echo $groupcssclasses[$i]; ?>">
	<?php if($flag) {?>
	<a class="fc_alpha_index" href="#" onclick="document.getElementById('alpha_index').value='';document.getElementById('adminForm').submit();"><?php echo JText::_('FLEXI_ALL'); ?></a>
	<?php $flag = false;}?>
	<?php
		foreach ($letters as $letter) :
			// a. Skip on empty $letter (2 commas ,,)
			$letter = trim($letter);
			if ($letter==='') continue;
			
			// b. Check for ALIASes
			$letter_label = $letter;
			if ($letter==='#' ) {
				$letter = "0-9";
			}
			
			// c. Try to get range of characters
			$range = explode("-", $letter);
			
			// d. Check if character exists 
			if(count($range)==1) {
				// ERROR CHECK: String has single character
				if (mb_strlen($letter) > 1) {
					echo "Error in Alpha Index please correct letter: ".$letter." must have only one character<br>";
					continue;
				}
				// Check Single character exists
				$has_item = in_array($letter, $this->alpha);
			} else {
				// ERROR CHECK: Character range has only one minus(-)
				if (count($range) != 2) {
					echo "Error in Alpha Index please correct letter range: ".$letter."<br>";
					continue;
				}
				
				// Get range characters
				$startletter = $range[0];  $endletter = $range[1];
				
				// ERROR CHECK: Range START and END are single character strings
				if (mb_strlen($startletter) > 1 || mb_strlen($endletter) > 1) {
					echo "Error in Alpha Index please correct letter range: ".$letter." start and end must be one character<br>";
					continue;
				}
				
				// Get rangle length
				$range_length = utf8ord($endletter) - utf8ord($startletter);
				
				// ERROR CHECK: Character range has at least one character
				if ($range_length > 200 || $range_length < 0) {
					// A sanity check that the range is something logical and that 
					echo "Error in Alpha Index, letter range: ".$letter.", is incorrect or contains more that 200 characters<br>";
				}
				// Check at that range has at least on character
				for($ch=$startletter; $ch<=$endletter; $ch++) :
					if (in_array($ch, $this->alpha)) {
						$has_item = true;
						break;
					}
				endfor;
			}
			
			if ($alphacharsep) $aiclass = "fc_alpha_index_sep";
			else $aiclass = "fc_alpha_index";
			if ($has_item) :
				if ($alphacharsep) echo "<span class=\"fc_alpha_index_sep\">$alphacharsep</span>";
				echo "<a class=\"$aiclass\" href=\"#\" onclick=\"document.getElementById('alpha_index').value='".$letter."'; document.getElementById('adminForm').submit();\">".strtoupper($letter_label)."</a>";
			elseif (!$alphaskipempty) :
				if ($alphacharsep) echo "<span class=\"fc_alpha_index_sep\">$alphacharsep</span>";
				echo "<span class=\"$aiclass\">".strtoupper($letter_label)."</span>";
			endif;
		endforeach;
	?>
	</div>
	<?php
	}?>
</div>
