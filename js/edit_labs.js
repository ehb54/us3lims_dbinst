// JavaScript routines for edit_labs.php

// Pick up change of user listbox and redirect page
function get_lab(control)
{
    var id = control.value;
    location.href = 'edit_labs.php?ID=' + id;
}

