<?php
defined('is_running') or die('Not an entry point...');

gpPlugin::Incl('Common.php');

class MultiLang extends MultiLang_Common{

	public function __construct(){
		parent::__construct();
	}


	public static function GetObject(){
		static $object;

		if( !$object ){
			$object = new MultiLang();
		}

		return $object;
	}


	/**
	 * Determine a user's language preference and redirect them to the appropriate homepage if necessary
	 * How do we differentiate between a user requesting the home page (to get the default language content) and a request that should be redirected?
	 * 	... don't create any empty links (set $config['homepath'] to false)
	 * 	... redirect all empty paths?
	 *
	 */
	public static function _WhichPage($path){
		$object = self::GetObject();
		return $object->WhichPage($path);
	}

	public function WhichPage($path){
		global $config;

		/*
		debug('passed $path = ' . $path);									// TODO remove
		debug('$config[\'homepath\'] = ' . $config['homepath']);			// TODO remove
		debug('$config[\'homepath_key\'] = ' . $config['homepath_key']);	// TODO remove
		*/

		$home_title					= $config['homepath'];
		$home_key					= $config['homepath_key'];
		$config['homepath_key']		= false;
		$config['homepath']			= false;


		/*
		if( !empty($path) && $path !== $home_title ){
			// not homepage
			return $path;
		}
		*/

		if( !empty($path) ){
			// we have a $path, so don't redirect
			return $path;
		}

		$translated_key = $this->WhichTranslation($home_key);
		// msg('$translated_key =' . pre($translated_key)); // TODO remove

		if( is_null($translated_key) ){
			// no translation found for the homepage
			return $home_title;
		}

		// translation found
		$home_title = common::IndexToTitle($translated_key);

		// redirect if needed
		if( $home_title != $path ){
			common::Redirect(common::GetUrl($home_title));
		}
	}


	/**
	 * Return the translated page index according to the users ACCEPT_LANGUAGE
	 *
	 */
	public function WhichTranslation($key){

		//only if translated
		$list = $this->GetList($key);
		if( !$list ){
			return;
		}

		//only if user has language settings
		if( empty($_SERVER['HTTP_ACCEPT_LANGUAGE']) ){
			return;
		}

		//check for appropriate translation
		$langs = $this->RequestLangs();
		foreach($langs as $lang => $importance){
			if( isset($list[$lang]) ){
				return $list[$lang];
			}
		}
	}


	public function RequestLangs(){
		$langs = array();
		$temp = $_SERVER['HTTP_ACCEPT_LANGUAGE'];

		// break up string into pieces (languages and q factors)
		preg_match_all(
			'/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i',
			$temp,
			$lang_parse
		);

		if( count($lang_parse[1]) ){
			// create a list like "en" => 0.8
			$langs = array_combine($lang_parse[1], $lang_parse[4]);

			// set default to 1 for any without q factor
			foreach ($langs as $lang => $val) {
				if( $val === '' ){
					$langs[$lang] = 1;
				}
			}

			// sort list based on value
			arsort($langs, SORT_NUMERIC);
		}

		return $langs;
	}


	/**
	 * Gadget Unordered List
	 * Show related titles
	 *
	 */
	public static function _Gadget(){
		$object = self::GetObject();
		$object->Gadget();
	}


	public function Gadget(){
		global $page;

		//admin and special pages cannot be translated
		if( $page->pagetype != 'display' ){
			return;
		}

		$list = $this->GetVisibleList($page->gp_index);
		if( count($list) < 2 ){
			return;
		}

		$current_page_lang = array_search($page->gp_index, $list);

		//show the list
		echo '<div class="multi_lang_select"><div>';
		echo '<b>Languages</b>';
		$links = array();
		foreach($this->avail_langs as $lang_code => $lang_label){

			if( !isset($list[$lang_code]) ){
				continue;
			}

			if( $lang_code == $current_page_lang ){
				continue;
			}

			$index		= $list[$lang_code];
			$title		= common::IndexToTitle($index);
			$links[]	= common::Link($title, $lang_label);
		}

		if( $links ){
			echo '<ul>';
			echo	'<li>';
			echo implode('</li><li>', $links);
			echo	'</li>';
			echo '</ul>';
		}

		if( common::loggedIn() ){
			echo '<p>Admin: ';
			echo common::Link(
					'Admin_MultiLang',
					'Manage Translations',
					'cmd=TitleSettings&index=' . $page->gp_index,
					array('name' => 'gpabox')
				);
			echo '</p>';
		}

		echo '</div>';
		echo '</div>'; // /.multi_lang_select
	}


