/* main.js
 *
 * JavaScript routines available to all scripts
 *
 */

function construction()
{
  alert( "That page is under construction. Please check back later!" );

  return false;
}

// Function to open a new window to display a file 
function get_detail(file)
{
   window.open(file,
               "convert",
               "toobar=no,location=no,directories=no,status=no," +
               "scrollbars=yes,resizable=yes,copyhistory=no,"    +
               "width=480,height=640,title=Application Detail"   );
}

// Function to display a graphic file in a new window
function show_image(file)
{
   window.open("display_image.php?file=" + file,
               "convert",
               "toobar=no,location=no,directories=no,status=no," +
               "scrollbars=yes,resizable=yes,copyhistory=no,"    +
               "width=800,height=600,title=Image Detail"   );
}

// Function to toggle the display of a division
function toggleDisplay( whichDiv, anchorID, anchorContent )
{
  if ( document.getElementById )
  {
    // This is the way the standards work
    var div = document.getElementById(whichDiv);
    var style2 = document.getElementById(whichDiv).style;
    // alert("toggle: style2=" + style2.display + ";div=" + whichDiv );
    style2.display = style2.display ? "" : "block";

    var anchor = document.getElementById( anchorID );
    if ( style2.display == 'block' )
    {
      if ( document.all )
        anchor.innerHTML = "Hide " + anchorContent;
      else
        anchor.textContent = "Hide " + anchorContent;
    }

    else
    {
      if ( document.all )
        anchor.innerHTML = "Show " + anchorContent;
      else
        anchor.textContent = "Show " + anchorContent;
    }

  }

  else if (document.all)
  {
    // This is the way old msie versions work
    var style2 = document.all[whichDiv].style;
    style2.display = style2.display ? "" : "block";

    var content = document.all[anchorID];
  }

  else if (document.layers)
  {
    // This is the way nn4 works
    var style2 = document.layers[whichDiv].style;
    style2.display = style2.display ? "" : "block";
  }
}

