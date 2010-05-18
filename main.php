<?php
set_include_path(dirname(__FILE__) . PATH_SEPARATOR . get_include_path());
require_once "Classes/FusionLibServer.class.php";
//edit your own path for hooks
require_once dirname(__FILE__) ."/examples/MyWebSite/FusInvHooks.class.php";

$configs = parse_ini_file("configs.ini", true);

$fusionLibServer = FusionLibServer::getInstance();

//We launch multiple action with its config
foreach($configs['actions'] as $action){
    $fusionLibServer->setActionConfig($action, $configs[$action]);
}
$fusionLibServer->start();
?>
