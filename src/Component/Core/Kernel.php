<?php

namespace bblue\ruby\Component\Core;

use bblue\ruby\Component\Autoloader\Psr4ClassLoader;
use bblue\ruby\Component\Config\ConfigAwareInterface;
use bblue\ruby\Component\Config\ConfigAwareTrait;
use bblue\ruby\Component\Container\Container;
use bblue\ruby\Component\Container\ContainerAwareInterface;
use bblue\ruby\Component\Container\ContainerAwareTrait;
use bblue\ruby\Component\Container\ObjectBuilder;
use bblue\ruby\Component\Container\ProxyBuilder;
use bblue\ruby\Component\EventDispatcher\EventDispatcherAwareInterface;
use bblue\ruby\Component\EventDispatcher\EventDispatcherAwareTrait;
use bblue\ruby\Component\Logger\Psr3LoggerHandler;
use bblue\ruby\Component\Logger\tLoggerHelper;
use bblue\ruby\Component\Package\iPackage;
use bblue\ruby\Component\Request\iInternalRequest;
use bblue\ruby\Component\Request\RequestFactory;
use bblue\ruby\Component\Request\RequestHandler;
use bblue\ruby\Component\Response\iResponse;
use bblue\ruby\Component\Router\Router;
use psr\Log\LoggerAwareInterface;

abstract class Kernel implements LoggerAwareInterface, ContainerAwareInterface, EventDispatcherAwareInterface, ConfigAwareInterface, KernelEvent
{
    use ContainerAwareTrait;
    use tLoggerHelper;
    use ConfigAwareTrait;
    use EventDispatcherAwareTrait;
    
	/**
     * Minimum version of PHP required to run the script
     * @var string
	 */
    const REQUIRED_PHP_VERSION = '7.0.0';
	/**
     * Time limit for script execution until a logger event is triggered
     * @var int
	 */
    const EXECUTION_WARNING_TIME_LIMIT = 350;
	/**
     * Variable to hold the start time of the kernel
	 * @var integer
	 */
    protected $iStartTime;
	/**
     * Variable to hold the end time of the kernel
     * @var integer
	 */
    protected $iEndTime;
	/**
     * @var Psr4ClassLoader
	 */
    private $loader;

    /**
     * Constructor for Kernel
     * @param         $config
     * @param boolean $bDebug
     * @throws \Exception
     * @internal param string $sEnvironment Environment of system (prod or dev)
     */
	public function __construct($config, $bDebug = false)
	{
        if(!defined('VENDOR_PATH')) {
            throw new \Exception('Vendor path is unset');
        }
	    // Save the configuration instance
	    $this->config = $config;
	     
	    // Prepare the logger mechanism @todo: Vurdere om ikke denne skal flyttes til en 'package'. Dersom det flyttes til en package må jeg ta en ny vurdering på hvordan jeg enabler logging. i.e. hva vis jeg ikke har valgt logging package, men at jeg har enabled logging?
	    $logger = new Psr3LoggerHandler($this->config->sLogLevelThreshold);

	    // Inject the logger handler into the config file //@todo virker veldig merkelig � gj�re dette
	    $this->config->setLogger($logger);
	    
	    // Save debug parameter
	    $this->config->setDebugMode($bDebug);
	    
        // Save logger instance to Kernel
        $this->setLogger($logger);
        $this->setLoggerPrefix('kernel');
	}
	
	public function __destruct()
	{
	    $this->logger->debug('Closing connection');
	}

