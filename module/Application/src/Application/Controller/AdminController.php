<?php

namespace Application\Controller;

use Application\Model\Role;

use Nette\Application\Request;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Application\Model\OptionTable;
use Application\Model\UserTable;
use Application\Model\RoleTable;
use Zend\Authentication\AuthenticationService;

class AdminController extends AbstractActionController
{
	protected $adapter;
	public $option_table;
	public $user_table;
	public $role_table;
	public $auth;
	public $user;
	
	protected function initProperties()
	{
		if(!$this->adapter) {
			$this->adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
		}
		if(!$this->role_table) {
			$this->role_table = new RoleTable($this->adapter);
		}
		if(!$this->user_table) {
			$this->user_table = new UserTable($this->adapter);
		}
		if(!$this->option_table) {
			$this->option_table = new OptionTable($this->adapter);
		}
		if (!$this->auth) {
			$this->auth = new AuthenticationService();
		}
		if(!$this->user) {
			$this->user = $this->auth->getStorage()->read();
		}
	}
	
	protected function checkPermission()
	{
		if(!empty($this->user)) {
			$role_id = $this->user->role_id;
		} else {
			$role_id = $this->role_table->getDefault()->id;
		}
		if(empty($role_id) || !$this->role_table->isAllowed($role_id, "mvc:admin")) {
			return false;
		}
		return true;
	}

	public function indexAction()
	{
		$this->initProperties();
		$permission = $this->checkPermission();
		if(!$permission) {
			return $this->response->setStatusCode(403);//forbidden
		}
		return $this->redirect()->toRoute("application/admin", array("action" => "option"));
	}
	
	public function optionAction() 
	{ 
		$this->initProperties();
		$permission = $this->checkPermission();
		if(!$permission) {
			return $this->response->setStatusCode(403);//forbidden
		}
		return new ViewModel(); 
	}
	
	public function optionDataAction()
	{
		$this->initProperties();
		$permission = $this->checkPermission();
		if(!$permission) {
			return $this->response->setStatusCode(403);//forbidden
		}
		$json = $this->option_table->getAsJson(array(
			'page'        => $this->request->getQuery()->page,
			'sidx'        => $this->request->getQuery()->sidx,
			'sord'        => $this->request->getQuery()->sord,
			'rownumber'   => $this->request->getQuery()->rows,
			'id'		  => $this->request->getQuery()->id,
			'name'		  => "%".$this->request->getQuery()->name."%",
			'value'		  => "%".$this->request->getQuery()->value."%",
			'description' => "%".$this->request->getQuery()->description."%",
		));

		return new JsonModel($json);
	}
	
	public function updateOptionAction()
	{
		$this->initProperties();
		$permission = $this->checkPermission();
		if(!$permission) {
			return $this->response->setStatusCode(403);//forbidden
		}
		if($this->getRequest()->getPost('oper') == "edit") {
			$option = $this->option_table->getByName($this->getRequest()->getPost('id'));
			if($this->getRequest()->getPost('value') != "") {
				$option->value = $this->getRequest()->getPost('value');
				$this->option_table->save($option);
				return new JsonModel(array("success"));
			}
		}
		return $this->response->setStatusCode(503);//service unavailable
	}
	
	public function userAction()
	{
		$this->initProperties();
		$permission = $this->checkPermission();
		if(!$permission) {
			return $this->response->setStatusCode(403);//forbidden
		}
		
		$roles = $this->role_table->fetchAll();
		$roles_for_grid = ":nicht definiert;";
		foreach($roles as $role) {
			$roles_for_grid .= $role->id . ":" . $role->name . ";";
		}
		$roles_for_grid = substr($roles_for_grid, 0, -1);
		return new ViewModel(array(
			"roles" => $roles_for_grid
		)); 
	}
	
	public function userDataAction()
	{
		$this->initProperties();
		$permission = $this->checkPermission();
		if(!$permission) {
			return $this->response->setStatusCode(403);//forbidden
		}
		$json = $this->user_table->getAsJson(array(
				'page'        => $this->request->getQuery()->page,
				'sidx'        => $this->request->getQuery()->sidx,
				'sord'        => $this->request->getQuery()->sord,
				'rownumber'   => $this->request->getQuery()->rows,
				'id'		  => "%".$this->request->getQuery()->id."%",
				'username'	  => "%".$this->request->getQuery()->username."%",
				'firstname'	  => "%".$this->request->getQuery()->firstname."%",
				'lastname'	  => "%".$this->request->getQuery()->lastname."%",
				'role_id'	  => "%".$this->request->getQuery()->role_id."%",
		));
	
		return new JsonModel($json);
	}
	
	public function updateUserAction()
	{
		$this->initProperties();
		$permission = $this->checkPermission();
		if(!$permission) {
			return $this->response->setStatusCode(403);//forbidden
		}
		if($this->getRequest()->getPost('oper') == "edit") {
			$row = $this->user_table->getById($this->getRequest()->getPost('id'));
			$properties = array(
				'firstname',
				'lastname',
				'role_id',
			);
			$post = $this->getRequest()->getPost();
			foreach($properties as $p) {
				if(isset($post[$p])) {
					$row->$p = $post[$p];
				}
			}
			$this->user_table->save($row);
			//if current user changes role, update session
			if(isset($post["role_id"]) && $row->id == $this->user->id) {;
				$this->auth->clearIdentity();
				$this->auth->getStorage()->write($row);
			}
			return new JsonModel(array("success"));
		}
		return $this->response->setStatusCode(503);//service unavailable
	}
	
	public function roleAction()
	{
		$this->initProperties();
		$permission = $this->checkPermission();
		if(!$permission) {
			return $this->response->setStatusCode(403);//forbidden
		}

		return new ViewModel(array(
		));
	}


	public function roleDataAction()
	{
		$this->initProperties();
		$permission = $this->checkPermission();
		if(!$permission) {
			return $this->response->setStatusCode(403);//forbidden
		}
		$json = $this->role_table->getAsJson(array(
				'page'        => $this->request->getQuery()->page,
				'sidx'        => $this->request->getQuery()->sidx,
				'sord'        => $this->request->getQuery()->sord,
				'rownumber'   => $this->request->getQuery()->rows,
				'id'		  => "%".$this->request->getQuery()->id."%",
				'name'		  => "%".$this->request->getQuery()->name."%",
				'default'	  => "%".$this->request->getQuery()->default."%",
				'admin'		  => "%".$this->request->getQuery()->admin."%",
		));
	
		return new JsonModel($json);
	}
	
	public function updateRoleAction()
	{
		$this->initProperties();
		$permission = $this->checkPermission();
		if(!$permission) {
			return $this->response->setStatusCode(403);//forbidden
		}
		$post = $this->getRequest()->getPost();
		if($post["oper"] == "add") {
			$row = new Role();
			$row->name = $post["name"];
			$row->default = $post["default"];
			$row->admin = $post["admin"];
			$this->role_table->save($row);
			return new JsonModel(array("success"));
		}elseif($post["oper"] == "edit") {
			$row = $this->role_table->getById($this->getRequest()->getPost('id'));
			$properties = array(
					'name',
					'default',
					'admin',
			);
			foreach($properties as $p) {
				if(isset($post[$p])) {
					$row->$p = $post[$p];
				}
			}
			$this->role_table->save($row);
			return new JsonModel(array("success"));
		}elseif($post["oper"] == "del") {
			$row = $this->role_table->deleteById($post["id"]);
			return new JsonModel(array("success"));
		}
		return $this->response->setStatusCode(503);//service unavailable
	}
}
