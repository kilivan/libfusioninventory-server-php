<?php
require_once "Classes/FusionLib.class.php";

$fusionLib = FusionLib::getInstance();

$fusionLib->init();

// storageEngine and storageLocation relevant
$myConfigs = array(
storageEngine => "directory", 
storageLocation => "data", 
applicationName => "GLPI", 
criterias => array(maxFalse => 2, items => array("assetTag", "motherboardSerial", "macAddress")));

$fusionLib->setConfigs($myConfigs);

$fusionLib->start();

?>
