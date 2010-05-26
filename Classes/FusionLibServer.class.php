<?php
require_once dirname(__FILE__) . '/Action.class.php';
// +----------------------------------------------------------------------+
// | PHP version 5                                                        |
// +----------------------------------------------------------------------+
// | FusionLibServer provides a solution to process data from both OCS    |
// | agents and Fusion agents.                                            |
// | Users can easily retrieve data from these agents with hooks system.  |
// +----------------------------------------------------------------------+
// | Author: Taha Goulamhoussen <taha.goulamhoussen@gmail.com>            |
// +----------------------------------------------------------------------+
//
// FusionLib.class.php,v 1 17/05/2010
//

/**
* @package FusionInventory
* @category Server process
* @author Taha Goulamhoussen <taha.goulamhoussen@gmail.com>
* @license BSD
* @link http://fusioninventory.org/
*/
class FusionLibServer
{
    protected static $_instance;
    private $_actionsConfigs = array();
    private $_applicationName;

    /**
    * Disable instance
    * @access private
    */
    private function __construct()
    {
    }


    /**
    * Singleton
    */
    public static function getInstance()
    {
        if(self::$_instance == null)
        {
            self::$_instance = new self();
        }
        return self::$_instance;
    }


    /**
    * Configs :
    * @param string $action
    * @param array $configs
    */
    public function setActionConfig($action, $config)
    {
        $this->_actionsConfigs[$action] = $config;

    }

    /**
    * Application name :
    * @param string $applicationName
    */
    public function setApplicationName($applicationName)
    {
        $this->_applicationName = $applicationName;

    }

    public function start()
    {
        $simpleXMLObj = simplexml_load_string(@gzuncompress($GLOBALS["HTTP_RAW_POST_DATA"]));
        //$simpleXMLObj = simplexml_load_file(dirname(__FILE__) ."/../data/aofr.ocs");

        if($simpleXMLObj->QUERY == "PROLOG")
        {
            if(isset($simpleXMLObj->OLD_DEVICEID))
            {
                $deviceIdPath = dirname(__FILE__) ."/../hardware/{$simpleXMLObj->DEVICEID}";
                $oldDeviceIdPath = dirname(__FILE__) ."/../hardware/{$simpleXMLObj->OLD_DEVICEID}";
                if(file_exists($oldDeviceIdPath))
                {
                    rename($oldDeviceIdPath,$deviceIdPath);
                }
            }

            $this->_getXMLResponse($this->_actionsConfigs);
        }
        else
        {
            foreach ($this->_actionsConfigs as $actionName => $config)
            {
                if ($simpleXMLObj->QUERY == strtoupper($actionName))
                {
                    $action = ActionFactory::createAction($actionName);
                    $action->checkConfig($this->_applicationName, $config);
                    $action->startAction($simpleXMLObj);
                }
            }
        }
    }

    private function _getXMLResponse($actionsConfigs)
    {

        $response = <<<RESPONSE
<REPLY>
  <RESPONSE>SEND</RESPONSE>
  <PROLOG_FREQ>1</PROLOG_FREQ>
</REPLY>
RESPONSE;
        $dom = new DOMDocument();
        $dom->loadXML($response);
        //TODO: add options to response
        echo gzcompress($dom->saveXML());
    }
}

?>
