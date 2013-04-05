<?php

namespace Application\Model;

use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Where;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Permissions\Acl\Acl;
use Zend\Permissions\Acl\Role\GenericRole;

class RoleTable extends AbstractTableGateway
{
    protected $table ='role';
    protected $acl;
    protected $resources = array();

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;

        $this->resultSetPrototype = new ResultSet();
        $this->resultSetPrototype->setArrayObjectPrototype(new Role());

        $this->initialize();		
        $this->initAcl();
    }
    
    public function initAcl()
    {
    	$this->acl = new Acl();
    	//set properties of Role as resources
    	$role = new Role();
    	$resources = array_keys($role->getArrayCopy());
    	$not_resources = array("id", "name", "default");
    	$this->resources = array_diff($resources, $not_resources);
    	$i = 0;
    	$roles = $this->fetchAll();//get all roles from db
    	foreach($roles as $role) {
    		$this->acl->addRole(new GenericRole($role->id));//add role to acl
    		foreach($this->resources as $resource) {
    			if($i == 0) {//add resource on first run
    				$this->acl->addResource('mvc:'.$resource);
    				$i++;
    			}
    			
    			if($role->$resource == 1) {
    				$this->acl->allow((string)$role->id, 'mvc:'.$resource, null);//allow
    			}else {
    				$this->acl->deny((string)$role->id, 'mvc:'.$resource, "all");//deny
    			}
    			
    		}
    	}
    }

    public function fetchAll()
    {
        $resultSet = $this->select();
        return $resultSet;
    }

    public function getById($id)
    {
        $id  = (int) $id;
        $rowset = $this->select(array(
            'id' => $id,
        ));
        $row = $rowset->current();
        if (!$row) {
            throw new \Exception("Could not find row $id");
        }

        return $row;
    }

    public function save(Role $object)
    {
        $data = array();
        foreach($this->resources as $resource) {
        	$data[$resource] = $object->$resource;
        }
        $data['name'] = $object->name;
		
        $id = (int) $object->id;
        
        //set all default values to 0 except the new one
        if($object->default == 1) {
        	$this->setDefault($id);
        }

        if ($id == 0) {
            $insert = $this->insert($data);
            $id = $this->getLastInsertValue();
        } elseif ($this->getById($id)) {
            $this->update(
                $data,
                array(
                    'id' => $id,
                )
            );
        } else {
            throw new \Exception('Form id does not exist');
        }
        if(!empty($object->default) && $object->default == 1) {
        	$this->setDefault($id);
        }
    }

    public function deleteById($id)
    {
        $this->delete(array(
            'id' => $id,
        ));
    }
    
    public function setDefault($role_id)
    {
    	if($this->getById($role_id)) {
    		$this->update(array("default" => 0));
    		$this->update(array("default" => 1), array("id" => $role_id));
    		return true;
    	}
    	return false;
    }
    
    public function getDefault()
    {
    	$result = $this->select(array("default" => 1));
    	$row = $result->current();
    	if (!$row) {
    		throw new \Exception("No default role set");
    	}
    	
    	return $row;
    	
    }
    
    public function IsAllowed($role_id, $resource=null, $privilege=null)
    {
    	return $this->acl->isAllowed((string)$role_id, $resource, $privilege);
    }
    
    public function getAcl()
    {
    	return $this->acl;
    }
    
    public function getAsJson($data)
    {
    	$sql = new Sql($this->adapter);
    	$select = $sql->select();
    	$select->from('role');
    	$where = array(
    			(($data["id"] != '%%') ? "id LIKE '".$data["id"]."'" : ""),
    			(($data["name"] != '%%') ? "name LIKE '".$data["name"]."'" : ""),
    			(($data["default"] != '%%') ? "default LIKE '".$data["default"]."'" : ""),
    			(($data["admin"] != '%%') ? "admin LIKE '".$data["admin"]."'" : ""),
    	);
    	$where = array_diff($where, array(''));//remove all empty values
    	$select->where($where);
    
    	$total_statement = $sql->prepareStatementForSqlObject($select);
    	$total_result = $total_statement->execute()->count();
    	$total_pages = ceil($total_result / $data['rownumber']);
    
    	$select->order($data['sidx'].' '.$data['sord']);
    	$select->limit($data['rownumber']);
    	$select->offset(($data['page'] - 1) * $data['rownumber']);
    
    	$statement = $sql->getSqlStringForSqlObject($select);
    	$adapter = $this->adapter;
    	$results = $adapter->query($statement, $adapter::QUERY_MODE_EXECUTE);
    
    	$return = array(
    			"page"	=>  $data["page"],
    			"total"	=>  $total_pages,
    			"records"=> $total_result,
    			"rows"	=>  array(),
    	);
    	foreach($results as $row) {
    		$return["rows"][] = array(
    				"id"	=>	$row->id,
    				"cell"	=> 	array(
    						$row->id,
    						$row->name,
    						$row->default,
    						$row->admin,
    				),
    		);
    	}
    	return $return;
    }

}