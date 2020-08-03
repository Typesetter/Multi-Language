<?php
defined('is_running') or die('Not an entry point...');

gpPlugin::Incl('Common.php');

class MultiLang_Admin extends MultiLang_Common{

	protected $cmds				= array();		//executable commands

	public function __construct(){
		global $config;

		$config += array('menus' => array());

		parent::__construct();

		$this->AddResources();

		$this->cmds['TitleSettingsSave']	= '';
		$this->cmds['TitleSettings']		= '';
		$this->cmds['RemoveTitle']			= 'TitleSettings';
		$this->cmds['NotTranslated']		= '';
		$this->cmds['SaveLanguages']		= 'SelectLanguages';
		$this->cmds['SelectLanguages']		= '';
		$this->cmds['PrimaryLanguage']		= '';
		$this->cmds['PrimaryLanguageSave']	= 'DefaultDisplay';
		$this->cmds['AllTitles']			= '';

		$cmd = common::GetCommand();
		$this->RunCommands($cmd);
	}


	/**
	 * Run Commands
	 * See \gp\Base::RunCommands() (available in TS 5.0)
	 *
	 */
	protected function RunCommands($cmd){

		$this->cmds	= array_change_key_case($this->cmds, CASE_LOWER);
		$cmd		= strtolower($cmd);

		if( !isset($this->cmds[$cmd]) ){
			$this->DefaultDisplay();
			return;
		}

		$cmds = (array)$this->cmds[$cmd];
		array_unshift($cmds, $cmd);

		foreach($cmds as $cmd){
			if( method_exists($this,$cmd) ){
				$this->$cmd();
			}elseif( is_callable($cmd) ){
				call_user_func($cmd, $this);
			}
		}
	}


	public function DefaultDisplay(){
		$this->ShowStats();
		$this->AllMenus();
		$this->SmLinks();
	}


	/**
	 * Display for for selecting the primary language
	 *
	 */
	public function PrimaryLanguage(){
		global $langmessage;

		echo '<div>';
		echo '<form method="post" action="' . common::GetUrl('Admin_MultiLang') . '">';
		echo '<input type="hidden" name="cmd" value="PrimaryLanguageSave" />';
		echo '<h3>Select Primary Language</h3>';

		echo '<select class="gpselect" name="primary">';
		foreach($this->avail_langs as $lang => $language){
			if( $lang == $this->lang ){
				echo '<option value="' . $lang . '" selected>' . htmlspecialchars($language) . '</option>';
			}else{
				echo '<option value="' . $lang . '">' . htmlspecialchars($language) . '</option>';
			}
		}
		echo '</select>';

		echo '<hr/>';

		echo '<input type="submit" class="gpsubmit" value="' . $langmessage['save'] . '" />';
		echo '<input type="button" value="' . $langmessage['cancel'] . '" class="admin_box_close gpcancel" />';

		echo '</form>';
		echo '</div>';
	}


	public function PrimaryLanguageSave(){
		global $langmessage;

		$primary = $_REQUEST['primary'];

		if( !isset($this->avail_langs[$primary]) ){
			msg($langmessage['OOPS'] . ' (Invalid Language)');
			return;
		}


		$this->config['primary']	= $primary;

		if( $this->SaveConfig() ){
			$this->lang				= $primary;
			$this->language			= $this->avail_langs[$this->lang];
		}
	}


	/**
	 * Save the list of languages to be used
	 *
	 */
	public function SaveLanguages(){
		global $langmessage;

		$langs = array();
		foreach($_POST['langs'] as $code => $on){
			if( !isset($this->avail_langs[$code]) ){
				msg($langmessage['OOPS'] . ' (Invalid Language)');
				return false;
			}
			$langs[$code] = $this->avail_langs[$code];
		}

		if( !count($langs) ){
			msg($langmessage['OOPS'] . ' (Can not be empty)');
			return false;
		}

		$this->config['langs'] = $langs;

		$this->SaveConfig();
	}


