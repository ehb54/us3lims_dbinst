<?php

include 'config.php';

$link = mysql_connect( $dbhost, $dbusername, $dbpasswd ) 
        or die("Could not connect to database server.");

mysql_select_db($dbname, $link) 
        or die("Could not select database. " .
               "Please ask your Database Administrator for help.");
?>
