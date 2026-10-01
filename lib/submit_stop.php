<?php
// Renders a "not submitted" page for $submit_method and $submit_stop_msg (HTML).
// Included at the top level of a submit page, which then returns (CLI) or exits.
$page_title = "$submit_method Analysis Not Submitted";

if ( $is_cli )
{
  $cli_errors[] = "ERROR: $page_title: " . strip_tags( $submit_stop_msg );
  echo end( $cli_errors ) . "\n";
}
else
{
  include 'header.php';
  echo <<<HTML
<div id='content'>
  <h1 class="title">$page_title</h1>
  <p>$submit_stop_msg</p>
  <p><a href="queue_setup_1.php">Submit another request</a></p>
</div>
HTML;
  include 'footer.php';
}
