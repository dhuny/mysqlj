# mysqlj
A php / mysql library to convert mysqli into a multi-level reusable static query cache for HTML5 service workers and offline data usage


# Installation

Install the latest version with

$ composer require dhuny/mysqlj:dev-master

#Basic Usage

Include the following codes in your PHP  

require 'vendor/autoload.php';
use dhuny\mysqlj\mysqlj;
use dhuny\mysqlj\mysqlj_result;
use dhuny\mysqlj\mysqlj_cache;
use dhuny\mysqlj\filterfiles;

# Application of the Library

Replace your mysqli library connection with a mysqlj one.
i.e. Replace

$conn = new mysqli($hostname_conn,$username_conn,$password_conn,$database_conn);

by

$conn = new mysqlj($hostname_conn,$username_conn,$password_conn,$database_conn);


# Use of MySQLj

The mySQLj is a library that reads the DB queries and converts it into an apprpriate Web SQL database [or easily modifyiable to IndexedDB]. The Converted DB is saved in a js file bearing the same name as the php file. The JS file can then be recalled by the server to load the data from file rather than re-querying the DB server or pass that to the client to create a Multi level reusable static query cache for HTML5 service workers and offline data storage.   


# Testing the codes

To Test the codes, pull same using composer [composer require dhuny/mysqlj:dev-master]. The vendor/dhuny/mysqlj/ contains some sample codes for testing purposes. Cut Connections folder, cache*.php and existing*.php. Paste them in the root folder of your project.
Download and install Oracle Sample Employees Database https://github.com/datacharmer/test_db
Open Connections/ conn and set up your DB connections

Run Cache*.php to test code.

This code is still experimental and currently in use as part of a research work referenced under IEEE Explore as [https://ieeexplore.ieee.org/document/8079969/]. 
Library may be currently inappropriate for production as only a few mysqli classes were extended. 

# Notes
For queries, bugs and contributions the author can be contacted via <riyad@dhuny.org>
