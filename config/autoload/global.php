<?php
/**
 * Global Configuration Override
 *
 * You can use this file for overriding configuration values from modules, etc.
 * You would place values in here that are agnostic to the environment and not
 * sensitive to security.
 *
 * @NOTE: In practice, this file will typically be INCLUDED in your source
 * control, so do not include passwords or other sensitive information in this
 * file.
 */

return array(
	'navigation' => array(
         'default' => array(
         		array(
         				'label' => 'Administration',
         				'route' => 'application/admin',
         				'resource' => "mvc:admin",
         				'pages'      => array(
         						array(
         								'label' => 'Optionen',
         								'route'	=> 'application/admin',
         								'controller' => 'Application\Controller\AdminController',
         								'action' => 'option',
         						),
         						array(
         								'label' => 'User',
         								'route'	=> 'application/admin',
         								'controller' => 'Application\Controller\AdminController',
         								'action' => 'user',
         						),
         						array(
         								'label' => 'Rollen',
         								'route'	=> 'application/admin',
         								'controller' => 'Application\Controller\AdminController',
         								'action' => 'role',
         						),
         				),
         		),
         		array(
         				'label' => 'Account',
         				'route' => 'application/auth',
         		),
         ),
     ),
     'service_manager' => array(
         'factories' => array(
             'navigation' => 'Zend\Navigation\Service\DefaultNavigationFactory',
			 'Zend\Db\Adapter\Adapter' => 'Zend\Db\Adapter\AdapterServiceFactory',
         ),
     ),
);