    /**
     * Method to initialize the kernel boot sequence
     * Boot() will set error reporting, confirm system is valid and set up the service locator
     * @param iInternalRequest $request
     * @throws \Exception
     * @todo Flytte request over i handle, det f�les feil � ha den her
     * @todo Dette burde v�re bootstrap funksjonen til kernel, og dispatcher og slikt burde muligens flyttes hit fra handle()
     */
	public function boot(iInternalRequest $request)
	{
		try {
		    $this->logger->info('****** Initializing kernel booting sequence *******');
		    $this->initializeErrorReporting();
		    $this->confirmPhpVersion();

		    $this->logger->info('Client connected from ' . $request->getClientAddress());
		    
		    $this->initializeContainer();

		    // @todo alle disse er vel ting som burde g� i appkernel, som deretter overstyres eller videre utbedres med packages.
		    // @todo muligens s� kan noen av disse g� inn i constructor til enkelte components, slik som alle tingene jeg har med auth.
		    $this->container
                ->register($this->loader, 'classLoader')
                ->register(new RequestHandler($this->container, new RequestFactory()), 'requestHandler')
                ->register($request, 'request')
                ->register('bblue\ruby\Component\EventDispatcher\EventDispatcher', 'eventDispatcher')
                ->register('bblue\ruby\Component\Core\SessionHandler', 'session')//@todo Denne st�tter ikke cli, jeg m� nok ha en egen boot() eller tilsvarende i kernel for CLI - slik at jeg kan sette paramtere korrekt
                    ->addConstructorCallback('start')//@todo Hva gj�r jeg dersom jeg �nsker en annen
                // session handler? Denne m�
                // kunne extendes p� et vis. Tror jeg hadde dette probelmet med andre ting, mne husker ikke hvordan/om jeg l�ste det
                ->register('bblue\ruby\Component\Flasher\SessionFlasherStorage', 'flashstorage')
                ->register('bblue\ruby\Component\Flasher\Flasher', 'flash')//@todo: Vurdere � flytte denne til en package
                    ->addConstructorCallback('setStorageMechanism', ['@flashstorage'])
                ->register('bblue\ruby\Component\Core\UserProviderStack', 'userProviderStack')//denne er generisk og trenger ikke � flyttes
                ->register('bblue\ruby\Component\Security\AuthStorage', 'authStorage')
                    ->addConstructorParameter('@userProviderStack', 2)
                ->register('bblue\ruby\Component\Security\Auth', 'auth')// tror ikke auth burde flyttes. Den er vel
                // en del av core?
                    ->addConstructorParameters(['@authStorage', $request]);
            $this->setEventDispatcher($this->container->get('eventDispatcher'));
		    $this->initializePackages();
		} catch (\Exception $e) {
		    throw $e;
		}
	}

    /**
     * Configure PHPs built in error reporting based on the configuration parameters of the application
     */
    protected function initializeErrorReporting()
    {
        // sanitize the error print option
        if ($this->config->bDebug && $this->config->bPrintErrorMessages) {
            $this->config->bPrintErrorMessages = $this->sanitizeErrorPrintParameter();
        }

        // check if we should enable error reporting
        if ($this->config->bDebug && $this->config->bPrintErrorMessages) {
            $this->enableErrorReporting();
        } else {
            $this->disableErrorReporting();
        }
    }

