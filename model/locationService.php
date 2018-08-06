<?php

/**
	Includes all other controllers and models
**/
require_once 'controller/includes.php';

class LocationService
{	
	private $conn;
	
	public function __construct()
	{
		$database = new Database();
		$db = $database->dbConnection();
		$this->conn = $db;
	}
	
	/**
		select the records if location not available
	**/	
	public function selectLocationIfNotAvailable()
	{
		# Variable declaration
		$message = $dataStatus = $data = null;
		
		try {
			$stmt = $this->conn->prepare("CALL spSelLocationNotAvailableRecords()");
			$stmt->execute();
			if($stmt->rowCount() > 0) {
				while($resultRow = $stmt->fetch(PDO::FETCH_ASSOC)){
					$data[] = $resultRow;
				}
				$message = "location not available data fetched";
				$dataStatus = 'success';
			}
			else {
				$message = "no data available";
				$dataStatus = 'failure';
			}
		}
		catch (PDOException $e) {
			$dataStatus =  'error';
			$logData = "| location not available exception|".$e->getMessage()."".CONFIG::NEWLINE_ERROR."|";
		}
		return array($dataStatus, $message, $data);
	}	
	
	
	/**
		update the location 
	**/	
	public function updateLocationIfNotAvailable($updateID, $locationName)
	{
		# Variable declaration
		$message = $dataStatus = null;

		try {
			$stmt = $this->conn->prepare("CALL spUpdLocationNotAvailableRecords(?,?)");
			$stmt->bindParam(1,$updateID, PDO::PARAM_STR);
			$stmt->bindParam(2,$locationName, PDO::PARAM_STR);
			$stmt->execute();
			$message = "location updated data fetched";
			$dataStatus = 'success';
		}
		catch (PDOException $e) {
			$dataStatus =  'error';
			$logData = "| location updated data exception|".$e->getMessage()."".CONFIG::NEWLINE_ERROR."|";
		}
		return array($dataStatus, $message);
	}
	
	/**
		Insert Location
	**/	
	public function insertLocationDetails($latitude, $longitude, $location)
	{
		# Variable declaration
		$message = $dataStatus = $data = null;
		
		try {
			$stmt = $this->conn->prepare("CALL spInsLocation(?,?,?)");
			$stmt->bindParam(1,$latitude, PDO::PARAM_STR);
			$stmt->bindParam(2,$longitude, PDO::PARAM_STR);
			$stmt->bindParam(3,$location, PDO::PARAM_STR);
			$stmt->execute();
			if($stmt->rowCount() > 0) {
				$message = "location name data inserted";
				$dataStatus = 'success';
			}
			else {
				$message = "no data available";
				$dataStatus = 'failure';
			}
		}
		catch (PDOException $e) {
			$dataStatus =  'error';
			$logData = "| insert location name data exception|".$e->getMessage()."".CONFIG::NEWLINE_ERROR."|";
		}
		return array($dataStatus, $message);
	}	
	
	/**
		Fetch Location
	**/	
	public function fetchLocationByLatLong($latitude, $longitude)
	{
		# Variable declaration
		$message = $dataStatus = $data = null;
		
		try {
			$stmt = $this->conn->prepare("CALL spSelLocationByLatLong(?,?)");
			$stmt->bindParam(1,$latitude, PDO::PARAM_STR);
			$stmt->bindParam(2,$longitude, PDO::PARAM_STR);
			$stmt->execute();
			if($stmt->rowCount() > 0) {
				$rowResult = $stmt->fetch(PDO::FETCH_ASSOC);
				$data = $rowResult['location_name'];
				$message = "location name data fetched";
				$dataStatus = 'success';
			}
			else {
				$message = "no data available";
				$dataStatus = 'failure';
			}
		}
		catch (PDOException $e) {
			$dataStatus =  'error';
			$logData = "|fetch  location name data exception|".$e->getMessage()."".CONFIG::NEWLINE_ERROR."|";
		}
		return array($dataStatus, $message, $data);
	}	
		
