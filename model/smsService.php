<?php

/**
	Includes all other controllers and models
**/
require_once 'controller/includes.php';

class SMSService
{	
	private $conn;
	
	public function __construct()
	{
		$database = new Database();
		$db = $database->dbConnection();
		$this->conn = $db;
	}
	
	/**
		select if user requested SMS
	**/	
	public function spSelSMSAlertByIMEI($imei)
	{
		# Variable declaration
		$message = $dataStatus = $data = null;
		
		try {
			$stmt = $this->conn->prepare("CALL spSelSMSAlertByIMEI(?)");
			$stmt->bindParam(1,$imei, PDO::PARAM_STR);
			$stmt->execute();
			if($stmt->rowCount() > 0) {
				while($resultRow = $stmt->fetch(PDO::FETCH_ASSOC)){
					$data[] = $resultRow;
				}
				$message = "sms data fetched";
				$dataStatus = 'success';
			}
			else {
				$message = "no data available for the imei ".$imei."";
				$dataStatus = 'failure';
			}
		}
		catch (PDOException $e) {
			$dataStatus =  'error';
			$logData = "| get SMS alert exception|".$e->getMessage()."".CONFIG::NEWLINE_ERROR."|";
		}
		return array($imei, $dataStatus, $message, $data);
	}	
	
	
	/**
		SMS service - Insert commands
	**/	
	public function sendCommandToDevice($smsAlertTypeID, $dateStamp, $IMEI)
	{
		# Variable declaration
		$message = $dataStatus = $data = null;

		try {
			$stmt = $this->conn->prepare("CALL spInsSendCommandToDevice(?,?,?)");
			$stmt->bindParam(1,$smsAlertTypeID, PDO::PARAM_STR);
			$stmt->bindParam(2,$dateStamp, PDO::PARAM_STR);
			$stmt->bindParam(3,$IMEI, PDO::PARAM_STR);
			$stmt->execute();
			//$stmt->rowCount() 
			$message = "sms data fetched";
			$dataStatus = 'success';
		}
		catch (PDOException $e) {
			$dataStatus =  'error';
			$logData = "| get SMS alert exception|".$e->getMessage()."".CONFIG::NEWLINE_ERROR."|";
		}
		return array($IMEI, $dataStatus, $message);
	}
	
	/**
		Check request already sent to device
	**/	
	public function checkCommandAlreadySent($smsAlertTypeID, $IMEI)
	{
		# Variable declaration
		$message = $dataStatus = null;
		$data = 0;
		
		try {
			$stmt = $this->conn->prepare("CALL spSelSendCommandHistoryByIMEIAndAlert(?,?)");
			$stmt->bindParam(1,$smsAlertTypeID, PDO::PARAM_STR);
			$stmt->bindParam(2,$IMEI, PDO::PARAM_STR);			
			$stmt->execute();
			$data = $stmt->rowCount();
			$message = "device command history data fetched";
			$dataStatus = 'success';
		}
		catch (PDOException $e) {
			$dataStatus =  'error';
			$logData = "| get device command sent alert exception|".$e->getMessage()."".CONFIG::NEWLINE_ERROR."|";
		}
		return array($IMEI, $dataStatus, $message, $data);
	}	
	
	
	/**
		Device Response
	**/	
	public function updateDeviceReply($rawData)
	{
		# Variable declaration
		$message = $dataStatus = null;
		
		#Formatting data
		$deviceResponseFormatedData = str_replace('?reply=', '', $rawData);
		$deviceResponseFormatedDataExplode = explode(",",$deviceResponseFormatedData);
		$IMEI = $deviceResponseFormatedDataExplode[2];

		try {
			$stmt = $this->conn->prepare("CALL spUpdResponseFromDevice(?,?)");
			$stmt->bindParam(1,$IMEI, PDO::PARAM_STR);
			$stmt->bindParam(2,$deviceResponseFormatedData, PDO::PARAM_STR);
			$stmt->execute();
			$message = "Device response updated";
			$dataStatus = 'success';
		}
		catch (PDOException $e) {
			$dataStatus =  'error';
			$logData = "| Device response update exception|".$e->getMessage()."".CONFIG::NEWLINE_ERROR."|";
		}
		return array($IMEI, $dataStatus, $message);
	}	
}
?>