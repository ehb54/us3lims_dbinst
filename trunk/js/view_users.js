// JavaScript routines for edit_users.php

// Pick up change of user listbox and redirect page
function get_person(control)
{
    var id = control.value;
    location.href = 'view_users.php?personID=' + id;
}

