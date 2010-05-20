<?php
require_once dirname(__FILE__) . '/../Storage/Inventory/StorageInventory.class.php';

class InventoryAction extends Action
{
    private $_applicationName;
    private $_config;
    private $_possibleCriterias = array(
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

    /**
    * Config :
    * User defines:
    * - where and how the data will be store
    * - the application that will use the library
    * - the list of criterias and a margin for errors
    * @param string $applicationName (GLPI,MyWebSite ...)
    * @param array $config (
    * storageEngine => "directory",
    * storageLocation => "data",
    * criterias => array(maxFalse => 0, items => array("asset tag", "motherboard serial")))
    */
    public function checkConfig($applicationName, $config)
    {

        if (!(file_exists(dirname(__FILE__) ."/../../user/applications/$applicationName")))
        {
            throw new Exception ("Put your application in the user/applications directory");
        }

        $this->_applicationName = $applicationName;

        if(isset($config["storageEngine"],
        $config["storageLocation"],
        $config["criterias"],
        $config["maxFalse"]))
        {

            if (!(in_array($config["storageEngine"], array("Directory", "Database"))))
            {
                throw new Exception ("storageEngine that you specified doesn't exist");
            }

            foreach($config["criterias"] as $criteria)
            {
                if (!(in_array($criteria, $this->_possibleCriterias)))
                {
                    throw new Exception ("an criteria that you specified doesn't exist");
                }
            }

            if ($config["maxFalse"] < 0)
            {
                throw new Exception ("maxFalse must be at least 0");
            }

            $this->_config = $config;

        } else {
            throw new Exception ("you have to complete correctly configuration array for inventory");
        }

    }

    function setXMLData($simpleXMLObj)
    {
        if($simpleXMLObj->QUERY == "PROLOG")
        {
            $this->_getXMLResponse();
        } else if($simpleXMLObj->QUERY == "INVENTORY"){
            $this->_startAction($simpleXMLObj);
        }
    }

    private function _getXMLResponse()
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



    /**
    * Inventory process
    * @param simpleXML $simpleXMLObj
    */
    protected function _startAction($simpleXMLObj)
    {
        $libData = StorageInventoryFactory::createStorage($this->_applicationName, $this->_config, $simpleXMLObj);

        if ($internalId = $libData->isMachineExist())
        {
            //echo " machine exists $internalId";

            //Sections update
            $xmlSections = $this->_getXMLSections($simpleXMLObj);

            $libData->updateLibMachine($xmlSections, $internalId);
        } else {

            //echo " machine doesn't exist";

            //We launch CreateMachine() hook and provide an InternalId
            $xmlSections = $this->_getXMLSections($simpleXMLObj);
            $internalId = uniqid();
            try {
                $externalId = Hooks::createMachine();

                $libData->addLibMachine($internalId, $externalId);
                $libData->addLibCriteriasMachine($internalId);

                $libData->updateLibMachine($xmlSections, $internalId);
            } catch (Exception $e) {
                echo 'created machine stage: error';
            }
        }
    }


    /**
    * get all sections with its hash,name and data from XML file
    * @param simpleXML $simpleXMLObj
    * @return array $xmlSections (hash,name and data)
    */
    private function _getXMLSections($simpleXMLObj)
    {

        $xmlSections = array();

        foreach($simpleXMLObj->CONTENT->children() as $section)
        {
            ob_start();
            foreach ($section->children() as $data)
            {
                echo $data->getName().": ".$data."<br />";
            }
            $sectionData = ob_get_contents();
            ob_end_clean();

            array_push($xmlSections, (array(
            "sectionId" => 0,
            "sectionHash" => md5($sectionData),
            "sectionName" => $section->getName(),
            "sectionData" => $sectionData)));
        }
        return $xmlSections;
    }

}
?>
