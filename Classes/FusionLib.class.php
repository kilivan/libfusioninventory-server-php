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
        if(self::$_instance == null)
        {
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
            $simpleXMLObj = simplexml_load_file("data/UC021527-2010-04-09-14-49-31.ocs");
        } catch (Exception $e) {
            echo 'Exception : ',  $e->getMessage(), "\n";
        }

        $this->_possibleCriterias = array(
        motherboardSerial => $simpleXMLObj->CONTENT->BIOS->MOTHERBOARDSERIAL,
        assetTag => $simpleXMLObj->CONTENT->BIOS->ASSETTAG,
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

        if ($internalId = $this->_isMachineExist())
        {
            echo " machine exists $internalId";

            //Sections update
            $xmlSections = $this->_getXMLSections($simpleXMLObj);
            $iniSections = $this->_getINISections($internalId);

            $this->_updateLibMachine($xmlSections, $iniSections, $internalId);


        } else {
            echo " machine doesn't exist";

            //We launch CreateMachine() hook and provide an InternalId
            $xmlSections = $this->_getXMLSections($simpleXMLObj);
            $internalId = uniqid();
            try {
                $externalId = Hooks::createMachine();
                
                // it's a new machine, we add directly all machine's sections
                foreach($xmlSections as &$section)
                {
                    $section["sectionId"] = Hooks::addSection(
                    $externalId,
                    $section['sectionName'],
                    $section['sectionData']);
                }
                var_dump($xmlSections);
                $this->_addLibMachine($internalId, $externalId, $xmlSections);
            } catch (Exception $e) {
                echo 'created machine stage: error';
            }
        }
    }


    /**
    * We look for the machine with the relevant criterias defined by user, if it doesn't exist, return false; else return true.
    * @param SimpleXml $simpleXMLObj
    * @return bool false or internalId
    */
    private function _isMachineExist()
    {
        $falseCriteriaNb=0;
        $internalId;

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
                        switch($criteria)
                        {
                            case "drivesSerial":
                            foreach($criteriaValue as $drives)
                            {
                                if ($drives->SYSTEMDRIVE==1)
                                {
                                    if (file_exists($this->_getCriteriaDSN($criteria, $drives->SERIAL)))
                                    {
                                        $internalId = scandir($this->_getCriteriaDSN($criteria, $drives->SERIAL));
                                    } else {
                                        $falseCriteriaNb++;
                                    }
                                }
                            }
                            break;
                            
                            case "storagesSerial":
                            foreach($criteriaValue as $storages)
                            {
                                if ($storages->TYPE=="disk")
                                {
                                    if (file_exists($this->_getCriteriaDSN($criteria, $storages->SERIAL)))
                                    {
                                        $internalId = scandir($this->_getCriteriaDSN($criteria, $storages->SERIAL));
                                    } else {
                                        $falseCriteriaNb++;
                                    }
                                }
                            }
                            break;
                            
                            case "macAddress":
                            foreach($criteriaValue as $networks)
                            {return $internalId[2];
                                if ($networks->VIRTUALDEV!=1 AND $networks->DESCRIPTION=="eth0")
                                {
                                
                                    if (file_exists($this->_getCriteriaDSN($criteria, $networks->MACADDR)))
                                    {
                                        $internalId = scandir($this->_getCriteriaDSN($criteria, $networks->MACADDR));
                                    } else {
                                        $falseCriteriaNb++;
                                    }
                                }
                            }
                            break;
                            
                            default:
                            if (file_exists($this->_getCriteriaDSN($criteria, $criteriaValue)))
                            {
                                $internalId = scandir($this->_getCriteriaDSN($criteria, $criteriaValue));
                            } else {
                                $falseCriteriaNb++;
                            }
                            break;

                        }
                    }
                }
            }
        }
        if (isset($internalId[2]))
        {
            return $internalId[2];
        }
        else {
            throw new Exception ("no avalaible criterias to compare");
        }
        
        
    }

    /**
    * We create directory tree for machine and store the externalId within YAML file.
    * @param $internalId
    * @param $externalId
    * @param $xmlHashSections
    */
    private function _addLibMachine($internalId, $externalId, $xmlSections)
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
        if (!file_exists($infoPath."/infos.ini"))
        {
            $infoFile = fopen($infoPath."/infos.ini","w");
            fclose($infoFile);
        }

        //Add directly hash section to the new machine
        ob_start();
        foreach($xmlSections as $section)
        {
            echo $section["sectionId"]."=".$section["sectionHash"]."
";
        }
        $sectionsHashData = ob_get_contents();
        ob_end_clean();

        $data = <<<INFOCONTENT
[externalId]
0=$externalId

