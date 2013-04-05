<?php
namespace Application\Model;

class Role
{
    public $id;
	public $name;
	public $default;
	public $admin;
    protected $adapter;
    protected $table;
    
    public function __construct(Adapter $adapter=null) 
    {
    	if(!empty($adapter)) {
			$this->setAdapter($adapter);
    	}
    }
    
    public function setAdapter(Adapter $adapter) {
    	if(!empty($adapter)) {
    		$this->adapter = $adapter;
    		$this->table = new RoleTable($this->adapter);
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
    	unset($vars["table"]);
    	return $vars;
    }
    
    public function save(Adapter $adapter=null)
    {
		$this->setAdapter($adapter);
    	if(!empty($this->table)) {
	    	$this->table->save($this);
	    	return true;
    	}
    	return false;
    	
    }
    
    public function setAsDefault(Adapter $adapter=null)
    {
		$this->setAdapter($adapter);
    	if(!empty($this->table)) {
    		$this->table->setDefault($this->id);
    		return true;
    	}
    	return false;
    }
    
    public function IsAllowed(Adapter $adapter, $role_id, $resource=null, $privilege=null)
    {
    	$this->setAdapter($adapter);
    	if(!empty($this->table)) {
    		return $this->table->IsAllowed($role_id,$resource,$privilege);
    	}
    }
}