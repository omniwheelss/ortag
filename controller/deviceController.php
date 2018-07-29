<?php

/**
	Includes all other controllers and models
**/
require_once 'includes.php';
require_once 'includesModels.php';

class DeviceController
{
	
	/**
		Invoke model to access all the methods
	**/	
	public function __construct()
	{
		$this->helper = new HelperController();
	}

	
	/**
		Record the device data into DB
	**/
	public function recordDeviceData($data, $do, $format) {

		$tempDataResult = $gpsDataResult = $processDataResult = $message = $status = $datas = null;
		
		# Data logger
		$this->helper->dataLoggerFile($data, $logPrefix = null, 'success');

		# Data parsing for individual log
		if ($do == 'data'){
			list($formatType,$protocolVersion,$IMEI) = explode(",",$data);
			$dataPocketCount = count(explode(",",$data));
			
			# Data logger if valid IMEI number
			if(strlen($IMEI) == Config::IMEI_LENGTH) {
				$this->helper->dataLoggerFile($data, $logPrefix = $IMEI, 'success');
			}
		}
		if(!empty($data)){
			# Insert Data into Temp
			$processDataResult = $this->processDeviceData($do, $data);
		}	
	}


	
	/**
		Validate the device data
	**/
	private function processDeviceData($do, $data) {
		
		$currentDataUpdateResult = $validateDataResult = null;
		$dataPocketCount = 0;
		$tempDataArray = null;
		$this->deviceService = new DeviceService();
		$this->smsService = new SMSService();
		
		# Insert temp data
		$tempDataResult = $this->deviceService->insertDataIntoTemp($data);

		# Select Temp Data
		$tempDataResult = $this->deviceService->selectTempData();

		
		# For reply from device
		if($do == 'reply'){
			$insertDeviceReplyResult = $this->smsService->updateDeviceReply($data);	

			# Delete the temp data
			$deleteTempDataResult = $this->deviceService->deleteTempData($data);
		}		
	
		if($tempDataResult[0] === 'success'){
			$tempDataArray = $tempDataResult[1];
					
			foreach($tempDataArray as $tempDataVal){
				# validate data
				$vaidateTempDataResult = $this->validateTempData($tempDataVal);
			}
		}
	}	
		
	
	/**
		Record the device data into DB
	**/
	private function validateTempData($datas) {

		$dataContent = $datas['content'];
		$dataStatus = $datas['status'];
		$dataDateStamp = $datas['date_stamp'];
		$dataContentCount = count(explode(",",$dataContent));
		$insertDeviceDataResult = $changeDateFormatResult = $deleteTempDataResult = $spSelSMSAlertData = $checkCommandAlreadySentResult = $Results = $locationResult = null;
		$fetchLocationDataResult = $fetchLocationDataResultStatus  = $validateLatLongResult = $fetchLocationDataVendorResult = $locationName = $locationKey = null;
		
		$this->deviceService = new DeviceService();
		$this->smsService = new SMSService();
		$this->locationService = new LocationService();

		if($dataContentCount == Config::DATA_LENGTH){
			# Data parsing
			list($formatType,$protocolVersion,$IMEI,$dateStamp,$liveData,$gpsStatus,$latitude,$longitude,$altitude,$speed,$direction,$odometer,$gpsMoveStatus,$externalBatteryVolt,$internalBatteryPercent,$gsmSignal,$unUsed,$alertMsgCode,$sensorInterface,$IGN,$analogInput1,$digitalInput1,$output1,$sequenceNo,$checkSum) = explode(",",$dataContent);
			
			# validating values
			$changeDateFormatResult = $this->helper->changeDateFormat($dateStamp);
			$dateStamp = $changeDateFormatResult[0];
			$deviceEpochTime = $changeDateFormatResult[1];
			

			# fetch location name
			$fetchLocationDataResult = $this->locationService->fetchLocationByLatLong($latitude, $longitude);
			$fetchLocationDataResultStatus = $fetchLocationDataResult[0];
			if($fetchLocationDataResultStatus == 'success'){
				$locationName = $fetchLocationDataResult[2];
				//echo "Location Inside --".$locationName."\n\n";
			}
			else{
				$validateLatLongResult = $this->helper->validateLatLong($latitude,$longitude);
				if($validateLatLongResult){
					# update location
					$fetchLocationDataVendorResult = $this->locationService->fetchLocationFromVendorByLatLong($latitude, $longitude, 'api');
					$locationName = $fetchLocationDataVendorResult[0];
					$locationKey = $fetchLocationDataVendorResult[1];
					//echo "Location Google --".$locationName."\n\n";
					# Data logger
					$this->helper->dataLoggerFile($locationKey, $logPrefix = 'googleGeo', 'success');						
				}	
			}			
			
			if($gpsStatus != 0) {
				# update device current status
				$insertDeviceDataResult = $this->deviceService->insertDeviceData($dataContent, $locationName, $deviceEpochTime, $dateStamp);
				
				# Delete the temp data
				$deleteTempDataResult = $this->deviceService->deleteTempData($dataContent);
				
				# select SMS alert
				$spSelSMSAlertDataResult = $this->smsService->spSelSMSAlertByIMEI($IMEI);
				$spSelSMSAlertData = $spSelSMSAlertDataResult[3];
				
				# Choose the sms alert
				if($spSelSMSAlertData != null){
					foreach($spSelSMSAlertData as $spSelSMSAlertDataVal){
						$smsAlertTypeID = $spSelSMSAlertDataVal['sms_alert_type_id'];
						$smsConfiguration = $spSelSMSAlertDataVal['configuration'];
						echo $this->deviceCommandToBeSend($IMEI, $speed, $IGN, $spSelSMSAlertDataVal, $dateStamp, $smsAlertTypeID, $smsConfiguration);
					}
				}		
			}
		}		
	}	
	
	
	/**
		deviceCommandToBeSend to the device
	**/
	private function deviceCommandToBeSend($IMEI, $speed, $IGN, $spSelSMSAlertDataVal, $dateStamp, $smsAlertTypeID, $smsConfiguration) {

		$Results = null;

		switch($smsAlertTypeID){
			//speed_alert
			case 1:
				if($speed > $smsConfiguration && $IGN == 1){
					$checkCommandAlreadySentResult = $this->smsService->checkCommandAlreadySent($smsAlertTypeID, $IMEI);
					// If request not sent to earlier
					if($checkCommandAlreadySentResult[3] == 0){
						$Results = $this->formatSMSAlert($spSelSMSAlertDataVal, $speed, $dateStamp);
						$sendRequestToDeviceResult = $this->smsService->sendCommandToDevice($smsAlertTypeID, $dateStamp, $IMEI);
					}
				}
			break;
			//engine_on_alert
			case 2:
				if($IGN == 1){
					$checkCommandAlreadySentResult = $this->smsService->checkCommandAlreadySent($smsAlertTypeID, $IMEI);
					if($checkCommandAlreadySentResult[3] == 0){
						$Results = $this->formatSMSAlert($spSelSMSAlertDataVal, $speed, $dateStamp);
						$sendRequestToDeviceResult = $this->smsService->sendCommandToDevice($smsAlertTypeID, $dateStamp, $IMEI);
					}
				}
			break;
			//engine_off_alert
			case 3:
				if($IGN == 0){
					$checkCommandAlreadySentResult = $this->smsService->checkCommandAlreadySent($smsAlertTypeID, $IMEI);
					if($checkCommandAlreadySentResult[3] == 0){
						$Results = $this->formatSMSAlert($spSelSMSAlertDataVal, $speed, $dateStamp);
						$sendRequestToDeviceResult = $this->smsService->sendCommandToDevice($smsAlertTypeID, $dateStamp, $IMEI);
					}
				}	
			break;
			default:
			break;
		}
		return $Results;
	}
	
	
	
