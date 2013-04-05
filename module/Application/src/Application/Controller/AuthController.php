<?php

namespace Application\Controller;

use Application\Model\OptionTable;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Application\Model\UserTable;
use Application\Model\User;
use Zend\Mail\Message;
use Zend\Mail\Transport\Sendmail as SendmailTransport;
#auth
use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Adapter\Ldap as AuthAdapter;
use Zend\Config\Reader\Ini as ConfigReader;
use Zend\Config\Config;
use Zend\Log\Logger;
use Zend\Log\Writer\Stream as LogWriter;
use Zend\Log\Filter\Priority as LogFilter;

class AuthController extends AbstractActionController
{
	protected $auth;
	
	public function __construct()
	{
		if (! $this->auth) {
			$this->auth = new AuthenticationService();
		}
	}

	public function indexAction()
	{
		$this->adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
		$user = $this->auth->getIdentity();
		if(empty($user)) {
        	return new ViewModel();
		}else {
			return $this->redirect()->toRoute("application/auth", array("action" => "profile"));
		}
	}

	public function loginAction()
	{
		$username = $this->getRequest()->getPost('username');
		$password = $this->getRequest()->getPost('password');

		$configReader = new ConfigReader();
		$configData = $configReader->fromFile('./config/ldap-config.ini');
		$config = new Config($configData, true);

		$log_path = $config->production->ldap->log_path;
		$options = $config->production->ldap->toArray();
		unset($options['log_path']);

		$adapter = new AuthAdapter($options,
				                   $username,
				                   $password);
		
		$result = $this->auth->authenticate($adapter);
		$messages = $result->getMessages();

		if ($log_path) {
			$logger = new Logger;
			$writer = new LogWriter($log_path);

			$logger->addWriter($writer);

			$filter = new LogFilter(Logger::DEBUG);
			$writer->addFilter($filter);

			foreach ($messages as $i => $message) {
				if ($i-- > 1) { // $messages[2] and up are log messages
				    $message = str_replace("\n", "\n  ", $message);
				    $logger->debug("Ldap: $i: $message");
				}
			}
		}
		if(empty($messages[0])) {//successfull ldap login
			$return = "success";
			$adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
			$user_table = new UserTable($adapter);
			
			if(!$user_table->existsByUsername($username)) {//if no user exists in db
				
				$user = new User();
				$user->exchangeArray(array(
					"username" => $username,
					"type" => "ldap"
				));
				$user->save($adapter);//save new user to db
				$this->auth->clearIdentity();//delete session
				$return = "Der Admin muss noch eine Rolle zuweisen, damit Sie sich anmelden k&ouml;nnen!";
				
				//get options from database
				$option_table = new OptionTable($adapter);
				$admin = $option_table->getValue("admin_notify_email");
				$system = $option_table->getValue("system_email");
				$app_name = $option_table->getValue("app_name");

				//send message to admin
				$message = new Message();
				$message->addTo($admin)
				->addFrom($system)
				->setSubject($app_name.': User "'.$username.'" möchte sich anmelden')
				->setBody('Der User "'.$username.'" hat versucht sich bei '.$app_name.' anzumelden. Momentan hat er keine erweiterten Rechte. 
					Um ihm mehr Rechte zu geben, weisen Sie ihm eine Rolle zu!');
				
				$transport = new SendmailTransport();
				$transport->send($message);
				
			}elseif(!$user_table->hasRoleByUsername($username)) {//if user got no role
				$this->auth->clearIdentity();
				$return = "Der Admin muss noch eine Rolle zuweisen, damit Sie sich anmelden k&ouml;nnen!";
			}else {//if everything ok, write user information in storage
				$user = $user_table->getByUsername($username);
				$this->auth->getStorage()->write($user);
			}
		} else {
			switch($messages[0]) {
				case "A password is required": 		$return = "Bitte geben Sie ein Passwort ein!";
													break;
				case "A username is required": 		$return = "Bitte geben Sie einen Benutzernamen ein!";
													break;
				case "An unexpected failure occurred": 	$return = "Ein unerwarteter Fehler ist aufgetreten!";
													break;								
				default: $return = $messages[0];
			}
		}
		$json = new JsonModel(array("message" => $return));
		return $json;
	}
	
	public function logoutAction()
	{
		$this->auth->clearIdentity();
		return $this->redirect()->toRoute("application", array("action" => "index"));
	}
	
	public function profileAction()
	{
		return new ViewModel(array(
			"user" => $this->auth->getIdentity(),
		));
	}


}
