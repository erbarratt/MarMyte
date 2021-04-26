<?php
namespace MarMyte;

if(!defined('Move')){ exit; }

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

class Move
{
	
	/* @var string $_sessionKey The array key to store any passed messages */
		private string $_sessionKey;
		
	/* @var string $_location Where to move to */
		private string $_location;
		
	/* @var string $_message Message being passed */
		private string $_message;
	
	/* @var string $_messageClass Style for message element */
		private string $_messageClass;
	
	/**
	 * Set member variables for use in performAction()
	 * @param string $location Location to move to
	 * @param string $msg Message being passed
	 * @param string $sessionKey The array key to store any passed messages
	 * @param string $sessionMessageClass Any passed class used for styling the message
	 */
		function __construct(string $location = '', string $msg = '', string $sessionKey = '', string $sessionMessageClass = '')
		{
			
			$this->_sessionKey = $sessionKey;
			$this->_location = $location;
			$this->_message = $msg;
			$this->_messageClass = $sessionMessageClass;
			
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
			
			//set message class
				if (trim($this->_messageClass) !== '' && trim($this->_sessionKey) !== ''){
					$_SESSION[$this->_sessionKey.'_class'] = $this->_messageClass;
				}
			
			//go
				header('Location: '.$this->_location);
				
			exit;
			
		}
	
}
