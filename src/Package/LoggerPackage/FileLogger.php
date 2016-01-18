<?php
namespace bblue\ruby\Package\LoggerPackage;

use bblue\ruby\Component\Logger\iLogLevelThreshold;
use bblue\ruby\Component\Logger\LogLevel;
use bblue\ruby\Traits\PathNormalizer;
use DateTime;
use psr\Log\AbstractLogger;
use RuntimeException;

/**
 * Built on KLogger by Kenny Katzgrau <katzgrau@gmail.com>
 * 
 * @author Aleksander Lanes
 *
 */

final class FileLogger extends AbstractLogger implements iLogLevelThreshold
{
    use PathNormalizer;
    
    private $sClientAddress;
    
    /**
     * Path to the log file
     * @var string
     */
    private $logFilePath = null;

    /**
     * This holds the file handle for this instance's log file
     * @var resource
     */
    private $fileHandle = null;

    private $level;
    
    /**
     * Valid PHP date() format string for log timestamps
     * @var string
     */
    private $dateFormat = 'Y-m-d G:i:s.u';

    /**
     * Octal notation for default permissions of the log file
     * @var integer
     */
    private $defaultPermissions = 0777;
    
    /**
     * Current minimum logging threshold
     * @var integer
     */
    private $logLevelThreshold = LogLevel::DEBUG;

    /**
     * Class constructor
     * @param string     $logDirectory      File path to the logging directory
     * @param int|string $logLevelThreshold The LogLevel Threshold
     * @param string     $sClientAddress
     * @todo pass options as array
     */
    public function __construct($logDirectory, $logLevelThreshold = LogLevel::DEBUG, $sClientAddress = 'unidentified')
    {
        $this->setLogLevelThreshold($logLevelThreshold);
        $this->sClientAddress = $sClientAddress;
        
        $logDirectory = $this->normalizeDirectoryPath($logDirectory);
        
        if (!file_exists($logDirectory)) {
            if(is_writable($logDirectory)) {
                mkdir($logDirectory, $this->defaultPermissions, true);
            } else {
                throw new RuntimeException('The log directory ('.$logDirectory.') could not be written to. Check permissions.');
            }
        }

        $this->logFilePath = $logDirectory.DIRECTORY_SEPARATOR.'log_'.date('Y-m-d').'.log';
        
        if (file_exists($this->logFilePath) && !is_writable($this->logFilePath)) {
            throw new RuntimeException('The file could not be written to. Check that appropriate permissions have been set.');
        }
        
        $this->fileHandle = fopen($this->logFilePath, 'a');
        
        if (!$this->fileHandle) {
            throw new RuntimeException('The log file could not be opened. Check permissions.');
        }
    }

    /**
     * Sets the Log Level Threshold
     * @param $logLevelThreshold
     * @internal param string $dateFormat Valid format string for date()
     */
    public function setLogLevelThreshold($logLevelThreshold)
    {
        $this->logLevelThreshold = $logLevelThreshold;
    }

    /**
     * Class destructor
     */
    public function __destruct()
    {
        if ($this->fileHandle) {
        	$this->debug('Closing log file');
            fclose($this->fileHandle);
        }
    }
       
    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return void
     */
    public function log($level, $message, array $context = array())
    {
        if (LogLevel::belowLogLevelThreshold($level, $this->logLevelThreshold)) {
            return;
        }
        $this->setLevel($level); //@todo: Her kan jeg sjekke om level er valid (og det burde jeg)
        $message = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $this->formatMessage($message, $context)); // Remove empty lines
        $this->write($message);
    }

    private function setLevel($sLevel)
    {
        $this->level = strtoupper($sLevel);
    }

    /**
     * Formats the message for logging.
     *
     * @param  string $level   The Log Level of the message
     * @param  string $message The message to log
     * @param  array  $context The context
     * @return string
     */
    private function formatMessage($message, $context)
    {
        if (!empty($context)) {
            $message .= PHP_EOL.$this->indent($this->contextToString($context));
        }
        return $this->addPrefixToNewLine($message, $this->prefix()).PHP_EOL;
    }

    /**
     * Indents the given string with the given indent.
     * @param  string $string The string to indent
     * @param  string $indent What to use as the indent.
     * @return string
     */
    private function indent($string, $indent = '    ')
    {
        return $indent . str_replace("\n", "\n" . $indent, $string);
    }

    /**
     * Takes the given context and coverts it to a string.
     * @param  array $context The Context
     * @return string
     */
    private function contextToString($context)
    {
        $export = '';
        foreach ($context as $key => $value) {
            var_dump($value);
            $export .= "{$key}: ";
            $export .= preg_replace(array(
                '/=>\s+([a-zA-Z])/im',
                '/array\(\s+\)/im',
                '/^  |\G  /m',
            ), array(
                '=> $1',
                'array()',
                '    ',
            ), str_replace('array (', 'array(', var_export($value, true)));
            $export .= PHP_EOL;
        }
        return str_replace(array('\\\\', '\\\''), array('\\', '\''), rtrim($export));
    }

    private function addPrefixToNewLine($string, $prefix = '')
    {
        return $prefix . str_replace("\n", "\n" . $prefix, $string);
    }

    private function prefix()
    {
       return "[{$this->getTimestamp()}] [{$this->sClientAddress}] [{$this->getLevel()}] " . ((!empty($this->aTags)) ? implode(' ', $this->aTags) . ' ' : '');
    }

    /**
     * Gets the correctly formatted Date/Time for the log entry.
     * PHP DateTime is dump, and you have to resort to trickery to get microseconds
     * to work correctly, so here it is.
     * @return string
     */
    private function getTimestamp()
    {
        $originalTime = microtime(true);
        $micro = sprintf("%06d", ($originalTime - floor($originalTime)) * 1000000);
        $date = new DateTime(date('Y-m-d H:i:s.' . $micro, $originalTime));

        return $date->format($this->dateFormat);
    }

    private function getLevel()
    {
        return $this->level;
    }

    /**
     * Writes a line to the log without preceding a status or timestamp
     * @param $message
     * @internal param string $line Line to write to the log
     */
    private function write($message)
    {
        if (!is_null($this->fileHandle)) {
            if (fwrite($this->fileHandle, $message) === false) {
                throw new RuntimeException('The file could not be written to. Check that appropriate permissions have been set.');
            }
        }
        empty($this->aTags);
        $this->aTags = [];
    }

    /**
     * Sets the date format used by all instances
     * @param string $dateFormat Valid format string for date()
     */
    public function setDateFormat($dateFormat)
    {
        $this->dateFormat = $dateFormat;
    }
}