<?php
/*
 * mysql_admin.php
 *
 * Admin's page to enter SQL queries directly
 *
 */
include_once 'checkinstance.php';

if ( $_SESSION['userlevel'] != 5 )    // Super admin only
{
  header('Location: index.php');
  exit();
} 

include 'config.php';
include 'db.php';

$page_title = 'MySQL Administration';
include 'header.php';
?>
<div id='content'>

  <h1 class="title">MySQL Administration</h1>

<?php
if (isset($_POST['do_sql']))
{
  // Process the submitted SQL statement
  $query =  stripslashes( $_POST['query'] );
  $result = mysqli_query( $link, $query ) 
            or die ("Query failed : $query<br />" . mysqli_error($link));

  // Display another SQL input box at the top of the screen
  SQL_input_form($query);

  // Check to see if we have a result
  if (!$result)
        die ("Invalid query : $query<br />" . mysqli_error($link));

  // Check to see if we have any rows
  else if ($result === TRUE)
  {
    // The result of an UPDATE, DELETE, DROP, INSERT, etc.
    $rows = mysqli_affected_rows();
    echo "<p class='margin_c_2em_0em_20em_s_'>$rows rows affected</p>\n";
  }
    
  // Only SELECT, DESCRIBE, etc. here
  else if (mysqli_num_rows($result) < 1)
  {
    echo "<p class='margin_c_2em_0em_20em_s_'>No rows returned</p>\n";
  }

  else
  {
    // Display query results in a table
    $num_cols = mysqli_num_fields($result);
    echo "<table cellspacing='0' class='style1'>\n" .
         "  <thead><tr><th colspan='$num_cols'>Query Result</th></tr></thead>\n" .
         "  <tbody>\n" .
         "  <tr>\n";

    // First, table headers with column names
    for ($i = 0; $i < $num_cols; $i++)
    {
      $field_name = mysqli_field_name($result, $i);
      echo "    <th>$field_name</th>\n";
    }
    echo "</tr>\n";

    // Now the query's returned data
    while ($row = mysqli_fetch_array($result, MYSQLI_NUM))
    {
      echo "<tr>\n";
      foreach ($row as $column)
      {
        echo "  <td>$column</td>\n";
      }
      echo "</tr>\n";
    }
    echo "  </tbody>\n" .
         "</table>\n";
  }
}

else
{
  // First time here, so just display a box for SQL input
  SQL_input_form();

  // Display the table structures
  $query = "SHOW TABLES ";
  $result = mysqli_query( $link, $query ) 
            or die("Query failed : $query<br />" . mysqli_error($link));
  while ($row = mysqli_fetch_array($result, MYSQLI_NUM))
    $tables[] = $row[0];

  // Now we have an array of the table names
  echo "<h4>Table structures:</h4>\n";
  foreach ($tables as $table)
  {
    $query = "DESC $table ";
    $result = mysqli_query( $link, $query ) 
              or die ("Query failed : $query<br />" . mysqli_error($link));
    echo "<table cellspacing='0' cellpadding='4' class='style1'>\n" .
         "  <thead><tr><th colspan='6'>$table</th></tr></thead>\n" .
         "  <tbody>\n" .
         "  <tr>\n" .
         "    <th>Field</th>\n" .
         "    <th>Type</th>\n" .
         "    <th>Null</th>\n" .
         "    <th>Key</th>\n" .
         "    <th>Default</th>\n" .
         "    <th>Extra</th>\n" .
         "  </tr>\n";
    while ($row = mysqli_fetch_array($result))
    {
      echo "  <tr>\n" .
           "    <td>$row[0]</td>\n" .
           "    <td>$row[1]</td>\n" .
           "    <td>$row[2]</td>\n" .
           "    <td>$row[3]</td>\n" .
           "    <td>$row[4]</td>\n" .
           "    <td>$row[5]</td>\n" .
           "  </tr>\n";
    }
    echo "  </tbody>\n" .
         "</table>\n";
  }

}
?>
 
</div>

<?php
include 'footer.php';
exit();

function SQL_input_form($last_query = "")
{
    echo "<form action='$_SERVER[PHP_SELF]' method='post'>\n"
        . "<p>mysql> <br />"
        . "<textarea name='query' rows='6' cols='65' wrap='virtual'>$last_query</textarea><br />\n"
        . "<input type='submit' name='do_sql' value='Submit' />\n"
        . "</p></form>\n";
}
?>
