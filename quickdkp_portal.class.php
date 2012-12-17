<?php
 /*
 * Project:		EQdkp-Plus
 * License:		Creative Commons - Attribution-Noncommercial-Share Alike 3.0 Unported
 * Link:		http://creativecommons.org/licenses/by-nc-sa/3.0/
 * -----------------------------------------------------------------------
 * Began:		2008
 * Date:		$Date: 2012-09-30 18:05:11 +0200 (So, 30. Sep 2012) $
 * -----------------------------------------------------------------------
 * @author		$Author: godmod $
 * @copyright	2006-2011 EQdkp-Plus Developer Team
 * @link		http://eqdkp-plus.com
 * @package		eqdkp-plus
 * @version		$Rev: 12156 $
 * 
 * $Id: quickdkp_portal.class.php 12156 2012-09-30 16:05:11Z godmod $
 */

if ( !defined('EQDKP_INC') ){
	header('HTTP/1.0 404 Not Found');exit;
}

class quickdkp_portal extends portal_generic {
	public static function __shortcuts() {
		$shortcuts = array('user', 'pdh', 'pdc', 'core', 'html', 'jquery', 'game', 'tpl', 'config');
		return array_merge(parent::$shortcuts, $shortcuts);
	}

	protected $path		= 'quickdkp';
	protected $data		= array(
		'name'			=> 'QuickDKP Module',
		'version'		=> '2.1.0',
		'author'		=> 'EQDKP-PLUS Dev Team',
		'contact'		=> EQDKP_PROJECT_URL,
		'description'	=> 'Quick overview of your DKP',
	);
	protected $positions = array('left1', 'left2', 'right');
	protected $settings	= array(
		'pm_quickdkp_mdkps' => array(
			'name'		=> 'pm_quickdkp_mdkps',
			'language'	=> 'pm_quickdkp_mdkps',
			'property'	=> 'jq_multiselect',
			'options'	=> array(),
		),
		'pm_quickdkp_tooltip' => array(
			'name'		=> 'pm_quickdkp_tooltip',
			'language'	=> 'pm_quickdkp_tooltip',
			'property'	=> 'checkbox'
		),
	);
	protected $install	= array(
		'autoenable'		=> '1',
		'defaultposition'	=> 'left1',
		'defaultnumber'		=> '2',
		'visibility'		=> array(2,3,4),
	);
	
	private $css_added = false;
	
	public function get_settings() {
		$this->settings['pm_quickdkp_mdkps']['options'] = $this->pdh->aget('multidkp', 'name', 0, array($this->pdh->get('multidkp', 'id_list')));
		asort($this->settings['pm_quickdkp_mdkps']['options']);
		return $this->settings;
	}
	
	public function install() {
		$this->config->set(array('pm_quickdkp_mdkps' => serialize(array(max($this->pdh->get('multidkp', 'id_list')))), 'pm_quickdkp_tooltip' => 1));
		$this->create_page_object();
		return $this->install;
	}
	
	public function update_function($old_version) {
		$old_version = str_replace('.', '', $old_version);
		$update_function = 'updatefrom_'.$old_version;
		if(method_exists($this, $update_function)) $this->$update_function();
	}
	
	private function updatefrom_200() {
		$this->create_page_object();
		$this->config->set('pm_quickdkp_tooltip', 1);
	}
	
	private function create_page_object() {
		$preset = array('points', 'earned', array('%member_id%', '%dkp_id%', '%event_id%', '%with_twink%'), array());
		$this->pdh->update_user_preset('event_earned', $preset);
		$preset = array('points', 'spent', array('%member_id%', '%dkp_id%', '%event_id%', 0, '%with_twink%'), array());
		$this->pdh->update_user_preset('event_spent', $preset);
		$preset = array('points', 'adjustment', array('%member_id%', '%dkp_id%', '%event_id%', '%with_twink%'), array());
		$this->pdh->update_user_preset('event_adjustment', $preset);
		$preset = array('points', 'current', array('%member_id%', '%dkp_id%', '%event_id%', 0, '%with_twink%'), array('%dkp_id%', false, false));
		$this->pdh->update_user_preset('event_current', $preset);
		$this->pdh->delete_page($this->config->get('eqdkp_layout'), 'quickdkp');
		$this->pdh->add_page($this->config->get('eqdkp_layout'), 'quickdkp', array(
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
			//Tooltip position:
			switch($this->position){
				case 'left1':
				case 'left2': $ttpos = 'top left';
				break;
				case 'right': $ttpos = 'top right';
				break;
				default: $ttpos = 'top bottom';
			}
		
		
			$quickdkp  = '<table width="100%" border="0" cellspacing="1" cellpadding="2" class="colorswitch">';
			$preset = $this->pdh->pre_process_preset('current', array(), 0);
			$multidkps = $this->pdh->sort($this->pdh->get('multidkp', 'id_list'), 'multidkp', 'name');
			$in_config = ($this->config->get('pm_quickdkp_mdkps')) ? unserialize($this->config->get('pm_quickdkp_mdkps')) : array();
			$in_config = (is_array($in_config)) ? $in_config : array();
			foreach($memberids as $member_id) {
				if(!$this->config->get('pk_show_twinks') && !$this->pdh->get('member', 'is_main', array($member_id))) {
					continue;
				}
				$member_class = $this->game->decorate('classes', array($this->pdh->get('member', 'classid', array($member_id)))).' '.$this->pdh->geth('member', 'memberlink', array($member_id, $this->root_path.'viewcharacter.php'));
				$quickdkp .= '<tr><td colspan="2">'.$member_class.'</td></tr>';
				foreach($multidkps as $mdkpid) {
					if(!in_array($mdkpid, $in_config)) continue;
					$tooltip = '';
					if($this->config->get('pm_quickdkp_tooltip')) {
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
					$current = $this->pdh->geth($preset[0], $preset[1], $preset[2], array('%member_id%' => $member_id, '%dkp_id%' => $mdkpid, '%with_twink%' =>!$this->config->get('pk_show_twinks')));

					$quickdkp .= '<tr><td>'.$this->pdh->get_html_caption('points', 'current', array($mdkpid, true, true, array('my' => $ttpos, 'name' => 'quickdkp_tt'))).'</td>';
					$quickdkp .= ($tooltip != "") ? '<td id="quickdkp_tt'.$member_id.'_'.$mdkpid.'">'.$current.$tooltip : '<td>'.$current;
					$quickdkp .= '</td></tr>';
				}
			}
		} else {
			$quickdkp  = '<table width="100%" border="0" cellspacing="1" cellpadding="2" class="noborder">';
			$quickdkp  .='<tr><td class="row1">'.$this->user->lang('quickdkp_char').'</td></tr>';
		}
		$quickdkp  .='</table>';
		return $quickdkp;
	}
}
if(version_compare(PHP_VERSION, '5.3.0', '<')) registry::add_const('short_quickdkp_portal', quickdkp_portal::__shortcuts());
?>