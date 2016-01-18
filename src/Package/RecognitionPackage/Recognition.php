<?php

namespace bblue\ruby\Package\RecognitionPackage;

use bblue\ruby\Component\Core\DispatcherEvent;
use bblue\ruby\Component\Core\KernelEvent;
use bblue\ruby\Component\EventDispatcher\Event;
use bblue\ruby\Component\Package\AbstractPackage;
use bblue\ruby\Component\Security\AuthEvent;

/**
 * Class responsible for creating user objects
 * @todo Lage en component som lager guest users uansett
 * @author Aleksander Lanes
 *
 */
final class Recognition extends AbstractPackage
{
    public function boot()
    {
        // Register the entities
        $entityManager = $this->container->get('entityManager');
        $entityManager->getConfiguration()->getMetadataDriverImpl()->addPaths([__DIR__ . '\Entities']);
        
        $this->container->get('classLoader')->addNamespace('bblue\ruby\Entities', __DIR__ . '\Entities');
        
        // Register the services
        $this->container
            ->register('bblue\ruby\Package\RecognitionPackage\UserService', 'UserService')
            ->register('bblue\ruby\Package\RecognitionPackage\VisitorService', 'VisitorService')
            ->register('bblue\ruby\Component\Security\AuthTokenFactory', 'authTokenFactory')
            ->register('bblue\ruby\Package\RecognitionPackage\NativeLogin', 'nativeLogin')
            ->addConstructorParameter('@services.login')
            ->addConstructorParameter('@userProviderStack')
            ->register('services.login', 'bblue\ruby\Package\RecognitionPackage\LoginService')
            ->addConstructorParameter('@request', 2)
            ->addConstructorParameter('@authTokenFactory', 3)
            ->register('GuestProvider', 'bblue\ruby\Package\RecognitionPackage\GuestProvider');
            /**->register('auth', 'bblue\ruby\Package\RecognitionPackage\AuthenticationService')
                ->addConstructorArgument(new Reference('request'), 1)
            ->register('LoginTokenHandler', 'bblue\ruby\Package\RecognitionPackage\LoginTokenHandler')*/
        
        /** Add loader instance to twig for package specific files */
        $this->eventDispatcher->addListener('package.twig.loaded', function(Event $event)
        {
            $twig = $event->twig;
            $this->eventDispatcher->addListener(DispatcherEvent::VIEW_LOADED, function(Event $event) use ($twig)
            {
                //@todo: legacy, denne kan nok fjernes $view = $event->view;
                if(is_dir($sTemplateDir = __DIR__ . '/Modules/User/templates')) {
                    $loader = new \Twig_Loader_Filesystem();
                    $loader->addPath($sTemplateDir, 'User');
                    $twig->getLoader()->addLoader($loader);
                }
            });
        });
        
        /** Add routes to routing table */
    	$this->eventDispatcher->addListener(KernelEvent::ROUTER, function(Event $event)
    	{
    		$event->router->addRoutes(array(
    		    'user/login'	=> array(
    		        'CONTROLLER'	=> 'controllers.userController',
    		        'VIEW'			=> 'views.userView',
    		        'ACTION'		=> 'login'
    		    )));
    	});
    	/** Add a user providers to usr provider stack */
        $this->container->addConstructorCallback('add', ['@UserService'], '@userProviderStack');
        $this->container->addConstructorCallback('add', ['@GuestProvider'], '@userProviderStack');
    	/** Register new modules */
    	$this->registerModules();
    	/** Enable anonomyous authentication */
    	$this->eventDispatcher->addListener(AuthEvent::NO_AUTH_TOKEN, function(Event $event) {
    	    $tokenProvider = new AnonomyousAuthTokenProvider($this->container->get('authTokenFactory'), $this->container->get('request'), $this->container->get('userProviderStack'));
    	    $event->auth->handle($tokenProvider->getToken());
    	});
        return true;
    }
    
    private function registerModules()
    {
        $this->container
            ->register('bblue\ruby\Package\RecognitionPackage\Modules\User\UserController', 'controllers.userController')
            ->addConstructorParameter('@request')
            ->register('views.userView', 'bblue\ruby\Package\RecognitionPackage\Modules\User\UserView')
            ->addConstructorParameter('@response')
            ->addConstructorParameter('@request')
            ->addConstructorCallback('setTwig', ['@twig']);
    }
}