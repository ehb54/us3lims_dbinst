<?php
/*
 * data_sharing.php
 *
 * A place to define who you would like to share your data with
 *
 */
include_once 'checkinstance.php';

$loginID = $_SESSION['loginID'];
$message = '';

include 'config.php';
include 'db.php';
// ini_set('display_errors', 'On');

// Update permits table if we submitted some data
if ( isset( $_POST['count'] ) )
{
  $count = $_POST['count'];

  $query  = "DELETE FROM permits " .
            "WHERE personID = ? " .
            "AND instrumentID IS NULL ";    // Only delete the collaborator records
  $args = [ $loginID ];
  $stmt = $link->prepare( $query );
  $stmt->bind_param( 'i', ...$args );
  $stmt->execute()
        or die ("Query failed : $query<br/>" . $stmt->error);
  $stmt->close();
  $query = "INSERT INTO permits " .
      "SET personID   = ?, " .
      "collaboratorID = ?, " .
      "instrumentID   = NULL ";
  $stmt = $link->prepare( $query );

  foreach ( $_POST[ 'cb' ] as $invID => $value )
  {
    if ( $value == 'on' )
    {

      $args = [ $loginID, $invID ];
      $stmt->bind_param( 'ii', ...$args );
      $stmt->execute()
            or die ("Query failed : $query<br/>" . $stmt->error);
    }
  }
  $stmt->close();

  $message = "Your permit list has been updated.";
}

// Let's get a list of everyone in the database
$names = array();
$IDs   = array();
$query  = "SELECT personID, lname, fname FROM people where personID != ?" .
          "ORDER BY lname, fname ";
$stmt = $link->prepare( $query );
$stmt->bind_param( 'i', $loginID );
$stmt->execute()
      or die ("Query failed : $query<br/>" . $stmt->error);
$result = $stmt->get_result()
          or die( "Query failed : $query<br/>\n" . $stmt->error );

$count  = $result->num_rows;
for ( $i = 0; $i < $count; $i++ )
{
  list( $iID, $lname, $fname ) = mysqli_fetch_array( $result );
  if ( $iID != $loginID )           // Skip ourself!
  {
    $names[ $i ] = "$lname, $fname";
    $IDs  [ $i ] = $iID;
  }
}
$result->close();
$stmt->close();

// Let's get a current list of collaborators
$collaborators = array();
$query  = "SELECT collaboratorID FROM permits " .
          "WHERE personID = ? " .
          "AND instrumentID IS NULL ";
$stmt = $link->prepare( $query );
$stmt->bind_param( 'i', $loginID );
$stmt->execute()
      or die ("Query failed : $query<br/>" . $stmt->error);
$result = $stmt->get_result()
          or die ( "Query failed : $query<br />" . $stmt->error );

while ( list( $cID ) =  mysqli_fetch_array( $result ) )
{
  $collaborators[] = $cID;
}
$result->close();
$stmt->close();


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
  </div>

  <p><input type='hidden' name='count' value='<?php echo $count;?>' />
     <input type="submit" value="Update Settings"/></p>
  </form>

</div>

<?php
include 'footer.php';
exit();
?>
