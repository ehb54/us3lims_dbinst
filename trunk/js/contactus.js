// JavaScript routines for contactus.php

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
  if ( trim( form.fname.value ) == "" )
  {
    msg += "--first name is missing\n";
    errors++;
  }

  // Last name is required
  if ( trim( form.lname.value ) == "" )
  {
    msg += "--last name is missing\n";
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
    msg += "--email address is missing\n";
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
    msg += "--email address is invalid\n";
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
