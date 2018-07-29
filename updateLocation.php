<?php

	/**
		Gateway to redirect the page based on the input
	**/
	require_once 'controller/includes.php';

	$location = new LocationController();
	
	$result = $location->updateLocation();
	echo $result;
?>
