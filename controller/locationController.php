<?php

/**
	Includes all other controllers and models
**/
require_once 'includes.php';
require_once 'includesModels.php';

class LocationController
{

	private $locationService = NULL;
	
	/**
		Invoke model to access all the methods
	**/	
	public function __construct()
	{
		$this->locationService = new LocationService();
		$this->helper = new HelperController();
	}

	
	/**
		update the location if not available
	**/
	public function updateLocation() {
			
		$this->do = isset($_REQUEST['do'])?$_REQUEST['do']:NULL;	
		if($this->do != 'yes'){
			exit;
		}
			
		$locationDataResult = $fetchLocationDataResult = $fetchLocationDataVendorResult = $message = $locationKey = $locationUpdateFor = $locationUpdateStartMsg = null;
		$totalRecordsTobeProcessed = 0;
		# Fetch records
		$locationDataResult = $this->locationService->selectLocationIfNotAvailable();

		if($locationDataResult[0] == 'success'){	
		
			$locationData = $locationDataResult[2];
			$totalRecordsTobeProcessed = count($locationData);
			echo $locationUpdateStartMsg = "Total Records to be processed : ". $totalRecordsTobeProcessed."\n\n";

			foreach($locationData as $locationDataVal){
				$latitude = $locationDataVal['latitude'];
				$longitude = $locationDataVal['longitude'];
				$updateID = $locationDataVal['id'];

				echo $locationUpdateFor = "Updating location for : ". $latitude."|".$longitude."\n";
				
				# Fetch Location Name
				$fetchLocationDataResult = $this->locationService->fetchLocationByLatLong($latitude, $longitude);
				$fetchLocationDataResultStatus = $fetchLocationDataResult[0];
				if($fetchLocationDataResultStatus == 'success'){
					$locationName = $fetchLocationDataResult[2];
					echo "Location Inside --".$locationName."\n\n";
				}
				else{
					$validateLatLongResult = $this->helper->validateLatLong($latitude,$longitude);
					if($validateLatLongResult){

						# update location
						$fetchLocationDataVendorResult = $this->locationService->fetchLocationFromVendorByLatLong(Config::PRIMARY_GEO_ENGINE ,$latitude, $longitude, 'api');
						$locationName = $fetchLocationDataVendorResult[0];
						$locationKey = $fetchLocationDataVendorResult[1];
						$geoEngine = $fetchLocationDataVendorResult[2];
						echo "Location ".$geoEngine." --".$locationName."\n\n";
						# Data logger
						$this->helper->dataLoggerFile($locationKey, $logPrefix = 'googleGeo', 'success');						
					}	
				}
				# update location
				$locationUpdateDataResult = $this->locationService->updateLocationIfNotAvailable($updateID, $locationName);
			}
			//resetting the value if location is not available
			$fetchLocationDataResultStatus = null;
			
			$message = "\nlocation update finished ";
		}
		return $message;
	}	
	
	/**
		update the location if not available
	**/
	public function testLocation() {
			
		$this->do = isset($_REQUEST['do'])?$_REQUEST['do']:NULL;	
		if($this->do != 'yes'){
			exit;
		}
			
		$locationDataResult = $fetchLocationDataResult = $fetchLocationDataVendorResult = $message = $locationKey = $locationUpdateFor = $locationUpdateStartMsg = null;
		$totalRecordsTobeProcessed = 0;
		# Fetch records
		$locationDataResult = $this->locationService->selectLocationIfNotAvailable();

		if($locationDataResult[0] == 'success'){	
		
			$locationData = $locationDataResult[2];
			$totalRecordsTobeProcessed = count($locationData);
			echo $locationUpdateStartMsg = "Total Records to be processed : ". $totalRecordsTobeProcessed."\n\n";

			foreach($locationData as $locationDataVal){
				$latitude = $locationDataVal['latitude'];
				$longitude = $locationDataVal['longitude'];

				echo $locationUpdateFor = "Updating location for : ". $latitude."|".$longitude."\n";
				
				# Fetch Location Name
				$validateLatLongResult = $this->helper->validateLatLong($latitude,$longitude);

				# update location
				$fetchLocationDataVendorResult = $this->locationService->fetchLocationFromVendorByLatLong(Config::PRIMARY_GEO_ENGINE ,$latitude, $longitude, 'cron');
				$locationName = $fetchLocationDataVendorResult[0];
				$locationKey = $fetchLocationDataVendorResult[1];
				$geoEngine = $fetchLocationDataVendorResult[2];
				echo "Location ".$geoEngine." --".$locationName."\n\n";
			}
			//resetting the value if location is not available
			$fetchLocationDataResultStatus = null;
			
			$message = "\nlocation test finished ";
		}
		return $message;
	}	
	
}

?>