	public function SelectLanguages(){
		global $langmessage;

		echo '<h2>Languages</h2>';

		echo '<form method="post" action="' . common::GetUrl('Admin_MultiLang') . '">';
		echo '<input type="hidden" name="cmd" value="SaveLanguages" />';
		echo '<table class="bordered checkbox_table">';
		echo	'<tr>';
		echo		'<th>&nbsp;</th>';
		echo		'<th>Code</th>';
		echo		'<th>Language</th>';
		echo		'<th class="column-border">&nbsp;</th>';
		echo		'<th>Code</th>';
		echo		'<th>Language</th>';
		echo		'<th class="column-border">&nbsp;</th>';
		echo		'<th>Code</th>';
		echo		'<th>Language</th>';
		echo	'</tr>';
		$i = 1;
		foreach($this->avail_langs as $code => $label){
			$class = ($i % 6 ? '' : 'even');
			$attr = '';
			// so that if $this->langs isn't set, all of the entries won't be checked
			if( isset($this->config['langs'][$code]) ){
				$class	.= ' checked';
				$attr	= ' checked="checked"';
			}
			if( $i % 3 == 1 ){
				echo	'<tr class="' . trim($class) . '">';
			}
			echo		'<td' . ($i % 3 != 1 ? ' class="column-border" ' : '') . '>';
			echo			'<span class="sm">' . str_pad($i, 3, '0', STR_PAD_LEFT) . '</span> ';
			echo			'<input type="checkbox" name="langs[' . $code . ']" ' . $attr . ' />';
			echo		'</td>';
			echo		'<td>' . $code . '</td>';
			echo		'<td>' . $label . '</td>';
			if( $i % 3 == 0 || $i == count($this->avail_langs) ){
				echo	'</tr>';
			}
			$i++;
		}
		echo '</table>';

		echo '<p><br/>';
		echo	'<input type="submit" value="' . $langmessage['save'] . '" class="gpsubmit" /> ';
		echo	'<input type="button" name="cmd" value="' . $langmessage['cancel'] . '"';
		echo		' class="admin_box_close gpcancel" />';
		echo '</p>';
		echo '</form>';


	}

	/**
	 * Show language statistics
	 *
	 */
	public function ShowStats(){
		global $gp_index;

		//get some data
		$per_lang	= array();
		$list_sizes = array();
		foreach($this->lists as $list_index => $list){

			$list_sizes[$list_index] = count($list);
			foreach($list as $lang => $index){
				//per lang
				if( !isset($per_lang[$lang]) ){
					$per_lang[$lang] = 1;
				}else{
					$per_lang[$lang]++;
				}
			}
		}

		echo '<h2>Statistics</h2>';

		//Page Statistics
		echo '<div class="ml_stats">';
		echo '<div>';

		echo '<table class="bordered">';
		echo	'<tr>';
		echo		'<th colspan="2">Page Statistics</th>';
		echo	'</tr>';

		//count titles with translations
		echo	'<tr>';
		echo		'<td>Pages with Translations</td>';
		echo		'<td>' . number_format(count($this->titles)) . '</td>';
		echo	'</tr>';

		//count titles with translations
		echo	'<tr>';
		echo		'<td>';
		echo common::Link(
				'Admin_MultiLang',
				'Pages without Translations',
				'cmd=NotTranslated'
			);
		echo		'</td>';
		echo		'<td>' . number_format(count($gp_index) - count($this->titles)) . '</td>';
		echo	'</tr>';

		// % of titles with translations
		$percentage = count($this->titles) / count($gp_index) * 100;
		echo	'<tr>';
		echo		'<td>Percentage of Pages with Translations</td>';
		echo		'<td>' . number_format($percentage, 1) . '%</td>';
		echo	'</tr>';

		// # of lists
		echo	'<tr>';
		echo		'<td>Number of Page Lists</td>';
		echo		'<td>'.number_format(count($this->lists)).'</td>';
		echo 	'</tr>';

		// Average pages per list
		$average = 0;
		if( count($this->lists) > 0 ){
			$average = count($this->titles) / count($this->lists);
		}
		echo	'<tr>';
		echo		'<td>Average Pages Per List</td>';
		echo		'<td>' . number_format($average, 1) . '</td>';
		echo	'</tr>';

		echo '</table>';

		echo '</div>';
		echo '</div>'; // /.ml_stats

		$this->PageCount($per_lang);
	}


