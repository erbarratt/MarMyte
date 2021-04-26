<?php
namespace MarMyte;

if(!defined('controller')){ exit; }

/**
* Controller routing class
*
* The controller determines how to route a given request
*
* @package    MarMyte Framework
* @author     Elliott Barratt
* @copyright  Elliott Barratt, all rights reserved.
*
*/ 
class Controller extends \MarMyte\PDO
{

	/* @var string $_appFolder App folder definition */
		private string $_appFolder;

	/* @var string $_directory Directory definition */
		private string $_directory;

	/* @var array $_directoryArray Directory array */
		private array $_directoryArray;

	/* @var string $_pageUrl Page url definition */
		private string $_pageUrl;

	/**
	 * construct contains the main process or view logic
	 * @param \PDO|null $DBH Database handle
	 * @throws \Exception
	 */
		function __construct(\PDO &$DBH = null)
		{
			
			parent::__construct($DBH);
			
			//parse URL to correct process location
				$urlNodes = URL_NODES; 											//needed for use of end()
				$nodeCount = count($urlNodes);
				
				//root URL
					if (!isset($urlNodes[0])){									
					
						$this->_appFolder = DEFAULT_DIRECTORY;					// default directory
						$this->_directory = '';									// default directory
						$this->_directoryArray = ['root']; 						// for database selection
						$this->_pageUrl = 'index';								//i.e.empty, homepage.
					
				//physical app directory, but root of
					} elseif ($nodeCount === 1 && is_dir(DOC_ROOT.'/app/'.$urlNodes[0])){
							
						$this->_appFolder = $urlNodes[0];						// default directory
						$this->_directory = '';									// default directory
						$this->_directoryArray = [$urlNodes[0]];				// for database selection
						$this->_pageUrl = 'index';								// single webpage
						
				//single webpage?
					} elseif ($nodeCount === 1){
							
						$this->_appFolder = DEFAULT_DIRECTORY;					// default directory
						$this->_directory = '';									// default directory
						$this->_directoryArray = ['root'];						// for database selection
						$this->_pageUrl = $urlNodes[0];							// /single webpage
						
				//webpage in physical app directory, with N sub directories
					} elseif(is_dir(DOC_ROOT.'/app/'.$urlNodes[0])) {
						
						$this->_appFolder = $urlNodes[0];						//app directory
						$dirBits = $urlNodes;
						unset($dirBits[0]);										//remove first element
						array_pop($dirBits);									//remove last element
						$this->_directory = implode('/',$dirBits);				//full directory
						$this->_directoryArray = $dirBits;						// for database selection
						$this->_pageUrl = end($urlNodes);						//end of array
					
				//webpage in default directory in N sub directories
					} else {
						
						$this->_appFolder = DEFAULT_DIRECTORY;					//app directory
						$dirBits = $urlNodes;
						array_pop($dirBits);									//remove last element
						$this->_directory = implode('/',$dirBits);				//full directory
						$this->_directoryArray = $dirBits;						// for database selection (use end())
						$this->_pageUrl = end($urlNodes);						//end of array
						
					}
			
			//post requests
				if ($_SERVER['REQUEST_METHOD'] === 'POST'){
					
					//Grab common app actions
						if (file_exists(DOC_ROOT.'/app/'.$this->_appFolder.'/'.$this->_appFolder.'_post.php')) {
							define('COMMON', true);
							require(DOC_ROOT.'/app/'.$this->_appFolder.'/'.$this->_appFolder.'_post.php');
						}
					
					//Post to file in app
						if (file_exists(DOC_ROOT.'/app/'.$this->_appFolder.'/controller/'.(($this->_directory !== '') ? $this->_directory.'/': '').'control_'.$this->_pageUrl.'.php')) {
							define('PROCESS', true);
							require(DOC_ROOT.'/app/'.$this->_appFolder.'/controller/'.(($this->_directory !== '') ? $this->_directory.'/': '').'control_'.$this->_pageUrl.'.php');
						}
				
			//get requests
				} else {
					
					//Global path for file
						define('RESPONSE_PATH', DOC_ROOT.'/app/'.$this->_appFolder.'/view/views/'.(($this->_directory !== '') ? $this->_directory.'/': '').'view_'.$this->_pageUrl.'.php');
					
					//Grab common app actions
						if (file_exists(DOC_ROOT.'/app/'.$this->_appFolder.'/'.$this->_appFolder.'.php')) {
							define('COMMON', true);
							require(DOC_ROOT.'/app/'.$this->_appFolder.'/'.$this->_appFolder.'.php');
						}
					
					//Grab any response preparation
						if (file_exists(DOC_ROOT.'/app/'.$this->_appFolder.'/model/'.(($this->_directory !== '') ? $this->_directory.'/': '').'model_'.$this->_pageUrl.'.php')) {
							define('PREPARE', true);
							require(DOC_ROOT.'/app/'.$this->_appFolder.'/model/'.(($this->_directory !== '') ? $this->_directory.'/': '').'model_'.$this->_pageUrl.'.php');
						}
						
					//Loop through template if exists, otherwsie include direct file, Template then has to call file in RESPONSE_PATH
						define('RESPONSE', true);
						if(defined('TEMPLATE')){
							
							if(is_array(TEMPLATE)){
								
								foreach (TEMPLATE as $templatePart){
									
									require(DOC_ROOT.'/app/'.$this->_appFolder.'/view/common/'.$templatePart.'.php');
									
								}
								
							}
							
						} elseif (file_exists(RESPONSE_PATH)) {
							require(RESPONSE_PATH);
						} else {
							header("HTTP/1.0 404 Not Found");
						}
						
				}
			
		}
	
}