	/*
	Format SMS alert
	*/
	private function formatSMSAlert($spSelSMSAlertDataVal, $speed, $dateStamp){
		
		$deviceCommandTemplate = null;
		$firstName = $spSelSMSAlertDataVal['firstname'];
		$smsTemplate = $spSelSMSAlertDataVal['template_content'];
		$vehicleNo = $spSelSMSAlertDataVal['vehicle_no'];
		$sendMobile = $spSelSMSAlertDataVal['send_mobile'];
		$deviceCommand = $spSelSMSAlertDataVal['device_command'];
		
		
		// For SMS
		if(strpos($smsTemplate, '[USER_NAME]')){
			$smsTemplate = str_replace('[USER_NAME]', $firstName, $smsTemplate);
			$smsTemplate = str_replace('[VEHICLE_NUMBER]', $vehicleNo, $smsTemplate);
			$smsTemplate = str_replace('[SPEED]', $speed, $smsTemplate);
			$smsTemplate = str_replace('[DATE_TIME]', $dateStamp, $smsTemplate);
		}
		// For Device_Command	//$IPCFG,<DEVCMD: SMS=[MOBILE_NUMBER],[MESSAGE] >
		$deviceCommandTemplate = str_replace('[MOBILE_NUMBER]', $sendMobile, $deviceCommand);
		$deviceCommandTemplate = str_replace('[MESSAGE]', $smsTemplate, $deviceCommandTemplate);
		
		return $deviceCommandTemplate;
	}	
	
}

?>