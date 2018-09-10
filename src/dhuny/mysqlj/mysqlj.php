<?php 
namespace dhuny\mysqlj;
class mysqlj
{
	
/*Imported data types */

	public $filename;
	public $phpHost;
	public $dbname;
	public $jtables;
	public $isCached;
	private $numQueriesOnPage = 0; // Number of opened queries this connection has. Allow us to load seperare files.
	protected $datadir;
	public $cacheValid = false;
	protected $result;
	protected $debugInfo = array();
	
	public  static $mysql_data_type_hash = array(1=>'tinyint',2=>'smallint',3=>'int',4=>'float',5=>'double',7=>'timestamp',8=>'bigint',9=>'mediumint',10=>'date',11=>'time',12=>'datetime',13=>'year',16=>'bit',
	//252 is currently mapped to all text and blob types (MySQL 5.0.51a)
	252=> 'text',253=>'varchar',254=>'char',246=>'decimal');

public  static $sqlite_data_type = array(
    1=>'INTEGER',
    2=>'INTEGER',3=>'INTEGER',4=>'REAL',5=>'REAL',7=>'NUMERIC',8=>'bigint',9=>'mediumint',10=>'NUMERIC',11=>'NUMERIC',12=>'NUMERIC',13=>'NUMERIC',16=>'NUMERIC',
    //252 is currently mapped to all text and blob types (MySQL 5.0.51a)
	252=> 'TEXT',253=>'TEXT',254=>'TEXT',246=>'NUMERIC');


