<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application;

use Application\Model\OptionTable;
use Application\Model\RoleTable;
use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;

class Module
{
    public function onBootstrap(MvcEvent $e)
    {
    	$application 		= $e->getApplication();
    	$sm 				= $application->getServiceManager();
        $eventManager 		= $application->getEventManager();
        $adapter 			= $sm->get('Zend\Db\Adapter\Adapter');
        $translator 		= $sm->get('translator');
        
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);
        
        
        $role_table = new RoleTable($adapter);
		\Zend\View\Helper\Navigation::setDefaultAcl($role_table->getAcl());
		\Zend\View\Helper\Navigation::setDefaultRole($role_table->getDefault()->id);
		
		$viewModel = $application->getMvcEvent()->getViewModel();
		$viewModel->adapter 		= $adapter;
		$viewModel->option_table	= new OptionTable($adapter);
		$viewModel->sm				= $sm;
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }
}
