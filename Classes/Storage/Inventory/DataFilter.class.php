<?php
/**
* Filter some input data :
* - if PCIID or USBID (CONTROLLERS) are avalaible, retrieve manufacturer/device from the mapping files.
* - retrieve the network interface manufacturer from oui database
*/
class DataFilter
{

    /**
    * get device from pciid
    * @access public
    * @param string $pciid
    */
    public static function filter($section)
    {
        switch($section->getName())
        {
            case 'CONTROLLERS':
                foreach ($section->children() as $data)
                {
                    if($data->getName() == 'PCIID' and $data != '')
                    {
                        self::_filterFromPCIID($data);
                    }
                }
            break;

            case 'NETWORKS':
                foreach ($section->children() as $data)
                {
                    if($data->getName() == 'MACADDR' and $data != '')
                    {
                        self::_filterFromMACADDR($data);
                    }
                }
            break;

            default:
            break;
        }
/*
        foreach($this->_datasToFilterList as $dataToFilter)
        {
            if($section->getName == $dataToFilter['section'])
            {
                switch($dataToFilter['section'])
            }
        }
        */
    }

    /**
    * filter from pciid
    * @access private
    * @param string $pciid
    */
    private static function _filterFromPCIID($pciid)
    {
        echo "X";
    }

    private static function _filterFromUSBID($usbid)
    {
    }

    private static function _filterFromMACADDR($macaddr)
    {
        echo "O";
    }

}


?>