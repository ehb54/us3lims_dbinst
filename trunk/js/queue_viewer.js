// JavaScript routines for queue_viewer.php

// A jQuery function that will update the queue content every 20 sec or so
// Another advantage is that the next call of this function will only
//   occur after previous one has completed.
function update_queue_content()
{
  $.ajax(
  {
    url:      'queue_content.php',
    success:  function( data )
              {
                $('#queue_content').html( data );
              },
    complete: function()
              {
                setTimeout( update_queue_content, 20000 );
              }
  })
}

function show_info( jobid )
{
  more_info  = document.getElementById( "more_info" + jobid );
  info       = document.getElementById( "info" + jobid );

  if ( info.style.display == 'block' ) 
  {  
    if ( document.all )  // old IE
      more_info.innerHTML = "More Info";
    else
      more_info.textContent = "More Info";

    info.style.display = 'none';
  }
  else
  {
    if ( document.all )  // old IE
      more_info.innerHTML = "Hide Info";
    else
      more_info.textContent = "Hide Info";

    info.style.display = 'block';
  }

  return false;
}

