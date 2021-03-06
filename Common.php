<?php
defined('is_running') or die('Not an entry point...');

gpPlugin::Incl('Languages.php');

class MultiLang_Common extends MultiLang_Langs{

	protected $config_file;
	protected $config;
	protected $lists	= array();
	protected $titles	= array();
	protected $langs	= array();

	protected $lang;
	protected $language;


	public function __construct(){
		global $addonPathData, $config;


		$this->config_file	= $addonPathData.'/config.php';
		$this->lang			= $config['language'];

		$this->GetData();
	}


	public function GetData(){

		$config = array();
		if( file_exists($this->config_file) ){
			require($this->config_file);
		}

		$config += array(
			'titles'	=> array(),
			'lists'		=> array(),
			'langs'		=> array()
		);

		$this->config	= $config;
		$this->FixConfig();
		$this->lists	= $this->config['lists'];
		$this->titles	= $this->config['titles'];


		//primary lang
		if( isset($this->config['primary']) ){
			$this->lang = $this->config['primary'];
		}
		$this->language		= $this->avail_langs[$this->lang];

		if( !count($this->config['langs']) ){
			$this->langs = $this->avail_langs;
		}else{
			$this->langs =$this->config['langs'];
		}
	}


	/**
	 * Add multi language elements to the $page
	 *
	 */
	public function AddResources(){
		global $page, $addonRelativeCode;
		static $added = false;

		if( $added ){
			return;
		}

		\gp\tool\Plugins::js('script.js', false);
		\gp\tool\Plugins::css('admin.css', false);

		$added = true;
	}


	/**
	 * Remove entries from titles if they aren't in lists
	 *
	 */
	public function FixConfig(){
		if( !isset($this->config['titles']) || !is_array($this->config['titles']) ){
			return;
		}

		foreach($this->config['titles'] as $title_index => $list){
			if( !isset($this->config['lists'][$list]) ||
				!is_array($this->config['lists'][$list])
			){
				unset($this->config['titles'][$title_index]);
				continue;
			}

			if( !in_array($title_index, $this->config['lists'][$list]) ){
				unset($this->config['titles'][$title_index]);
				continue;
			}
		}
	}


	/**
	 * Get the list of only non-private pages for a title
	 * when logged-in private pages will be included
	 *
	 */
	public function GetVisibleList($page_index){
		global $gp_titles;

		$list		= $this->GetList($page_index);
		if( \gp\tool::LoggedIn() ){
			return $list;
		}

		$vis_list	= array();

		foreach($list as $lang => $index){
			if( empty($gp_titles[$index]['vis']) ){
				$vis_list[$lang] = $index;
			}
		}

		return $vis_list;
	}


	/**
	 * Get the list for a title
	 *
	 */
	public function GetList($page_index){

		$list_index = $this->GetListIndex($page_index);
		if( $list_index === false ){
			return array();
		}

		return $this->lists[$list_index];
	}


	/**
	 * Get the list index for a title
	 *
	 */
	public function GetListIndex($page_index){
		if( isset($this->titles[$page_index]) ){
			return $this->titles[$page_index];
		}

		return false;
	}


	/**
	 * Create a new list index
	 *
	 */
	public function NewListIndex(){

		$num_index = 0;
		if( is_array($this->lists) ){
			foreach($this->lists as $index => $values){
				$temp = base_convert($index, 36, 10);
				$num_index = max($temp, $num_index);
			}
		}
		$num_index++;

		do{
			$index = base_convert($num_index, 10, 36);
			$num_index++;
		}while( is_numeric($index) );

		return $index;
	}
}
