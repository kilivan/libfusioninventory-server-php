<?php
require_once dirname(__FILE__) . '/Logger.class.php';
class MyException extends Exception
{
    function __construct($msg)
    {
        $log = new Logger(dirname(__FILE__) . '/../Logs');
        $log->notifyExceptionMessage($msg);
        parent::__construct($msg);
    }
}
?>