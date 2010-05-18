<?php
/**
* Hooks Contract
*/
interface IExistingHooks
{
    public static function createMachine();
    public static function addSections($data);
    public static function removeSections($sectionsId);
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
        $dbh = new PDO('sqlite:'.dirname(__FILE__).'/../examples/MyWebSite/inventory.sqlite3');

        $stmt = $dbh->prepare("INSERT INTO machine (time) VALUES (:date)");
        $stmt->bindParam(':date', mktime());
        $stmt->execute();
        return $dbh->lastInsertId();
    }

    /**
    * add new sections to the machine in an application
    * @access public
    * @param array $data(externalId, sectionName, dataSection)
    * @return array $sectionsId
    */
    public static function addSections($data)
    {
        echo "sections created";
        $sectionsId = array();
        $dbh = new PDO('sqlite:'.dirname(__FILE__).'/../examples/MyWebSite/inventory.sqlite3');

        $dbh->beginTransaction();
        foreach($data as $section)
        {
            $stmt = $dbh->prepare("INSERT INTO section (sectionName, sectionData, idmachine) VALUES (:sectionName, :dataSection, :externalId)");
            $stmt->bindParam(':sectionName', $section['sectionName']);
            $stmt->bindParam(':dataSection', $section['dataSection']);
            $stmt->bindParam(':externalId', $section['externalId']);
            $stmt->execute();

            array_push($sectionsId,$dbh->lastInsertId());
        }
        $dbh->commit();

        //notify changes
        $idmachine = $section['externalId'];
        $res = $dbh->query("SELECT idchange FROM change WHERE idmachine=$idmachine");

        if($res->fetch()==false){
            $stmt = $dbh->prepare("INSERT INTO change (nbSectionsChanged, time, idmachine) VALUES (:nbSectionsChanged, :time, :externalId)");
            $stmt->bindParam(':nbSectionsChanged', count($sectionsId));
            $stmt->bindParam(':time', mktime());
            $stmt->bindParam(':externalId', $idmachine);
            $stmt->execute();
        } else {
            $stmt = $dbh->prepare("UPDATE change SET nbSectionsChanged=:nbSectionsChanged, time=:time WHERE idmachine=:externalId");
            $stmt->bindParam(':nbSectionsChanged', count($sectionsId));
            $stmt->bindParam(':time', mktime());
            $stmt->bindParam(':externalId', $idmachine);
            $stmt->execute();
        }

        return $sectionsId;
    }


    /**
    * remove a machine's section in an application
    * @access public
    * @param array $sectionsId
    */
    public static function removeSections($sectionsId)
    {
        echo "sections removed";
        $dbh = new PDO('sqlite:'.dirname(__FILE__).'/../examples/MyWebSite/inventory.sqlite3');
        $dbh->beginTransaction();
        foreach($sectionsId as $sectionId)
        {
            $stmt = $dbh->prepare("DELETE FROM section WHERE idsection = :sectionId");
            $stmt->bindParam(':sectionId', $sectionId);
            $stmt->execute();
        }
        $dbh->commit();
    }

}
?>
