<?php

/**
	Includes all other controllers and models
**/
require_once 'includes.php';

class GatewayController
{
	private static $common;
	public $do;
	public $data;
	

	/**
		__construct function
	**/
	public function __construct()
	{
		$this->device = new DeviceController();
		$this->helper = new HelperController();
		
		$this->queryString = isset($_SERVER['QUERY_STRING'])?$_SERVER['QUERY_STRING']:NULL;
		$queryStringParse = explode("=",$this->queryString);
		$this->do = $queryStringParse[0];
		$this->data = isset($_REQUEST[$this->do])?$_REQUEST[$this->do]:NULL;
		$this->format = isset($_REQUEST['format'])?$_REQUEST['format']:'json';
	}
	
	/**
		function to get the login & SEO values after setting it
	**/	
	public function getInstance()
	{
		if (!isset(self::$common))
		{
			$class = __CLASS__;
			self::$common = new $class();
		}
		return self::$common;
	}

	
		
	/**
		function to check query string to handle the page direction
	**/
	public function handleRequest()
	{
		# Getting the values from getInstance
		$common = GatewayController::getInstance();
		$this->device->recordDeviceData($this->data, $this->do, $this->format);
	}
}

?>