    /**
     * Make sure that the client is not attempting to circumvent the error print whitelist check
     * @return boolean Returns the sanitized boolean value of bPrintErrorMessages
     * @todo H�rer hjemme i request objektet
     * @todo M� lese om vi er i CLI mode eller ikke
     */
    protected function sanitizeErrorPrintParameter()
    {
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $this->logger->warning('$_SERVER[\'HTTP_CLIENT_IP\' is set. Error printing disabled for security reasons.');
            return false;
        }

        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $this->logger->warning('$_SERVER[\'HTTP_X_FORWARDED_FOR\' is set. Error printing disabled for security reasons.');
            return false;
        }

        if (!isset($_SERVER['REMOTE_ADDR']) || !in_array($_SERVER['REMOTE_ADDR'], $this->config->aDebugWhitelistIPs)) {
            $this->logger->notice('Client IP address is not in whitelist. Error printing disabled for security reasons.');
            return false;
        }

        return $this->config->bPrintErrorMessages;
    }

    /**
     * Method to enable PHP error reporting
     * @return void;
     * @todo H�rer hjemme i config
     * @todo M� st�tte CLI (gjerne html tags, pretty print)
     */
    private function enableErrorReporting()
    {
        // Show errors
        ini_set('display_errors', 1);
        ini_set('html_errors', 1);
        error_reporting(E_ALL);

        // Make errors pretty
        ini_set('error_prepend_string', '<pre>');
        ini_set('error_append_string', '</pre>');

        $this->logger->warning('Error reporting to browser enabled');
    }

    /**
     * Method to disable PHP error reporting
     * @return void;
     * @todo H�rer hjemme i config
     */
    private function disableErrorReporting()
    {
        // configure error reporting
        error_reporting(0);
        ini_set('display_errors', 'Off');

        $this->logger->debug('Error reporting to browser disabled');
    }

    /**
     * Confirms system can handle the application
     * @throws RuntimeExcpetion
     */
    protected function confirmPhpVersion()
    {
        $this->logger->debug('System running on PHP v' . PHP_VERSION);

        if ($this->config->bDebug) {
            if ($this->supportedSystem() !== true) {
                throw new \RuntimeException('Your host needs to use PHP' . self::REQUIRED_PHP_VERSION . 'or higher to run this version of bblue. You are running ' . PHP_VERSION);
            }
        }
    }

    /**
     * Confirm host runs a supported PHP version
     * @return mixed Triggers error on non-supported host, boolen true otherwise
     * @todo H�rer hjemme i config
     */
    private function supportedSystem()
    {
        return (!version_compare(PHP_VERSION, self::REQUIRED_PHP_VERSION, '<'));
    }

    protected function initializeContainer()
    {
        $this->logger->debug('Initializing master container');
        $this->setContainer(new Container($this->config, $this->logger, new ProxyBuilder(), new ObjectBuilder()));
    }
	    
	/**
     * Initializes all registered packages
     * @return void
     * @todo: Dette kunne nesten v�rt en egen klasse ala PackageLoader
     */
    private function initializePackages()
    {
        $this->logExecutionTime($_SERVER['REQUEST_TIME_FLOAT']);
        // Register packages in container and obtain a reference to each
        $packages = $this->registerPackages();
        foreach ($packages as $alias => $fqcn) {
            $alias = 'package.' . $alias;
            $this->container->register($fqcn, $alias)->addConstructorCallback([$this, 'bootPackage'], ['@' . $alias]);
        }

        //@todo This causes double boots in case of an error
        // Load the boot file of each package
        foreach ($packages as $alias => $packageReference) {
            $alias = 'package.' . $alias;
            $this->bootPackage($this->container->get($alias));
        }
    }

    public function bootPackage(iPackage $package)
    {
        if (!$package->isBooted()) {
            $this->logger->debug('Booting ' . $package->getName());
            try {
                if (!$package->bootPackage()) {
                    throw new \Exception('Package->boot() returned false (' . $package->getName() . ')');
                }
            } catch (\Exception $e) {
                $this->logger->critical('Boot failure in ' . $package->getName() . ' package', ['exception' => $e]);
                throw $e;
            }
            $this->logger->debug($package->getName() . ' booted');
        }
    }

    /**
     * Handle input/output request by passing controller dispatcher and router to the front controller. Returns a
     * response object.
     * @param Request $request
     * @return Response
     * @todo Vurdere om alle new statements burde handles av container. Det gj�r dependency injection lettere
     */
    public function handle(iInternalRequest $request): iResponse
    {
        $request->setRequestStartTimestamp($request->_server('REQUEST_TIME_FLOAT'));
        $this->eventDispatcher->dispatch(KernelEvent::REQUEST, ['request' => $request]);

        // Initialize the router mechanism
        $router = new Router($this->eventDispatcher, $this->logger, $this->registerRoutes());

        $this->container->register($request, 'request');
        $this->container->register($router, 'router');

        // Dispatch the router event for extensions
        $this->eventDispatcher->dispatch(KernelEvent::ROUTER, ['router' => $router]);
        // Set up the dispatch method to handle controller dispatch
        $dispatcher = new ModuleDispatcher();
        $this->container->register($dispatcher, 'dispatcher');

        $this->eventDispatcher->dispatch(KernelEvent::DISPATCHER, ['dispatcher' => $dispatcher]);

        $response = $this->container->get('requestHandler')->handle($request);

        $this->logger->debug('Returning response to client');
        $this->logExecutionTime($request->getExecutionTime());

        return $response;
    }

    private function logExecutionTime($time)
    {
        $sLogLevel = ($time > self::EXECUTION_WARNING_TIME_LIMIT) ? 'warning' : 'info';
        $this->logger->$sLogLevel('Execution time: ' . $time . ' ms');
    }

    public function setClassLoader(Psr4ClassLoader $loader)
    {
        $this->loader = $loader;
    }
}