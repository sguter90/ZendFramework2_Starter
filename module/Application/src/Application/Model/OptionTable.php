<?php

namespace Application\Model;

use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Where;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\AbstractTableGateway;

class OptionTable extends AbstractTableGateway
{
    protected $table ='option';
    protected $options = array(
    	array(
	    	"name" => "admin_notify_email",
	    	"value" => "admin@example.com",
	    	"description" => "Admin-Email-Adresse: erhält alle Admin-Nachrichten",
    	), 
    	array(
	    	"name" => "system_email",
	    	"value" => "system@example.com",
	    	"description" => "Absender-Email-Adresse für System-Nachrichten",
    	),
		array(
    		"name" => "app_name",
    		"value" => "Mein System",
    		"description" => "Name der Applikation",
    	),
    	array(
    		"name" => "footer_text",
    		"value" => "Das ist der Footer von meiner Applikation",
    		"description" => "Text der ganz unten auf der Seite angezeigt wird",
    	),
    );

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
        $this->resultSetPrototype = new ResultSet();
        $this->resultSetPrototype->setArrayObjectPrototype(new Option());

        $this->registerOptions();
        $this->initialize();
    }

    public function fetchAll()
    {
        $resultSet = $this->select();
        return $resultSet;
    }

    public function getByName($name)
    {
        $rowset = $this->select(array("name" => $name));
        $row = $rowset->current();
        if (!$row) {
            return false;
        }

        return $row;
    }
    
    public function getValue($name) {
    	$row = $this->getByName($name);
    	$row = $row->getArrayCopy();
    	return $row["value"];
    }

    public function save(Option $object)
    {
        $data = array(
            'name' => $object->name,
        	'value' => $object->value,
			'description' => $object->description,
        );

        try {
			$this->update($data, array('name' => $object->name));
        } catch (\Exception $e) {
        	throw new \Exception("Could not find row $object->name");
        }
    }
    
    protected function registerOptions() 
    {
    	foreach($this->options as $option) {
    		$row = $this->getByName($option["name"]);
    		if(empty($row)) {
    			$this->insert($option);
    		}
    	}
    }
    
    public function getAsJson($data)
    {	
		$sql = new Sql($this->adapter);
		$select = $sql->select();
		$select->from('option');
		$where = array(
			(($data["name"] != '%%') ? "name LIKE '".$data["name"]."'" : ""),
			(($data["value"] != '%%') ? "value LIKE '".$data["value"]."'" : ""),
			(($data["description"] != '%%') ? "description LIKE '".$data["description"]."'" : ""),
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
				"id"	=>	$row->name,
				"cell"	=> 	array(
					$row->name,
					$row->value,
					$row->description,
				),
			);
		}
    	return $return;
    }
    

}