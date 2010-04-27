<?php
require_once "Classes/FusionLib.class.php";

$fusionLib = FusionLib::getInstance();

$myConfigs = array(storageEngine => "directory", storageLocation => "/data", applicationName => "GLPI", criterias => array("asset tag", "motherboard serial"));

$fusionLib->setConfigs($myConfigs);

$fusionLib->start();

?>