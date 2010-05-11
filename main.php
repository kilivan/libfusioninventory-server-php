<?php
set_include_path(dirname(__FILE__) . PATH_SEPARATOR . get_include_path());
require_once "Classes/FusionLibServer.class.php";

$fusionLibServer = FusionLibServer::getInstance();

// storageEngine and storageLocation relevant
$myConfigs = array(
"storageEngine" => "Directory",
"storageLocation" => "data", 
"applicationName" => "MyWebSite",
"criterias" => array(maxFalse => 1, items => array("assetTag", "motherboardSerial", "macAddress", "baseboardSerial")));

$fusionLibServer->setConfigs($myConfigs);

$fusionLibServer->start("inventory");

?>
