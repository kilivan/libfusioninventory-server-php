<?php
require_once "hooks.class.php";
require_once "storage.class.php";

class FusionLib
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
    * Initialization
    */
    public function init()
    {
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
    * - the list of criterias and a margin for errors
    * @param array $configs (
    * storageEngine => "directory",
    * storageLocation => "/data",
    * applicationName => "GLPI",$simpleXMLData
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

            if (!(in_array($configs["storageEngine"], array("Directory", "Database"))))
            {
                throw new Exception ("storageEngine that you specified doesn't exist");
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
        } catch (Exception $e) {
            echo 'Exception : ',  $e->getMessage(), "\n";
        }

        $libData = StorageFactory::createStorage($this->_configs, $simpleXMLObj);

        if ($internalId = $libData->isMachineExist())
        {
            echo " machine exists $internalId";

            //Sections update
            $xmlSections = $this->_getXMLSections($simpleXMLObj);

            $libData->updateLibMachine($xmlSections, $internalId);


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
                $libData->addLibMachine($internalId, $externalId, $xmlSections);
            } catch (Exception $e) {
                echo 'created machine stage: error';
            }
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

}

?>
