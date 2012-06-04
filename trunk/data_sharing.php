<?php
/*
 * data_sharing.php
 *
 * A place to define who you would like to share your data with
 *
 */
session_start();

// Are we authorized to view this page?
if ( ! isset($_SESSION['loginID']) )
{
  header('Location: index.php');
  exit();
} 

$loginID = $_SESSION['loginID'];
$message = '';

include 'config.php';
include 'db.php';

// Update permits table if we submitted some data
if ( isset( $_POST['count'] ) ) 
{
  $count = $_POST['count'];

  $query  = "DELETE FROM permits " .
            "WHERE personID = $loginID " .
            "AND instrumentID IS NULL ";    // Only delete the collaborator records
  mysql_query($query) 
        or die( "Query failed : $query<br/>\n" . mysql_error() );

  foreach ( $_POST[ 'cb' ] as $invID => $value )
  {
    if ( $value == 'on' )
    {
      $query = "INSERT INTO permits " .
               "SET personID   = $loginID, " .
               "collaboratorID = $invID, " .
               "instrumentID   = NULL ";
      mysql_query($query) 
            or die( "Query failed : $query<br/>\n" . mysql_error() );
    }
  }

  $message = "Your permit list has been updated.";
}

// Let's get a list of everyone in the database
$names = array();
$IDs   = array();
$query  = "SELECT personID, lname, fname FROM people " .
          "ORDER BY lname, fname ";
$result = mysql_query($query) 
          or die( "Query failed : $query<br/>\n" . mysql_error() );

$count  = mysql_num_rows( $result );
for ( $i = 0; $i < $count; $i++ )
{
  list( $iID, $lname, $fname ) = mysql_fetch_array( $result );
  if ( $iID != $loginID )           // Skip ourself!
  {
    $names[ $i ] = "$lname, $fname";
    $IDs  [ $i ] = $iID;
  }
}

// Let's get a current list of collaborators
$collaborators = array();
$query  = "SELECT collaboratorID FROM permits " .
          "WHERE personID = $loginID " .
          "AND instrumentID IS NULL ";
$result = mysql_query($query) 
          or die( "Query failed : $query<br/>\n" . mysql_error() );

while ( list( $cID ) =  mysql_fetch_array( $result ) )
{
  $collaborators[] = $cID;
}

// Start displaying page
$page_title = "Share Data with Other Investigators";
$js = 'js/data_sharing.js';
include 'header.php';
?>
<!-- Begin page content -->
<div id='content'>

  <h1 class="title">Share Data with Other Investigators</h1>
  <!-- Place page content here -->

  <p>Check other investigators who are permitted to view your reports.</p>

  <?php echo "<p id='message' class='message'>$message</p>\n"; ?>

  <form action='data_sharing.php' method='post'>
  <table class='noborder'>

<?php
  $count--;     // Accounting for ourself
  $rows  = (int) ceil($count / 3.0);
  $extra = $count % 3;

  for ( $i = 0; $i < $rows; $i++ )
  {
    // Entry $e
    $e       = $i;
    $inv     = $IDs[$e];
    $checked = ( in_array( $inv, $collaborators ) ) ? " checked='checked'" : "";
    echo "<tr><td><input type='checkbox' name='cb[$inv]'$checked " .
         "         onclick='reset_message();' /> $names[$e]</td>\n";
  /////////

    $e       = $rows + $i;
    $inv     = $IDs[$e];
    $checked = ( in_array( $inv, $collaborators ) ) ? " checked='checked'" : "";
    if ( $i < $rows-1  ||  $extra != 1 )
    echo "<td><input type='checkbox' name='cb[$inv]'$checked " .
         "     onclick='reset_message();' /> $names[$e]</td>\n";
  /////////
    
    $e = $rows * 2 + $i;
    if ( $extra == 1 ) $e--;
    $inv = $IDs[$e];
    $checked = ( in_array( $inv, $collaborators ) ) ? " checked='checked'" : "";
    
    if ( $i < $rows-1  || $extra == 0 )
    echo "<td><input type='checkbox' name='cb[$inv]'$checked " .
         "     onclick='reset_message();' /> $names[$e]</td>\n";

    echo "</tr>\n";

  }
?>
  </table> 

  <p><input type='hidden' name='count' value='<?php echo $count;?>' />
     <input type="submit" value="Update Settings"/></p>
  </form>

</div>

<?php
include 'footer.php';
exit();
?>
