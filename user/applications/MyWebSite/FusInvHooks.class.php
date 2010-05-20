<?php
require_once "Logger.class.php";

/**
* Hooks Contract
*/
interface IExistingHooks
{
    public static function createMachine();
    public static function addSections($data, $idmachine);
    public static function removeSections($idsections, $idmachine);
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
        $dbh = new PDO('sqlite:'.dirname(__FILE__).'/inventory.sqlite3');
        $stmt = $dbh->prepare("INSERT INTO machine (time) VALUES (:date)");

        $stmt->bindParam(':date', mktime());
        $stmt->execute();

        $idmachine = $dbh->lastInsertId();

        //changes log
        $logger = new Logger($dbh);
        $logger->notifyAddedMachine($idmachine);

        return $idmachine;
    }

    /**
    * add new sections to the machine in an application
    * @access public
    * @param array $data(sectionName, dataSection)
    * @param int $idmachine
    * @return array $sectionsId
    */
    public static function addSections($data, $idmachine)
    {
        $sectionsId = array();
        $dbh = new PDO('sqlite:'.dirname(__FILE__).'/inventory.sqlite3');

        $dbh->beginTransaction();

        foreach($data as $section)
        {
            $stmt = $dbh->prepare("INSERT INTO section (sectionName, sectionData, idmachine) VALUES (:sectionName, :dataSection, :externalId)");
            $stmt->bindParam(':sectionName', $section['sectionName']);
            $stmt->bindParam(':dataSection', $section['dataSection']);
            $stmt->bindParam(':externalId', $idmachine);
            $stmt->execute();

            array_push($sectionsId,$dbh->lastInsertId());
        }

        $dbh->commit();

        //changes log
        $logger = new Logger($dbh);
        $logger->notifyAddedSection($idmachine, count($sectionsId));

        return $sectionsId;
    }


    /**
    * remove a machine's section in an application
    * @access public
    * @param array $idsections
    * @param int $idmachine
    */
    public static function removeSections($idsections, $idmachine)
    {
        $dbh = new PDO('sqlite:'.dirname(__FILE__).'/inventory.sqlite3');
        $dbh->beginTransaction();
        foreach($idsections as $idsection)
        {
            $stmt = $dbh->prepare("DELETE FROM section WHERE idsection = :idsection");
            $stmt->bindParam(':idsection', $idsection);
            $stmt->execute();
        }
        $dbh->commit();

        //changes log
        $logger = new Logger($dbh);
        $logger->notifyRemovedSection($idmachine, count($idsections));
    }

}

?>
