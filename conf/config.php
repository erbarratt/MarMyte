<?php

	//String function settings 
		mb_internal_encoding('UTF-8');	// Tell PHP that we're using UTF-8 strings until the end of the script use mb_ for string functions...
		mb_http_output('UTF-8'); 		// Tell PHP that we'll be outputting UTF-8 to the browser 


	//Naive performance metrics
		define('MAR_RUSTART', getrusage());
		define('MAR_TIME_START',  microtime(true));
		
		//$executionTime = (MAR_TIME_START - microtime(true))/60;
		
		function MarRutime($ru, $rus, $index) {
			return ($ru['ru_'.$index.'.tv_sec']*1000 + intval($ru['ru_'.$index.'.tv_usec']/1000))
			 -  ($rus['ru_'.$index.'.tv_sec']*1000 + intval($rus['ru_'.$index.'.tv_usec']/1000));
		}
		
		
	//Check version 
		if (PHP_MAJOR_VERSION  < 7){
			throw new \Exception('This framework only supports PHP 7.1+');
		}
		
	
	//Set up directory root
		define('DOC_ROOT',dirname(__DIR__));
	
	
	//Grab the application configuration that may include DB connection and other configuration constants
		if (file_exists(DOC_ROOT.'/conf/app_config.php')) {
			define('APP_CONFIG', true);
			require(DOC_ROOT.'/conf/app_config.php');
		}
		
		
	//Set up display warnings for debug, defaults to true
		if (!defined('DEBUG')){
			define ('DEBUG', true);
		}
		
		if (DEBUG){
			error_reporting(E_ALL);
			ini_set('display_errors', '1');
		}
		
		
	//Default constants - override these in /engine/plugin/core_config
		if (!defined('SITE_NAME')){ define('SITE_NAME','MarMyte'); }
		if (!defined('SITE_LINK')){ define('SITE_LINK','https://'.$_SERVER['HTTP_HOST']); }
		if (!defined('SITE_DESCRIPTION')){ define('SITE_DESCRIPTION','MarMyte Default Installation'); }
		if (!defined('DEFAULT_DIRECTORY')){ define('DEFAULT_DIRECTORY','public'); }
		
	//if scripts need to set own headers, they can be ignored for session start further down.
		if (!defined('SESSION_IGNORES')){ define('SESSION_IGNORES', []); }

		
	//Define base URL NODES, URL_REQUEST AND REQUEST_METHOD
		define ('URL_REQUEST', $_SERVER['REQUEST_URI']);
		define('URL_PATH', parse_url(URL_REQUEST, PHP_URL_PATH));
		define ('REQUEST_METHOD', $_SERVER['REQUEST_METHOD']);
	
	//re-register get params
		define('REQUEST_QUERY_STRING', parse_url(URL_REQUEST, PHP_URL_QUERY));
		mb_parse_str(REQUEST_QUERY_STRING, $_GET);

	//set up nodes
		$marNodes = explode('/', trim(URL_PATH, '/')); //trim important for URL_NODES index numbers
		$marNodes = array_filter( $marNodes, function($value) { return $value !== ''; });
		define('URL_NODES', $marNodes);
	
	/*
	* Define PSR-4 autoloader for classes
	* @param string $className The fully-qualified class name.
	* @return void
	*/
		spl_autoload_register(function($classString){
			
			$baseDir = DOC_ROOT.'/lib/';
			$classString = str_replace('\\', DIRECTORY_SEPARATOR, $classString);
			
			if (file_exists($baseDir.$classString .'.php')){
				
				//create a string constant to protect against direct file usage
					$classArray = explode('/',$classString);
					$defineString = end($classArray);
					define($defineString, true);
					
				require($baseDir.$classString .'.php');
				
			}

		});
		
		
	//Grab the vendor autloader if it exists
		if (file_exists(DOC_ROOT.'/vendor/autoload.php')) {
			require(DOC_ROOT.'/vendor/autoload.php');
		}
		
	
	//Get the database connection from core_config, or set to null (if DB not needed)
		if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER') && defined('DB_PASSWORD')){
			try { 
				$DBH = new \PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASSWORD);
				$DBH->setAttribute(\PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				$DBH->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
				$DBH->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, false);
				$DBH->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false); 			//return database data type instead of all strings
			}  
			catch(\PDOException $e) {
				header($_SERVER["SERVER_PROTOCOL"] . ' 500 Internal Server Error', true, 500);
				throw new \Exception($e->getMessage());
			} 
		} else {
			$DBH = null;
		}
		
		
	//Only start if current location not within session ignore array
		if (( !in_array(trim(URL_REQUEST,'/'), SESSION_IGNORES) )){
			if (session_status() == PHP_SESSION_NONE) {
				
				// **PREVENTING SESSION HIJACKING**
				// Prevents javascript XSS attacks aimed to steal the session ID
				ini_set('session.cookie_httponly', 1);

				// **PREVENTING SESSION FIXATION**
				// Session ID cannot be passed through URLs
				ini_set('session.use_only_cookies', 1);

				// Uses a secure connection (HTTPS) if possible
				ini_set('session.cookie_secure', 1);
				
				session_start();
			}
		}
		

	//controller instantiate
		$marController = new \MarMyte\Controller($DBH);
