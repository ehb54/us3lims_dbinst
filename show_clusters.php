<?php
/*
 * show_clusters.php
 *
 * Show cluster info:
 *
 */
$page_title = 'Clusters';
include_once 'checkinstance.php';
include 'header.php';
include 'lib/utility.php';
?>

<div id='content'>
	<h1 class="title">Current Status of Clusters:</h1>
<p/>
<div>
<?php echo showClusters(); ?>
</div>
</div>
<?php include 'footer.php'; ?>
