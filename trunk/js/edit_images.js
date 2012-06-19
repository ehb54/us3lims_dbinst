// JavaScript routines for edit_images.php

// Pick up change of user listbox and redirect page
function get_image(control)
{
    var id = control.value;
    location.href = 'edit_images.php?ID=' + id;
}

// jQuery image-type controls
$(document).ready(function()
{
  // imageBuffer setup
  $("#imageBuffer").change( function()
  {
    if ( $("#imageBuffer").is(":checked") )
    {
       $("#imageLink").load( 'image_linkInfo.php?type=buffer' );
    }

  });

  // imageAnalyte setup
  $("#imageAnalyte").change( function()
  {
    if ( $("#imageAnalyte").is(":checked") )
    {
       $("#imageLink").load( 'image_linkInfo.php?type=analyte' );
    }

  });

  // imageSolution setup
  $("#imageSolution").change( function()
  {
    if ( $("#imageSolution").is(":checked") )
    {
       $("#imageLink").load( 'image_linkInfo.php?type=solution' );
    }

  });
});