	/**
	 * Show Page Counts per language
	 *
	 */
	public function PageCount($per_lang){

		if( empty($per_lang) ){
			return;
		}

		//Language Statistics
		echo '<div class="ml_stats">';
		echo '<div>';

		echo '<table class="bordered">';
		echo	'<tr>';
		echo		'<th>Langauge</th>';
		echo		'<th>Page Count</th>';
		echo	'</tr>';

		// # of pages per language
		foreach($per_lang as $lang => $count){
			echo	'<tr>';
			echo		'<td>';
			echo common::Link(
					'Admin_MultiLang',
					$this->avail_langs[$lang],
					'cmd=AllTitles'
				);

			if( $lang == $this->lang ){
				echo ' - ' . common::Link(
						'Admin_MultiLang',
						'Primary Language',
						'cmd=PrimaryLanguage',
						'name="gpabox"'
					);
			}

			echo		'</td>';
			echo 		'<td>' . number_format($count) . '</td>';
			echo	'</tr>';
		}
		echo '</table>';

		echo '</div>';
		echo '</div>'; // /.ml_stats
	}


	/**
	 * Display all pages and their associated translations
	 *
	 */
	public function AllMenus(){
		global $gp_menu, $config;

		//show main menu
		$this->ShowMenu($gp_menu,'','Main Menu');


		//all other menus
		foreach($config['menus'] as $id => $menu_label){
			$array = gpOutput::GetMenuArray($id);
			$this->ShowMenu($array, $id, $menu_label);
		}
	}


	/**
	 * Display a menu and it's translated pages
	 *
	 */
	public function ShowMenu($menu, $id, $menu_label){
		global $langmessage;

		echo '<h3>';
		echo common::Link(
				'Admin_Menu',
				$menu_label,
				'menu=' . $id,
				array('data-arg' => 'cnreq')
			);
		echo '</h3>';

		$langs = $this->WhichLanguages();
		unset($langs[$this->lang]);

		echo '<table class="bordered full_width">';

		echo	'<tr>';
		echo		'<th width="1">' . $this->language . ' (Primary Language)</th>';
		foreach($langs as $lang){
			echo		'<th width="1">' . $this->avail_langs[$lang] . '</th>';
		}
		echo		'<th width="1">&nbsp;</th>';
		echo	'</tr>';

		$i = 0;
		foreach($menu as $page_index => $title_info){

			if( substr($page_index, 0, 1) === "_" ){
				// don't list external link menu entries
				continue;
			}

			$page_list = $this->GetList($page_index);

			//primary language
			echo	'<tr class="' . ($i % 2 ? 'even' : '') . '">';
			echo		'<td>';
			if( isset($page_list[$this->lang]) ){
				$title = common::IndexToTitle($page_list[$this->lang]);
				echo common::Link_Page($title);
			}else{
				$title = common::IndexToTitle($page_index);
				echo common::Link_Page($title);
			}
			echo		'</td>';

			foreach($langs as $lang){
				echo		'<td>';
				if( isset($page_list[$lang]) ){
					$title = common::IndexToTitle($page_list[$lang]);
					echo common::Link_Page($title);
				}
				echo		'</td>';
			}

			echo		'<td>';
			echo common::Link(
					'Admin_MultiLang',
					$langmessage['options'],
					'cmd=TitleSettings&index=' . $page_index,
					array('data-cmd' => 'gpabox')
				);
			echo		'</td>';
			echo	'</tr>';
			$i++;
		}

		echo '</table>';
	}


	/**
	 * Which languages
	 * Return a list of languages being used
	 *
	 */
	public function WhichLanguages(){

		$langs = array();

		foreach($this->lists as $list_index => $list){
			foreach($list as $lang => $index){
				if( !isset($per_lang[$lang]) ){
					$langs[$lang] = $lang;
				}
			}
		}

		return $langs;
	}


