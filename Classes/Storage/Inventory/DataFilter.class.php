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
    }

    /**
    * filter from pciid
    * @access private
    * @param string $pciid
    */
    private static function _filterFromPCIID($pciid)
    {
        $pciidArray = explode(":", $pciid);
        $vendor = $pciidArray[0];
        $device = $pciidArray[1];

        $pciFile = fopen(dirname(__FILE__)."/pci.ids","r");
        fclose($pciFile);
    }

    /**
    * filter from usbid
    * @access private
    * @param string $usbid
    */
    private static function _filterFromUSBID($usbid)
    {
        $usbFile = fopen(dirname(__FILE__)."/usb.ids","r");
        fclose($usbFile);
    }

    /**
    * filter from macaddr
    * @access private
    * @param string $macaddr
    */
    private static function _filterFromMACADDR($macaddr)
    {
        $ouiFile = fopen(dirname(__FILE__)."/oui.txt","r");
        fclose($ouiFile);
    }

}


?>