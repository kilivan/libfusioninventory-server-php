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
    if(self::$_instance == null) {
    self::$_instance = new self();
    }
    return self::$_instance;

    }

    /**
    * Initialization
    */
    public function init()
    {
    $this->_machine = new Machine();
    $this->_section = new Section();
    
    $this->_possibleCriterias = array(
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

    foreach($configs["criterias"]["items"] as $criteria)
    {
        if (!(in_array($criteria, $this->_possibleCriterias)))
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
    // TODO: file reception
    try {
    $simpleXMLObj = simplexml_load_file("data/aofr.ocs");
    } catch (Exception $e){
    echo 'Exception : ',  $e->getMessage(), "\n";
    }

    $this->_possibleCriterias = array(
    motherboardSerial => $simpleXMLObj->CONTENT->BIOS->ASSETTAG,
    assetTag => $simpleXMLObj->CONTENT->BIOS->MOTHERBOARDSERIAL,
    msn => $simpleXMLObj->CONTENT->BIOS->MSN,
    ssn => $simpleXMLObj->CONTENT->BIOS->SSN,
    baseboardSerial => $simpleXMLObj->CONTENT->BIOS->BASEBOARDSERIAL,
    macAddress => $simpleXMLObj->CONTENT->NETWORKS,
    uuid => $simpleXMLObj->CONTENT->HARDWARE->UUID,
    winProdKey => $simpleXMLObj->CONTENT->HARDWARE->WINPRODKEY,
    biosSerial => $simpleXMLObj->CONTENT->BIOS->BIOSSERIAL,
    enclosureSerial => $simpleXMLObj->CONTENT->BIOS->ENCLOSURESERIAL,
    smodel => $simpleXMLObj->CONTENT->BIOS->SMODEL,
    storagesSerial => $simpleXMLObj->CONTENT->STORAGES,
    drivesSerial => $simpleXMLObj->CONTENT->DRIVES);

    if ($this->_isMachineExist())
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
    * @param SimpleXml $simpleXMLObj
    * @return $bool boolean
    */
    private function _isMachineExist()
    {

    $falseCriteriaNb=0;

    foreach($this->_configs["criterias"]["items"] as $criteria)
    {
        if($falseCriteriaNb == $this->_configs["criterias"]["maxFalse"])
        {
        return false;
        }

        foreach($this->_possibleCriterias as $criteriaName => $criteriaValue)
        {

        if ($criteria == $criteriaName)
        {
        if ($criteriaValue)
        {
        switch($criteria){
            case "drivesSerial":
            foreach($criteriaValue as $drives){
            if ($drives->SYSTEMDRIVE==1){
            if (file_exists($this->_getCriteriaDSN($criteria, $drives->SERIAL)))
            {
                continue;
            } else {
                $falseCriteriaNb++;
            }
            }
            }
            break;
            case "storagesSerial":
            foreach($criteriaValue as $storages){
            if ($storages->TYPE=="disk"){
            if (file_exists($this->_getCriteriaDSN($criteria, $storages->SERIAL)))
            {
                continue;
            } else {
                $falseCriteriaNb++;
            }
            }
            }
            break;
            case "macAddress":
            foreach($criteriaValue as $networks){
            if ($networks->VIRTUALDEV!=1){
            if (file_exists($this->_getCriteriaDSN($criteria, $networks->MACADDR)))
            {
                continue;
            } else {
                $falseCriteriaNb++;
            }
            }
            }
            break;
            default:
            if (file_exists($this->_getCriteriaDSN($criteria, $criteriaValue)))
            {
            continue;
            } else {
                $falseCriteriaNb++;
            }
            break;

            }
        }
        }
    }
    }
    return true;
    }

    /**
    * We create directory tree for machine and store the externalId within YAML file.
    * @param int $internalId
    * @param int $externalId
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
    * We create directory tree for criteria and store internalId within YAML file.
    * @param int $internalId
    * @param int $externalId
    */
    private function _addLibCriterias($internalId, $externalId)
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
    * @param string $criteriaName
    * @param string $criteriaValue
    * @return string
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

    /**
    * get all sections md5
    * @param SimpleXml $simpleXMLObj
    * @return array
    */
    private function _getHashSections($simpleXMLObj)
    {


    }


}

?>