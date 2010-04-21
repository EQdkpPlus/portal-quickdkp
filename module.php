<?php
/*
 * Project:     EQdkp-Plus
 * License:     Creative Commons - Attribution-Noncommercial-Share Alike 3.0 Unported
 * Link:		http://creativecommons.org/licenses/by-nc-sa/3.0/
 * -----------------------------------------------------------------------
 * Began:       2008
 * Date:        $Date$
 * -----------------------------------------------------------------------
 * @author      $Author$
 * @copyright   2006-2008 Corgan - Stefan Knaak | Wallenium & the EQdkp-Plus Developer Team
 * @link        http://eqdkp-plus.com
 * @package     eqdkp-plus
 * @version     $Rev$
 * 
 * $Id$
 */

if ( !defined('EQDKP_INC') ){
    header('HTTP/1.0 404 Not Found');exit;
}

$portal_module['quickdkp'] = array(
			'name'			    => 'QuickDKP Module',
			'path'			    => 'quickdkp',
			'version'		    => '1.0.2',
			'author'        	=> 'Corgan',
			'contact'		    => 'http://www.eqdkp-plus.com',
			'description'   	=> 'Quick DKP Overview',
			'positions'     	=> array('left1', 'left2', 'right'),
      		'signedin'      	=> '1',
      		'install'       => array(
                            'autoenable'        => '1',
                            'defaultposition'   => 'left1',
                            'defaultnumber'     => '2',
                          ),
    );

$portal_settings['quickdkp'] = array(
);

/**
	 * create a Table with the DKP of all members assigned to the active user
	 * the function defined the TPL Var {POINTSV}
	 * and returned the Array
	 *
	 * @return Array
	 */
if(!function_exists(quickdkp_module)){
  function quickdkp_module()
  {
    global $user, $db, $eqdkp, $dkpplus, $html,$conf_plus,$tpl, $plang,$pdc, $eqdkp_root_path;

		if ( $user->data['user_id'] != ANONYMOUS )
		{
  			$quickdkp = $pdc->get('dkp.portal.modul.quickdkp.'.$user->data['user_id'].'.'.$eqdkp_root_path,false,true);
  			if (!$quickdkp) 
  			{  			  		
				
				$quickdkp  = '<table width="100%" border="0" cellspacing="1" cellpadding="2" >';
				$quickdkp  .='';
				//get member ID from UserID
				$sql3 = 'SELECT member_id
						FROM __member_user
						WHERE user_id = '. $user->data['user_id'] .'';
	
			 	$result3 = $db->query($sql3);
				while ( $row3 = $db->fetch_record($result3) )
				{
					$member_id = $row3[member_id];
					//get member info
	
					$sql	 = 'SELECT m.member_name, m.member_class_id, c.class_name, c.class_id
								FROM __classes c
								INNER JOIN __members m ON c.class_id = m.member_class_id
								where m.member_id='.$member_id ;
	
					$result = $db->query($sql);
					$member_name = '' ;
					$member_classID = '';
					while ( $row = $db->fetch_record($result) )
					{
						$member_name = $row[member_name];
						$member_classID = $row[member_class_id];
						$member_class = $row[class_name];
	
						if($member_name != '')
						{
							$quickdkp  .= ' <tr class="'.$eqdkp->switch_row_class().'"><td colspan=2>'.
														get_classNameImgViewmembers($member_name,$member_class,$member_classID). '</td></tr>';
	
							if($conf_plus['pk_multidkp'] == 1)
							{
	
								$member_multidkp = $dkpplus-> multiDkpMemberArray($row[member_name]) ; // create the multiDKP Table
								if(!empty($member_multidkp[$row[member_name]]))
								{
									 foreach ($member_multidkp[$row[member_name]] as $key)
									 {
										$quickdkp  .= '<tr class="'.$eqdkp->switch_row_class().'"><td>'.$key['name']." ".$plang['Points_DKP'].'</td>
																	<td> <span class='.color_item($key['current']).'>
																	  <b>'.$html->ToolTip($key['dkp_tooltip'],$key['current']). '</b> </span>
																	</td></tr>';
									 } // end foreach
								}
	
							}
							else
							{
								//get DKP
								$sql2 = "SELECT member_earned + member_adjustment - member_spent as dkp
										FROM __members WHERE member_name = '".$member_name."'";
								$result2 = $db->query($sql2);
								$member_dkp = 0 ;
								while ( $row2 = $db->fetch_record($result2) )
								{
										$member_dkp = runden($row2[dkp]);
	
								}
								$db->free_result($result2);
	
									$quickdkp  .= '<tr class="'.$eqdkp->switch_row_class().'">
													<td>'.$plang['Points_DKP'].'</td>
													<td><b>'.$member_dkp. '</b></td></tr>';
	
							} //end else config plus
						} // end if member
					} // end user2member while
					$db->free_result($result);
				} // end member while
	
				$db->free_result($result3);
	
				if(!$member_id > 0)
				{
					$quickdkp  = '<table width="100%" border="0" cellspacing="1" cellpadding="2" class="noborder">';
					$quickdkp  .='<tr><td class="row1">'.$plang['Points_CHAR'].'</td></tr>';
				}
	
				$quickdkp  .='</table>';
				
				$pdc->put('dkp.portal.modul.quickdkp.'.$user->data['user_id'].'.'.$eqdkp_root_path,$quickdkp,86400,false,true);
  			}
				return $quickdkp;
  				
  			
		}
  }
}
?>