[sections]
$sectionsHashData
INFOCONTENT;

        file_put_contents($infoPath."/infos.ini", $data);

        //Add criterias for this machine
        $this->_addLibCriteriasMachine($internalId);

    }

    /**
    * We create directory tree for criteria and internalId.
    * @param int $internalId
    */
    private function _addLibCriteriasMachine($internalId)
    {
        foreach($this->_possibleCriterias as $criteriaName => $criteriaValue)
        {
            if ($criteriaValue)
            {
                switch($criteriaName)
                {
                    case "drivesSerial":
                    foreach($criteriaValue as $drives)
                    {
                        if ($drives->SYSTEMDRIVE==1)
                        {
                            $criteriaPath = $this->_getCriteriaDSN($criteriaName, $drives->SERIAL);

                            $internalIdPath = sprintf('%s/%s',
                            $criteriaPath,
                            $internalId);

                            mkdir($internalIdPath,0777,true);
                        }
                    }
                    break;

                    case "storagesSerial":
                    foreach($criteriaValue as $storages)
                    {
                        if ($storages->TYPE=="disk")
                        {
                            $criteriaPath = $this->_getCriteriaDSN($criteriaName, $storages->SERIAL);

                            $internalIdPath = sprintf('%s/%s',
                            $criteriaPath,
                            $internalId);

                            mkdir($internalIdPath,0777,true);
                        }
                    }
                    break;

                    case "macAddress":
                    foreach($criteriaValue as $networks)
                    {
                        if ($networks->VIRTUALDEV!=1 AND $networks->DESCRIPTION=="eth0")
                        {
                            $criteriaPath = $this->_getCriteriaDSN($criteriaName, $networks->MACADDR);

                            $internalIdPath = sprintf('%s/%s',
                            $criteriaPath,
                            $internalId);

                            mkdir($internalIdPath,0777,true);
                        }
                    }
                    break;

                    default:
                    $criteriaPath = $this->_getCriteriaDSN($criteriaName, $criteriaValue);

                    $internalIdPath = sprintf('%s/%s',
                    $criteriaPath,
                    $internalId);

                    mkdir($internalIdPath,0777,true);
                    break;

                }
            }
        }
    }

    /**
    * Determine data source name of criterias
    * @param string $criteriaName
    * @param string $criteriaValue
    * @return string $dsn
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
    * get all sections with its hash,name and data from XML file
    * @param simpleXML $simpleXmlObj
    * @return array $xmlSections (hash,name and data)
    */
    private function _getXMLSections($simpleXMLObj)
    {

        $xmlSections = array();

        foreach($simpleXMLObj->CONTENT->children() as $section)
        {
            ob_start();
            echo $section->getName()."<br />";
            foreach ($section->children() as $data)
            {
                echo $data->getName().": ".$data."<br />";
            }
            $sectionData = ob_get_contents();
            ob_end_clean();

            array_push($xmlSections, (array(
            sectionId => 0,
            sectionHash => md5($sectionData),
            sectionName => $section->getName(),
            sectionData => $sectionData)));
        }
        return $xmlSections;
    }

    /**
    * get all sections with its hash,and sectionId from INI file
    * @param int $internalId
    * @return array $iniSections (hash and sectionId)
    */
    private function _getINISections($internalId)
    {
        $infoPath = sprintf('%s/%s/%s/%s',
        $this->_configs["storageLocation"],
        "machines",
        $internalId,
        $this->_configs["applicationName"]);

        try
        {
            $iniSections = parse_ini_file($infoPath."/infos.ini", true);

        } catch (Exception $e) {
            echo 'error parse: ini file';
        }

        return $iniSections;
    }

    /**
    * Determine if there are sections changement and update
    * @param array $xmlSections
    * @param array $iniSections
    * @param int $internalId
    */
    private function _updateLibMachine($xmlSections, $iniSections, $internalId)
    {

        $xmlHashSections = array();
        foreach($xmlSections as $xmlSection)
        {
            array_push($xmlHashSections, $xmlSection["sectionHash"]);
        }


        $sectionsToAdd = array_diff($xmlHashSections, $iniSections["sections"]);
        $sectionsToRemove = array_diff($iniSections["sections"], $xmlHashSections);

        var_dump($sectionsToAdd);
        if ($sectionsToRemove)
        {

            foreach($sectionsToRemove as $sectionId => $hashSection)
            {
                Hooks::removeSection($sectionId, $iniSections["externalId"][0]);
                unset($iniSections["sections"][$sectionId]);
            }
        }
        if ($sectionsToAdd)
        {

            foreach($sectionsToAdd as $arrayId => $hashSection)
            {
                $iniSections["sections"] = array_merge(array(
                Hooks::addSection(
                $iniSections["externalId"][0],
                $xmlSections[$arrayId]['sectionName'],
                $xmlSections[$arrayId]['sectionData'])
                => $xmlSections[$arrayId]['sectionHash']),
                $iniSections["sections"]);

            }
        }

        if ($sectionsToAdd or $sectionsToRemove)
        {
            ob_start();
            foreach($iniSections["sections"] as $key => $hash)
            {
                echo $key."=".$hash."
";
            }
            $sectionsHashData = ob_get_contents();
            ob_end_clean();
            
            $externalId=$iniSections["externalId"][0];
            
            $data = <<<INFOCONTENT
[externalId]
0=$externalId

[sections]
$sectionsHashData
INFOCONTENT;

            $infoPath = sprintf('%s/%s/%s/%s',
            $this->_configs["storageLocation"],
            "machines",
            $internalId,
            $this->_configs["applicationName"]);
            
            file_put_contents($infoPath."/infos.ini", $data);
        }
    }

}

?>