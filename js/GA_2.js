// JavaScript routines specifically for the GA_2.php page

function get_solute_count(control)
{
    var count = control.value;
    location.href = 'GA_2.php?count=' + count;
}

function isEmpty(s)
{
    return ((s == null) || (s.length == 0));
}

function isWhitespace(s)
{
    var whitespace = /^\s+$/        // One or more white spaces takes up the whole line

    return(isEmpty(s) || whitespace.test(s));
}

function validate_solutes( count )
{
    for ( var i = 1; i <= count; i++ )
    {
        var s_min = document.getElementById(i + "_min");
        if ( ( s_min == null ) || isWhitespace(s_min.value) )
        {
            alert( "Please enter min value for Solute "+ i );
            s_min.focus();
            return( false );
        }

        s_max = document.getElementById(i + "_max");
        if ( ( s_max == null ) || isWhitespace(s_max.value) )
        {
            alert( "Please enter max value for Solute "+ i );
            s_max.focus();
            return( false );
        }

        if ( parseFloat(s_max.value) < parseFloat(s_min.value) )
        {
          var swap = confirm( "The maximum s-value is less than the minimum "    +
                              "s-value for solute " + i + ". If you would like " +
                              "to switch them, please click on OK to continue. " +
                              "If you would rather edit them yourself, click "   +
                              "cancel to return to the submission form." );

          if ( ! swap )
          {
            s_min.focus();
            return( false );
          }

          var temp = parseFloat( s_min.value );
          s_min.value = parseFloat( s_max.value );
          s_max.value = temp;
        }

        // |s_min| >= 0.1
        if ( Math.abs(parseFloat(s_min.value)) < 0.1 )
        {
            alert( "Absolute value of s-min must be >= 0.1 -- Solute " + i );
            s_min.focus();
            return( false );
        }

        // |s_max| >= 0.1
        if ( Math.abs(parseFloat(s_max.value)) < 0.1 )
        {
            alert( "Absolute value of s-max must be >= 0.1 -- Solute " + i );
            s_max.focus();
            return( false );
        }

        // What if s range includes -0.1 ~ 0.1?
        if ( parseFloat(s_min.value) < -0.1 &&
             parseFloat(s_max.value) >  0.1 )
        {
          alert( "Your s-value range overlaps the -0.1 ~ 0.1 range for " +
                 "solute " + i + ". This range is excluded from the GA analysis.");
        }

        ff0_min = document.getElementById(i + "_ff0_min");
        if ( ( ff0_min == null ) || isWhitespace(ff0_min.value) )
        {
            alert("Please enter f/f0 min value for Solute "+ i);
            ff0_min.focus();
            return false;
        }

        ff0_max = document.getElementById(i + "_ff0_max");
        if ( ( ff0_max == null ) || isWhitespace(ff0_max.value) )
        {
            alert("Please enter f/f0 max value for Solute "+ i);
            ff0_max.focus();
            return false;
        }

        if ( parseFloat(ff0_max.value) < parseFloat(ff0_min.value) )
        {
          var swap = confirm( "The maximum ff0-value is less than the minimum "    +
                              "ff0-value for solute " + i + ". If you would like " +
                              "to switch them, please click on OK to continue. " +
                              "If you would rather edit them yourself, click "   +
                              "cancel to return to the submission form." );

          if ( ! swap )
          {
            ff0_min.focus();
            return( false );
          }

          var temp2 = parseFloat( ff0_min.value );
          ff0_min.value = parseFloat( ff0_max.value );
          ff0_max.value = temp2;
        }
    }

    return true;
}

// A minimal onkeypress handler to enable trapping of the enter key
function trapEnterKey(evt)
{
    var evt = (evt) ? evt : ((event) ? event : null);
    var node = (evt.target) ? evt.target : ((evt.srcElement) ? evt.srcElement : null);

    // Verify we have pressed the enter key from a text element first
    if ((evt.keyCode != 13) || (node.type != "text")) 
        return true;

    // Enter key is pressed; see if solute count has changed
    sol_count = document.getElementById("sol");
    if (sol_count.value != <?php echo $sol_count; ?>)
    {
        // count has changed; redraw screen
        location.href = 'GA_2.php?count=' + sol_count.value;
        return false;
    }

    // count has not changed; just trap the key
    return false; 
}

// Enable onkeypress handler
document.onkeypress=trapEnterKey;

