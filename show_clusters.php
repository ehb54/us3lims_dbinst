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
<div>
    <?php
    // Add rss information from TACC
    require_once 'lib/rss_fetch.inc';

    //$time8=microtime(true) - $time0;
    $url = 'http://www.tacc.utexas.edu/rss/TACCUserNews.xml';
    $num_items = 5;

    $rss = fetch_rss($url);
    echo "<h3>{$rss->channel['title']}</h3>\n";
    $items = array_slice($rss->items, 0, $num_items);
    //  $items = array();
    // Generate table
    //echo print_r($items, true);
    echo "<table cellpadding='7' cellspacing='0'>\n";
    foreach ( $items as $item )
    {
        $title       = $item['title'] ?? "";
        $url         = $item['link'] ?? "";
        $description = $item['description'] ?? "";
        echo "<tr><td><a href=$url>$title</a></td> <td>$description</td></tr>";

    }
    echo "</table>\n";


    //$time9=microtime(true) - $time0;
    //echo "  <p>time8 = $time8 </p>";
    //echo "  <p>time9 = $time9 </p>";
    ?>
</div>
</div>
<?php include 'footer.php'; ?>
