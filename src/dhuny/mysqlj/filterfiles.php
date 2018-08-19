<?php 
namespace Dhuny\mysqlj;
class filterfiles extends \RecursiveFilterIterator
{
 protected $MemFiles;
 
 public function __construct(\RecursiveIterator $iterator, $MemFiles)
 {
	 parent::__construct($iterator);
	 $this->MemFiles = is_array($MemFiles) ? $MemFiles : (array) $MemFiles;
	 }
 public function accept()
 {
	 	if($this->hasChildren())
		{
			return false; // when true all folders are listed if new RecursiveArrayIterator is called
			}
			return $this->current()->isFile() && 
			in_array(($this->current()->getFileName()),$this->MemFiles);
	 }	
	 public function getChildren()
	 {
		 return new self($this->getInnerIterator()->getChildren(),$this->MemFiles);
		 }
}

?>