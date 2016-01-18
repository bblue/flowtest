<?php

namespace bblue\ruby\Component\Core;

use bblue\ruby\Component\Logger\Psr3LoggerHandler;
use bblue\ruby\Component\Logger\tLoggerAware;
use Psr\Log\LoggerAwareInterface;

class SessionHandler implements LoggerAwareInterface
{
    use tLoggerAware;
    
    /**
     * Default session settings
     * @var array
     */
    private $_settings = array(
        'session_expire_time'		=> 60, // In minutes
        'use_only_cookies'			=> true,
        'prefix'                    => 'bblue',
        'restartOnExpired'          => true,
        'autostartOnConstruct'      => false 
    );
    
    private $_expired;
    
    /**
     * True if the session had to be restarted
     * @var bool
     */
    private $_autoRestarted;
    
    public function __construct(Psr3LoggerHandler $logger, array $aSettings = array())
    {
        $this->setLogger($logger);
        $this->configureSession($aSettings);
        
        if($this->_settings['autostartOnConstruct'] === true) {
            $this->start();
        }
    }
    
    
    private function configureSession(array $aSettings = array())
    {
        // Change the value of session expire time in php.ini
        ini_set('session.gc_maxlifetime', ((!empty($aSettings['session_expire_time'])) ? $aSettings['session_expire_time'] : $this->_settings['session_expire_time']) * 60 );
    
        // Make sure we only use cookie sessions
        ini_set('session.use_only_cookies', (!empty($aSettings['use_only_cookies'])) ? $aSettings['use_only_cookies'] : $this->_settings['use_only_cookies']);
    
        // Configure cookie time
        //ini_set('session.cookie_lifetime', ($this->_settings['session_expire_time'] * 60));
              
        return $this;
    }

    /**
     * Start the session. This should only be called once.
     * @throws \RuntimeException
     * @return boolean|\bblue\ruby\Component\Core\SessionHandler
     */
    public function start()
    {
        if (session_status() == PHP_SESSION_ACTIVE) {
            throw new \RuntimeException('Cannot start session. Session is already active');
        }

        // Initiate the session
        if (!session_start()) {
            throw new \RuntimeException('Session failed to start due to an unknown reason. Check PHP error logs');
        }

        if ($this->isNewSession()) {
            $this->logger->info('Session started with id ' . session_id());
            $this->set('created', true, true);
        } else {//@todo n� tror jeg ikke session sjekker om session er expired. I hvert fall ikke her, og det burde den vel?
            $this->logger->info('Session resumed with id ' . session_id());
        }

        $this->updateActivityTimestamp();

        return $this;

        // @todo Dette er fjernet pga problemer med iphone
        /*
         // Try to limit the damage from compromised session ID by saving a hash of the User-Agent
         if (!$this->getVar('user_agent')) {
         //Create the hash of the user-agent
         $this->setVar('user_agent', md5($_SERVER['HTTP_USER_AGENT']));
         } elseif ($this->getVar('user_agent') != md5($_SERVER['HTTP_USER_AGENT'])) {
         // If we end up here we might have a compromised account
         $this->endSession();
         throw new \Exception ('Compromised account. Session closed.');
         }
         */

        // update last activity time stamp
    }

    public function isNewSession()
    {
        return !($this->getSessionVar('created') === true);
    }

    private function getSessionVar($var)
    {
        return (isset($_SESSION[$this->_settings['prefix']][$var])) ? $_SESSION[$this->_settings['prefix']][$var] : null;
    }

    /**
     * Write a variable to the session
     * @param string $var    The variable name
     * @param mixed  $value  The value of the variable
     * @param bool   $forced Defaults to false. Set to true to bypass session expire check.
     * @return \bblue\ruby\Component\Core\SessionHandler
     */
    public function set($var, $value, $forced = false)
    {
        if ($this->hasExpired()) {
            $this->onExpired($var, $forced);
        }

        $_SESSION[$this->_settings['prefix']][$var] = $value;
        $this->logger->debug('Writing to session', [$var => $value]);

        return $this;
    }

