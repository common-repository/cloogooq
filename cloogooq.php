<?php
/*
Plugin Name: CloOGooQ

Plugin URI: http://blog.vimagic.de/cloud-of-google-queries-wordpress-plugin/

Description: Cloud-O-Google Queries is a filter to display keyword clouds based on search engine queries.  Once activated head over to the <a href="options-general.php?page=cloogooq.php">CloOGooQ options</a> panel.

Version: 1.1

History:
1.0 initial release
1.1 new option: exclude numbers
	new option: sorting by weight, centered

Author: Thomas M. B&ouml;sel
Author URI: http://blog.vimagic.de/

Lisense : GPL(http://www.gnu.org/copyleft/gpl.html)
/*  Copyright 2006  Thomas M. Bosel  (email : tmb@vimagic.de, site : http://blog.vimagic.de)
**
**  This program is free software; you can redistribute it and/or modify
**  it under the terms of the GNU General Public License as published by
**  the Free Software Foundation; either version 2 of the License, or
**  (at your option) any later version.
**
**  This program is distributed in the hope that it will be useful,
**  but WITHOUT ANY WARRANTY; without even the implied warranty of
**  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
**  GNU General Public License for more details.
**
**  You should have received a copy of the GNU General Public License
**  along with this program; if not, write to the Free Software
**  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

define("DEBUGGING","0");

class WpClooGooQ {
	////////////////////
	// USER-VARIABLES //
	////////////////////
	var $user_min_hits_key;			// MINIMUM NUMBER OF QUERIES REQUIRED FOR DISPLAY
	var $user_min_hits_phr;			// MINIMUM NUMBER OF QUERIES REQUIRED FOR DISPLAY
	var $user_min_char;				// MINIMUM CHARACTER LENGTH FOR A WORD
	var	$user_min_font_size;		// MINIMUM FONT SIZE
	var	$user_min_font_color;		// MINIMUM FONT SIZE
	var $user_max_number_of_items;	// LIMITS THE NUMBER OF KEYWORDS RETURNED
	var	$user_max_font_size;		// MAXIMUM FONT SIZE
	var	$user_max_font_color;		// MAXIMUM FONT COLOR
	var	$user_perc_line_height;		// LINE HEIGHT IN PERCENT OF MAX_FONT_SIZE		
	var $user_separator;			// SEPARATOR STRING
	var $user_sep_space_prior;		// INSERT SPACE BEFORE SEPARATOR?
	var $user_sep_space_after;		// INSERT SPACE AFTER SEPARATOR
	var $user_exclude_domain;		// DOMAIN EXCLUDE LIST
	var $user_exclude;				// EXCLUDE LIST
	var $user_include;				// INCLUDE LIST
	var $user_table_name;			// TABLE NAME FOR SEARCH QUERIES
	var $user_table_key;			// REFERER KEY
	var $user_include_local_search;	// INCLUDE LOCAL SEARCHES?
	var $user_table_name_local;		// TABLE NAME FOR LOCAl SEARCHES
	var $user_table_key_local;		// URL KEY
	var $user_querykeys;			// STRINKG OF QUERY KEYS
	var $user_link_to;				// LINK KEYWORDS TO ?
	var $user_sort;					// SORT KEYWORDS BY ?
	var $user_limit;
	var $user_table_id;
	var $options;
	//////////////////////////////////////////////////////////////////////////////
	// PRELIMINARIES															//
	//////////////////////////////////////////////////////////////////////////////
	function WpClooGooQ() {
		$this->user_min_hits_key=2;
		$this->user_min_hits_phr=3;
		$this->user_min_char=4;
		$this->user_include_numbers=1;
		$this->user_exclude_numbers=0;
		$this->user_min_font_size=10;
		$this->user_min_font_color='000000';
		$this->user_max_number_of_items=50;
		$this->user_max_font_size=30;
		$this->user_max_font_color='FF0000';
		$this->user_perc_line_height='0.75';
		$this->user_separator='<font style="font-size:10px;font-weight:bold;color:#BBB"> &curren; </font>';
		$this->user_sep_space_prior=1;
		$this->user_sep_space_after=1;
		$this->user_exclude_domain=array("search.live.com");
		$this->user_exclude=array("und","von","33");
		$this->user_include=array("tmb");
		$this->user_table_name='wp_Counterize';
		$this->user_table_key='referer';
		$this->user_table_name_local='wp_Counterize';
		$this->user_table_key_local='url';
		$this->user_include_local_search=1;
		$this->user_querykeys=array('q=','as_q=','p=','query=','qkw=','key=','su=');
		$this->user_link_to='locally';
		$this->pluginURL = trailingslashit(get_settings('home'));	
		$this->user_sort='random';
		$this->user_scaling='weighted';
		$this->user_limit=5000;
		$this->user_table_id='id';
		$this->options= array (
			'user_min_hits_key' 	=> 'int',
			'user_min_hits_phr' 	=> 'int',
			'user_min_char' 		=> 'int',
			'user_include_numbers' 	=> 'bool',
			'user_exclude_numbers' 	=> 'bool',
			'user_min_font_size' 	=> 'int',
			'user_min_font_color' 	=> 'string',
			'user_max_number_of_items' => 'int',
			'user_max_font_size' 	=> 'int',
			'user_max_font_color' 	=> 'string',
			'user_perc_line_height'	=> 'string',
			'user_separator' 		=> 'html',
			'user_sep_space_prior'	=> 'bool',
			'user_sep_space_after'	=> 'bool',
			'user_exclude_domain' 	=> 'array',
			'user_exclude' 			=> 'array',
			'user_include' 			=> 'array',
			'user_table_name' 		=> 'string',
			'user_table_key' 		=> 'string',
			'user_include_local_search' => 'bool',
			'user_table_name_local' => 'string',
			'user_table_key_local' 	=> 'string',
			'user_querykeys' 		=> 'array',
			'user_link_to' 			=> 'string',
			'user_sort' 			=> 'string',
			'user_scaling' 			=> 'string',
			'user_limit' 			=> 'int',
			'user_table_id' 		=> 'string'
		);
		add_filter('the_content', array(&$this, '_filter'), 2);
	}

	//////////////////////////////////////////////////////////
	// 														//
	//////////////////////////////////////////////////////////
	function _decode($str) {
		return strtolower(get_settings('blog_charset')) == 'utf-8' ? utf8_encode($str) : $str;
	}
	
	//////////////////////////////////////////////////////////////////////////////
	//	cgq() - PRINTS GOOGLE KEYWORD CLOUD							  			//
	//			NEEDS THE SOME STATISTICS PLUGIN THAT KEEPS TRACK OF THE REFERER//
	//////////////////////////////////////////////////////////////////////////////
	function cgq($mode) {
		$this->get_setting();
		if($mode=="cumulus")	{
			$this->user_min_hits_key=9;
			$this->user_min_font_size=3;
			$this->user_max_font_size=30;
		}
		/////////////////////////////////////
		// FETCHING SEARCH ENGINE KEYWORDS //
		/////////////////////////////////////
		if($this->user_limit>0 && $this->user_table_id!='')	{
			$entries = array();
			if($this->user_include_local_search)	{
				array_push($this->user_querykeys,'s=');
				$foo=cloogooq_getQueries($this->user_table_name_local,$this->user_table_key_local,'s=',$this->user_table_id,$this->user_limit);
				foreach($foo as $f => $o)	{
					$id=$this->user_table_id;
					$key=$this->user_table_key_local;
					if(!preg_match ("/cache/i", $o->$key)) {$entries[$o->$id]=$o->$key;}
				}
			}
			foreach($this->user_querykeys as $querykey)	{
				$foo=cloogooq_getQueries($this->user_table_name,$this->user_table_key,"&".$querykey,$this->user_table_id,$this->user_limit);
				foreach($foo as $f => $o)	{
					$id=$this->user_table_id;
					$key=$this->user_table_key;
					if(!preg_match ("/cache/i", $o->$key)) {$entries[$o->$id]=$o->$key;}
				}
				$foo=cloogooq_getQueries($this->user_table_name,$this->user_table_key,"?".$querykey,$this->user_table_id,$this->user_limit);
				foreach($foo as $f => $o)	{
					$id=$this->user_table_id;
					$key=$this->user_table_key;
					if(!preg_match ("/cache/i", $o->$key)) {$entries[$o->$id]=$o->$key;}
				}
			}
			krsort($entries);
			array_splice($entries,$this->user_limit);
		}
		else	{
			if($this->user_include_local_search)	{
				array_push($this->user_querykeys,'s=');
				$entries=cloogooq_getQueries($this->user_table_name_local,$this->user_table_key_local,'s=',$this->user_table_id,$this->user_limit);
			}
			else	{$entries = array();}
			foreach($this->user_querykeys as $querykey)	{
				$foo=cloogooq_getQueries($this->user_table_name,$this->user_table_key,"&".$querykey,$this->user_table_id,$this->user_limit);
				if(is_array($foo))	{$entries = array_merge($entries,$foo);}
				$foo=cloogooq_getQueries($this->user_table_name,$this->user_table_key,"?".$querykey,$this->user_table_id,$this->user_limit);
				if(is_array($foo))	{$entries = array_merge($entries,$foo);}
			}
		}
		
		if(DEBUGGING)	{echo ">>>> Es wurden insgesamt ".count($entries)." Entries gefunden<br />\n";}

		foreach($entries as $key => $value) {
			$domain = explode('/', $value);	
			if	(!in_array($domain[2],$this->user_exclude_domain))	{
				foreach($this->user_querykeys as $querykey)	{			################################
					$query = explode("&".$querykey, $value);			## MAKING SURE, THAT WE ONLY ###
					if($query[1] == '')	{								## COUNT CORRECT MATCHES AND ###
						$query = explode("?".$querykey, $value);		## NOT PARTIAL MATCHES		 ###
					}													################################
					if($query[1] != '')	{
			    		$query = explode('&', $query[1]);    		
						$query[0]=str_replace('%22', '',$query[0]);
						$query[0]=$this->_decode($query[0]);
					    $query[0]=strtolower(urldecode($query[0]));
						$query[0]=str_replace('+', '',$query[0]);
						$query[0]=str_replace(',', '',$query[0]);
						switch($mode)	{
							default:
							case 'keywords':
								$words = split (' ',$query[0]);
								for($j=0;$j<count($words);$j++)	{
									if($words[$j] != '' 
										&& !in_array($words[$j],$this->user_exclude)
										&& (!$this->user_exclude_numbers || !is_numeric($words[$j]))
										&& (strlen($words[$j])>=$this->user_min_char || ($this->user_include_numbers && is_numeric($words[$j]))  || in_array($words[$j],$this->user_include)))	{
										$WORD_ARRAY[$words[$j]]++;
									}
								}
								break;
							case 'phrases':
								if(!in_array($query[0],$this->user_exclude) && strlen($query[0])>=$this->user_min_char)	{
	#								$query[0]=str_replace(' ', '&nbsp;',$query[0]);
									$WORD_ARRAY[$query[0]]++;
								}
								break;
						}
					}
				}
			}
		}
		if(count($WORD_ARRAY)==0)	{
			$cgq_STRING.='sorry - no search engine queries found. [wrong or empty table: <strong>'.$this->user_table_name.' &#187; '.$this->user_table_key.'</strong> ?]';
		}
		else {
			//////////////////////////
			// DOING SOME COUNTING	//
			//////////////////////////
			arsort($WORD_ARRAY);
			$HIGHEST=0;
			$bFoo=0;
			$sValues=0;
			$nDiffValues=0;
			$nValues=0;
			switch($mode)	{
				default:
				case 'keywords':
					$min_hits=$this->user_min_hits_key;
					break;
				case 'phrases':	
					$min_hits=$this->user_min_hits_phr;
					break;
			}
			foreach ($WORD_ARRAY as $key => $value) {
				if($HIGHEST==0)	{$HIGHEST=$value;}
				if($value>=$min_hits   || in_array($words[$j],$this->user_include))	{
					if($this->user_max_number_of_items== -1 || ($nValues<$this->user_max_number_of_items || $LOW==$value))	{
						if($LOW!=$value)	{$nDiffValues++;}
						$WORD_COLOR_ARRAY[$key]=($nDiffValues-1);
						if($bfoo==0)	{
							$HIGH=$value;
							$bfoo=1;
						}
						$sValues+=$value;
						$LOW=$value;
						$nValues++;
					}
					else	{$WORD_ARRAY[$key]='';}
				}
			}
			if($HIGH==0)	{
				$cgq_STRING.='<strong>Being a bit ambitious?</strong><br />Minimum number of hits required: '.$min_hits.'<br />Maximum number of hits found: '.$HIGHEST.'<br />';			
			}
			else	{
				//////////////////////////////
				// PREPARING KEYWORD CLOUD	//
				//////////////////////////////
				$COLORS = $this->MultiColorFade(array($this->user_max_font_color,$this->user_min_font_color), $nDiffValues);
				switch($this->user_sort)	{				###################
					case 'alph_alph':						### SORTING THE	###
						$keys = array_keys($WORD_ARRAY);	### KEYWORDS 	###
						asort($keys);						### BASED ON 	###
						break;								### USER PREFS	###
					case 'alph_reverse':					###################
						$keys = array_keys($WORD_ARRAY);
						rsort($keys);
						break;
					case 'value_value':
						arsort($WORD_ARRAY);
						$keys = array_keys($WORD_ARRAY);
						break;
					case 'value_reverse':
						asort($WORD_ARRAY);
						$keys = array_keys($WORD_ARRAY);
						break;
					case 'center_weight':
						asort($WORD_ARRAY);
						$i=0;
						foreach ($WORD_ARRAY as $key => $value)	{
							if($i%2==0)	{$k1[$key]=$value;}
							else		{$k2[$key]=$value;}
							$i++;
						}
						arsort($k2);
						foreach ($k2 as $key => $value)	{
							$k1[$key]=$value;
						}						
						$keys=array_keys($k1);
						
						break;
					default:
					case 'random':
						$keys = array_keys($WORD_ARRAY);
						shuffle($keys);
						break;
				}
				//////////////////////////////
				// PRINTING KEYWORD CLOUD	//
				//////////////////////////////
				$cgq_STRING.='<div style="font-size:'.$this->user_max_font_size.'px;line-height:'.round($this->user_perc_line_height*$this->user_max_font_size,0).'px;">';
				foreach ($keys as $key) {
					if($WORD_ARRAY[$key]>=$min_hits)	{
						if($HIGH>$LOW)	{$nenner=$HIGH-$LOW;}
						elseif ($HIGH=$LOW)	{$nenner=1;}						# AVOIDING DIVISION BY ZERO
						else	{die("SOMETHING'S WRONG WITH THE COUNTING: $HIGH < $LOW ?!?");}

						switch($this->user_scaling)	{
							case 'linear':
								$font_size=round($this->user_min_font_size+($nDiffValues-1-$WORD_COLOR_ARRAY[$key])/($nDiffValues-1)*($this->user_max_font_size-$this->user_min_font_size),0);
								break;
							default:
							case 'weighted':
								$font_size=round($this->user_min_font_size+($WORD_ARRAY[$key]-$LOW)/($nenner)*($this->user_max_font_size-$this->user_min_font_size),0);
								break;
						}	
						switch($this->user_link_to)	{
							case 'off':
								$cgq_STRING.='<font style="font-size:'.$font_size.'px; color:#'.$COLORS[$WORD_COLOR_ARRAY[$key]].';">'.$key.'</font>';
								break;
							case 'techno':
								$cgq_STRING.='<a href="http://technorati.com/search/'.$key.'?from='.$this->pluginURL.'" title="'.$WORD_ARRAY[$key].' queries so far" style="font-size:'.$font_size.'px; color:#'.$COLORS[$WORD_COLOR_ARRAY[$key]].';">'.$key.'</a>';
								break;
							case 'google':
								$cgq_STRING.='<a href="http://google.com/search?q='.$key.'" title="'.$WORD_ARRAY[$key].' queries so far" style="font-size:'.$font_size.'px; color:#'.$COLORS[$WORD_COLOR_ARRAY[$key]].';">'.$key.'</a>';
								break;

							default:
							case 'locally':
								$urlkey=str_replace('&nbsp;', '+',$key);
								$urlkey=urlencode($urlkey);
								$cgq_STRING.='<a href="'.$this->pluginURL.'?s='.$urlkey.'" title="'.$WORD_ARRAY[$key].' queries so far" style="font-size:'.$font_size.'px; color:#'.$COLORS[$WORD_COLOR_ARRAY[$key]].';">'.$key.'</a>';
								break;
						}
						if($this->user_sep_space_prior)	{$cgq_STRING.=' ';}
						$cgq_STRING.=$this->user_separator;
						if($this->user_sep_space_after)	{$cgq_STRING.=' ';}
					}
				}
				$cgq_STRING.='</div>';
			}
		}
		if(DEBUGGING)	{
			$cgq_STRING.='<strong>HIGHEST:</strong>'.$HIGHEST.'<br />';
			$cgq_STRING.='<strong>LOW:</strong>'.$LOW.'<br />';
			$cgq_STRING.='<strong>nDiffValues:</strong>'.$nDiffValues.'<br />';
			$cgq_STRING.='<table>';
			foreach ($this->options as $option => $type) {
				$cgq_STRING.='<tr><td>DEB::'.$option.'::GET_OPTION: '.get_option('cloogooq_'.$option).'</td><td>CURRENTLY SET TO: '.$this->$option.'</td></tr>';
			}
			$cgq_STRING.='</table>';
		}
		return $cgq_STRING;
	}
	
	//////////////////////////////////////////////////////////////////////////////
	//	OPTION HANDLING															//
	//////////////////////////////////////////////////////////////////////////////
	function install() {
		add_option('cloogooq_user_min_hits_key', $this->user_min_hits_key,'Minimum required hits for keywords','no');
		add_option('cloogooq_user_min_hits_phr', $this->user_min_hits_phr,'Minimum required hits for phrases','no');
		add_option('cloogooq_user_min_char', $this->user_min_char,'Minimum characters required for keywords','no');		
		add_option('cloogooq_user_include_numbers', $this->user_include_numbers,'Always include numbers','no');
		add_option('cloogooq_user_exclude_numbers', $this->user_exclude_numbers,'Always exclude numbers','no');
		add_option('cloogooq_user_min_font_size', $this->user_min_font_size,'Minimum font size','no');
		add_option('cloogooq_user_min_font_color', $this->user_min_font_color,'Minimum font color','no');
		add_option('cloogooq_user_max_number_of_items', $this->user_max_number_of_items,'Maximum number of items','no');
		add_option('cloogooq_user_max_font_size', $this->user_max_font_size,'Maximum font size','no');
		add_option('cloogooq_user_max_font_color', $this->user_max_font_color,'Maximum font color','no');
		add_option('cloogooq_user_perc_line_height', $this->user_perc_line_height,'Maximum font color','no');
		add_option('cloogooq_user_separator', $this->user_separator,'Item Seperator','no');
		add_option('cloogooq_user_sep_space_prior', $this->user_sep_space_prior,'Whitespace before Seperator?','no');
		add_option('cloogooq_user_sep_space_after', $this->user_sep_space_after,'Whitespace after Seperator?','no');
		add_option('cloogooq_user_exclude_domain', implode(",",$this->user_exclude_domain),'Domain Exlude List','no');
		add_option('cloogooq_user_exclude', implode(",",$this->user_exclude),'Exlude List','no');
		add_option('cloogooq_user_include', implode(",",$this->user_include),'Include List','no');
		add_option('cloogooq_user_table_name', $this->user_table_name,'Stats Table Name','no');
		add_option('cloogooq_user_table_key', $this->user_table_key,'Stats Table Key','no');
		add_option('cloogooq_user_include_local_search', $this->user_include_local_search,'Include Local Search','no');
		add_option('cloogooq_user_table_name_local', $this->user_table_name_local,'Local Stats Table Name','no');
		add_option('cloogooq_user_table_key_local', $this->user_table_key_local,'Local Stats Table Key','no');
		add_option('cloogooq_user_querykeys', $this->user_querykeys,'Query Key Array','no');
		add_option('cloogooq_user_link_to', $this->user_link_to,'Link Keywords to ?','no');
		add_option('cloogooq_user_sort', $this->user_sort,'Sort Keywords by ?','no');
		add_option('cloogooq_user_scaling', $this->user_scaling,'Sort Keywords by ?','no');
		add_option('cloogooq_user_limit', $this->user_limit,'Limit to last #entries','no');
		add_option('cloogooq_user_table_id', $this->user_table_id,'sql unique id','no');
	}
	function get_setting() {
		foreach ($this->options as $option => $type) {
			$this->$option = get_option('cloogooq_'.$option);
			switch ($type) {
				case 'bool':
				case 'int':
					$this->$option = intval($this->$option);
					break;
				case 'string':
					$value = strval($_POST[$option]);
					break;
				case 'array':
					$this->$option=explode(",",$this->$option); // MAKING ARRAY FROM COMMA SEPARATED STRING
					break;
			}
		}
	}
	function update_settings() {
		if(isset($_POST['user_min_hits']) && intval($_POST['user_min_hits'])<1)	{$this->user_min_hits=1;$_POST['user_min_hits']=1;}
		if(isset($_POST['user_min_char']) && intval($_POST['user_min_char'])<0)	{$this->user_min_char=0;$_POST['user_min_char']=0;}
		if(isset($_POST['user_max_number_of_items']) && intval($_POST['user_max_number_of_items'])<-1)	{$this->user_max_number_of_items=-1;$_POST['user_max_number_of_items']=-1;}
		if(!isset($_POST['user_sep_space_prior']))	{$this->user_sep_space_prior=0;$_POST['user_sep_space_prior']=0;}
		if(!isset($_POST['user_sep_space_after']))	{$this->user_sep_space_after=0;$_POST['user_sep_space_after']=0;}
		if(!isset($_POST['user_include_numbers']))	{$this->user_include_numbers=0;$_POST['user_include_numbers']=0;}
		if(!isset($_POST['user_exclude_numbers']))	{$this->user_exclude_numbers=0;$_POST['user_exclude_numbers']=0;}
		if(!isset($_POST['user_include_local_search']))	{$this->user_include_local_search=0;$_POST['user_include_local_search']=0;}
		foreach ($this->options as $option => $type) {
			if (isset($_POST[$option])) {
				switch ($type) {
					case 'int':
						$value = intval($_POST[$option]);
						break;
					case 'string':
						$value = strval($_POST[$option]);
						break;
					case 'array':
						$value = strtolower(strval($_POST[$option]));
						break;
					case 'bool':
						if(intval($_POST[$option]))	{
							$value = 1;
						}
						else	{
							$value = 0;
						}
						break;
					default:
						$value = stripslashes($_POST[$option]);
				}
				update_option('cloogooq_'.$option, $value);
			}
			else {
				update_option('cloogooq_'.$option, $this->$option);
			}
		}
		header('Location: '.get_bloginfo('wpurl').'/wp-admin/options-general.php?page=cloogooq.php&updated=true');
		die();
	}
	//////////////////////////////////////////////////////////////////////////////
	//	PRINTS OPTION HTML FORM FOR THE ADMIN OPTION PAGE						//
	//////////////////////////////////////////////////////////////////////////////	
	function options_form() {
		if($this->user_sep_space_prior)	{$space_prior=' checked';}
		if($this->user_sep_space_after)	{$space_after=' checked';}		
		if($this->user_include_numbers)	{$include_numbers=' checked';}	
		if($this->user_exclude_numbers)	{$exclude_numbers=' checked';}	
		if($this->user_include_local_search)	{$local_search=' checked';}
		switch($this->user_link_to)	{
			case 'off':
				$link_off=' checked="checked"';
				break;
			case 'techno':
				$link_techno=' checked="checked"';
				break;
			case 'google':
				$link_google=' checked="checked"';
				break;

			default:
			case 'locally':
				$link_local=' checked="checked"';
				break;
		}
		switch($this->user_sort)	{
			case 'alph_alph':
				$sort_aa=' checked="checked"';
				break;
			case 'alph_reverse':
				$sort_ar=' checked="checked"';
				break;
			case 'value_value':
				$sort_vv=' checked="checked"';
				break;
			case 'value_reverse':
				$sort_vr=' checked="checked"';
				break;
			case 'center_weight':
				$sort_cw=' checked="checked"';
				break;
			default:
			case 'random':
				$sort_random=' checked="checked"';
				break;
		}
		switch($this->user_scaling)	{
			case 'linear':
				$sclaing_l=' checked="checked"';
				break;
			default:
			case 'weighted':
				$sclaing_w=' checked="checked"';
				break;
		}		
		print('
			<div class="wrap">
				<h2>'.__('&#187; SQL TABLE &amp; KEY NAMES', 'blog.vimagic.de').'</h2>
				<form name="cloogooq" action="'.get_bloginfo('wpurl').'/wp-admin/options-general.php" method="post">
					<fieldset class="options">
						<table border="0" cellspacing="5" cellpadding="0"><tr><td colspan="3">&nbsp;</td><td align="right">
						<font style="color:#999;"><label for="user_include_local_search">'.__('Include Local Searches:', 'blog.vimaigc.de').'</label></font>
						</td><td>
						<input type="checkbox" name="user_include_local_search" value="1"'.$local_search.' />
						</td></tr><tr><td align="right">
						<label for="user_table_name">'.__('Counter table name:', 'blog.vimaigc.de').'</label>
						</td><td>
						<input type="text" name="user_table_name" size="20" value="'.$this->user_table_name.'" />
						</td><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td align="right">
						<font style="color:#999;"><label for="user_table_name">'.__('Local Counter table name:', 'blog.vimaigc.de').'</label></font>
						</td><td>
						<input type="text" name="user_table_name_local" size="20" value="'.$this->user_table_name_local.'" />
						</td></tr><tr><td align="right">
						<label for="user_table_key">'.__('Referer field name:', 'blog.vimaigc.de').'</label>
						</td><td>
						<input type="text" name="user_table_key" size="20" value="'.$this->user_table_key.'" />
						</td><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td align="right">
						<font style="color:#999;"><label for="user_table_key">'.__('Url field name:', 'blog.vimaigc.de').'</label></font>
						</td><td>
						<input type="text" name="user_table_key_local" size="20" value="'.$this->user_table_key_local.'" />
						</td></tr>
						<tr><td align="right">
						<font style="color:#999;"><label for="user_table_id">'.__('Unique ID name:', 'blog.vimaigc.de').'</label></font>
						</td><td>
						<input type="text" name="user_table_id" size="20" value="'.$this->user_table_id.'" />
						</td>
						<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td align="right">
						</td><td>
						</td>
						</tr>
						</table>
					</fieldset>
			</div>
			<div class="wrap">
				<h2>'.__('&#187; SETUP', 'blog.vimagic.de').'</h2>
				<form name="cloogooq" action="'.get_bloginfo('wpurl').'/wp-admin/options-general.php" method="post">
					<fieldset class="options">
						<table border="0" cellspacing="5" cellpadding="0"><tr><td align="right">
						<label for="user_querykeys">'.__('Search Engine query keys:', 'blog.vimaigc.de').'</label></td><td>&nbsp;</td><td align="left"  colspan="5">
						<input type="text" name="user_querykeys" size="80" value="'.implode(",",$this->user_querykeys).'" /><br />
						[<font style="color:#bbb;font-weight:bold;font-size:0.75em;">Careful when editing this comma separated list (there\'s currently no validation of this array).  Avoid doubles for accurate results.</font>]
						</td></tr>
						<tr><td align="right">
						<label for="user_link_to">'.__('Linking:', 'blog.vimaigc.de').'</label></td><td>&nbsp;</td><td align="left">
						<input type="radio" name="user_link_to" value="off" '.$link_off.'/> no links<br />
						<input type="radio" name="user_link_to" value="locally" '.$link_local.'/> locally<br />
						<input type="radio" name="user_link_to" value="techno" '.$link_techno.'/> Technorati<br />
						<input type="radio" name="user_link_to" value="google" '.$link_google.'/> Google<br />
						</td>
						<td>&nbsp;</td>
						<td align="right">
						<label for="user_link_to">'.__('Sorting:', 'blog.vimaigc.de').'</label></td><td>&nbsp;</td><td align="left">
						<input type="radio" name="user_sort" value="alph_alph" '.$sort_aa.'/> Alphabetical<br />
						<input type="radio" name="user_sort" value="alph_reverse" '.$sort_ar.'/> Alphabetical Reverse<br />
						<input type="radio" name="user_sort" value="value_value" '.$sort_vv.'/> Value Decreasing<br />
						<input type="radio" name="user_sort" value="value_reverse" '.$sort_vr.'/> Value Incresing<br />
						<input type="radio" name="user_sort" value="center_weight" '.$sort_cw.'/> Center Weight<br />
						<input type="radio" name="user_sort" value="random" '.$sort_random.'/> Random<br />
						</td>
						</tr>
						<tr><td align="right">
						<label for="user_scaling">'.__('Scaling:', 'blog.vimaigc.de').'</label></td><td>&nbsp;</td><td align="left"  colspan="5">
						<input type="radio" name="user_scaling" value="linear" '.$sclaing_l.'/> linear&nbsp;&nbsp;[<font style="color:#bbb;font-weight:bold;font-size:0.75em;">Linear scaling of font sizes between the maximum and minimum value.</font>]<br />
						<input type="radio" name="user_scaling" value="weighted" '.$sclaing_w.'/> weighted&nbsp;&nbsp;[<font style="color:#bbb;font-weight:bold;font-size:0.75em;">The font size will be determined by the value (number of hits) of each keyword.</font>]<br />
						</td>
						</tr>
						</table>
					</fieldset>
			</div>
			<div class="wrap">
				<h2>'.__('&#187; FILTER', 'blog.vimagic.de').'</h2>
					<fieldset class="options">
						<table border="0" cellspacing="5" cellpadding="0"><tr><td align="right">
						<label for="user_min_hits">'.__('Minimal&nbsp;Keyword&nbsp;Hits&nbsp;required:', 'blog.vimaigc.de').'</label>
						</td><td>
						<input type="text" name="user_min_hits_key" size="3" value="'.$this->user_min_hits_key.'" />
						</td><td align="left">&nbsp;</td><td align="left">
						[<font style="color:#bbb;font-weight:bold;font-size:0.75em;">Any keywords with less hits will be filtered. Value must be >0</font>]
						</td></tr><tr><td align="right">
						<label for="user_min_hits">'.__('Minimal Phrase Hits required:', 'blog.vimaigc.de').'</label>
						</td><td>
						<input type="text" name="user_min_hits_phr" size="3" value="'.$this->user_min_hits_phr.'" />
						</td><td align="left">&nbsp;</td><td align="left">
						[<font style="color:#bbb;font-weight:bold;font-size:0.75em;">Any phrase with less hits will be filtered. Value must be >0</font>]
						</td></tr><tr><td align="right">
						<label for="user_min_char">'.__('Minimum&nbsp;Characters&nbsp;per&nbsp;Keyword:', 'blog.vimaigc.de').'</label>
						</td><td>
						<input type="text" name="user_min_char" size="3" value="'.$this->user_min_char.'" />
						</td><td align="left">&nbsp;</td><td align="left">
						[<font style="color:#bbb;font-weight:bold;font-size:0.75em;">Any keyword or phrase that has less characters will be filtered. Value must be >= 0</font>]
						</td></tr><tr><td align="right">
						<label for="user_include_numbers">'.__('&nbsp;&nbsp;&nbsp;Always include numbers:', 'blog.vimaigc.de').'</label>
						</td><td>
						<input type="checkbox" name="user_include_numbers" value="1"'.$include_numbers.' />  
						</td><td align="left">&nbsp;</td><td align="left">
						[<font style="color:#bbb;font-weight:bold;font-size:0.75em;">When checked, numbers will be include even if they are &quot;too short&quot;</font>]
						</td><td>&nbsp;</td></tr>
						
						<tr><td align="right">
						<label for="user_exclude_numbers">'.__('&nbsp;&nbsp;&nbsp;Always exclude numbers:', 'blog.vimaigc.de').'</label>
						</td><td>
						<input type="checkbox" name="user_exclude_numbers" value="1"'.$exclude_numbers.' />  
						</td><td align="left">&nbsp;</td><td align="left">
						[<font style="color:#bbb;font-weight:bold;font-size:0.75em;">When checked, numbers will always be exclude</font>]
						</td><td>&nbsp;</td></tr>
						
						<tr><td align="right">
						<label for="user_max_number_of_items">'.__('Maximal Number Of Items:', 'blog.vimaigc.de').'</label>
						</td><td>
						<input type="text" name="user_max_number_of_items" size="3" value="'.$this->user_max_number_of_items.'" />
						</td><td align="left">&nbsp;</td><td align="left">
						[<font style="color:#bbb;font-weight:bold;font-size:0.75em;">This will (possibly) limit the amount of returned results. Starting with the highest hits first, CloOGooQ will return keywords until the maximal number of items has been reached (it will also include all items of the same current level of hits.) Value must be > 0 or -1 for &quot;No Limit&quot;</font>]
						</td></tr>
						
						<tr><td align="right">
						<label for="user_limit">'.__('Limit to last #entries:', 'blog.vimaigc.de').'</label>
						</td><td>
						<input type="text" name="user_limit" size="6" value="'.$this->user_limit.'" />
						</td><td align="left">&nbsp;</td><td align="left">
						[<font style="color:#bbb;font-weight:bold;font-size:0.75em;">This will limit the output to the last #entries.  Use -1 for no limit.  <font style="color:#f00;">Requires the Unique ID name!</font></font>]
						</td></tr>
						

						<tr><td align="right">						
						<label for="user_exclude_domain">'.__('Domain Exlude Liste:', 'blog.vimaigc.de').'</label>
						</td><td colspan="3" align="left">
						<input type="text" name="user_exclude_domain" size="80" value="'.implode(",",$this->user_exclude_domain).'" /><br />
						[<font style="color:#bbb;font-weight:bold;font-size:0.75em;">Any domain in this comma separated list will be excluded from evaluation.</font>]
						</td></tr>
						<tr><td align="right">						
						<label for="user_exclude">'.__('Exlude Liste:', 'blog.vimaigc.de').'</label>
						</td><td colspan="3" align="left">
						<input type="text" name="user_exclude" size="80" value="'.implode(",",$this->user_exclude).'" /><br />
						[<font style="color:#bbb;font-weight:bold;font-size:0.75em;">Any keyword or phrase in this comma separated list will be excluded from evaluation.</font>]
						</td></tr><tr><td align="right">
						<label for="user_exclude">'.__('Include Liste:', 'blog.vimaigc.de').'</label>
						</td><td colspan="3" align="left">
						<input type="text" name="user_include" size="80" value="'.implode(",",$this->user_include).'" /><br />
						[<font style="color:#bbb;font-weight:bold;font-size:0.75em;">Any keyword or phrase in this comma separated list will be included, even if too short or with few hits than required. The include list has lower priority than the exclude list.</font>]
						</td></tr></table>
					</fieldset>
			</div>
			<div class="wrap">		
				<h2>'.__('&#187; STYLING', 'blog.vimagic.de').'</h2>
					<fieldset class="options">
						<table border="0" cellspacing="5" cellpadding="0"><tr><td align="right">
						<label for="user_min_font_size">'.__('Minimal Font Size:', 'blog.vimaigc.de').'</label>
						</td><td>
						<font style="color:#fff;">#</font><input type="text" name="user_min_font_size" size="3" value="'.$this->user_min_font_size.'" />
						</td><td rowspan="2" align="left"><font style="color:#'.$this->user_min_font_color.';font-size:'.$this->user_min_font_size.'px;">Minimal Font Values</font></td></tr>
						<tr><td  align="right">
						<label for="user_min_font_color">'.__('Minimal Font Color:', 'blog.vimaigc.de').'</label>
						</td><td>
						#<input type="text" name="user_min_font_color" size="7" value="'.$this->user_min_font_color.'" /> 
						</td></tr><tr><td  align="right">
						<label for="user_max_font_size">'.__('Maximal Font Size:', 'blog.vimaigc.de').'</label>
						</td><td>
						<font style="color:#fff;">#</font><input type="text" name="user_max_font_size" size="3" value="'.$this->user_max_font_size.'" /> 
						</td><td rowspan="2" align="left"><font style="color:#'.$this->user_max_font_color.';font-size:'.$this->user_max_font_size.'px;">Maximal Font Values</font></td></tr>
						<tr><td  align="right">
						<label for="user_max_font_color">'.__('Maximal Font Color:', 'blog.vimaigc.de').'</label>
						</td><td>
						#<input type="text" name="user_max_font_color" size="7" value="'.$this->user_max_font_color.'" /> 
						</td></tr><tr><td  align="right">		
						<label for="user_perc_line_height">'.__('% Line Height:', 'blog.vimaigc.de').'</label>
						</td><td>
						<font style="color:#fff;">#</font><input type="text" name="user_perc_line_height" size="4" value="'.$this->user_perc_line_height.'" /> 
						</td></tr><tr><td  align="right">
						<label for="user_separator">'.__('Separator:', 'blog.vimaigc.de').'</label>
						</td><td colspan="2">
						<font style="color:#fff;">#</font><input type="text" name="user_separator" size="70" value="'.htmlentities($this->user_separator).'" />
						</td></tr><tr><td  align="right">
						<label for="user_sep_space_prior">'.__('Auto&nbsp;space&nbsp;prior&nbsp;separator:', 'blog.vimaigc.de').'</label>
						</td><td>
						<font style="color:#fff;">#</font><input type="checkbox" name="user_sep_space_prior" value="1"'.$space_prior.' />
						</td></tr><tr><td  align="right">
						<label for="user_sep_space_after">'.__('Auto&nbsp;space&nbsp;after&nbsp;separator:', 'blog.vimaigc.de').'</label>
						</td><td>
						<font style="color:#fff;">#</font><input type="checkbox" name="user_sep_space_after" value="1"'.$space_after.' />  
						</td></tr></table>
						<input type="hidden" name="cloogooq_action" value="cloogooq_update_settings" />
					</fieldset>
					<p class="submit"><input type="submit" name="submit_buttom" value="'.__('Update CloOGooQ Settings', 'blog.vimaigc.de').'" /></p>
				</form>
			</div>
		');
	}
	////////////////////////////////////////////////////////
	// RETURNS HEX COLOR ARRAY FOR FADING IN $steps STEPS //
	////////////////////////////////////////////////////////
	function MultiColorFade($hex_array, $steps) {
		$tot = count($hex_array);
		$gradient = array();
		$fixend = 2;
		$passages = $tot-1;
		$stepsforpassage = floor($steps/$passages);
		$stepsremain = $steps - ($stepsforpassage*$passages);
		for($pointer = 0; $pointer < $tot-1 ; $pointer++) {
			$hexstart = $hex_array[$pointer];
			$hexend = $hex_array[$pointer + 1];
			if($stepsremain > 0){
				if($stepsremain--){$stepsforthis = $stepsforpassage + 1;}
			}else{$stepsforthis = $stepsforpassage;}
			if($pointer > 0){ $fixend = 1;}
			$start['r'] = hexdec(substr($hexstart, 0, 2));
			$start['g'] = hexdec(substr($hexstart, 2, 2));
			$start['b'] = hexdec(substr($hexstart, 4, 2));
			$end['r'] = hexdec(substr($hexend, 0, 2));
			$end['g'] = hexdec(substr($hexend, 2, 2));
			$end['b'] = hexdec(substr($hexend, 4, 2));
			$step['r'] = ($start['r'] - $end['r']) / ($stepsforthis);
			$step['g'] = ($start['g'] - $end['g']) / ($stepsforthis);
			$step['b'] = ($start['b'] - $end['b']) / ($stepsforthis);
			for($i = 0; $i <= $stepsforthis-$fixend; $i++) {
				$rgb['r'] = floor($start['r'] - ($step['r'] * $i));
				$rgb['g'] = floor($start['g'] - ($step['g'] * $i));
				$rgb['b'] = floor($start['b'] - ($step['b'] * $i));
				$hex['r'] = sprintf('%02x', ($rgb['r']));
				$hex['g'] = sprintf('%02x', ($rgb['g']));
				$hex['b'] = sprintf('%02x', ($rgb['b']));
				$gradient[] = strtoupper(implode(NULL, $hex));
			}
		}
		$gradient[] = $hex_array[$tot-1];
		return $gradient;
	}
	//////////////////////////////////////////////////////////////////////////////
	//	_filter()  SELFEXPLANITORY ;)											//
	//////////////////////////////////////////////////////////////////////////////
	function Cumulus()	{
		$cats=$this->cgq("cumulus");
#		$cats=preg_replace('# style=\"font-size:\d+px; color:\#.*?;\"#sie','',$cats);
		$cats=preg_replace('# color:\#.*?;#sie','',$cats);
#		$cats=preg_replace('#<font style="font-size:10px;font-weight:bold;color:\#BBB">&nbsp;&curren; </font>#si',' %288%29<br />',$cats);
		$cats=preg_replace('# title=\".*?\"#sie','',$cats);


		$cats=preg_replace('#<font style="font-size:10px;font-weight:bold;color:\#BBB">&nbsp;&curren; </font>#si',"<br />\n",$cats);
		$cats=preg_replace('#<div style="font-size:30px;line-height:15px;">(.*?)</div>#si','$1',$cats);
		$cats=preg_replace('#px#si','pt',$cats);
#		$cats=preg_replace('#"#si','\'',$cats);
		
#		$cats=preg_replace('# style#si',' class=\'tag-link-16\' title=\'1 topic\' rel="tag" style',$cats);
		
		$cats=urlencode($cats);
		return $cats;
	}

	function HitCount()	{
#		foreach($this->user_querykeys as $querykey)	{
#			$bar=cloogooq_getQueries($this->user_table_name,$this->user_table_key,"&".$querykey,$this->user_table_id,$this->user_limit);
#			if(is_array($bar))	{
#				$foo += count($bar);
#			}
#			$bar=cloogooq_getQueries($this->user_table_name,$this->user_table_key,"?".$querykey,$this->user_table_id,$this->user_limit);
#			if(is_array($bar))	{
#				$foo += count($bar);
#			}
#		}
#		if($this->user_include_local_search)	{$foo+=count(cloogooq_getQueries($this->user_table_name_local,$this->user_table_key_local,'s=',$this->user_table_id,$this->user_limit));}
#		return '[<font class="storydate">'.$foo.' search engine queries</font>]';
	}
	
	function _filter($text) {
		$text = preg_replace('#\[CloOGooQ_KEYWORDS\]#sie', '$this->cgq(keywords)', $text);
		$text = preg_replace('#\[CloOGooQ_PHRASES\]#sie', '$this->cgq(phrases)', $text);
		$text = preg_replace('#\[CloOGooQ_HITCOUNT\]#sie', '$this->HitCount()', $text);
		return $text;
	}
}

$WpClooGooQ = new WpClooGooQ();

//////////////////////////////////////////////////////
// SOME FUNCTIONS FOR THE CORRECT EVENT HANDLING	//
//////////////////////////////////////////////////////
if (isset($_GET['activate']) && $_GET['activate'] == 'true') {
		$bInstall=0;
		foreach ($WpClooGooQ->options as $option => $type) {
			if(get_option('cloogooq_'.$option) == '' )	{
				$bInstall=1;
			}
		}
		if($bInstall)	{$WpClooGooQ->install();}
}
$WpClooGooQ->get_setting();

function cloogooq_options_form() {
	global $WpClooGooQ;
	$WpClooGooQ->options_form();
}
function cloogooq_options() {
	if (function_exists('add_options_page')) {
		add_options_page(
			__('CloOGooQ Options', 'blog.vimagic.de'),
			__('CloOGooQ', 'blog.vimagic.de'),
			10,
			basename(__FILE__),
			'cloogooq_options_form'
		);
	}
}

//////////////////////////////////
// FUNCTION CALL FOR SIDEBARS	//
//////////////////////////////////
function cloogooq($foo='keywords') {
	global $WpClooGooQ;
	print($WpClooGooQ->cgq($foo));
}


///////////////////////////////////////////////
// THE FOLLOWING TWO FUNCTION WHERE TAKEN 	 //
// FROM THE COUNTERIZE PLUGIN TO AVOID		 //
// HICKUPS IN CASE COUNTERIZE IS NOT RUNNING //
///////////////////////////////////////////////
function cloogooq_getQueries($table,$key,$qs,$id,$limit)	{
	if($limit>0 && $id!='')	{
#		$sql = 'SELECT '.$id.','.$key.' FROM '.$table.' WHERE '.$key.' LIKE '."'%".$qs."%'"." ORDER BY `".$id."` DESC LIMIT ".$limit;		# DON'T LET SQL SORT OR FILTER
#		$sql = 'SELECT '.$key.' FROM '.$table.' WHERE '.$key.' LIKE '."'%".$qs."%'"." ORDER BY `".$id."` DESC";								# IT WILL TAKE AGES ...
		$sql = 'SELECT '.$id.','.$key.' FROM '.$table.' WHERE '.$key.' LIKE '."'%".$qs."%'";
		$wpdb =& $GLOBALS['wpdb'];
		if(DEBUGGING)	{echo $sql."=>".count($wpdb->get_results($sql))."<br />\n";}
		return $wpdb->get_results($sql);
	}
	else	{
		$sql = 'SELECT '.$key.' FROM '.$table.' WHERE '.$key.' LIKE '."'%".$qs."%'";
		$wpdb =& $GLOBALS['wpdb'];
		if(DEBUGGING)	{echo $sql."=>".count($wpdb->get_results($sql))."<br />\n";}
		return $wpdb->get_col($sql);
	}
}

///////////////////////////////////////////////
// ADDING WORDPRESS ACTIONS				 	 //
///////////////////////////////////////////////
add_action('admin_menu', 'cloogooq_options');

if (!empty($_POST['cloogooq_action'])) {
	switch($_POST['cloogooq_action']) {
		case 'cloogooq_update_settings': 
			$WpClooGooQ->update_settings();
			break;
	}
}

?>
