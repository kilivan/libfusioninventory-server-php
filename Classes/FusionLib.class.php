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
		
		// TODO: file reception
		try {
		    $req = simplexml_load_file("data/aofr.ocs");
		} catch (Exception $e){
			echo 'Exception : ',  $e->getMessage(), "\n";
		}
		
		if ($this->_isMachineExist($req))
		{
			
			echo " machine exists";
			
			
		} else {
			
			echo " machine doesn't exist";
			
			//We launch CreateMachine() hook and provide an InternalId (how?)
			
			try {
			    $internalId = 12; // TODO
			    $externalId = Hooks::CreateMachine();			
			    $this->_addLibMachine($internalId, $externalId);
			} catch (Exception $e){
				
			}
		}

    }
	
	
	/**
   * We look for the machine with the relevant criterias defined by user, if it doesn't exist, return false; else return true.
   * @param $req SimpleXml object
   * @return $bool boolean
   */
	private function _isMachineExist($simpleXMLObj)
	{
		foreach($this->_configs["criterias"] as $criteria)
		{
			switch($criteria){
				case "asset tag":
				if ($simpleXMLObj->CONTENT->BIOS->ASSETTAG)
				{
					if (file_exists($configs["storageLocation"]."/"."assettag"."/".$configs["applicationName"]."/".$simpleXMLObj->CONTENT->BIOS->ASSETTAG))
					{
						continue;
					} else {
						return false;
					}
				}
				break;
				case "motherboard serial":
				if ($simpleXMLObj->CONTENT->BIOS->ASSETTA)
				{
					var_dump($simpleXMLObj->CONTENT->BIOS->ASSETTAG);
				}
				break;
				
				
			}
		}
		
		return true;
	}
	
	/**
   * We create directory tree for machine and store software name and the externalId within YAML file.
   * @param $internalId
   * @param $externalId
   */
	private function _addLibMachine($internalId, $externalId)
	{
		
	
	}
	

}

?>