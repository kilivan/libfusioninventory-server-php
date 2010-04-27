<?php
require_once "machine.class.php";
require_once "section.class.php";
require_once "hooks.class.php";

class FusionLib
{
	
	protected static $_instance;
	
	private $_configs;
	
	private $_machine;
	private $_section;
	
	/**
     * Disable instance
	 * @access private
     */
	private function __construct()
	{
	}
	
	public function addHook($notification, $nameFunctionToLaunch, $uri)
	{
		// TODO	
	}
	
	
	/**
     * Singleton
     */
	public static function getInstance()
    {
		if(self::$_instance == null) {
            self::$_instance = new self();
        }
        return self::$_instance;
		
    }
	
	/**
     * Initialization
     */
	private function _init()
    {
		$this->_machine = new Machine();
		$this->_section = new Section();		
    }
    
	/**
	* Configs :
	* User defines:
	* - where and how the data will be store
	* - the application that will use the library
	* - the priority and the list of criterias
	* @param array $configs (storageEngine => "directory", storageLocation => "/data", applicationName => "GLPI", criterias => array("asset tag", "motherboard serial"))
)
	*/
	public function setConfigs($configs)
    {
        if(isset($configs["storageEngine"], $configs["storageLocation"], $configs["applicationName"], $configs["criterias"])){
			
			if (!(in_array($configs["storageEngine"], array("directory", "database"))))
			{
				throw new Exception ("storageEngine that you specified doesn't exist");
			}
			
			if (!(is_string($configs["storageLocation"])))
			{
				throw new Exception ("storageLocation isn't a string");
			}
			
			if (!(is_string($configs["applicationName"])))
			{
				throw new Exception ("applicationName isn't a string");
			}
			
			foreach($configs["criterias"] as $criteria)
			{
				if (!(in_array($criteria, array("asset tag", "motherboard serial"))))
				{
					throw new Exception ("an criteria that you specified doesn't exist");
				}
			}
			
			$this->_configs = $configs;
			
		} else {
			throw new Exception ("you have to complete correctly configuration array");
		}
		

    }
	
	public function start()
    {
        $this->_init();
		
		try {
		    $req = simplexml_load_file("data/aofr.ocs");
		} catch (Exception $e){
			echo 'Exception : ',  $e->getMessage(), "\n";
		}
		
		
		
		
		
    }
	

}

?>