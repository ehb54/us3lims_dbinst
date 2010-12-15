// JavaScript routines for queue_viewer.php

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

