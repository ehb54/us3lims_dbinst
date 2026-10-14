<?php
// Included at the top level of a submit page before anything is written: with
// no cluster selected, renders a "not submitted" page and sets $submit_stopped.
//
// lib/utility.php's cluster fieldset lists only clusters in the user's
// clusterAuthorizations, so an account authorized for no configured cluster
// posts no 'cluster' and the job would otherwise be reported as accepted.
$submit_stopped = false;
if ( ! isset( $_SESSION['cluster'] ) )
{
  $submit_stop_msg = "<b>No cluster was selected, so nothing was submitted.</b><br/>"
                   . "If no clusters were offered on the previous page, this account is not "
                   . "authorized for any configured cluster. Ask your administrator to check "
                   . "its cluster authorizations.";
  include __DIR__ . '/submit_stop.php';
  $submit_stopped = true;
}