	/**
	 * Show a list of pages that don't have a translation setting
	 *
	 */
	public function NotTranslated(){
		global $gp_index, $config, $gp_menu, $page, $langmessage;

		$page->head_js[] = '/include/thirdparty/tablesorter/tablesorter.js';
		$page->jQueryCode .= '$("table.tablesorter").tablesorter({ ' .
			'cssHeader : "gp_header", cssAsc : "gp_header_asc", cssDesc : "gp_header_desc" ' .
			'});';


		$menu_info				= array();
		$menu_info['gp_menu']	= $gp_menu;

		$menu_labels			= array();
		$menu_labels['gp_menu'] = 'Main Menu';

		foreach($config['menus'] as $menu => $label){
			$menu_info[$menu] = gpOutput::GetMenuArray($menu);
			$menu_labels[$menu] = $label;
		}

		echo '<h2>Pages Without Translations</h2>';

		echo '<table class="bordered full_width tablesorter">';
		echo	'<thead>';
		echo		'<tr>';
		echo			'<th>Page</th>';
		echo			'<th>Slug</th>';
		echo			'<th>Menus</th>';
		echo			'<th>&nbsp;</th>';
		echo		'</tr>';
		echo	'</thead>';

		echo	'<tbody>';
		foreach($gp_index as $slug => $page_index){
			if( isset($this->titles[$page_index]) ){
				continue;
			}

			echo		'<tr>';
			echo			'<td>';
			$title = common::IndexToTitle($page_index);
			echo common::Link_Page($title);
			echo			'</td>';

			echo			'<td>';
			echo $title;
			echo			'</td>';

			echo			'<td>';
			$which_menus = array();
			foreach($menu_info as $menu => $info){
				if( isset($menu[$page_index]) ){
					$which_menus[] = common::Link(
						'Admin_Menu',
						$menu_labels[$menu],
						'menu=' . $menu,
						array('data-cmd' => 'cnreq')
					);
				}
			}
			echo implode(', ', $which_menus);
			echo			'</td>';

			echo			'<td>';
			echo common::Link('Admin_MultiLang',
					$langmessage['options'],
					'cmd=TitleSettings&index=' . $page_index,
					array('data-cmd' => 'gpabox')
				);
			echo			'</td>';
			echo		'</tr>';
		}
		echo	'</tbody>';
		echo '</table>';

	}


	/**
	 * Save the current configuration
	 * If successful, reset the lists and titles variables
	 */
	public function SaveConfig($refresh_msg = false){
		global $langmessage;

		if( !gpFiles::SaveArray($this->config_file, 'config', $this->config) ){
			msg($langmessage['OOPS']);
			return false;
		}

		$this->lists	= $this->config['lists'];
		$this->titles	= $this->config['titles'];
		if( count($this->config['langs']) ){
			$this->langs = $this->config['langs'];
		}

		if( $refresh_msg ){
			msg($langmessage['SAVED'] . ' ' . $langmessage['REFRESH']);
		}else{
			msg($langmessage['SAVED']);
		}

		return true;
	}


	/**
	 * Combobox for language / title selection
	 *
	 */
	public function Select($name, $data_source, $default=''){
		echo '<div>';
		echo '<span class="gpinput combobox" data-source="' . $data_source . '">';
		echo	'<input type="text" name="' . $name . '"';
		echo		' value="' . htmlspecialchars($default) . '"';
		echo		' class="combobox" />';
		echo	'</span>';
		echo '</div>';
	}


	/**
	 * Save new translations
	 *
	 */
	public function TitleSettingsSave(){
		$saved = $this->_TitleSettingsSave();

		if( $saved ){
			$this->TitleSettings();
		}else{
			$this->TitleSettings($_POST);
		}
	}


	public function _TitleSettingsSave(){
		global $gp_titles, $langmessage;

		//check from index
		$from_index = $_POST['index'];
		if( !isset($gp_titles[$from_index]) ){
			msg($langmessage['OOPS'] . ' (Invalid Title - 1)');
			return false;
		}

		//from language?
		$from_lang = $this->lang;
		if( isset($_POST['from_lang']) ){
			$from_lang	= $this->PostedLanguage($_POST['from_lang']);
			if( !$from_lang ){
				msg($langmessage['OOPS'] . '. (Language not found)');
				return false;
			}
		}

		//check to language
		$to_lang	= $this->PostedLanguage($_POST['to_lang']);
		if( !$to_lang ){
			msg($langmessage['OOPS'] . '. (Language not found)');
			return false;
		}

		//check to index
		$to_index	= $this->PostedTitle($_POST['to_slug']);
		if( !$to_index ){
			msg($langmessage['OOPS'] . '. (Title not found)');
			return false;
		}

		// a title can't be a translation of itself
		if( $from_index == $to_index ){
			msg($langmessage['OOPS'] . ' (Same Title)');
			return false;
		}

		// already a part of a list?
		$change_list = $this->GetListIndex($to_index);
		if( $change_list ){

			// don't stop if there's only one title in the list
			$list		= $this->GetList($to_index);
			if( count($list) > 1 ){
				$label = common::GetLabelIndex($to_index);
				$link = common::Link(
					'Admin_MultiLang',
					$label,
					'cmd=TitleSettings&index=' . $to_index,
					array('data-cmd' => 'gpabox')
				);
				msg('Sorry, ' . $link . ' is already part of a translation.');
				return false;
			}
		}

		//new or existing list
		$list_index = $this->GetListIndex($from_index);
		if( !$list_index ){
			$list_index = $this->NewListIndex();
		}

		// delete abandoned list
		if( $change_list ){
			unset($this->config['lists'][$change_list]);
		}

		//save data
		$this->config['lists'][$list_index][$from_lang]		= $from_index;
		$this->config['titles'][$from_index]				= $list_index;

		$this->config['lists'][$list_index][$to_lang]		= $to_index;
		$this->config['titles'][$to_index]					= $list_index;

		return $this->SaveConfig(true);
	}


