<?php

include 'config.php';

$link = mysqli_connect( $dbhost, $dbusername, $dbpasswd, $dbname )
        or die("Could not connect to $dbname on database server.");

?>
