/* reports.js
 *
 * JavaScript routines for use with reports
 *
 */

// Function to display a database graphic blob in a new window
function show_report_detail( ID )
{
   window.open("report_detail.php?ID=" + ID,
               "convert",
               "toobar=no,location=no,directories=no,status=no," +
               "scrollbars=yes,resizable=yes,copyhistory=no,"    +
               "width=640,height=480,title=Report Detail"   );
}