	/**
	 * Get the language key from the language name
	 *
	 */
	public function PostedLanguage($lang){

		if( in_array($lang,$this->avail_langs) ){
			return array_search($lang,$this->avail_langs);
		}
	}


	/**
	 * Get the title index from the posted title
	 *
	 */
	public function PostedTitle($posted_slug){
		global $gp_index;

		foreach($gp_index as $slug => $index){

			if( $slug === $posted_slug ){
				return $index;
			}
		}
	}


	/**
	 * Language selection popup
	 *
	 */
	public function TitleSettings($args=array()){
		global $gp_titles, $langmessage, $langmessage, $gp_index;

		$args += array('to_lang' => '', 'to_slug' => '');

		$page_index = $_REQUEST['index'];
		if( !isset($gp_titles[$page_index]) ){
			echo $langmessage['OOPS'] . ' (Invalid Title - 3)';
			return;
		}

		$list		= $this->GetList($page_index);

		echo '<div class="mlm_title_settings_box">';
		echo '<form method="post" action="' . common::GetUrl('Admin_MultiLang') . '">';
		echo	'<input type="hidden" name="cmd" value="TitleSettingsSave" />';
		echo	'<input type="hidden" name="index" value="' . $page_index . '" />';

		echo	'<h3>Page Settings</h3>';

		echo	'<table class="bordered full_width">';
		echo		'<tr>';
		echo			'<th>Language</th>';
		echo			'<th>Title</th>';
		echo			'<th>Options</th>';
		echo		'</tr>';

		//not set yet
		if( !$list ){
			$in_menu	= $this->InMenu($page_index);
			echo	'<tr>';

			echo 		'<td>';
			if( $in_menu ){
				echo $this->language;
			}else{
				$this->Select('from_lang', '#lang_data');
			}
			echo		'</td>';

			echo		'<td>';
			$title = common::IndexToTitle($page_index);
			echo common::Link_Page($title);
			echo		'</td>';

			echo		'<td>&nbsp;</td>';

			echo	'</tr>';
		}


		//current settings
		foreach($this->avail_langs as $lang => $language){
			if( !isset($list[$lang]) ){
				continue;
			}

			$index = $list[$lang];

			echo	'<tr>';
			echo		'<td>';
			echo $language .' (' . $lang . ')';
			echo		'</td>';

			echo		'<td>';
			$title = common::IndexToTitle($index);
			echo common::Link_Page($title);
			echo		'</td>';

			echo		'<td>';
			echo common::Link(
					'Admin_MultiLang',
					'Remove',
					'cmd=RemoveTitle&index=' . $page_index . '&rmindex=' . $index,
					array(
						'data-cmd'	=> 'gpabox',
						'class'		=> 'gpconfirm',
						'title'		=> 'Remove this entry?',
					)
				);
			echo		'</td>';
			echo	'</tr>';

		}


		//option to add another title
		echo	'<tr>';

		echo		'<td>';
		$this->Select('to_lang', '#lang_data', $args['to_lang']);
		echo		'</td>';

		echo		'<td>';
		$this->Select('to_slug', '#lang_titles', $args['to_slug']);
		echo		'</td>';

		echo		'<td>';
		echo			'<input type="submit" value="' . $langmessage['save'] . '" class="gpabox gpbutton" />';
		echo		'</td>';
		echo	'</tr>';


		echo '</table>';
		echo '</form>';

		$this->SmLinks();


		//add languages as json
		$data = array();
		foreach($this->langs as $code => $label){
			$data[] = array($label, $code);
		}
		echo "\n";
		echo '<span id="lang_data" data-json=\'';
		echo	htmlspecialchars(json_encode($data), ENT_QUOTES & ~ENT_COMPAT);
		echo	'\'>';
		echo '</span>';


		//add titles as json
		$data = array();
		foreach($gp_index as $slug => $index){
			$label = common::GetLabelIndex($index);
			$data[] = array($slug, common::LabelSpecialChars($label));
		}
		echo "\n";
		echo '<span id="lang_titles" data-json=\'';
		echo	htmlspecialchars(json_encode($data), ENT_QUOTES & ~ENT_COMPAT, 'UTF-8', false);
		echo	'\'>';
		echo '</span>';

		// since 5.2 gpabox is fixed so we need space for the autocomplete dropdown
		echo '<div class="mlm_box_spacer"></div>';

		echo '</div>'; // /.mlm_title_settings_box
	}


