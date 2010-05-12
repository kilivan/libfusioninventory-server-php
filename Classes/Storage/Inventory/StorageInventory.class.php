<?php
/**
* We manage storage engines here
*/
class StorageInventoryFactory
{
    public static function createStorage($configs, $simpleXMLData)
    {
        $baseClass = 'StorageInventory';
        $targetClass = ucfirst($configs["storageEngine"]).$baseClass;
        if (file_exists ($path='Classes/Storage/Inventory/'.$targetClass.'.class.php'))
        {
            require_once $path;
            if (class_exists($targetClass) && is_subclass_of($targetClass, $baseClass))
            {
                return new $targetClass($configs, $simpleXMLData);
            } else {
                throw new Exception("The storage engine is not recognized.");
            }
        }
    }
}

abstract class StorageInventory
{
    protected  $_possibleCriterias;

    protected $_configs;

    abstract function isMachineExist();
    abstract function addLibMachine($internalId, $externalId);
    abstract function updateLibMachine($xmlSections, $internalId);

}

?>