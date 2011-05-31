<?php
/*
 * display_image.php
 *
 * Displays an image passed to it
 *
 */

if ( ! isset( $_GET['file'] ) )
  exit();

$file = $_GET['file'];

echo <<<HTML
<html>
<head>
  <title>Image Detail</title>
</head>

<body>

<div>
  <img src='$file' />
</div>

</body>
</html>

HTML;
?>