    /**
     * Check if current session has soft-expired
     * @return bool
     */
    public function hasExpired()
    {
        if ($this->_expired === true) {
            return true;
        } elseif ($this->_expired === false) {
            return false;
        } else {
            if ($this->getRemainingSessionTime() <= 0) {
                $this->expire();
                return true;
            } else {
                $this->_expired = false;
                return false;
            }
        }
    }

    private function getRemainingSessionTime()
    {
        $lastActivityTimestamp = $this->getSessionVar('LAST_ACTIVITY');

        if ($lastActivityTimestamp === null) {
            $iRemainingTime = $this->_settings['session_expire_time'] * 60;
        } else {
            $iExpireTimestamp = time() - ($this->_settings['session_expire_time'] * 60); // 100 - 60 = 40
            $iRemainingTime = $lastActivityTimestamp - $iExpireTimestamp; // 80 - 40 = 40
        }

        if ($iRemainingTime >= 0) {
            $this->logger->info('Session will expire in ' . $iRemainingTime . ' seconds');
        } else {
            $this->logger->notice('Session expired ' . $iRemainingTime . ' seconds ago');
        }

        return $iRemainingTime;
    }

    public function expire()
    {
        $this->logger->notice('Session status explicitly set to expired');
        $this->_expired = true;
    }

    private function onExpired($var, $forced = false)
    {
        if ($forced === true) {
            $this->logger->debug('Read/write on expired session! (' . $var . ')');
            return;
        } else {
            if ($this->_settings['restartOnExpired'] === true) {
                $this->logger->info('Session has expired and will restart');
                $this->restart();
                $this->_autoRestarted = true;
                return;
            } else {
                throw new \RuntimeException('Session has soft-expired. All variables are locked');
            }
        }
    }

    // Function to end the session

    /**
     * End the current session, then start a new
     */
    public function restart()
    {
        $this->endSession();
        $this->start();
    }

    public function endSession()
    {
        setcookie(session_name(), '', time() - 3600);
        session_unset();
        session_destroy();
        $this->logger->info('Session ended');
    }

    private function updateActivityTimestamp()
    {
        $iTimestamp = time();
        $this->logger->debug('Updating session timestamp');
        $this->set('LAST_ACTIVITY', $iTimestamp, true);
    }

    public function __clone()
    {
        trigger_error('Clone is not allowed for ' . __CLASS__, E_USER_ERROR);
    }

    public function append($variablePath, $value, $forced = false)
    {
        if ($this->hasExpired()) {
            $this->onExpired($variablePath, $forced);
        }

        $temp = &$_SESSION[$this->_settings['prefix']];
        $exploded = explode('.', $variablePath);
        foreach ($exploded as $key) {
            $temp = &$temp[$key];
        }
        $temp[] = $value;
    }

    private function array_has_key($array, $key)
    {
        return array_key_exists($key, $array);
    }

    /**
     * Check if the session was restarted automatically
     * @return boolean
     */
    public function autoRestarted()
    {
        return $this->_autoRestarted;
    }

    public function delete($sVariable)
    {
        unset($_SESSION[$this->_settings['prefix']][$sVariable]);
        $this->logger->debug('Deleting from session', [$sVariable]);
        return $this;
    }

    public function getSessionId()
    {
        return session_id();
    }

    /**
     * Query the session for a variable
     * @param string $sVariable
     * @return Ambigous <NULL, mixed>
     */
    public function query($var, $forced = false)
    {
        if ($this->hasExpired()) {
            $this->onExpired($var, $forced);
        }

        // Trace who requested the session variable
        $this->logger->info('Query session for (' . $var . ')');

        return $this->getSessionVar($var);
    }
     
    // Disable PHP5's cloning method for session so people can't make copies of the session instance

    /**
     * Regenerate the session id
     * @throws \RuntimeException
     * @return \bblue\ruby\Component\Core\SessionHandler
     */
    public function regenerate()
    {
        if (session_regenerate_id()) {
            $this->logger->debug('Session id regenerated to prevent session fixation. New id is ' . session_id());
            return $this;
        } else {
            throw new \RuntimeException('Unable to regenerate session id');
        }
    }
}

final class SessionHasExpiredException extends \RuntimeException
{
    
}