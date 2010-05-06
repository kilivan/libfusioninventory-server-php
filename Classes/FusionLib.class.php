<?php
require_once "action.class.php";
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
// FusionLib.class.php,v 1 04/05/2010
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
    private $_configs;
    private $_possibleCriterias;

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
    * @param array $configs
    */
    public function setConfigs($configs)
    {
        $this->_configs = $configs;
    }

    public function start($actionName)
    {
        //$inputSocket = fopen('php://input','rb');
        //$contents = stream_get_contents($inputSocket);
        //fclose($inputSocket);
        //file reception
        $simpleXMLObj = simplexml_load_string(@gzuncompress($GLOBALS["HTTP_RAW_POST_DATA"]));

        $action = ActionFactory::createAction($actionName);
        $action->checkConfigs($this->_configs);
        $action->setXMLData($simpleXMLObj);

    }
}

?>
