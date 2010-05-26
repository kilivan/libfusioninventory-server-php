<?php

/** A logging class.
* Examples:
* $log = new Logger("logFilePath");
* $log->notifyDebugMessage("machine $internalId created");
* $log->notifyExceptionMessage("Put your application in the user/applications directory");
*/

class Logger
{
    const EXCEPT = 1;
    const DEBUG = 2;

    private $_filePath;

    public function __construct($filePath)
    {

        $this->_filePath = $filePath;

        if(!file_exists($this->_filePath))
        {
            $new_handle = fopen($filePath, "x+");
            fclose($new_handle);
        }


        if (!is_writable($filePath))
        {
            throw new Exception("$filePath isn't writable. Check permissions.");
        }

    }


    public function notifyDebugMessage($line)
    {
        $this->_log($line, Logger::DEBUG);
    }


    public function notifyExceptionMessage($line)
    {
        $this->_log($line, Logger::EXCEPT);
    }


    private function _log($line, $messageType)
    {
        $status = $this->_getStatus($messageType);
        $iniContent = file_get_contents($this->_filePath);
        file_put_contents($this->_filePath, "$status $line \n$iniContent");
    }


    private function _getStatus($messageType)
    {
        $time = date("D, d M Y H:i:s O");

        switch($messageType)
        {
            case Logger::DEBUG:
                $status = "$time - DEBUG -->";
            break;
            case Logger::EXCEPT:
                $status = "$time - EXCEPTION -->";
            break;
            default:
                $status = "$time - LOG   -->";
            break;
        }
        return $status;
    }

}


?>