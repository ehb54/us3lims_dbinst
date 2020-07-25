<?php
/*
 * create_captcha.php
 *
 * Creates a captcha text string onto a jpeg background and sends it back
 *
 */
session_start();

if ( ! isset( $_SESSION['captcha'] ) )
{
  header( 'Content-Type: image/jpeg' );
  echo "";
  exit();
}

$captcha = $_SESSION['captcha'];
//$font    = '/usr/share/fonts/liberation/LiberationSans-BoldItalic.ttf';

// add any number of paths here
// alternatively we could use fontconfig to look it up - but this way seems simple enough

$font_check = array(
    '/usr/share/fonts/liberation/LiberationSans-BoldItalic.ttf',
    '/usr/share/fonts/liberation-sans/LiberationSans-BoldItalic.ttf',
    '/usr/share/fonts/truetype/liberation/LiberationSans-BoldItalic.ttf'
    );

$font = "font_missing";

foreach ( $font_check as $v ) {
    if ( file_exists( $v ) ) {
        $font = $v;
        break;
    }
}

if ( $font === "font_missing" ) {
    error_log( "could not find a font for captcha" );
}

$size      = 60;

/* Create Imagick object from background file*/
$image = new Imagick( 'cap_bg.jpg' );

/* Create a drawing object and set the font size */
$ImagickDraw = new ImagickDraw();
$ImagickDraw->setFont( $font );
$ImagickDraw->setFontSize( $size );

// Figure out position
$bbox = $image->queryFontMetrics( $ImagickDraw, $captcha );

$x  = ( 320 - $bbox['textWidth'] ) / 2 - 5 ; // the distance from left
$y  = ( 200 - $bbox['textHeight'] ) / 2 + $bbox['textHeight'] - 15; // from top to baseline

settype( $x, 'int' );
settype( $y, 'integer' );

// Change the font color
$colors    = array();
$colors[]  = 'black';
$colors[]  = '#2B4E72';       // dark blue
$colors[]  = '#4E4E4E';       // gray
$colors[]  = '#2790B0';       // teal
$colors[]  = '#00FFFF';       // cyan
srand( make_seed() );
$ndx       = rand( 0, 4 );
$color     = $colors[ $ndx ];
$ImagickDraw->setFillColor( $color );
$ImagickDraw->setFillOpacity( 1.0 );   // 100%

/* Write the text on the image */
$image->annotateImage( $ImagickDraw, $x, $y, 0, $captcha );

/* Add some swirl */
$image->swirlImage( 30 );

/* Draw the ImagickDraw object contents to the image. */
$image->drawImage( $ImagickDraw );

/* Give the image a format */
$image->setImageFormat( 'png' );

/* Send headers and output the image */
header( "Content-Type: image/{$image->getImageFormat()}" );
echo $image->getImageBlob( );

$ImagickDraw->destroy();
$image->destroy();
exit();

function make_seed()
{
  list($usec, $sec) = explode(' ', microtime());
  return (float) $sec + ((float) $usec * 100000);
}
?>
