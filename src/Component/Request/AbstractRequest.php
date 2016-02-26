<?php

namespace bblue\ruby\Component\Request;

abstract class AbstractRequest implements iRequest
{   
    private $aGetArray = array();
    private $aPostArray = array();
    private $aCookieArray = array();
    private $aServerArray = array();
    private $aFilesArray = array();

    public function __construct(array $aGetArray = array(), array $aPostArray = array(), array $aCookieArray = array(), array $aFilesArray = array(), array $aServerArray = array()) {
        $this->aGetArray = $aGetArray;
        $this->aPostArray = $aPostArray;
        $this->aCookieArray = $aCookieArray;
        $this->aServerArray = $aServerArray;
        $this->aFilesArray = $aFilesArray;
    } 
    
    static function isCommandLineInterface()
    {
        return (php_sapi_name() === 'cli');
    }
    
    /**
     * @todo Gj�re denne CLI-vennlig
     */
    public function getTargetUrl()
    {
        return $this->_post('target_url');;
    }

    abstract public function getClientAddress();
    abstract public function getUserAgent();
}