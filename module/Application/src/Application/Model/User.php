<?php

//http://framework.zend.com/manual/1.12/en/zend.db.table.relationships.html

namespace Application\Model;

class User
{
    public $id;
    public $username;
    public $type;
    public $password;
    public $firstname;
    public $lastname;
    public $role_id;
    protected $adapter;
    
    public function __construct(Adapter $adapter=null) 
    {
    	if(!empty($adapter)) {
    		$this->adapter = $adapter;
    	}
    }

    public function exchangeArray($data)
    {
    	$vars = $this->getArrayCopy();
    	foreach($vars as $name => $value) {
    		$this->$name = (isset($data[$name])) ? $data[$name] : null;
    	}
    }

    public function getArrayCopy()
    {
    	$vars = get_object_vars($this);
    	unset($vars["adapter"]);
    	return $vars;
    }
    
    public function save(Adapter $adapter=null)
    {
    	if(!empty($adapter)) {
    		$this->adapter = $adapter;
    	}
    	if(!empty($this->adapter)) {
	    	$user_table = new UserTable($this->adapter);
	    	$user_table->save($this);
	    	return true;
    	}
    	return false;
    	
    }
}