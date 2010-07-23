/*
 * analysis.js
 *
 * some common functions called by the analysis programs for limiting
 * submissions. All should handle situation where they don't exist
 * on the form using try/catch sequence, and return error code if not
 * present. f is always a pointer to the form.
 */

const READ_ERROR = -1;

function valid_field( fieldName )
{
  // Let's see if this field is on the form at all
  return( typeof fieldName != 'undefined' );
}

function get_radio_value( fieldName )
{
  try
  {
    for ( var i = 0; i < fieldName.length; i++ )
    {
      if ( fieldName[i].checked )
        return( fieldName[i].value );
    }
  }
  catch(e_get_radio_value) {}

  return( READ_ERROR );
}