 public $mysql_flags_hash = array(1 => 'Not NULL',2=>'PRIMARY KEY',4=>'UNIQUE KEY',8=>'MULTIPLE KEY FLAG (Part of Key)',16=>'Blob',32=>'UNSIGNED',64=>'ZERO FILL',128 => 'BINARY',256=> 'ENUM',512=> 'AUTO INCREMENT',1024=>'TIMESTAMP'
 /*
 NOT_NULL_FLAG=>1,         /* Field can't be NULL 
PRI_KEY_FLAG=>2,         /* Field is part of a primary key 
UNIQUE_KEY_FLAG=>4,         /* Field is part of a unique key 
MULTIPLE_KEY_FLAG=>8,         /* Field is part of a key 
BLOB_FLAG=>16,        /* Field is a blob 
UNSIGNED_FLAG=>32,         /* Field is unsigned 
ZEROFILL_FLAG=>64,         /* Field is zerofill 
BINARY_FLAG=>128,         /* Field is binary  
ENUM_FLAG=>256,         /* field is an enum 
AUTO_INCREMENT_FLAG=>512,         /* field is a autoincrement field 
TIMESTAMP_FLAG=>1024         /* Field is a timestamp 
*/
 );
 public $exportOptions = "";



/*End of Imported data types  */	
	
protected $_connection;
/* The construct is modified to include a refresh in seconds
Purpose is to requery database after period for any changes.
*/
public function __construct($host, $user, $pwd, $db,$refresh=0)
{
   		$args = func_get_args();
		//$filename::setfileName("".$args[3]."");
		 $this->phpHost = ($_SERVER['SERVER_NAME'])."_".($args[3]);
		 $this->dbname = ($args[3]);
		 $this->filename = (basename($_SERVER['SCRIPT_NAME']));
		 $filename = $this->filename.".js";
		 
		 if((file_exists($this->filename.".js")))
		 {
	// if file exists open file and extract database structure.	
	//VERY IMP = Data stored in line 2 and Results in line 11.
	//  Calling will mean start counting from 0. so Data = line 1 and result = line 10.		 
	$docs = new \SplFileObject($this->filename.".js");
	$textfile = $docs->openFile();
	$OfflineExport = $textfile->seek(1);
	$OfflineExport = $textfile->current();
	$OfflineExport = ltrim($OfflineExport,"var data ='");
	$OfflineExport = str_replace("]';","]",$OfflineExport); 
	$OfflineExport = json_decode($OfflineExport);
	//var_dump($OfflineExport);
	//swap the values within the array as the order is incorrect for Drop in replacement for DBStructure function.
	$temp = $OfflineExport[0];
	$OfflineExport[0] = $OfflineExport[4];
	$OfflineExport[4] = $temp;
	$this->exportOptions = $OfflineExport;
	$this->isCacheConsistent(10);
	
	if($this->isCacheConsistent(10))  //Here we compare if timestamp of file to check if cache valid
	{
		echo("\n Cache is valid Pulling data from JS file and loading into PHP \n <br>");
		// If cache valid we will load data from text file immediately.
		$this->cacheValid = true;
		$textfile->seek(10); // read line 11 from textfile.
		$result = $textfile->current();
		//var_dump($result);
		$result = ltrim($result,"var results ='");
		//$result = rtrim($result,',null]\';"');
		$result = substr($result, 0, -3); // Very Important: Presence of Ending Space was causing error in file.
		//$result = str_replace("]';","]",$result);  //  Previous line is replacing for testing
		$result = json_decode($result);
		//unset($result[count($result) - 1]);
		//var_dump($result);
		$this->result = $result;
		array_push($OfflineExport,true); // set Cache Valid to true;
		//var_dump($result);
			//echo("<h4>Offline Export CacheValid = True & File Exists</h4>");
			
		//	var_dump($this->exportOptions);
	}
	else
	{
		//	echo("/n Cache is NOT valid /n");
			$this->cacheValid = false;
			array_push($OfflineExport,false);
			//echo("<h4>Offline Export CacheValid = False & File Exists</h4>");
			$this->exportOptions = $OfflineExport;
			//var_dump($this->exportOptions);
			$this->_connection = @new \mysqli($host, $user, $pwd, $db);
	} // End of timestamp comparison
			
	
}else
{
      // IF JS file does not exist we will have to create all from scratch.
	         $this->cacheValid = false;	
     // For the case of File does not exist, the exportOptions will be empty as we need to create them. So, the CacheValid option of false is sent for use only in the query module
			//echo("<h4>Offline Export CacheValid = False & File Does not Exist</h4>");
			//$this->exportOptions = $OfflineExport;	
			//var_dump($this->exportOptions);		 		
			// echo("<h2>FileName Does not Exists</h2>");			 
		$this->_connection = @new \mysqli($host, $user, $pwd, $db);
		
		if (\mysqli_connect_errno()) {
throw new RuntimeException('Cannot access database: '. \mysqli_connect_error());	
		 }
			


}

//var_dump($this->debugInfo);
} // end of constructor

public function info()
{
	return $this->debugInfo;
	}

public function isCacheConsistent($secs=0)
{
//echo("<br>Start of is Cache Consistent</br>");
	//var_dump($this->exportOptions);
	$time_start = microtime(true);
	$cache_Valid = true;
	$phpfile = $this->filename;
	$phptables = (array) $this->exportOptions[3];
	//echo("Display Array: <br>");
	//var_dump($phptables);
	$no_tables = sizeof($this->exportOptions[3]);
	//var_dump($this->exportOptions);
	$MemPath = "/mnt/ramdisk/".$this->dbname."/"; // To get only DBName.
	$MemPath = "/mnt/ramdisk/".$this->exportOptions[0][0]."/";
	$RamTable = array();
	//echo($MemPath);
	// start of if File Exists
if(!file_exists( $phpfile.".js" ))
	{
		array_push($this->debugInfo,".js file does not Exist");
	//echo($phpfile.".js File does not Exists<br>");
	// return false; // Let the equation know it has to regenerate the JS file.
		}
		else // else for if file exists
		{
	// echo($phpfile.".js File Exists<br>");
	array_push($this->debugInfo,".js file Exist");
$files = new \RecursiveDirectoryIterator('./',\FilesystemIterator::UNIX_PATHS);
$files = new filterfiles($files,$phptables);

//$files = new RecursiveIteratorIterator($files);
//var_dump(($files));

$maxTime = 0;
foreach ($files as $file) {
//echo $file->getFileName()." ".$file->getATime()." ".$file->getMTime(). '<br>';	
//echo $file->getFileName()." ".date('r',($file->getATime()))." ".date('r',($file->getMTime())). '<br>';
array_push($RamTable,$file->getFileName());

if($maxTime < $file->getMTime())	
{
	$maxTime = $file->getMTime();
	}
} // end of for each loop.

$curtime = filemtime($phpfile.".js");
//echo "<br> Current start Time: ".$curtime."<br>";

//echo "Max Time: ".$maxTime." ".date('r',($maxTime));
	
	$phptsp = filemtime($phpfile.".js");
	//echo("<br>phptsp: ".$phptsp." ".date('r',($phptsp)));
			// end of if file exists initially line 210
	//echo("<br> Hello:".($phptsp-$maxTime)."s = ".($phptsp-$maxTime)/(60*60)." hrs");	
	//echo("<br>RamTable:</br>");
	//var_dump($RamTable);

	//echo("<br>End RamTable:</br>");	
	
	if(count($phptables)<> count($RamTable))
	{
			$MissingTable = array_diff($phptables,$RamTable);
		echo("<br> The Timestamp for".json_encode($MissingTable)." is missing<br>We are creating them for the first time ignore the warnings. Refresh for performance gains");
		
		foreach($phptables as $missingfile)
		{
			//echo($missingfile);
			}
		
		               if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {  // detect if Windows
    				echo 'This is a server using Windows!';
							foreach($phptables as $missingfile)	{
									exec(`php NUL > $missingfile`);
									}
					
					} else {
   				 echo 'This is a server not using Windows!';
				foreach($phptables as $missingfile)	{touch($missingfile);}
				}
		
		
		
		return false;
	}
	else
	{
		// do nothing let the codes proceed to next level.
		// The base directory is touched to create a file with name of table. Changes on table are monitored for updates.
		// The Variable RamTable is used to detect the name of the touched filed created.
		}
		
	if($phptsp-$maxTime < $secs)
	{
		echo("<b style='font-size:0.6em;color:Orange;font-weight:normal;'> phpfile is not up to date.Using mysqli We will create a JS file with same name as PHP file. You can use this JS file in future client side for offline data usage </b><br>");
		array_push($this->debugInfo,"phpfile is not up to date.Using mysqli. We will create an offline JS file with the data");
		return false;
		}
		else
		{
		echo("<b style='font-size:0.6em;color:green;font-weight:normal;'> phpfile is up to date, using mysqlj. We are retrieving the data from a JS file having same name as your PHP file. You can use this file in future for client side offline storage</b><br>");
		array_push($this->debugInfo,"phpfile is up to date, using mysqlj");
		return true;
			}
			
			}

/*
echo("<br>Database:".$this->exportOptions[0][0]);
	for($t=0;$t<$no_tables;$t++)
	{
		$memtsp = "/mnt/ramdisk/".$this->exportOptions[0][0]."/".$this->exportOptions[3][$t];
		echo("<br>File Path:".$memtsp);
		echo("<br>TimeStamp:".filemtime($memtsp));
		}
	echo("<br>table:");
	//$datadir = $this->exportValues[4][3];
	$current_php = filemtime($phpfile);
	$current_js = filemtime($phpfile.".js");
	echo("<br>The File is ".$phpfile);
	echo("<br>The Timestamp for PHP is : ".date ("F d Y H:i:s.", filemtime($this->exportValues[4][2])));
	echo("<br>The Timestamp for JS is : ".date ("F d Y H:i:s.", filemtime($this->exportValues[4][2].".js")));
	echo("<br>".filemtime($this->exportValues[4][2].".js"));
	*/
//	End of interfering part*/	
}

public function query($sql)
{
// To add function to query mysqlj table, if updated then skip the 
//query loading and load content from JS. Else use the mysqlj_result to load.
//var_dump($this->cacheValid);
if($this->cacheValid == true){
	

$results = new mysqlj_cache($this->result); //
}else
{
	// if there is no file. This means no Export Ever done
$this->exportOptions = ($this->dbStructure($sql));
array_push($this->exportOptions,false);
$results = new mysqlj_result($sql, $this->_connection,$this->exportOptions);
}


//echo("<h2>Results</h2>");
//($results->next());

//$this->exportFile($this->phpHost,$this->jtables,$this->exportOptions[1],$results,$this->filename.".js");
//echo("<h3>FULL RESULTS</h3>");
return $results;
//$textfile = $docs->closeFile();
}

public function __destruct()
{
//var_dump(mysqlj_result->array_result());
if($this->cacheValid ==false){
$this->_connection->close();

}
}




public function setDatadir()
{
		$data2 = $this->_connection->query("SHOW VARIABLES WHERE Variable_Name = 'datadir';");
	$row2 = $data2->fetch_array();
	$this->datadir = $row2["Value"].$this->dbname.DIRECTORY_SEPARATOR;
	}

public function returnDatadir(){
	return $this->datadir;
	}







public function dbStructure($sql)
{
/* The DB structure will take the SQL as input and will query the fields to return the 
data type of fields + the Unique Key of field. These values are returned as an array so 
that it can be used in insert, edit and delete functions
return db,table, fields[0],fields[1] & constraint ,+ table names
*/
	/* This function will return the fields and the tables required for the db creation
	 The values will then be passed on to ExportDB.
	 Separating this will allow us to check for presence of fields rather than to recreate
	 structure each and every time. This will provide performance enhancement.
	 It would be wiser to allow output of SQL statement to create Db in Sqllite.
	 This function could be used to export results to SQLite then compress and send
	 Db to client. Impact. At client Side SQL can be queried at easily for results.
	 Codes should include If Client Side, use JS else, use PHP to query client instead 
	 of main Db.
	 
	 Return values are as follows: a[0] = Concat of table names
	                               a[1] = array of fields + unique field values
								   a[2] = unique field as listed in SQLite Manual
	 */
	 
	 $result2 = $this->_connection->query($sql);
	 
	 $finfo = $result2->fetch_fields();
	// echo("file Info");
		//var_dump($finfo);
		$table = array();
		$fields = array();
		$field_type = array();
		$flags = array();
		$type = array();
		$unique_field = array();
		foreach ($finfo as $val) {
		array_push($table,$val->table);
		array_push($flags,$val->flags);
	//	echo("Type Style ".mysqlj::$mysql_data_type_hash[$val->type]." <br>");
		array_push($type,$val->type);
	//	var_dump(mysqlj::DB_flags($val->flags));
	//	array_push($field_type,mysqlj::$sqlite_data_type[$val->type]);
		$flag_string = mysqlj::$sqlite_data_type[$val->type];
		//var_dump($b);
		if((\MYSQLI_NOT_NULL_FLAG & $val->flags) ) //NOT_NULL flag = 1;
		{
			$flag_string .= " NOT NULL";
		}
		if((\MYSQLI_PRI_KEY_FLAG & $val->flags) || (\MYSQLI_UNIQUE_KEY_FLAG & $val->flags) || (\MYSQLI_MULTIPLE_KEY_FLAG & $val->flags) || (\MYSQLI_PART_KEY_FLAG & $val->flags) || (\MYSQLI_GROUP_FLAG & $val->flags) ) //Primary key flag = 2;
		{
		//array_push($fields,$val->table.".".$val->name." <h1>Primary Key</h1>");
		//$flag_string = "Unique"." ".$flag_string;
		array_push($unique_field,$val->name);
		//array_push($fields,$val->name." ".$flag_string);
		array_push($fields,array($val->name,$flag_string)); 
		array_push($field_type,$flag_string);
		}else{
		//array_push($fields,$val->table.".".$val->name);
		//array_push($fields,$val->name." ".$flag_string);
		array_push($fields,array($val->name,$flag_string)); 
		array_push($field_type,$flag_string);
		}
    }
 //	echo("JSON Encode: ".json_encode($fields));
	// var_dump($fields);
	//echo(DB_flags($flags));
//	var_dump($type);
	//var_dump($field_type);
	//var_dump($unique_field);
	$unique_field_text = "Unique(".implode(",",$unique_field).")";
//	echo "$unique_field_text"."<br>";
	$table = array_unique($table);
	//echo(implode("_",$unique_field));
	// return db,table, fields[0],fields[1] & constraint
	$datafields = array($this->phpHost,implode("_",$table),$this->filename,$this->datadir);
	// on 02/02/17 datadields removed as its usage became useless.
	$datafields = array($this->phpHost,implode("_",$table),$this->filename);
	//echo("From DB Structure<br>");
	//var_dump($datafields);
	 return array(implode("_",$unique_field),$fields,$unique_field_text,$table,$datafields);
	}

/* End of DbStructure  */	


/*The bracket below ends the class mysqlj*/
}


/*General function outside the class definition */


?>
