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

// Function to display solution details in a new window
function show_solution_detail( compType, eID, triple )
{
   var properType = compType.charAt(0).toUpperCase() +
                    compType.substr(1);

   window.open("solution_detail.php?type=" + compType + "&expID=" + eID + "&triple=" + triple,
               properType,
               "toobar=no,location=no,directories=no,status=no," +
               "scrollbars=yes,resizable=yes,copyhistory=no,"    +
               "width=640,height=480,title=Detail"); // + properType + "Detail"   );
}

// jQuery format controls
var change_person = function ()
{
   var ID = $('#people_select').val();
   // If pID or rID are falsy, read them from the URL.
   const urlParams = new URLSearchParams(window.location.search);
   if (!ID) {
       ID = urlParams.get('personID') || "";
   }
   $('#people_select').unbind('change');
   // Update the URL with the current pID and rID selections.
   const newUrl = new URL(window.location.href);
   newUrl.searchParams.set('personID', ID);
   newUrl.searchParams.delete('reportID');
   history.replaceState(null, "", newUrl.toString());

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
   // If pID or rID are falsy, read them from the URL.
   const urlParams = new URLSearchParams(window.location.search);
   if (!pID) {
       pID = urlParams.get('personID') || "";
   }
   if (!rID) {
       rID = urlParams.get('reportID') || "";
   }

   $('#run_select').unbind('change');

   // Update the URL with the current pID and rID selections.
   const newUrl = new URL(window.location.href);
   newUrl.searchParams.set('personID', pID);
   if (`${rID}` !== '-1' && `${rID}` !== "") {
       newUrl.searchParams.set('reportID', rID);
   }
   else {
       newUrl.searchParams.delete('reportID');
   }

   history.replaceState(null, "", newUrl.toString());

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

