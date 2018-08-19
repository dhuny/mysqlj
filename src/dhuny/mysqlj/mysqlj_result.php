<?php
namespace dhuny\mysqlj;
/*
Prior to calling mysqlJ_result, the mysqlj class checks for presence of JS file 
If JS file is present and date is consistent, then the date from File is read and passed to mysqlj_result.  Our approach here is to use a polymorphic function to
load the data. Given the constraints of PHP, we will use the get_func_args to count the number of args and select the right  Constructor. The constructor will simply
load the 
*/

class mysqlj_result implements \Iterator, \Countable
{
protected $_current;
protected $_key;
protected $_valid;
protected $_result;
protected $_result_array = array();
protected $exportValues;
protected $updateness;
protected $type; // either 1 = ROW or 2 = ASSOC
public function __construct($sql, $connection,$exportOptions)
{
if (!$this->_result = $connection->query($sql)) {
throw new RuntimeException($connection->error . '. The actual query submitted was: ' . $sql);

}

// To remove echo and include in a function
echo " <script type='application/javascript' src='".$exportOptions[4][2].".js'></script> ";
$this->exportValues = $exportOptions;
//$this->IsCacheConsistent(10);

}
// other methods
public function next()
{
$this->_current = $this->_result->fetch_assoc();
//var_dump($this->_current);
$this->_valid = is_null($this->_current) ? false : true;
$this->_key++;
}
public function current()
{
return $this->_current;
}
public function key()
{
return $this->_key;
}
public function valid()
{
return $this->_valid;
}
public function rewind()
{
if (!is_null($this->_key)) {
$this->_result->data_seek(0);
}
$this->_key = 0;
$this->_current = $this->_result->fetch_assoc();
$this->_valid = is_null($this->_current) ? false : true;
}
public function count()
{
return $this->_result->num_rows;
}
public function fetch_assoc()
{
	$tmp = $this->_result->fetch_assoc();
	$this->push_in($tmp);
	$this->type = 0; // MYSQLI_ASSOC = 0, MYSQLI_NUM = 1, or MYSQLI_BOTH (To implement). 
	return $tmp;
}
public function fetch_clientSide()
{
	
	
	//$this->push_in($this->_result->fetch_assoc());
	//var_dump(json_encode($this->_result->fetch_assoc()));
	//var_dump($this->exportValues[1][2][0]);
	//return $this->_result->fetch_assoc();
	return("".$this->exportValues[1][0][0]);
	/*
	//return("<script>document.write(results.rows.item(0).emp_no);</script>");
	*/
}

/* Experimental function. NOT TESTED. Used to find out if export num will work

*/

public function fetch_row()
{
	$tmp2 = $this->_result->fetch_row();
	$this->type = 1; // MYSQLI_ASSOC = 0, MYSQLI_NUM = 1, or MYSQLI_BOTH (To implement). 
	$this->push_in($tmp2);
	
	return $tmp2;
}

public function push_in($result)
{
	array_push($this->_result_array,$result);
	}
public function array_result()
{
	return $this->_result_array;
	}
	
public function __destruct()
{
	if($this->exportValues[5] == false){ // Is CacheValid = false
$this->exportFile($this->exportValues[4][0],$this->exportValues[4][1],$this->exportValues[1],($this->array_result()),$this->exportValues[4][2].".js");
	}
	return;
	
		}
		
		
/* Start of function Export file */
public function exportFile($db,$table,$fields,$results,$name)
{
	//var_dump(func_get_args());
	/*
	Parameters:
	var db = "mydb";
var table = "employees";
var fields=[["emp_no","unique"],["first_name","text"],["last_name","text"]];
 var results = [["10001","Georgi","Facello"],["10002","Bezalel","Simmel"]];
	
	*/
	$db_fields = "";
	$insert_stmt = "INSERT INTO ".$table." (";
$create_stmt = "CREATE TABLE IF NOT EXISTS ".$table."( ";
$delete_stmt = "Delete from ".$table;
$select_stmt = "Select * from ".$table;
$quote = "?";
for($i=0;$i<count($fields)-1;$i++) // Less than -1 implies do not take last one.
	{
	$create_stmt.=$fields[$i][0]." ".$fields[$i][1].",";
	$insert_stmt.=$fields[$i][0].",";
	$quote.=",?";
	$db_fields.=$fields[$i][0]."','";	
	}
	$db_fields.=$fields[count($fields)-1][0]."";
	$create_stmt.=$fields[count($fields)-1][0]." ".$fields[count($fields)-1][1].")"; // here we add last one as -1 as in array of 10 numbers last index is 9.
	$insert_stmt.=$fields[count($fields)-1][0].") VALUES (".$quote.")";


$thejs;
$thejs  = "/* start of serialize*/\n";
//$thejs  = "/* This code can be executed as is in the Browser console and Web Database should be created.*/\n";
//$thejs  = "/* Use Chrome Browser, go to Console. paste JS , execute it and Visit the tab  Application Web SQL*/\n";
$thejs .= "var data ='".json_encode($this->exportValues,JSON_UNESCAPED_SLASHES)."';";
$thejs .= "\n /* End of serialize*/ \n";
$thejs .= "\n var create_stmt = '".$create_stmt."';\n ";
$thejs .= "var insert_stmt = '".$insert_stmt."';\n ";
$thejs .= "var delete_stmt = '".$delete_stmt."';\n ";
$thejs .= "var select_stmt = '".$select_stmt."';\n ";
$thejs .=  "var dbfields = ['".$db_fields."'];\n ";
$thejs .= "/* start of results*/  \n ";
$thejs .= "var results ='".json_encode($results)."';\n ";
$thejs .= "/*End of Results*/ \n ";
$thejs .= "var results =JSON.parse(results);\n ";
$thejs .= "var fields_length =".count($fields).";\n ";
$thejs .= "var db ='".$db."';\n ";
$thejs .= "var db = openDatabase(db, '1.0', 'Test DB', 2 * 1024 * 1024);"."\n ";
$thejs .= "var fn = function (tx, rs) { console.log(rs); };"."\n ";
$thejs .= "var tn = function (tx, err) { console.log(err); };"."\n ";
$thejs .= "db.transaction(function (tx) {"."\n ";
$thejs .= "tx.executeSql(create_stmt,[],fn,tn); // Create table"."\n ";
$thejs .= "tx.executeSql(delete_stmt,[],fn,tn); // Delete all from table"."\n ";
$thejs .= "for(i=0;i < results.length-1;i++){"."\n ";
$thejs .= "var a = [];"."\n ";
$thejs .= "for(j=0;j < fields_length;j++)"."\n ";
/* 
This function detects if fetch assoc was used and loads proper JS code.
*/
if ($this->type == 1){
$thejs .= "{a[j]= results[i][j];}"." // for MySQL Num \n";
}
if ($this->type == 0){
$thejs .= "{a[j]= results[i][dbfields[j]];} // for MySQL Assoc \n";
}
$thejs .= "tx.executeSql(insert_stmt,a,fn,tn);"."\n ";
$thejs .= "}"."\n ";
$thejs .= "});"."\n ";
//var_dump($this->exportValues);
//$ts = $this->tablestamp($this->exportValues[4][3],$this->exportValues[3],10);
//$thejs = $ts." ".$thejs;
//echo("the name: ".$name."<br>");
//echo($thejs);
$this->write_to_file($name,$thejs);
//return basename($_SERVER["SCRIPT_FILENAME"], '.php');
	}


/* End of function Export file */

	
/* This function is used to return the export fields */	
	public function exportFields()
	{
		return $this->exportOptions;
	}		
		
		
		
/* Function to return exact time table modified use:tablestamp($this->datadir,$this->exportOptions[3])  */

public function tablestamp($datadir,$tables){
 
 	$tableStamp = "var tableStamp = {"; 
	$jtable ="";
	foreach($tables as $value)
	{
$tableStamp .="'".$value."':".(filemtime($datadir.$value.".frm")).",";
$jtable = $value."_";
	}
$this->jtables = $jtable;
$tableStamp = rtrim($tableStamp,",");
$tableStamp .= "}; \n";
return $tableStamp;
	}
/* end of TableStamp */		


function write_to_file($filename,$content,$accepted_time=30)
{
	// This function gets the current file path and writes the new content JS in the Folder
	//By Default this path will be that of the location of the mysqlj library. This is currently set to /src/Dhuny/mysql in github
	// to move a few directories up use: ../../../.$filename
	//If you are working with composer, the path may be 5 levels higher, so use ../../../../../$filename
	
	//This section is set as a todo list, where we shall have a genJS folder for generated JS .
	// If this is set then the search within the folders should go to this path as well.
//	$filename = dirname(__FILE__).DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR.$filename;


	$filename = dirname(__FILE__).DIRECTORY_SEPARATOR.$filename;
	
	$filename = str_replace(DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'dhuny'.DIRECTORY_SEPARATOR.'mysqlj','',$filename);  // This is for GITHuB Replacement path
	
	$filename = str_replace(DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'dhuny'.DIRECTORY_SEPARATOR.'mysqlj','',$filename);  // Thi second replacement is for composer or packagist
//   The above 3 lines should be reviewd to improve portability of codes	
	//echo"The Filename is:  ".$filename;
	if (file_exists($filename)) { // check if file exists and caculate age of file.
	$since_last_update = (time() - filemtime($filename));
} else {
	
//	if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
  //  echo "<b style='font-size:0.6em;color:Red;font-weight:normal;'>This is a server using Windows! Touch Directory may not work as expected<b></br>";
//	exec(`php NUL > $filename`);
//} else {
  //  echo 'This is a server not using Windows!';
	touch($filename);
//}
	
	
	
	$since_last_update = $accepted_time + 1; // simply increase last update time to allow update condition to be called
}
	if ($since_last_update > $accepted_time)
{
	//echo($filename);
    $fh = fopen($filename, 'w') or die("can't open file");
	fwrite($fh, $content);
//	echo "<br><b style='font-size:0.6em;color:Orange;font-weight:normal;'> We are also updating the page as last update was more ".$since_last_update." s earlier representing ".($since_last_update/60)." m <b><br>";
	}
	

}

/* closing tag to close class */	
}



/* This function will allow conversion from Array to object smoothly
Alternatively, Array containing arrays will not be converted efficiently.
 */
function json_decode_nice($json, $assoc = TRUE){
    $json = str_replace(array("\n","\r"),"\\n",$json);
    $json = preg_replace('/([{,]+)(\s*)([^"]+?)\s*:/','$1"$3":',$json);
    $json = preg_replace('/(,)\s*}$/','}',$json);
    return json_decode($json,$assoc);
}



?>