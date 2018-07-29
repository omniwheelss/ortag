<?php

/**
	Includes all other controllers and models
**/
require_once 'controller/includes.php';

class DeviceService
{	
	private $conn;
	
	public function __construct()
	{
		$database = new Database();
		$db = $database->dbConnection();
		$this->conn = $db;
	}
	
	
	/**
		insert data into temp table
	**/	
	public function insertDataIntoTemp($data)
	{
		# Variable declaration
		$result = $dataStatus = null;
		$currentDateStamp = date("Y-m-d H:i:s");
		
		try {
			$stmt = $this->conn->prepare("CALL spInsTempData(?,?)");
			$stmt->bindParam(1,$data, PDO::PARAM_STR);
			$stmt->bindParam(2,$currentDateStamp, PDO::PARAM_STR);
			$stmt->execute();
			$result = $stmt->rowCount();
			$dataStatus =  'success';
		}
		catch (PDOException $e) {
			$dataStatus =  'error';
			$result = "|temp insert exception|".$e->getMessage()."".CONFIG::NEWLINE_ERROR."|";
		}
		return array($dataStatus, $result);
	}
	
	
	/**
		select data from temp table
	**/	
	public function selectTempData()
	{
		# Variable declaration
		$result = $dataStatus = null;
		
		try {
			$stmt = $this->conn->prepare("CALL spSelTempData()");
			$stmt->execute();
			if($stmt->rowCount() > 0){
				while($resultRow = $stmt->fetch(PDO::FETCH_ASSOC)){
					$data[] = $resultRow;
				}
				$result = $data;
				$dataStatus =  'success';
			}
		}
		catch (PDOException $e) {
			$dataStatus =  'error';
			$result = "|temp select exception|".$e->getMessage()."".CONFIG::NEWLINE_ERROR."|";
		}
		return array($dataStatus, $result);
	}	

	
	
	/**
		select data from temp table
	**/	
	public function insertDeviceData($dataContent, $locationName, $deviceEpochTime, $dateStamp)
	{
		# Variable declaration
		$result = $dataStatus = null;
		
		try {
			list($formatType,$protocolVersion,$IMEI,$deviceDateStamp,$liveData,$gpsStatus,$latitude,$longitude,$altitude,$speed,$direction,$odometer,$gpsMoveStatus,$externalBatteryVolt,$internalBatteryPercent,$gsmSignal,$unUsed,$alertMsgCode,$sensorInterface,$IGN,$analogInput1,$digitalInput1,$output1,$sequenceNo,$checkSum) = explode(",",$dataContent);
			$deviceDateStamp = $dateStamp;
			$serverDateStamp = date("Y-m-d H:i:s");
			
			$stmt = $this->conn->prepare("CALL spInsDeviceData(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
			$stmt->bindParam(1,$protocolVersion, PDO::PARAM_STR);
			$stmt->bindParam(2,$IMEI, PDO::PARAM_STR);
			$stmt->bindParam(3,$dateStamp, PDO::PARAM_STR);
			$stmt->bindParam(4,$liveData, PDO::PARAM_STR);
			$stmt->bindParam(5,$gpsStatus, PDO::PARAM_STR);
			$stmt->bindParam(6,$latitude, PDO::PARAM_STR);
			$stmt->bindParam(7,$longitude, PDO::PARAM_STR);
			$stmt->bindParam(8,$altitude, PDO::PARAM_STR);
			$stmt->bindParam(9,$speed, PDO::PARAM_STR);
			$stmt->bindParam(10,$direction, PDO::PARAM_STR);
			$stmt->bindParam(11,$odometer, PDO::PARAM_STR);
			$stmt->bindParam(12,$gpsMoveStatus, PDO::PARAM_STR);
			$stmt->bindParam(13,$externalBatteryVolt, PDO::PARAM_STR);
			$stmt->bindParam(14,$internalBatteryPercent, PDO::PARAM_STR);
			$stmt->bindParam(15,$gsmSignal, PDO::PARAM_STR);
			$stmt->bindParam(16,$unUsed, PDO::PARAM_STR);
			$stmt->bindParam(17,$alertMsgCode, PDO::PARAM_STR);
			$stmt->bindParam(18,$sensorInterface, PDO::PARAM_STR);
			$stmt->bindParam(19,$IGN, PDO::PARAM_STR);
			$stmt->bindParam(20,$analogInput1, PDO::PARAM_STR);
			$stmt->bindParam(21,$digitalInput1, PDO::PARAM_STR);
			$stmt->bindParam(22,$output1, PDO::PARAM_STR);
			$stmt->bindParam(23,$sequenceNo, PDO::PARAM_STR);
			$stmt->bindParam(24,$checkSum, PDO::PARAM_STR);
			$stmt->bindParam(25,$locationName, PDO::PARAM_STR);
			$stmt->bindParam(26,$deviceEpochTime, PDO::PARAM_STR);
			$stmt->bindParam(27,$serverDateStamp, PDO::PARAM_STR);

			$stmt->execute();
			$result = $stmt->rowCount();
			$dataStatus =  'success';
		}
		catch (PDOException $e) {
			$dataStatus =  'error';
			$result = "|device data insert exception|".$e->getMessage()."".CONFIG::NEWLINE_ERROR."|";
		}
		return array($dataStatus, $result);
	}	
	
	/**
		select data from temp table
	**/	
	public function deleteTempData($dataContent)
	{
		# Variable declaration
		$result = $dataStatus = null;
		$currentDateStamp = date("Y-m-d H:i:s");
		
		try {
			$stmt = $this->conn->prepare("CALL spDeleteTempData(?)");
			$stmt->bindParam(1,$dataContent, PDO::PARAM_STR);
			$stmt->execute();
			$result = $stmt->rowCount();
			$dataStatus =  'success';
		}
		catch (PDOException $e) {
			$dataStatus =  'error';
			$result = "|temp delete exception|".$e->getMessage()."".CONFIG::NEWLINE_ERROR."|";
		}
		return array($dataStatus, $result);
	}		

	
	
	
}
?>