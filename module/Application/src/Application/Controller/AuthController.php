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
use Zend\Authentication\Adapter\Ldap as AuthAdapterLdap;
use Zend\Authentication\Adapter\DbTable as AuthAdapterDb;
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
		try {
			$dbadapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
			$user_table = new UserTable($dbadapter);
			
			$username = $this->getRequest()->getPost('username');
			$password = $this->getRequest()->getPost('password');
			
			if($user_table->existsByUsername($username)) {
				$user = $user_table->getByUsername($username);
			}
	
			if(isset($user) && $user->type == "ldap") {
				#if ldap config exists init ldap-adapter
				if(file_exists('./config/ldap-config.ini')) {
					$configReader = new ConfigReader();
					$configData = $configReader->fromFile('./config/ldap-config.ini');
					$config = new Config($configData, true);
					$log_path = $config->production->ldap->log_path;
					$options = $config->production->ldap->toArray();
					unset($options['log_path']);
					$adapter = new AuthAdapterLdap($options, $username, $password);
				} else {
					throw new \Exception("User ".$user->username." wants to login with LDAP, but config/ldap-config.ini doesn't exist or isn't readable!");
				}
			} else {
				//login with db
				$adapter = new AuthAdapterDb($dbadapter, 'user', 'username', 'password', 'md5(?)');
				$adapter
				->setIdentity($username)
				->setCredential($password);
			}
			
			$result = $this->auth->authenticate($adapter);
			$messages = $result->getMessages();
	
			if (isset($log_path)) {
				$logger = new Logger;
				$writer = new LogWriter($log_path);
	
				$logger->addWriter($writer);
	
				$filter = new LogFilter(Logger::DEBUG);
				$writer->addFilter($filter);
	
				foreach ($messages as $i => $message) {
					if ($i-- > 1) { // $messages[2] and up are log messages
					    $message = str_replace("\n", "\n  ", $message);
					    $logger->debug("Auth: $i: $message");
					}
				}
			}
		}catch(\Exception $e) {
			$messages[0] = "ERROR: ".$e->getMessage();
		}
		
		if(empty($messages[0]) || $messages[0] == "Authentication successful.") {//successfull ldap login
			$return = "success";
			
			if(isset($user) && !$user_table->hasRoleByUsername($username)) {//if user got no role
				$this->auth->clearIdentity();
				$return = "Der Admin muss noch eine Rolle zuweisen, damit Sie sich anmelden k&ouml;nnen!";
			}else {//if everything ok, write user information in storage
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
				case "A record with the supplied identity could not be found.": $return = "Benutzername oder Passwort stimmen nicht überein!";
													break;	
				case "ERROR: A value for the identity was not provided prior to authentication with DbTable.": $return = "Bitte gib einen Benutzernamen ein!";
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