	/**
	 * Gadget Bootstrap 3 Dropdown Nav
	 *
	 * to be added after main menu via Layout Manager
	 * comment or delete the line
	 * $GP_ARRANGE = false;
	 * in template.php
	 *
	 */
	public static function _Gadget_BS3_Dropdown_Nav(){
		$object = self::GetObject();
		$object->Gadget_BS3_Dropdown_Nav();
	}


	public function Gadget_BS3_Dropdown_Nav(){
		global $page;

		common::LoadComponents('fontawesome');

		//admin and special pages cannot be translated
		if( $page->pagetype != 'display' ){
			return;
		}

		$list = $this->GetVisibleList($page->gp_index);
		if( count($list) < 2 ){
			return;
		}

		$current_page_lang = array_search($page->gp_index,$list);

		//show the list
		echo '<ul class="nav navbar-nav lang-dropdown-nav">';
		echo	'<li class="nav-item dropdown">';
		echo		'<a title="Languages" href="javascript:;"';
		echo			' class="nav-link dropdown-toggle"';
		echo			' data-toggle="dropdown">';
		echo			'<i class="fa fa-globe">&zwnj;</i>';
		echo			'<b class="caret"></b>';
		echo		'</a>';

		$links = array();
		foreach($this->avail_langs as $lang_code => $lang_label){

			if( !isset($list[$lang_code]) ){
				continue;
			}

			if( $lang_code == $current_page_lang ){
				continue;
			}

			$index		= $list[$lang_code];
			$title		= common::IndexToTitle($index);
			$links[]	= common::Link(
								$title,
								$lang_label,
								'',
								array(
									'hreflang'	=> $lang_code,
									'class'		=> 'dropdown-item',
								)
							);
		}

		echo		'<ul class="dropdown-menu">';

		if( $links ){
			echo			'<li class="nav-item">';
			echo				implode('</li><li class="nav-item">', $links);
			echo			'</li>';
		}

		echo		'</ul>'; // /.dropdown-menu
		echo	'</li>'; // /.nav-item.dropdown
		echo '</ul>'; // /.nav.navbar-nav.lang-dropdown-nav
	}


	/**
	 * Gadget Bootstrap 4 Dropdown Nav
	 *
	 * to be added after main menu via Layout Manager
	 * comment or delete the line
	 * $GP_ARRANGE = false;
	 * in template.php
	 */
	public static function _Gadget_BS4_Dropdown_Nav(){
		$object = self::GetObject();
		$object->Gadget_BS4_Dropdown_Nav();
	}


	public function Gadget_BS4_Dropdown_Nav(){
		global $page;

		common::LoadComponents('fontawesome');

		//admin and special pages cannot be translated
		if( $page->pagetype != 'display' ){
			return;
		}

		$list = $this->GetVisibleList($page->gp_index);
		if( count($list) < 2 ){
			return;
		}

		$current_page_lang = array_search($page->gp_index,$list);

		//show the list
		echo '<ul class="nav navbar-nav lang-dropdown-nav">';
		echo	'<li class="nav-item dropdown">';
		echo		'<a title="Languages" href="javascript:;"';
		echo			' class="nav-link dropdown-toggle"';
		echo			' data-toggle="dropdown">';
		echo			'<i class="fa fa-globe">&zwnj;</i>';
		echo			'<b class="caret"></b>';
		echo		'</a>';

		$links = array();
		foreach($this->avail_langs as $lang_code => $lang_label){

			if( !isset($list[$lang_code]) ){
				continue;
			}

			if( $lang_code == $current_page_lang ){
				continue;
			}

			$index		= $list[$lang_code];
			$title		= common::IndexToTitle($index);
			$links[]	= common::Link(
								$title,
								$lang_label,
								'',
								array(
									'hreflang'	=> $lang_code,
									'class'		=> 'dropdown-item',
								)
							);
		}

		echo		'<div class="dropdown-menu">';

		if( $links ){
			echo			implode('', $links);
		}

		echo		'</div>'; // /.dropdown-menu
		echo	'</li>'; // /.nav-item dropdown
		echo '</ul>'; // /.nav.navbar-nav.lang-dropdown-nav
	}


