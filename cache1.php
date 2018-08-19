<?php require_once('Connections/conn.php');  //USe file to SET DB Connections
//This sample code is using MySQL Employees DB as Example to cache data as Web SQL storage for further usage.
//You may visit   https://github.com/datacharmer/test_db or alternative Oracle mySQL Sample database or use any exiting db and write query sample below.
?>
<?php
$time_start = microtime(true);

require 'vendor/autoload.php';
use dhuny\mysqlj\mysqlj;
use dhuny\mysqlj\mysqlj_result;
use dhuny\mysqlj\mysqlj_cache;
use dhuny\mysqlj\filterfiles;

?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>POS test</title>
</head>

<body>
<?php
try{
$conn = new mysqlj($hostname_conn,$username_conn,$password_conn,$database_conn);
$result = $conn->query("SELECT SQL_NO_CACHE employees.emp_no,title, first_name,dept_emp.dept_no, last_name,gender,salary,salaries.from_date FROM salaries,employees,titles,dept_emp WHERE (salaries.from_date >= '2002-05-01 00:00:00') AND (salaries.from_date <= '2002-05-31 00:00:00') and (employees.emp_no = titles.emp_no) and (dept_emp.emp_no = employees.emp_no) and (dept_emp.from_date >= '2002-05-01 00:00:00') and (dept_emp.from_date >= '2002-05-31 00:00:00') limit 0,2000");


while($row = $result->fetch_assoc())
//while($row = $result->cacheAll3(MYSQL_ASSOC))
{
 echo $row['emp_no']." ".$row['title']."  ".$row['first_name']." ".$row['last_name']." ".$row['salary']." ".$row['dept_no']."<br>";
//echo($row->emp_no);
//var_dump($row);
}

/* Leave here to test for rows.
while($row = $result->fetch_row())
//while($row = $result->cacheAll3(MYSQL_ASSOC))
{
 echo $row[0]." ".$row[1]."  ".$row[2]." ".$row[3]."<br>";
}
*/

} catch (Exception $e) {
// echo 'This is an ordinary Exception: ' . $e->getMessage();
}
//var_dump($result->array_result());
//$mysqlj->close();

$time_end = microtime(true);
$time = $time_end - $time_start;	    
echo "\n<br><b style='font-size:0.6em;color:Orange;font-weight:normal;'> Generated in $time seconds\n</b>";
?>



</body>
</html>
