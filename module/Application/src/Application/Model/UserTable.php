<?php

namespace Application\Model;

use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Where;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\AbstractTableGateway;

class UserTable extends AbstractTableGateway
{
    protected $table ='user';

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
        $this->resultSetPrototype = new ResultSet();
        $this->resultSetPrototype->setArrayObjectPrototype(new User());

        $this->initialize();
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
    
    public function getByUsername($username)
    {
    	$rowset = $this->select(array(
    		"username" => $username
    	));
    	$row = $rowset->current();
    	if (!$row) {
    		throw new \Exception("Could not find row $id");
    	}
    
    	return $row;
    }

    public function save(User $object)
    {
        $data = array(
            'username' => $object->username,
        	'type' => $object->type,
        	'password' => $object->password,
        	'firstname' => $object->firstname,
        	'lastname' => $object->lastname,
        	'role_id' => $object->role_id,
        );

        $id = (int) $object->id;

        if ($id == 0) {
            $this->insert($data);
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
    }

    public function deleteById($id)
    {
        $this->delete(array(
            'id' => $id,
        ));
    }
    
    public function existsByUsername($username) {
		$row = $this->getByUsername($username);
    	if (!$row) {
    		return false;
    	}
    	return true;
    }
    
    public function hasRoleByUsername($username) {
    	$rowset = $this->select(array(
    		"username" => $username
    	));
    	 
    	$row = $rowset->current()->getArrayCopy();
    	if(!$row) {
    		return false;
    	}
		if(!empty($row["role_id"])) {
			return true;
		}
		return false;
    }
    
    public function getAsJson($data)
    {
    	$sql = new Sql($this->adapter);
    	$select = $sql->select();
    	$select->from('user');
    	$where = array(
    			(($data["id"] != '%%') ? "id LIKE '".$data["id"]."'" : ""),
    			(($data["username"] != '%%') ? "username LIKE '".$data["username"]."'" : ""),
    			(($data["firstname"] != '%%') ? "firstname LIKE '".$data["firstname"]."'" : ""),
    			(($data["lastname"] != '%%') ? "lastname LIKE '".$data["lastname"]."'" : ""),
    			(($data["role_id"] != '%%') ? "role_id LIKE '".$data["role_id"]."'" : ""),
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
    						$row->username,
    						$row->firstname,
    						$row->lastname,
    						$row->role_id,
    				),
    		);
    	}
    	return $return;
    }
}