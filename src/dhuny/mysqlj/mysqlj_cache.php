<?php
namespace dhuny\mysqlj;
/*
PHP load DATA from cache
This file is used to recall the JS file content. Load the Results and send the full results to PHP as if it was originating from same.
"Passed variable is not an array or object, using empty array instead" -> Error seem to originate from same.
*/

class mysqlj_cache extends \ArrayIterator
{
	
protected $_current;
protected $_key;
protected $_valid;
protected $_result;

public function __construct($jsonObject)
{
		if(!is_null($jsonObject))
	{
		parent::__construct($jsonObject);
	}else{
		echo("JSON Object Assigned is null");
		}

}
	

public function fetch_assoc()
{
	$this->_current = $this->current();
	$this->_key++;
	$this->_valid = is_null($this->_current) ? false : true;
	//var_dump($this->current());
	$this->next();
    return new \ArrayIterator($this->_current);
	
	}
	
public function fetch_row()
{
	$this->_current = $this->current();
	$this->_key++;
	$this->_valid = is_null($this->_current) ? false : true;
	//var_dump($this->current());
	$this->next();
    return new \ArrayIterator($this->_current);
	
	}
}


?>