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
   $('#tripleID').load( 'report_getInfo.php?type=t&rID=' + rID );
   $('#combos').load( 'report_getInfo.php?type=c&rID=' + rID );
   $('#runID').load( 'report_getInfo.php?type=r&pID=' + pID +
                     '&rID=' + rID,
     function()
     {
       // runID selection setup
       // make sure change event is bound after data is loaded
       $('#run_select').change( change_run_select );
     });
}

var change_docType = function ()
{
   // Get a list of all the checked boxes
   var imageIDs = $("input:checked").toArray();
   var docTypes = [];
   var tripleID = 0;

   // Now make a list of the document types, which are contained as text in the element
   for ( var i = 0; i < imageIDs.length; i++ )
   {
       var id = imageIDs[i].getAttribute('ID');
       var a  = id.split("_");
       tripleID = a[1];
       var docType  = a[2];

       docTypes.push( docType );
   }

   // Make a comma-separated list
   var types = docTypes.join(",");

   location.href = 'view_reports.php?triple=' + tripleID + '&a=' + types;
}

