<?php
/**
* Hooks Contract
*/
interface IExistingHooks
{
    public static function createMachine();
    public static function addSection($externalId, $sectionName, $dataSection);
    public static function removeSection($sectionId, $externalId);
}



/**
* User defines hooks in this class.
* There are three hooks to define: createMachine, addSection, removeSection
*/
class Hooks implements IExistingHooks
{
    /**
    * Disable instance
    * @access private
    *
    */
    private function __construct()
    {
    }


    /**
    * create a new machine in an application
    * @access public
    * @return int $externalId Id to match application data with the library
    */
    public static function createMachine()
    {
        echo "machine created";
        return uniqid();
    }

    /**
    * add a new section to the machine in an application
    * @access public
    * @param int $externalId
    * @param string $sectionName
    * @param array $dataSection
    * @return int $sectionId
    */
    public static function addSection($externalId, $sectionName, $dataSection)
    {
        echo "section created";
        return uniqid();
    }

    /**
    * remove a machine's section in an application
    * @access public
    * @param int $externalId
    * @param string $sectionName
    * @param array $dataSection
    */
    public static function removeSection($sectionId, $externalId)
    {
        echo "section removed";
    }

}
?>
