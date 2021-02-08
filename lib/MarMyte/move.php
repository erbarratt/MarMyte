<?php
namespace MarMyte;

if(!defined('move')){ exit; }

/**
* Deals with actions from do functions
*
* The control action holds the action, if any, of the do function
*
* @package    MarMyte Framework
* @author     Elliott Barratt
* @copyright  Elliott Barratt, all rights reserved.
*
*/ 

class move 
{
	
	/* @var string $_sessionKey The array key to store any passed messages */
		private $_sessionKey = '';
		
	/* @var string $_location Where to move to */
		private $_location = '';
		
	/* @var string $_message Message being passed */
		private $_message = '';
	
    /**
     * Set member variables for use in performAction()
	 * @param string $location Location to move to
	 * @param string $msg Message being passed
	 * @param string $sessionKey The array key to store any passed messages
	 * @return void
     */
		function __construct(string $location = '', string $msg = '', string $sessionKey = '')
		{
			
			$this->_sessionKey = $sessionKey;
			$this->_location = $location;
			$this->_message = $msg;
				
			return;
			
		}
	
    /**
     * Perform the action set up by construct and exit
     * @return void
     */
		public function performAction(): void
		{
			
			//set message
				if (trim($this->_message) !== '' && trim($this->_sessionKey) !== ''){
					$_SESSION[$this->_sessionKey] = $this->_message;
				}
			
			//go
				header('Location: '.$this->_location);
				
			exit;
			
		}
	
}
