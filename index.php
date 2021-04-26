<?php

//Surround whole app with error detection
	try{
	
		//Call up the config
			if (file_exists(__DIR__.'/conf/config.php')) {
				require(__DIR__.'/conf/config.php');
			} else {
				throw new \Exception('Config file not found.');
			}
		
	} catch (\Exception $e){
	
		$prgError = $e->getMessage();
		$prgError .= PHP_EOL;
		$prgError .= $e->getTraceAsString();
		
	} finally {
	
		if(isset($prgError)){
		
			error_log($prgError);
		
			if(!defined('DEBUG')){ define('DEBUG', true); }
		
			if(DEBUG){
				echo preg_replace('/\n/', '<br/>',$prgError);
			}
		
		}
	
	}
