<?php

namespace bblue\ruby\Component\Core;

use bblue\ruby\Component\Logger\Psr3LoggerHandler;

use bblue\ruby\Component\Container\Container;
use bblue\ruby\Component\Container\ContainerAwareInterface;
use bblue\ruby\Component\Config\ConfigAwareInterface;
use bblue\ruby\Component\EventDispatcher\EventDispatcherAwareInterface;
use bblue\ruby\Component\EventDispatcher\EventDispatcher;
use psr\Log\LoggerAwareInterface;
use bblue\ruby\Component\Router\Router;
use bblue\ruby\Component\Autoloader\Psr4ClassLoader;
use bblue\ruby\Component\Flasher\SessionFlasherStorage;
use bblue\ruby\Component\Flasher\Flasher;
use bblue\ruby\Component\Container\Reference;
use bblue\ruby\Component\Package\AbstractPackage;

abstract class Kernel implements LoggerAwareInterface, ContainerAwareInterface, EventDispatcherAwareInterface, ConfigAwareInterface, KernelEvent
{
    use \bblue\ruby\Component\Logger\LoggerAwareTrait;
    use \bblue\ruby\Component\Container\ContainerAwareTrait;
    use \bblue\ruby\Component\Config\ConfigAwareTrait;
    use \bblue\ruby\Component\EventDispatcher\EventDispatcherAwareTrait;
    
	/**
	 * @var Psr4ClassLoader
	 */
	private $loader;
	
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
	 * Minimum version of PHP required to run the script
	 * @var string
	 */
	const REQUIRED_PHP_VERSION = '5.6.0';

	/**
	 * Time limit for script execution until a logger event is triggered
	 * @var int
	 */
	const EXECUTION_WARNING_TIME_LIMIT = 350;

	/**
	 * Constructor for Kernel
	 *
	 * @param string $sEnvironment Environment of system (prod or dev)
	 * @param boolean $bDebug
	 *
	 * @return void
	 */
	public function __construct($config, $bDebug = false)
	{
	    // Save the configuration instance
	    $this->config = $config;
	     
	    // Prepare the logger mechanism @todo: Vurdere om ikke denne skal flyttes til en 'package'. Dersom det flyttes til en package mÃ¥ jeg ta en ny vurdering pÃ¥ hvordan jeg enabler logging. i.e. hva vis jeg ikke har valgt logging package, men at jeg har enabled logging?
	    $logger = new Psr3LoggerHandler($this->config->sLogLevelThreshold);

	    // Inject the logger handler into the config file //@todo virker veldig merkelig å gjøre dette
	    $this->config->setLogger($logger);
	    
	    // Save debug parameter
	    $this->config->setDebugMode($bDebug);
	    
        // Save logger instance to Kernel
        $this->setLogger($logger);
	}
	
	public function __destruct()
	{
	    $this->logger->debug('Closing connection');
	}
	
	/**
	 * Method to initialize the kernel boot sequence
	 * 
	 * Boot() will set error reporting, confirm system is valid and set up the service locator
	 * @return void;
	 * @todo Flytte request over i handle, det føles feil å ha den her
	 * @todo Dette burde være bootstrap funksjonen til kernel, og dispatcher og slikt burde muligens flyttes hit fra handle()
	 */
	public function boot(AbstractRequest $request)
	{
		try {
		    $this->logger->info('****** Initializing kernel booting sequence *******');
		    
		    $this->initializeErrorReporting();
		    $this->confirmPhpVersion();

		    $this->logger->info('Client connected from ' . $request->getClientAddress());
		    
		    $this->initializeContainer();
		    
		    // @todo alle disse er vel ting som burde gå i appkernel, som deretter overstyres eller videre utbedres med packages.
		    // @todo muligens så kan noen av disse gå inn i constructor til enkelte components, slik som alle tingene jeg har med auth.
		    $this->container
		      ->set($this->loader, 'classLoader')
		      ->set($request, 'request')
		      ->register('eventDispatcher', 'bblue\ruby\Component\EventDispatcher\EventDispatcher')
		      ->register('session', 'bblue\ruby\Component\Core\SessionHandler') //@todo Denne støtter ikke cli, jeg må nok ha en egen boot() eller tilsvarende i kernel for CLI - slik at jeg kan sette paramtere korrekt
		          ->addMethodCall('start')		    //@todo Hva gjør jeg dersom jeg ønsker en annen session handler? Denne må kunne extendes på et vis. Tror jeg hadde dette probelmet med andre ting, mne husker ikke hvordan/om jeg løste det
		      ->register('flashstorage', 'bblue\ruby\Component\Flasher\SessionFlasherStorage')
		      ->register('flash', 'bblue\ruby\Component\Flasher\Flasher')//@todo: Vurdere å flytte denne til en package
		          ->addConstructorArgument('@logger', 1)
		          ->addMethodCall('setStorageMechanism', ['@flashstorage'])
		      ->register('userProviderStack', 'bblue\ruby\Component\Core\UserProviderStack') //denne er generisk og trenger ikke å flyttes
		      ->register('authStorage', 'bblue\ruby\Component\Security\AuthStorage')
		          ->addConstructorArgument('@userProviderStack', 2)
		      ->register('auth', 'bblue\ruby\Component\Security\Auth') // tror ikke auth burde flyttes. Den er vel en del av core?
		          ->addConstructorArguments(['@authStorage', $request]);

            $this->setEventDispatcher($this->container->get('eventDispatcher'));
		    $this->initializePackages();
		} catch (\Exception $e) {
		    if($request->isCommandLineInterface()) {
		        echo $e;
		    }
		    throw $e;
		}
	}
	
