<?php
// Renders the outcome of a repeated metascheduler submission for $submit_method:
// the original receipt from $dispatch_prior['receipt'], or the refusal in
// $dispatch_prior['error']. Included at the top level of a submit page, which
// then returns (CLI) or exits. A repeat is never a new submission.
$dispatch_refused = isset( $dispatch_prior['error'] );
$page_title = "$submit_method Analysis " . ( $dispatch_refused ? 'Not Submitted' : 'Received' );
$dispatch_text = $dispatch_refused
  ? htmlspecialchars( $dispatch_prior['error'], ENT_QUOTES )
  : dispatch_intake_receipt_html( $dispatch_prior['receipt'], $GLOBALS['dispatch_intake_pool'] ) . "</pre>\n";

if ( $is_cli )
{
  if ( $dispatch_refused )
    $cli_errors[] = "ERROR: $page_title: " . $dispatch_prior['error'];
  echo "$page_title\n" . strip_tags( $dispatch_text ) . "\n";
}
else
{
  include 'header.php';
  echo <<<HTML
<div id='content'>
  <h1 class="title">$page_title</h1>
  <p>$dispatch_text</p>
  <p><a href="queue_setup_1.php">Submit another request</a></p>
</div>
HTML;
  include $common_dir . 'footer.php';
}
