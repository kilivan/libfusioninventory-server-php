<?php
require_once "Classes/FusionLib.class.php";

$fusionLib = FusionLib::getInstance();

// storageEngine and storageLocation relevant
$myConfigs = array(
storageEngine => "directory", 
storageLocation => "/data", 
applicationName => "GLPI", 
criterias => array(maxFalse => 2, items => array("asset tag", "motherboard serial")));

$fusionLib->setConfigs($myConfigs);

$fusionLib->start();

?>
