<?php
require_once 'PHPUnit/Framework.php';

require_once dirname(__FILE__) . '/../../../../Classes/Storage/Inventory/DirectoryStorageInventory.class.php';

/**
 * Test class for DirectoryStorageInventory.
 * Generated by PHPUnit on 2010-05-07 at 16:17:27.
 */
class DirectoryStorageInventoryTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var    DirectoryStorageInventory
     * @access protected
     */
    protected $object;
    private $_simpleXMLObj;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {

        $this->_simpleXMLObj = simplexml_load_file("data/aofr.ocs");

    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
    }

    /**
     * @test testIsMachineExist().
     */
    public function testIsMachineExist()
    {
        $this->assertTrue($this->_simpleXMLObj->CONTENT->BIOS->ASSETTAG == "MONTAG");
        $this->assertFalse(isset($this->_simpleXMLObj->CONTENT->BIOS->MOTHERBOARDSERIAL));
    }

    /**
     * @test testAddLibMachine().
     */
    public function testAddLibMachine()
    {

    }

    /**
     * @test testAddLibCriteriasMachine().
     */
    public function testAddLibCriteriasMachine()
    {


    }

    /**
     * @test testUpdateLibMachine().
     */
    public function testUpdateLibMachine()
    {

    }
}
?>
