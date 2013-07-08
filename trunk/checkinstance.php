<?php
/*
 * checkinstance.php
 *
 * Check that a session is set and that it is for the current DB instance
 *
 */
session_start();

include 'config.php';

// Are we currently logged in?
if ( ! isset($_SESSION['id']) )
{
  header('Location: index.php');
  exit();
} 

// Is the current instance the same as the one logged into
if ( isset($_SESSION['instance']) )
{
  if ( $_SESSION['instance'] != $dbname )
  {
    $message = "Error: Already logged into " . $_SESSION['instance']
       . ", not " . $dbname . " !&nbsp;&nbsp;&nbsp;Logout first!!";
    header('Location: index.php');
    exit();
  }
}

