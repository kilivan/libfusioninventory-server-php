<?php

require_once dirname(__FILE__) . '/Classes/FusionLibServerTest.php';
require_once dirname(__FILE__) . '/Classes/Action/InventoryActionTest.php';
require_once dirname(__FILE__) . '/Classes/Storage/Inventory/DirectoryStorageInventoryTest.php';


/**
 * Static test suite.
 */
class FusionLibSuite extends PHPUnit_Framework_TestSuite
{
    /**
     * Constructs the test suite handler.
     */
    public function __construct()
    {
        $this->setName('FusionLibSuite');
        $this->addTestSuite('FusionLibServerTest');
        //Inventory
        $this->addTestSuite('InventoryActionTest');
        $this->addTestSuite('DirectoryStorageInventoryTest');

    }

    /**
     * Creates the suite.
     */
    public static function suite()
    {
        return new self();
    }
}

