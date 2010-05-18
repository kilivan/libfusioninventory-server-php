<?php
set_include_path(dirname(__FILE__) . PATH_SEPARATOR . get_include_path());
require_once "Classes/FusionLibServer.class.php";

$configs = parse_ini_file("configs.ini", true);

$fusionLibServer = FusionLibServer::getInstance();

//We launch multiple action with its config
foreach($configs['actions'] as $action){
    $fusionLibServer->setActionConfig($action, $configs[$action]);
}
$fusionLibServer->start();
?>
