<?php
/*
 * access.php
 *
 * Who may see a report, an image or an experiment's solution data: its owner,
 *   anyone the owner has granted a permit, and user level 3 and above
 *
 */

// Function to check one owner; NULL means the record has no owner
function may_view_owner( $link, $ownerID )
{
  if ( ! isset( $_SESSION['id'], $_SESSION['userlevel'] ) ) return false;
  if ( $_SESSION['userlevel'] >= 3 ) return true;
  if ( $ownerID === NULL ) return false;

  $myID    = (int) $_SESSION['id'];
  $ownerID = (int) $ownerID;
  if ( $ownerID === $myID ) return true;

  // language=MariaDB
  $query  = "SELECT COUNT(*) FROM permits " .
            "WHERE personID = ? " .
            "AND collaboratorID = ? ";
  $stmt = mysqli_prepare( $link, $query );
  $stmt->bind_param( "ii", $ownerID, $myID );
  $stmt->execute() or die( "Query failed : $query<br />" . $stmt->error );
  $result = $stmt->get_result()
            or die( "Query failed : $query<br />" . $stmt->error );
  list( $count ) = mysqli_fetch_array( $result );
  $result->close();
  $stmt->close();

  return $count > 0;
}

// Function to return the first ID the user may see, or NULL
// The query selects ID and owner, and no rows means the record does not exist
function first_viewable( $link, $query, $types, ...$args )
{
  $stmt = mysqli_prepare( $link, $query );
  $stmt->bind_param( $types, ...$args );
  $stmt->execute() or die( "Query failed : $query<br />" . $stmt->error );
  $result = $stmt->get_result()
            or die( "Query failed : $query<br />" . $stmt->error );
  $rows   = $result->fetch_all( MYSQLI_NUM );
  $result->close();
  $stmt->close();

  foreach ( $rows as list( $ID, $ownerID ) )
    if ( may_view_owner( $link, $ownerID ) ) return $ID;

  return NULL;
}

function may_view_report( $link, $reportID )
{
  // language=MariaDB
  $query  = "SELECT report.reportID, personID " .
            "FROM report LEFT JOIN reportPerson ON report.reportID = reportPerson.reportID " .
            "WHERE report.reportID = ? ";

  return first_viewable( $link, $query, "i", $reportID ) !== NULL;
}

function may_view_triple( $link, $tripleID )
{
  // language=MariaDB
  $query  = "SELECT reportTriple.reportID, personID " .
            "FROM reportTriple LEFT JOIN reportPerson ON reportTriple.reportID = reportPerson.reportID " .
            "WHERE reportTripleID = ? ";

  return first_viewable( $link, $query, "i", $tripleID ) !== NULL;
}

// Returns the report through which the user may see the document, or NULL
function viewable_document_report( $link, $documentID )
{
  // language=MariaDB
  $query  = "SELECT reportTriple.reportID, personID " .
            "FROM documentLink " .
            "JOIN reportTriple ON documentLink.reportTripleID = reportTriple.reportTripleID " .
            "LEFT JOIN reportPerson ON reportTriple.reportID = reportPerson.reportID " .
            "WHERE reportDocumentID = ? " .
            "ORDER BY documentLinkID ";

  return first_viewable( $link, $query, "i", $documentID );
}

function may_view_image( $link, $imageID )
{
  // language=MariaDB
  $query  = "SELECT image.imageID, personID " .
            "FROM image LEFT JOIN imagePerson ON image.imageID = imagePerson.imageID " .
            "WHERE image.imageID = ? ";

  return first_viewable( $link, $query, "i", $imageID ) !== NULL;
}

// The experiment's own owners, or the owner of a report on this triple
function may_view_solution( $link, $experimentID, $triple )
{
  // language=MariaDB
  $query  = "SELECT experiment.experimentID, personID " .
            "FROM experiment LEFT JOIN experimentPerson " .
            "  ON experiment.experimentID = experimentPerson.experimentID " .
            "WHERE experiment.experimentID = ? " .
            "UNION ALL " .
            "SELECT report.experimentID, personID " .
            "FROM report " .
            "JOIN reportTriple ON report.reportID = reportTriple.reportID " .
            "JOIN reportPerson ON report.reportID = reportPerson.reportID " .
            "WHERE report.experimentID = ? " .
            "AND triple = ? ";

  return first_viewable( $link, $query, "iis", $experimentID, $experimentID, $triple ) !== NULL;
}
?>
