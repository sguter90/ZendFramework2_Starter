<?php

namespace Application\Model;

class Option
{
	public $name;
	public $value;
	public $description;

    public function exchangeArray($data)
    {
    	$vars = get_object_vars($this);
    	foreach($vars as $name => $value) {
    		$this->$name = (isset($data[$name])) ? $data[$name] : null;
    	}
    }

    public function getArrayCopy()
    {
        return get_object_vars($this);
    }
}