	/**
	 * Initializes all registered packages
	 * @return void
	 * @todo: Dette kunne nesten vært en egen klasse ala PackageLoader
	 */
	private function initializePackages()
	{
	    // Register packages in container and obtain a reference to each
	    $packages = $this->registerPackages();
	    foreach($packages as $alias => &$packageBootFile) {
	    	$packageAlias = 'package.'.$alias;
	        $packageBootFile = $this->container->register($packageAlias, $packageBootFile)->getAsReference();
	        $this->container->addMethodCall([$this, 'bootPackage'], [$packageBootFile]);
	    }

	    // Load the boot file of each package
	    foreach($packages as $packageReference) {
	    	if($packageReference instanceof Reference) {
	    		$this->bootPackage($packageReference);
	    	}
        }

	}
	
	public function bootPackage($package)
	{
        if($package instanceof Reference) {
        	$package = $this->container->get($package);
        }
        if(!$package->isBooted()) {
	        $this->logger->debug('Booting ' . $package->getName());
	    	try {
	    		$this->container->injectDependencies($package);
	         	if(!$package->bootPackage()) {
	         		throw new \Exception('Package->boot() returned false ('.$package->getName().')');
	         	}
	        } catch (\Exception $e) {
	            $this->logger->critical('Boot failure in ' . $package->getName() . ' package', ['exception'=>$e]);
	        }

	        $this->logger->debug($package->getName() . ' booted');
        }
	}

	/**
	 * Confirms system can handle the application
	 * 
	 * @throws RuntimeExcpetion
	 */
	protected function confirmPhpVersion()
	{
	    $this->logger->debug('System running on PHP v' . PHP_VERSION);
	    
	    if($this->config->bDebug) {
	        if ($this->supportedSystem() !== true) {
	            throw new \RuntimeException('Your host needs to use PHP'.self::REQUIRED_PHP_VERSION.'or higher to run this version of bblue. You are running ' . PHP_VERSION);
	        }
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
	
	protected function initializeContainer()
	{
	    $this->logger->debug('Initializing master container');
	    $this->setContainer(new Container($this->config, $this->logger));
	    $this->container->set($this->container, 'container');
	}
	
	/**
	 * Make sure that the client is not attempting to circumvent the error print whitelist check 
	 * 
	 * @return boolean Returns the sanitized boolean value of bPrintErrorMessages
	 * @todo Hører hjemme i request objektet
	 * @todo Må lese om vi er i CLI mode eller ikke
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
	
	public function setClassLoader(Psr4ClassLoader $loader)
	{
	    $this->loader = $loader;
	}
	    
	/**
	 * Handle input/output request by passing controller dispatcher and router to the front controller. Returns a response object.
	 * 
	 * @param Request $request
	 * @return Response
	 * @todo Vurdere om alle new statements burde handles av container. Det gjør dependency injection lettere
	 */
    public function handle(AbstractRequest $request)
    {	
        $this->iStartTime = $request->_server('REQUEST_TIME_FLOAT');
        
    	$this->eventDispatcher->dispatch(KernelEvent::REQUEST, ['request' => $request]);

        // Initialize the router mechanism
        $router = new Router($this->eventDispatcher, $this->logger, $this->registerRoutes());
        
        $this->container->set($request, 'request');
        $this->container->set($router, 'router');
        
        // Dispatch the router event for extensions
        $this->eventDispatcher->dispatch(KernelEvent::ROUTER, ['router' => $router]);
        
        // Set up the dispatch method to handle controller dispatch
        $dispatcher = new ModuleDispatcher();
        $this->container->set($dispatcher, 'dispatcher');
        
        $this->eventDispatcher->dispatch(KernelEvent::DISPATCHER, ['dispatcher' => $dispatcher]);
        
        // Initialize the front controller to handle request and preprocessing 
        $frontController = new FrontController($dispatcher, $router, $this->eventDispatcher, $this->logger, $this->container->get('flash'), $this->container);
        $response = $frontController->handle($request);

        $this->logger->debug('Returning response to client');
        
        $this->iEndTime = microtime(true);
        $iExecutionTime = $this->iEndTime - $this->iStartTime;
        $iExecutionTime = round(($iExecutionTime*1000), 0);
        $sLogLevel = ($iExecutionTime > self::EXECUTION_WARNING_TIME_LIMIT) ? 'warning' : 'info'; 
        $this->logger->$sLogLevel('Execution time: ' . $iExecutionTime . ' ms');
        
        return $response;
    }
    
	/**
	 * Confirm host runs a supported PHP version
	 * @return mixed Triggers error on non-supported host, boolen true otherwise
	 * @todo Hører hjemme i config 
	 */
	private function supportedSystem()
	{
	    return (!version_compare(PHP_VERSION, self::REQUIRED_PHP_VERSION, '<'));
	}
	
	/**
	 * Method to enable PHP error reporting
	 * @return void;
	 * @todo Hører hjemme i config 
	 * @todo Må støtte CLI (gjerne html tags, pretty print)
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
	 * @todo Hører hjemme i config 
	 */
	private function disableErrorReporting()
	{
	    // configure error reporting
	    error_reporting(0);
	    ini_set('display_errors', 'Off');
	    
	    $this->logger->debug('Error reporting to browser disabled');
	}
}