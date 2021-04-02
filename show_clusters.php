<?php
/*
 * show_clusters.php
 *
 * Show cluster info:
 *
 */
include_once 'checkinstance.php';
include 'header.php';
include 'lib/utility.php';
?>

<div id='content'>
	<h1 class="title">Current Status of Clusters:</h1>
<p/>
<?php echo showClusters(); ?>
</div>


<?php include 'footer.php'; ?>
