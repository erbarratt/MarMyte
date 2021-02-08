<?php

/*
* Surround whole app with error detection
*/

try{

	/**
	* call up the config
	*/
		if (file_exists(__DIR__.'/conf/config.php')) {
			require(__DIR__.'/conf/config.php');
		} else {
			throw new \Exception('Config file not found.');
		}
	
} catch (\Exception $e){
	
	echo $e->getMessage().'<br/>';
	echo preg_replace('/\n/', '<br/>',$e->getTraceAsString());
	exit;
	
}
