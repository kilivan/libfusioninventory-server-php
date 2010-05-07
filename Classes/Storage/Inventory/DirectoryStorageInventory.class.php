<?php
require_once "StorageInventory.class.php";

class DirectoryStorageInventory extends StorageInventory
{

    public function __construct($configs, $simpleXMLData)
    {
        $this->_configs=$configs;

        $this->_possibleCriterias = array(
        "motherboardSerial" => $simpleXMLData->CONTENT->BIOS->MOTHERBOARDSERIAL,
        "assetTag" => $simpleXMLData->CONTENT->BIOS->ASSETTAG,
        "msn" => $simpleXMLData->CONTENT->BIOS->MSN,
        "ssn" => $simpleXMLData->CONTENT->BIOS->SSN,
        "baseboardSerial" => $simpleXMLData->CONTENT->BIOS->BASEBOARDSERIAL,
        "macAddress" => $simpleXMLData->CONTENT->NETWORKS,
        "uuid" => $simpleXMLData->CONTENT->HARDWARE->UUID,
        "winProdKey" => $simpleXMLData->CONTENT->HARDWARE->WINPRODKEY,
        "biosSerial" => $simpleXMLData->CONTENT->BIOS->BIOSSERIAL,
        "enclosureSerial" => $simpleXMLData->CONTENT->BIOS->ENCLOSURESERIAL,
        "smodel" => $simpleXMLData->CONTENT->BIOS->SMODEL,
        "storagesSerial" => $simpleXMLData->CONTENT->STORAGES,
        "drivesSerial" => $simpleXMLData->CONTENT->DRIVES);
    }

    /**
    * We look for the machine with the relevant criterias defined by user, if it doesn't exist, return false; else return internalId.
    * @return bool false or internalId
    */
    public function isMachineExist()
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
                            {
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
    public function addLibMachine($internalId, $externalId, $xmlSections)
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
        $dsn = sprintf('%s/%s/%s/%s/%s',
        $this->_configs["storageLocation"],
        "criterias",
        $criteriaName,
        $this->_configs["applicationName"],
        $criteriaValue);
        return $dsn;
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
    public function updateLibMachine($xmlSections, $internalId)
    {

        $iniSections = $this->_getINISections($internalId);
        $xmlHashSections = array();
        foreach($xmlSections as $xmlSection)
        {
            array_push($xmlHashSections, $xmlSection["sectionHash"]);
        }


        $sectionsToAdd = array_diff($xmlHashSections, $iniSections["sections"]);
        $sectionsToRemove = array_diff($iniSections["sections"], $xmlHashSections);

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