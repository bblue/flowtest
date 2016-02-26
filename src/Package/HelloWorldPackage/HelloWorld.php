<?php

namespace bblue\ruby\Package\HelloWorldPackage;

use bblue\ruby\Component\Core\KernelEvent;
use bblue\ruby\Component\EventDispatcher\Event;
use bblue\ruby\Component\Package\AbstractPackage;
use bblue\ruby\Traits\Interpolate;
use Doctrine\ORM\EntityManager;

final class HelloWorld extends AbstractPackage
{
    use Interpolate;

	/**
	 * @var EntityManager $entityManager
	 * @return bool
	 */
    public function boot()
    {
        $this->wireEventListeners(); 
        $this->registerModules();

        return true;
    }

    private function wireEventListeners()
    {
    	$this->eventDispatcher->addListener(KernelEvent::ROUTER, function(Event $event){
    		$event->router->addRoutes(array(
	    		'error/403'	=> array(
	    			'CONTROLLER'	=> 'controllers.errorController',
	    			'VIEW'			=> 'views.errorView',
	   				'ACTION'		=> 'do403',
    		        'CAN_BYPASS_FORCED_LOGIN' => true
    		)));
    		
    		$event->router->addRoutes(array(
    		    'error/404'	=> array(
    		        'CONTROLLER'	=> 'controllers.errorController',
    		        'VIEW'			=> 'views.errorView',
    		        'ACTION'		=> 'do404',
    		        'CAN_BYPASS_FORCED_LOGIN' => true
    		    )));
    		
    		$event->router->addRoutes(array(
    		    'error/500'	=> array(
    		        'CONTROLLER'	=> 'controllers.errorController',
    		        'VIEW'			=> 'views.errorView',
    		        'ACTION'		=> 'do500',
    		        'CAN_BYPASS_FORCED_LOGIN' => true
    		    )));
            $event->router->addRoutes(array(
                'users/add'	=> array(
                    'CONTROLLER'	=> 'controllers.MyController',
                    'VIEW'			=> 'views.HelloWorldView',
                    'ACTION'		=> 'addUser',
                    'CAN_BYPASS_FORCED_LOGIN' => true
                )));
    	});
    	
    	/** Add loader instance to twig for package specific files */
    	$this->eventDispatcher->addListener('package.twig.loaded', function(Event $event)  {
    		$twig = $event->twig;
    		$this->eventDispatcher->addListener('dispatcher.view.loaded', function(Event $event) use ($twig) {
    			$view = $event->view;
    			if(is_dir($sTemplateDir = __DIR__ . '\Modules\\' . $view::MODULE_NAME . '\templates')) {
    				$loader = new \Twig_Loader_Filesystem();
    				$loader->addPath($sTemplateDir, 'HelloWorld');
    				$loader->addPath($sTemplateDir, 'Error'); //@todo: Dette viser at jeg ikke egentlig burde ha to ulike moduler innenfor en package
    				$twig->getLoader()->addLoader($loader);
    			}
    		});
    	});
    	
    	/** Add function to twig  @todo: Denne er kun et eksempel, dette burde v�re en egen package sannsynligvis */
    	$this->eventDispatcher->addListener('package.twig.loaded', function(Event $event)  {
    	    
    	    // md5 filter
			$md5filter = new \Twig_SimpleFilter('md5', function($str) {
				return md5($str);
			});
			$event->twig->addFilter($md5filter);
			
            // String converter filter			
			$arrToString = new \Twig_SimpleFilter('toString', function($arr) {
			    if(is_array($arr)) {
			        if(empty($arr)) {
			            return null;
			        } else {
			            return implode("\n", $arr);
			        }
			    } elseif(is_string($arr)) {
			        return $arr;
			    } else {
                    throw new \Exception('Custom twig filter (toString) cannot handle variable of type ' . gettype($arr));
			    }
			});
			$event->twig->addFilter($arrToString);
			
			// Interpolation filter
			$interpolationFilter = new \Twig_SimpleFilter('interpolate', function($string, $context) {
			    $context = is_array($context) ? $context : [$context];
			    return $this->replacePlaceholders($string, $context);
			});
			$event->twig->addFilter($interpolationFilter);
			
    	});
    }

	/**@todo Denne burde jeg automatisere noe via en abstract method, interfaces og auto-wiring */
    private function registerModules()
    {
	    $this->container
			->register('bblue\ruby\Package\HelloWorldPackage\Modules\HelloWorld\MyController', 'controllers.myController')
			->register('bblue\ruby\Package\HelloWorldPackage\Modules\HelloWorld\HelloWorldView', 'views.HelloWorldView')
				->addConstructorCallback('setTwig', ['@twig']);

	    $this->container
			->register('bblue\ruby\Package\HelloWorldPackage\Modules\Error\ErrorController', 'controllers.errorController')
			->register('bblue\ruby\Package\HelloWorldPackage\Modules\Error\ErrorView', 'views.errorView')
				->addConstructorCallback('setTwig', ['@twig']);
    }
}