	/**
	 * Gadget Compact Language Select
	 *
	 */
	public static function _Gadget_Compact_Select(){
		$object = self::GetObject();
		$object->Gadget_Compact_Select();
	}


	public function Gadget_Compact_Select(){
		global $page, $addonRelativeCode;


		$page->css_user[] = $addonRelativeCode . '/compact_select.css';

		//admin and special pages cannot be translated
		if( $page->pagetype != 'display' ){
			return;
		}

		$list = $this->GetVisibleList($page->gp_index);
		if( count($list) < 2 ){
			return;
		}

		$current_page_lang = array_search($page->gp_index, $list);

		//show the list
		$links = array();
		foreach($this->avail_langs as $lang_code => $lang_label){

			if( !isset($list[$lang_code]) ){
				continue;
			}

			$index			= $list[$lang_code];
			$title			= common::IndexToTitle($index);
			if( $lang_code == $current_page_lang ){
				$links[]	= '<a class="current-language"' .
					' hreflang="' . $lang_code . '"' .
					' title="' . $lang_label . '">' .
					$current_page_lang .
					'</a>';
			}else{
				$links[]	= common::Link(
					$title,
					$lang_code,
					'',
					array(
						'hreflang'	=> $lang_code,
						'title'		=> $lang_label
					)
				);
			}
		}

		echo '<ul class="compact-lang-select">';

		if( $links ){
			echo	'<li>';
			echo		implode('</li><li>', $links);
			echo	'</li>';
		}

		echo '</ul>';
	}


	/**
	 * [GetMenuArray] hook
	 * Translate a menu array using the translation lists
	 *
	 */
	public static function _GetMenuArray($menu){
		$object = self::GetObject();
		return $object->GetMenuArray($menu);
	}


	public function GetMenuArray($menu){
		global $page;

		//which language is the current page
		$list = $this->GetList($page->gp_index);
		if( !$list ){
			return $menu;
		}

		$page_lang = array_search($page->gp_index,$list);

		if( !$page_lang ){
			return $menu;
		}

		//if it's the default language, we don't need to change the menu
		// ... if the menu isn't actually in the primary language, we still want to translate it
		//if( $page_lang == $this->lang ){
		//	return $menu;
		//}


		//if we can determine the language of the current page, then we can translate the menu
		$new_menu = array();
		foreach($menu as $key => $value){

			$list = $this->GetList($key);
			if( !isset($list[$page_lang]) ){
				if( !isset($new_menu[$key]) ){
					$new_menu[$key] = $value;
				}
				continue;
			}

			$new_key = $list[$page_lang];
			if( !isset($new_menu[$new_key]) ){
				$new_menu[$new_key] = $value;
			}
		}

		return $new_menu;
	}


	/**
	 * Set $page->language
	 *
	 * if page is translated, use language of translation
	 * otherwise use existing $page-lang (since Typesrtter ver 5.1.1-b1) 
	 * or global config language (up to Typesetter 5.1)
	 *
	 */
	public static function _PageRunScript($cmd){
		$object = self::GetObject();
		return $object->PageRunScript($cmd);
	}


	public function PageRunScript($cmd){
		global $page, $config;

		$lang			= isset($page->lang) ? $page->lang : $config['language'];
		$page_lang		= $lang;
		$list			= $this->GetList($page->gp_index);
		if( is_array($list) ){
			$in_list	= array_search($page->gp_index, $list);
			if( $in_list !== false ){
				$page_lang = $in_list;
			}
		}
		$page->lang		= $page_lang;
		$page->language	= $this->avail_langs[$page_lang];

		if( $page->pagetype == 'display' ){
			$page->admin_links[] = common::Link(
				'Admin_MultiLang',
				'<i class="fa fa-language"></i> Multi Language',
				'cmd=TitleSettings&index=' . $page->gp_index,
				array('data-cmd' => 'gpabox')
			);
		}

		if( \gp\tool::LoggedIn() ){
			$this->AddResources();
		}

		return $cmd;
	}

}


//for backwards compat
global $ml_object;
$ml_object = MultiLang::GetObject();