	/**
		get location name from vendor
	**/	
	public function fetchLocationFromVendorByLatLong($geoEngine, $latitude,$longitude, $source)
	{
		$insertResult = $locationServiceResult = null;
		
		switch($geoEngine){
			case 'google':
				$geoKey = $this->googleReversGeoApiKey();
				$url = "https://maps.googleapis.com/maps/api/geocode/xml?latlng=".$latitude.",".$longitude."&sensor=true&key=".$geoKey."";
				if ($query = $this->loadXML($url)){
					$location = $query->result->formatted_address;
					# If location not available
					if(strlen(trim($location))==0){
						$locationResult = $this->fetchLocationFromVendorByLatLong('bing', $latitude,$longitude, $source);
						$location = $locationResult[0];
					}	
				}
			break;
			case 'bing':
				$geoKey = "AhagBeUCzi0-hsv42yPBftNG9PdZ8iR3ctH8jUZ0k00MBOVR8Svgz9iaru80rnep";
				$url = "http://dev.virtualearth.net/REST/v1/Locations/".$latitude.",".$longitude."?o=xml&key=".$geoKey."";
				if ($query =  $this->loadXML($url)){
					if($query->ResourceSets->ResourceSet->EstimatedTotal == 1){
						//print_r($query->ResourceSets->ResourceSet->Resources->Location->Address->FormattedAddress);
						$location = $query->ResourceSets->ResourceSet->Resources->Location->Address->FormattedAddress;
						# If location not available
						if(strlen(trim($location))==0){
							$locationResult = $this->fetchLocationFromVendorByLatLong('google', $latitude,$longitude, $source);
							$location = $locationResult[0];
						}	
					}
				}			
			break;
			case 'mapquest':
				$geoKey = "mDmGbEEMGlMddRKllJWXomQZ9D0DtmtP";
				$url = "http://www.mapquestapi.com/geocoding/v1/reverse?key=".$geoKey."&location=".$latitude.",".$longitude."&includeRoadMetadata=true&includeNearestIntersection=true";
				if ($query = $this->loadJSON($url)){

					if($query->options->maxResults == 1){
						$queryResult = $query->results;
						$addressArray = $queryResult[0]->locations[0];
						//print_r($addressArray);
						$location = $addressArray->street." ".$addressArray->adminArea5." ".$addressArray->adminArea3;
						# If location not available
						if(strlen(trim($location))==0){
							$locationResult = $this->fetchLocationFromVendorByLatLong('google', $latitude,$longitude, $source);
							$location = $locationResult[0];
						}	
					}
				}
			break;	
			default:
			break;
		}
		$location=trim($location);
		$location = str_replace(","," ",$location);
		$location = str_replace("\"","",$location);
		$location = str_replace("'","",$location);
		$Server_Date_Stamp = date("Y-m-d H:i:s");
		
		if(!empty($location)){
			$insertResult = $this->insertLocationDetails($latitude,$longitude, $location);
			
			# Usage of location service
			$locationServiceResult = $this->updateLocationServiceHistory($geoEngine,$geoKey, $source);
		}		
		
		if (strlen($location)==0){$location="location not available";}
		return array($location, $geoKey, $geoEngine);
	}


	/**
		// Function for Reverse Geo Coding to query the given URL using Curl.
		// This is required since the simplexml_load_file function does not offer a Timeout option.
		// By using Curl in an intermediate step, the request can be timed out
	**/	

	function loadXML($requestURL){
		$data = null;
		$data = simplexml_load_file($requestURL);
		return $data;
	}	
	
	
	
	/**
		get the location name from Vendor
	**/
	private function googleReversGeoApiKey(){
		$keyArray = array(
			'AIzaSyABIxljR0rtVmnWu7Fgo4OQQycURf7YdpA',
			'AIzaSyCDYice4etlOoHF8KKuEyUYjfytNsNPSHs',
			'AIzaSyBMAyTbGsFMChNbD7q_hDOSJnSEnZnDJ40',
			'AIzaSyAJJTubTf9NUEsnlPs0TLKvKCQ4GT5zqyA',
			'AIzaSyCNv-Uh4ZDwqcujDD8XfsiedwYJepQxykM',

			//'AIzaSyDgMqT0ID-YAlCZrytxB8HPSnK4oypSJAc',
			//'AIzaSyBvVKauuqFVOtdBHcRs-oDDOTQjR-sMRKg',
			//'AIzaSyDk465ePU-1_MKfHrF0mnh6Cm-VTx3Y7Qo',
			//'AIzaSyCYzTtJlwzzLa7eyWv7qOq0nkci8yKuz5k'
		);
		
		$randomKey = array_rand($keyArray);
		return $keyArray[$randomKey];
	}	
	
	
	/**
		update the location usage data
	**/	
	public function updateLocationServiceHistory($geoEngine, $geoKey, $source)
	{
		# Variable declaration
		$message = $dataStatus = null;

		try {
			$stmt = $this->conn->prepare("CALL spUpdLocationServiceHistory(?,?,?)");
			$stmt->bindParam(1,$geoEngine, PDO::PARAM_STR);
			$stmt->bindParam(2,$geoKey, PDO::PARAM_STR);
			$stmt->bindParam(3,$source, PDO::PARAM_STR);
			$stmt->execute();
			$message = "location service history updated";
			$dataStatus = 'success';
		}
		catch (PDOException $e) {
			$dataStatus =  'error';
			$logData = "| location service history exception|".$e->getMessage()."".CONFIG::NEWLINE_ERROR."|";
		}
		return array($dataStatus, $message);
	}	
	
	
	/**
		get JSON data URL
	**/	
	function loadJSON($requestURL){
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, $requestURL);
		$result = curl_exec($ch);
		curl_close($ch);

		$obj = json_decode($result);
		return $obj;
	}	
	
}
?>