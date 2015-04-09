<?php
/*	Project:	EQdkp-Plus
 *	Package:	quickDKP Portal Module
 *	Link:		http://eqdkp-plus.eu
 *
 *	Copyright (C) 2006-2015 EQdkp-Plus Developer Team
 *
 *	This program is free software: you can redistribute it and/or modify
 *	it under the terms of the GNU Affero General Public License as published
 *	by the Free Software Foundation, either version 3 of the License, or
 *	(at your option) any later version.
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU Affero General Public License for more details.
 *
 *	You should have received a copy of the GNU Affero General Public License
 *	along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

if ( !defined('EQDKP_INC') ){
	header('HTTP/1.0 404 Not Found');exit;
}

class quickdkp_portal extends portal_generic {

	protected static $path		= 'quickdkp';
	protected static $data		= array(
		'name'			=> 'QuickDKP Module',
		'version'		=> '3.0.2',
		'author'		=> 'EQDKP-PLUS Dev Team',
		'icon'			=> 'fa-user',
		'contact'		=> EQDKP_PROJECT_URL,
		'description'	=> 'Quick overview of your DKP',
		'lang_prefix'	=> 'quickdkp_'
	);
	protected static $positions = array('left1', 'left2', 'right');
	protected $settings	= array(
		'mdkps' => array(
			'type'		=> 'multiselect',
			'options'	=> array(),
		),
		'tooltip' => array(
			'type'		=> 'radio',
			'default'	=> '1',
		),
		'mainfirst' => array(
			'type'		=> 'radio'
		)
	);
	protected static $install	= array(
		'autoenable'		=> '1',
		'defaultposition'	=> 'left1',
		'defaultnumber'		=> '2',
		'visibility'		=> array(2,3,4),
	);
	
	protected static $apiLevel = 20;
	
	private $css_added = false;
	
	public function get_settings($state) {
		$this->settings['mdkps']['options'] = $this->pdh->aget('multidkp', 'name', 0, array($this->pdh->get('multidkp', 'id_list')));
		//$this->settings['mdkps']['default'] = array(max($this->pdh->get('multidkp', 'id_list')));
		asort($this->settings['mdkps']['options']);
		return $this->settings;
	}
	
	public static function install($child=false) {
		self::create_page_object();
		return self::$install;
	}
		
	private static function create_page_object() {
		$pdh = register('pdh');
		$preset = array('points', 'earned', array('%member_id%', '%dkp_id%', '%event_id%', '%with_twink%'), array());
		$pdh->update_user_preset('event_earned', $preset);
		$preset = array('points', 'spent', array('%member_id%', '%dkp_id%', '%event_id%', 0, '%with_twink%'), array());
		$pdh->update_user_preset('event_spent', $preset);
		$preset = array('points', 'adjustment', array('%member_id%', '%dkp_id%', '%event_id%', '%with_twink%'), array());
		$pdh->update_user_preset('event_adjustment', $preset);
		$preset = array('points', 'current', array('%member_id%', '%dkp_id%', '%event_id%', 0, '%with_twink%'), array('%dkp_id%', false, false));
		$pdh->update_user_preset('event_current', $preset);
		$pdh->delete_page('quickdkp');
		$pdh->add_page('quickdkp', array(
			'hptt_quickdkp_tooltip' => array(
				'name' => 'hptt_quickdkp_tooltip',
				'table_main_sub' => '%event_id%',
				'table_subs' => array('%event_id%', '%member_id%', '%dkp_id%'),
				'page_ref' => 'listraids.php',
				'no_root'		=> true,
				'show_numbers' => false,
				'show_select_boxes' => false,
				'show_detail_twink' => false,
				'table_sort_col' => 0,
				'table_sort_dir' => 'desc',
				'table_presets' => array(
					array('name' => 'ename', 'sort' => false, 'th_add' => '', 'td_add' => ''),
					array('name' => 'event_earned', 'sort' => false, 'th_add' => '', 'td_add' => ''),
					array('name' => 'event_spent', 'sort' => false, 'th_add' => '', 'td_add' => ''),
					array('name' => 'event_adjustment', 'sort' => false, 'th_add' => '', 'td_add' => ''),
					array('name' => 'event_current', 'sort' => false, 'th_add' => '', 'td_add' => ''),
				),
			)
		));
	}

	public function output() {
		//get member ID from UserID
		$memberids = $this->pdh->get('member', 'connection_id', array($this->user->data['user_id']));
		if(is_array($memberids) && count($memberids) > 0){
			if (!$this->config->get('enable_points')){
				// lets add the main char at the beginning of the member array
				if($this->config('mainfirst')){
					$main_charid	= $this->pdh->get('member', 'mainchar', array($this->user->data['user_id']));
					if(($key = array_search($main_charid, $memberids)) !== false) {
						unset($memberids[$key]);
						array_unshift($memberids, $main_charid);
					}
				}
				$quickdkp	= '<table class="table fullwidth colorswitch">';
				foreach($memberids as $member_id) {
					$member_class = $this->game->decorate_character($member_id).' '.$this->pdh->geth('member', 'memberlink', array($member_id, $this->routing->simpleBuild("character"), '', false, false, false, true));
					$quickdkp .= '<tr><td colspan="2">'.$member_class.'</td></tr>';
				}
				
			} else {
	
				//Tooltip position:
				switch($this->position){
					case 'left': $ttpos = 'top left';
					break;
					case 'right': $ttpos = 'top right';
					break;
					default: $ttpos = 'top bottom';
				}
			
			
				$quickdkp	= '<table class="table fullwidth colorswitch">';
				$preset		= $this->pdh->pre_process_preset('current', array(), 0);
				$multidkps	= $this->pdh->sort($this->pdh->get('multidkp', 'id_list'), 'multidkp', 'name');
				$in_config	= ($this->config('mdkps')) ? $this->config('mdkps') : array();
				$in_config	= (is_array($in_config)) ? $in_config : array();
				
				// lets add the main char at the beginning of the member array
				if($this->config('mainfirst')){
					$main_charid	= $this->pdh->get('member', 'mainchar', array($this->user->data['user_id']));
					if(($key = array_search($main_charid, $memberids)) !== false) {
					    unset($memberids[$key]);
						array_unshift($memberids, $main_charid);
					}
				}
	
				// start the output
				foreach($memberids as $member_id) {
					if(!$this->config->get('show_twinks') && !$this->pdh->get('member', 'is_main', array($member_id))) {
						continue;
					}
					$member_class = $this->game->decorate_character($member_id).' '.$this->pdh->geth('member', 'memberlink', array($member_id, $this->routing->simpleBuild("character"), '', false, false, false, true));
					$quickdkp .= '<tr><td colspan="2">'.$member_class.'</td></tr>';
					foreach($multidkps as $mdkpid) {
						if(!in_array($mdkpid, $in_config)) continue;
						$tooltip = '';
						if($this->config('tooltip')) {
							$page_setts = $this->pdh->get_page_settings('quickdkp', 'hptt_quickdkp_tooltip');
							$tooltip = '';
							if ($page_setts){
								$events = $this->pdh->get('multidkp', 'event_ids', array($mdkpid));
								$subs = array('%member_id%' => $member_id, '%dkp_id%' => $mdkpid, '%with_twink%' => false);
								$hptt = registry::register('html_pdh_tag_table', array($page_setts, $events, $this->pdh->sort($events, 'event', 'name'), $subs, $member_id.'_'.$mdkpid));
								$tooltip = '<div id="pm_qd_'.$member_id.'_'.$mdkpid.'" style="display:none;"><table width="600" class="no_bg_table">'.$hptt->get_html_table('', '', null, 1, null, true).'</table></div>';
								$this->jquery->qtip('#quickdkp_tt'.$member_id.'_'.$mdkpid, 'return $("#pm_qd_'.$member_id.'_'.$mdkpid.'").html();', array('contfunc' => true, 'classes' => 'quickdkp_tt', 'my' => $ttpos));
								if(!$this->css_added) {
									$this->tpl->add_css('.quickdkp_tt { max-width: 620px !important;}');
									$this->css_added = true;
								}
							}
						}
						$current = $this->pdh->geth($preset[0], $preset[1], $preset[2], array('%member_id%' => $member_id, '%dkp_id%' => $mdkpid, '%with_twink%' =>!$this->config->get('show_twinks')));
	
						$quickdkp .= '<tr><td>'.$this->pdh->get_html_caption('points', 'current', array($mdkpid, true, true, array('my' => $ttpos, 'name' => 'quickdkp_tt'))).'</td>';
						$quickdkp .= ($tooltip != "") ? '<td id="quickdkp_tt'.$member_id.'_'.$mdkpid.'">'.$current.$tooltip : '<td>'.$current;
						$quickdkp .= '</td></tr>';
					}
				}
			}
		} else {
			return $this->user->lang('quickdkp_char');
		}
		$quickdkp  .='</table>';
		return $quickdkp;
	}
}
?>