	/**
	 * Determine if the page is in a menu
	 *
	 */
	public function InMenu($page_index){
		global $gp_menu, $config;


		//show main menu
		if( isset($gp_menu[$page_index]) ){
			return true;
		}

		foreach($config['menus'] as $id => $menu_label){
			$array = gpOutput::GetMenuArray($id);
			if( isset($array[$page_index]) ){
				return true;
			}
		}

		return false;
	}


	/**
	 * Remove a title from a translation list
	 *
	 */
	public function RemoveTitle(){
		global $gp_titles, $langmessage;

		$page_index = $_REQUEST['rmindex'];
		if( !isset($gp_titles[$page_index]) ){
			echo $langmessage['OOPS'] . ' (Invalid Title - 4)';
			return;
		}

		//get its list
		$list_index = $this->GetListIndex($page_index);
		if( $list_index === false ){
			return;
		}

		$page_lang = array_search($page_index, $this->config['lists'][$list_index]);
		if( !$page_lang ){
			return;
		}

		unset($this->config['titles'][$page_index]);
		unset($this->config['lists'][$list_index][$page_lang]);

		/*delete list if there's only one title
		if( count($this->config['lists'][$list_index]) < 2 ){

			$keys = array_keys($this->config['titles'],$list_index);
			foreach($keys as $key){
				unset($this->config['titles'][$key]);
			}
		}
		*/

		if( count($this->config['lists'][$list_index]) < 1 ){
			unset($this->config['lists'][$list_index]);
		}

		$this->SaveConfig(true);
	}

	public function SmLinks(){
		echo '<p class="sm more_admin_links">';
		echo common::Link('Admin_MultiLang', 'Administration');
		echo ' - ';
		echo common::Link('Admin_MultiLang', 'Languages', 'cmd=SelectLanguages');
		echo '</p>';
	}


	/**
	 * View all pages of the requested language
	 *
	 */
	public function AllTitles(){
		global $langmessage;

		echo '<h2>' . $langmessage['Pages'] . '</h2>';

		$lang_lists = array();
		foreach($this->lists as $list_index => $list){
			foreach($list as $lang => $index){
				$lang_lists[$lang][] = $index;
			}
		}


		foreach( $lang_lists as $lang => $indexes){
			echo '<div class="ml_stats">';
			echo '<div>';

			echo '<table class="bordered striped">';
			echo	'<thead>';
			echo		'<tr>';
			echo			'<th>' . $this->avail_langs[$lang] . '</th>';
			echo			'<th>' . $langmessage['options'] . '</th>';
			echo		'</tr>';
			echo	'</thead>';

			echo	'<tbody>';
			foreach($indexes as $index){
				echo		'<tr>';

				echo			'<td>';
				$title = common::IndexToTitle($index);
				echo common::Link_Page($title);
				echo			'</td>';

				echo			'<td>';
				echo common::Link(
						'Admin_MultiLang',
						$langmessage['options'],
						'cmd=TitleSettings&index=' . $index,
						array('data-cmd' => 'gpabox')
					);
				echo		'</td>';

				echo	'</tr>';
			}

			echo	'</tbody>';
			echo '</table>';

			echo '</div>';
			echo '</div>'; // /.ml_stats
		}

	}

}
