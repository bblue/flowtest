<?php

namespace bblue\ruby\Package\DatabasePackage;

use bblue\ruby\Component\Autoloader\Psr4ClassLoader;
use bblue\ruby\Component\Core\DispatcherEvent;
use bblue\ruby\Component\Core\FrontControllerEvent;
use bblue\ruby\Component\EventDispatcher\Event;
use bblue\ruby\Component\Package\AbstractPackage;
use bblue\ruby\Package\HelloWorldPackage\Entities\Product;
use Doctrine\Common\EventManager;
use Doctrine\Common\Proxy\AbstractProxyFactory;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;

final class Doctrine extends AbstractPackage
{
    /**
     * @var AnnotationDriver
     */
    private $driverImpl;
    private $iFlushCounter = 0;
    private $entityManager;
    
    private $options = array(
        'iMaxBatchSize'     => 20
    );

	public function addPaths(array $aPaths = [])
	{
		$this->driverImpl->addPaths($aPaths);
	}

	public function boot()
	{
		$this->initializeDoctrine();

		// Add listener to track flush requirements
		$this->eventDispatcher->addListener(DoctrineEvent::SCHEDULE_FLUSH, function(Event $event){
		    $this->iFlushCounter++;
		    $this->logger->info('Doctrine flush scheduled. Flush count is now ' . $this->iFlushCounter);

		    $aConfig = isset($this->config->doctrine) ? $this->config->doctrine : array();
		    $iMaxBatchSize = !empty($aConfig['iMaxBatchSize']) ? $aConfig['iMaxBatchSize'] : $this->options['iMaxBatchSize'];

		    if($this->entityManager->getUnitOfWork()->size() >= $iMaxBatchSize) {
		        $this->logger->info('Doctrine unit of work has exceeded maximum batch size of ' . $iMaxBatchSize . ' and a flush is being forced.');
                $this->entityManager->flush();
		    }
		});

        $this->eventDispatcher->addListener(DispatcherEvent::CONTROLLER_SUCCESS, function(Event $event){
            if($this->iFlushCounter > 0) {
                    $this->logger->info('Flusing doctrine');
                    $this->entityManager->flush();
                    $this->entityManager->clear(); // Detaches all objects from Doctrine! @todo Hvorfor gj�re jeg dette? Skaper ikke dette bare masse probelmer??
            }
        });

        $this->eventDispatcher->addListener(FrontControllerEvent::CAUGHT_EXCEPTION, function(Event $event){
            $iUnitOfWorkSize = $this->entityManager->getUnitOfWork()->size();
            if($iUnitOfWorkSize > 0) {
                $this->logger->info('Exception identified. Clearing doctrine unit of work for a total of ' . $iUnitOfWorkSize . ' managed entries.');
				$this->entityManager->clear();
			}
        });
        return true;
	}
	
	/**
	 * @todo bygge inn st�tte for event listener/subscriper p� dette niv�et
	 */
	public function initializeDoctrine()
	{
		$config = new Configuration();

		if (!$this->config->isDevMode()) {
	        $cache = new \Doctrine\Common\Cache\ArrayCache;
	        $config->setAutoGenerateProxyClasses(AbstractProxyFactory::AUTOGENERATE_ALWAYS);
	    } else {
	        $cache = new \Doctrine\Common\Cache\ArrayCache();
	        $config->setAutoGenerateProxyClasses(AbstractProxyFactory::AUTOGENERATE_NEVER);
	    }

		// Set up caches
	    $config->setMetadataCacheImpl($cache);
		$config->setQueryCacheImpl($cache);
		$driverImpl = $config->newDefaultAnnotationDriver();
		/** @var AnnotationDriver $driverImpl */
	    $config->setMetadataDriverImpl($driverImpl);

		// Set up the logger
	    $config->setSQLLogger(new DoctrineLogger($this->logger));

		// Set up the event manager
	    $em = new EventManager();
	    $em->addEventListener(Events::onFlush, function () {
			$this->eventDispatcher->dispatch(DoctrineEvent::FLUSHED);
		});

		$sProxyCachePath = '../cache/doctrine_proxy_cache';
		/** @var Psr4ClassLoader $loader */
	    $loader = $this->container->get('classLoader');
	    $loader->addNamespace('Proxies', $sProxyCachePath);
	    $config->setProxyNamespace('Proxies');
	    $config->setProxyDir($sProxyCachePath);

		// the connection configuration
		$dbParams = array(
			'driver'     => $this->config->db_driver,
			'user'       => $this->config->db_user,
			'password'   => $this->config->db_password,
		    'host'       => $this->config->db_host,
			'dbname'     => $this->config->db_name,
		);

		$this->entityManager = EntityManager::create($dbParams, $config);

		// Store the entity mananger to the container
		$this->container->register($this->entityManager, 'entityManager');
	}
}