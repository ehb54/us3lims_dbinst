// JavaScript routines for edit_projects.php

// Pick up change of user listbox and redirect page
function get_project(control)
{
    var id = control.value;
    location.href = 'edit_projects.php?ID=' + id;
}

function trim(stringToTrim)
{
    // Remove leading and trailing white space
    return stringToTrim.replace( /^\s+|\s+$/g, "" );
}

function validate( form )
{
  var msg    = "";
  var errors = 0;

  // First name is required
  if ( trim( form.firstName.value ) == "" )
  {
    msg += "--first name is missing\n";
    errors++;
  }

  // Last name is required
  if ( trim( form.lastName.value ) == "" )
  {
    msg += "--last name is missing\n";
    errors++;
  }

  // Organization is required
  if ( trim( form.organization.value ) == "" )
  {
    msg += "--organization is missing\n";
    errors++;
  }

  // Address is required
  if ( trim( form.address.value ) == "" )
  {
    msg += "--address is missing\n";
    errors++;
  }

  // City is required
  if ( trim( form.city.value ) == "" )
  {
    msg += "--city is missing\n";
    errors++;
  }

  // State is required
  if ( trim( form.state.value ) == "" )
  {
    msg += "--state or province is missing\n";
    errors++;
  }

  // Zip is required
  if ( trim( form.zip.value ) == "" )
  {
    msg += "--postal code or zip is missing\n";
    errors++;
  }

  // Country is required
  if ( trim( form.country.value ) == "" )
  {
    msg += "--country is missing\n";
    errors++;
  }

  // Phone is required
  if ( trim( form.phone.value ) == "" )
  {
    msg += "--phone is missing\n";
    errors++;
  }

  // Email address is required
  if ( trim( form.email.value ) == "")
  {
    msg += "Email Address not specified\n";
    errors++;
  }
  else 
  {
    // Check for @ and ensire a . is at the right place.
    var checkEmail = form.email.value;

    if (  checkEmail.indexOf('@') < 0  ||
           ( checkEmail.charAt(checkEmail.length-4) != '.'
             && checkEmail.charAt(checkEmail.length-3) != '.'
           )
         )
    {
    msg += "Invalid Email Address\n";
    errors++;
    }
  }

  if ( errors > 0 )
  {
    var header = "The following errors were noted:\n";
    alert( header + msg );
    return false;
  }

  return true;
}
