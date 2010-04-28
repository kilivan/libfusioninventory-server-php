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
    * @param array $configs (
    * storageEngine => "directory", 
    * storageLocation => "/data", 
    * applicationName => "GLPI", 
    * criterias => array(maxFalse => 2, items => array("asset tag", "motherboard serial")))
    */
    public function setConfigs($configs)
    {
      if(isset($configs["storageEngine"], 
	$configs["storageLocation"], 
	$configs["applicationName"], 
	$configs["criterias"]["maxFalse"],
	$configs["criterias"]["items"]))
      {
	
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
	
	$definedCriterias = array(
	"motherboardSerial",
	"assetTag",
	"msn",
	"ssn",
	"baseboardSerial",
	"macAddress",
	"uuid",
	"winProdKey",
	"biosSerial",
	"enclosureSerial",
	"smodel",
	"storagesSerial",
	"drivesSerial");
	
	foreach($configs["criterias"]["items"] as $criteria)
	{
	  if (!(in_array($criteria, $definedCriterias)))
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
	  echo 'created machine stage: error';
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
      
      $falseCriteriaNb=0;

      foreach($this->_configs["criterias"]["items"] as $criteria)
      {
	  if($falseCriteriaNb == $this->_configs["criterias"]["maxFalse"])
	  {
	    return false;
	  }
  
	  switch($criteria){
	  case "assetTag":
	    if ($simpleXMLObj->CONTENT->BIOS->ASSETTAG)
	    {
	      if (file_exists($this->_getCriteriaDSN($criteria, $simpleXMLObj->CONTENT->BIOS->ASSETTAG)))
	      {
		continue;
	      } else {
		$falseCriteriaNb++;
	      }
	    }
	  break;
	  case "motherboardSerial":
	    if ($simpleXMLObj->CONTENT->BIOS->MOTHERBOARDSERIAL)
	    {
	      if (file_exists($this->_getCriteriaDSN($criteria, $simpleXMLObj->CONTENT->BIOS->MOTHERBOARDSERIAL)))
	      {
		continue;
	      } else {
		$falseCriteriaNb++;
	      }
	    }
	  break;
	  case "msn":
	    if ($simpleXMLObj->CONTENT->BIOS->MSN)
	    {
	      if (file_exists($this->_getCriteriaDSN($criteria, $simpleXMLObj->CONTENT->BIOS->MSN)))
	      {
		continue;
	      } else {
		$falseCriteriaNb++;
	      }
	    }
	  break;
	  case "ssn":
	    if ($simpleXMLObj->CONTENT->BIOS->SSN)
	    {
	      if (file_exists($this->_getCriteriaDSN($criteria, $simpleXMLObj->CONTENT->BIOS->SSN)))
	      {
		continue;
	      } else {
		$falseCriteriaNb++;
	      }
	    }
	  break;
	  case "baseboardSerial":
	    if ($simpleXMLObj->CONTENT->BIOS->BASEBOARDSERIAL)
	    {
	      if (file_exists($this->_getCriteriaDSN($criteria, $simpleXMLObj->CONTENT->BIOS->BASEBOARDSERIAL)))
	      {
		continue;
	      } else {
		$falseCriteriaNb++;
	      }
	    }
	  break;
	  case "macAddress":
	    if ($simpleXMLObj->CONTENT->NETWORKS)
	    {
	      foreach($simpleXMLObj->CONTENT->NETWORKS as $networks){
		if ($networks->VIRTUALDEV!=1){
		  if (file_exists($this->_getCriteriaDSN($criteria, $networks->MACADDRESS)))
		  {
		    continue;
		  } else {
		    $falseCriteriaNb++;
		  }
		}
	      }
	    }
	  break;
	  case "uuid":
	    if ($simpleXMLObj->CONTENT->HARDWARE->UUID)
	    {
	      if (file_exists($this->_getCriteriaDSN($criteria, $simpleXMLObj->CONTENT->HARDWARE->UUID)))
	      {
		continue;
	      } else {
		$falseCriteriaNb++;
	      }
	    }
	  break;
	  case "winProdKey":
	    if ($simpleXMLObj->CONTENT->HARDWARE->WINPRODKEY)
	    {
	      if (file_exists($this->_getCriteriaDSN($criteria, $simpleXMLObj->CONTENT->HARDWARE->WINPRODKEY)))
	      {
		continue;
	      } else {
		$falseCriteriaNb++;
	      }
	    }
	  break;
	  case "biosSerial":
	    if ($simpleXMLObj->CONTENT->BIOS->BIOSSERIAL)
	    {
	      if (file_exists($this->_getCriteriaDSN($criteria, $simpleXMLObj->CONTENT->BIOS->BIOSSERIAL)))
	      {
		continue;
	      } else {
		$falseCriteriaNb++;
	      }
	    }
	  break;
	  case "enclosureSerial":
	    if ($simpleXMLObj->CONTENT->BIOS->ENCLOSURESERIAL)
	    {
	      if (file_exists($this->_getCriteriaDSN($criteria, $simpleXMLObj->CONTENT->BIOS->ENCLOSURESERIAL)))
	      {
		continue;
	      } else {
		$falseCriteriaNb++;
	      }
	    }
	  break;
	  case "smodel":
	    if ($simpleXMLObj->CONTENT->BIOS->SMODEL)
	    {
	      if (file_exists($this->_getCriteriaDSN($criteria, $simpleXMLObj->CONTENT->BIOS->SMODEL)))
	      {
		continue;
	      } else {
		$falseCriteriaNb++;
	      }
	    }
	  break;
	  case "storagesSerial":
	    if ($simpleXMLObj->CONTENT->STORAGES)
	    {
	      foreach($simpleXMLObj->CONTENT->STORAGES as $storages){
		if ($storages->TYPE=="disk"){
		  if (file_exists($this->_getCriteriaDSN($criteria, $storages->SERIAL)))
		  {
		    continue;
		  } else {
		    $falseCriteriaNb++;
		  }
		}
	      }
	    }
	  break;
	  case "drivesSerial":
	    if ($simpleXMLObj->CONTENT->DRIVES)
	    {
	      foreach($simpleXMLObj->CONTENT->DRIVES as $drives){
		if ($drives->SYSTEMDRIVE==1){
		  if (file_exists($this->_getCriteriaDSN($criteria, $drives->SERIAL)))
		  {
		    continue;
		  } else {
		    $falseCriteriaNb++;
		  }
		}
	      }
	    }
	  break;    
	}
      }
      
      return true;
    }
    
    /**
    * We create directory tree for machine and store software name and the externalId within YAML file.
    * @param $internalId int
    * @param $externalId int
    */
    private function _addLibMachine($internalId, $externalId)
    {
      $infoPath = sprintf('%s/%s/%s/%s', 
	  $this->_configs["storageLocation"],
	  "machines",
	  $internalId,
	  $this->_configs["applicationName"]);
	  
      if(!is_dir($infoPath))
      {
        mkdir($infoPath,0777,true);
      }
      if (!file_exists($infoPath."/infos.yml")) 
      {
        $infoFile = fopen($infoPath."/infos.yml","w");
	fclose($infoFile);
      }
      
      $data = <<<INFOCONTENT
external id: $externalId

section:
  - regegerher
  - ghrhrghtrh
INFOCONTENT;

      file_put_contents($infoPath."/infos.yml", $data, FILE_APPEND);
      
      
    }
    
    /**
    * Determine data source name of criterias
    * @param $criteriaName string
    * @param $criteriaValue string
    */
    private function _getCriteriaDSN($criteriaName, $criteriaValue)
    {
        if ($this->_configs["storageEngine"] == "directory")
	{
	  $dsn = sprintf('%s/%s/%s/%s/%s', 
	  $this->_configs["storageLocation"],
	  "criterias",
	  $criteriaName,
	  $this->_configs["applicationName"],
	  $criteriaValue);
	  return $dsn;	  
	  
	}
	
    }
  
  
}

?>