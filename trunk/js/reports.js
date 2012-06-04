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

// jQuery format controls
var change_person = function ()
{
   var ID = $('#people_select').val();

   $('#people_select').unbind('change');
   $('#personID').load( 'report_getInfo.php?type=p&pID=' + ID,
     function()
     {
       // personID selection setup
       // make sure change event is bound after data is loaded
       $('#people_select').change( change_person );
     });

   $('#run_select').unbind('change');
   $('#runID').load( 'report_getInfo.php?type=r&pID=' + ID,
     function()
     {
       // runID selection setup
       $('#run_select').change( change_run_select );
     });

   $('#tripleID').html( '' );
}

var change_run_select = function ()
{
   var pID = $('#people_select').val();
   var rID = $('#run_select').val();

   $('#run_select').unbind('change');    
   $('#runID').load( 'report_getInfo.php?type=r&pID=' + pID +
                     '&rID=' + rID,
     function()
     {
       // runID selection setup
       // make sure change event is bound after data is loaded
       $('#run_select').change( change_run_select );
     });

   $('#tripleID').html( '' );
}

