<?php

/**
	Includes all other controllers and models
**/
require_once 'includes.php';
//require_once 'includesModels.php';

class HelperController
{

	/**
		__construct function
	**/
	public function __construct()
	{
		
	}
	
		
	/**
		data logger
	**/
	public function dataLoggerFile($logData, $logPrefix, $logDataStatus)
	{
		$Result = false;
		try {
			($logDataStatus == 'error')?$logPrefix = Config::ERROR_LOG : $logPrefix = $logPrefix;
			$this->dataLogger($logData, Config::LOGS_PATH, $logPrefix,"");
			$Result = true;
		}
		catch (PDOException $e) {
			$logDataStatus =  'error';
			$logData = "|insert logs exception|".$e->getMessage()."".CONFIG::NEWLINE_ERROR."|";
		}
		return $Result;
	}

	/**
		logger to store log information for all the store
	**/	
	static function dataLogger($data, $path, $logPrefix, $extraData) {
		
		$logFileCount = 0;
		// Setting null if  prefix empty
		$logPrefix = (!empty($logPrefix)? $logPrefix."_":"");

		$filePath = $path."/".$logPrefix."".@date("dmY").".log";
		$handle = fopen($filePath, 'a+');
		chmod($filePath, 0777);
		shell_exec("sudo chmod 777 ".$filePath."");
		$logFileRead = @file($filePath);
		$logFileReadCount = count($logFileRead);
		// For space between every line
		if (($logFileReadCount%2) == 0)
			$logFileCount = ($logFileReadCount/2)+1;

		$finalData = "".$logFileCount." - ".@date("d-m-Y H:i:s")." ".$data."";
		if(!fwrite($handle, "".CONFIG::NEWLINE."".$finalData."".CONFIG::NEWLINE."")) die("couldn't write to file. : Check the Folder permisson for (".$filePath.")");
	}
	
		
	/**
		get ip address for the user
	**/	
	public function getClientIP()
	{
		$ipAddress = '';
		if (getenv('HTTP_CLIENT_IP'))
			$ipAddress = getenv('HTTP_CLIENT_IP');
		else if (getenv('HTTP_X_FORWARDED_FOR'))
			$ipAddress = getenv('HTTP_X_FORWARDED_FOR');
		else if (getenv('HTTP_X_FORWARDED'))
			$ipAddress = getenv('HTTP_X_FORWARDED');
		else if (getenv('HTTP_FORWARDED_FOR'))
			$ipAddress = getenv('HTTP_FORWARDED_FOR');
		else if (getenv('HTTP_FORWARDED'))
		   $ipAddress = getenv('HTTP_FORWARDED');
		else if (getenv('REMOTE_ADDR'))
			$ipAddress = getenv('REMOTE_ADDR');
		else
			$ipAddress = 'UNKNOWN';
			
		return $ipAddress;
	}

	/**
		change date format
	**/	
	public function changeDateFormat($dateStamp){
		
		$year = substr($dateStamp, 0, 4);
		$month = substr($dateStamp, 4, 2);
		$date = substr($dateStamp, 6, 2);
		$hour = substr($dateStamp, 8, 2);
		$minute = substr($dateStamp, 10, 2);
		$seconds = substr($dateStamp, 12, 2);
		
		$dateStampFormat = $date."-".$month."-".$year;
		$timeStamp = $hour.":".$minute.":".$seconds;
		$deviceEpochTime = $this->convertToEpoch($dateStampFormat,$timeStamp);
		$dateStamp = $year."-".$month."-".$date." ".$timeStamp;
		
		return array($dateStamp, $deviceEpochTime);
	}
	
	
	/**
		convert epoch time
	**/	
	public function convertToEpoch($date,$time){
		
		$date_dd=substr($date,0,2);
		$date=substr($date,3);          
		$date_mm=substr($date,0,2);      
		$date_yyyy=substr($date,3);         
		$time_hh=substr($time,0,2);          
		$time=substr($time,3);               
		$time_mm=substr($time,0,2);           
		$time_ss=substr($time,3);                  
		$epochTime=mktime($time_hh,$time_mm,$time_ss,$date_mm,$date_dd,$date_yyyy);   
		return $epochTime;                                             
	}    
	
	
	/**
		redirection of the page
	**/	
	public function redirect($url)
	{
		header("Location: ".$url);
		exit;
	}
		
		
	/**	
		Redirects to views as per the input parameter
	**/
	public function gotoView($do)
	{
		include Config::VIEWS_PATH."/500.php";
		exit;
	}
			
			
	/**	
		Redirects to page as per the input parameter
	**/
	
	public function goto_page($do, $message)
	{
		$filename = "/".$do.".php";
		include Config::VIEWS_PATH."".$filename;
		exit;
	}
	
	/**
		Validate Latitude and Longitude
	**/	

	public function validateLatLong($latitude,$longitude){
	
		$latitudeValidation = $longitudeValidation = $result = false;
		
		if (preg_match("/^[-]?(([0-8]?[0-9])\.(\d+))|(90(\.0+)?)$/", $latitude)) {
			$latitudeValidation = true;
		}
			
		if(preg_match("/^[-]?((((1[0-7][0-9])|([0-9]?[0-9]))\.(\d+))|180(\.0+)?)$/",$longitude)) {
			$longitudeValidation = true;
		}
		
		if($latitudeValidation && $longitudeValidation){
			$result = true;
		}
		return $result;
	}	

}

?>