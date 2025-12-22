// JavaScript routines for queue_viewer.php

var selected_gfacIDs = new Set(JSON.parse(sessionStorage.getItem('selected_gfacIDs') || '[]'));

function save_selection() {
    sessionStorage.setItem('selected_gfacIDs', JSON.stringify(Array.from(selected_gfacIDs)));
}

// A jQuery function that will update the queue content every 20 sec or so
// Another advantage is that the next call of this function will only
//   occur after previous one has completed.
function update_queue_content()
{
  $.ajax(
  {
    url:      'queue_content.php',
    success:  function( data )
              {
                $('#queue_content').html( data );
                restore_selection();
              },
    complete: function()
              {
                //setTimeout( update_queue_content, 20000 );
                setTimeout( update_queue_content, 60000 );
              }
  })
}

function restore_selection() {
    $('.select_job').each(function() {
        var gfacID = String($(this).data('gfacid'));
        if (selected_gfacIDs.has(gfacID)) {
            $(this).prop('checked', true);
        } else {
            $(this).prop('checked', false);
        }
    });
    update_ui_from_selection();
}

function toggle_all_selection(checkbox) {
    var checked = $(checkbox).prop('checked');
    var indeterminate = $(checkbox).prop('indeterminate');
    
    if (indeterminate) {
        checked = true;
        $(checkbox).prop('indeterminate', false);
        $(checkbox).prop('checked', true);
    }

    $('.select_job').each(function() {
        var gfacID = String($(this).data('gfacid'));
        if (checked) {
            selected_gfacIDs.add(gfacID);
        } else {
            selected_gfacIDs.delete(gfacID);
        }
    });
    save_selection();
    restore_selection();
}

function toggle_runid_selection(checkbox, runID) {
    var checked = $(checkbox).prop('checked');
    var indeterminate = $(checkbox).prop('indeterminate');

    if (indeterminate) {
        checked = true;
        $(checkbox).prop('indeterminate', false);
        $(checkbox).prop('checked', true);
    }

    $('.select_job[data-runid="' + runID + '"]').each(function() {
        var gfacID = String($(this).data('gfacid'));
        if (checked) {
            selected_gfacIDs.add(gfacID);
        } else {
            selected_gfacIDs.delete(gfacID);
        }
    });
    save_selection();
    restore_selection();
}

function toggle_runid_anal_selection(checkbox, runID, analType) {
    var checked = $(checkbox).prop('checked');
    var indeterminate = $(checkbox).prop('indeterminate');

    if (indeterminate) {
        checked = true;
        $(checkbox).prop('indeterminate', false);
        $(checkbox).prop('checked', true);
    }

    $('.select_job[data-runid="' + runID + '"][data-analtype="' + analType + '"]').each(function() {
        var gfacID = String($(this).data('gfacid'));
        if (checked) {
            selected_gfacIDs.add(gfacID);
        } else {
            selected_gfacIDs.delete(gfacID);
        }
    });
    save_selection();
    restore_selection();
}

function toggle_runid_anal_status_selection(checkbox, runID, analType, status) {
    var checked = $(checkbox).prop('checked');
    var indeterminate = $(checkbox).prop('indeterminate');

    if (indeterminate) {
        checked = true;
        $(checkbox).prop('indeterminate', false);
        $(checkbox).prop('checked', true);
    }

    $('.select_job[data-runid="' + runID + '"][data-analtype="' + analType + '"][data-status="' + status + '"]').each(function() {
        var gfacID = String($(this).data('gfacid'));
        if (checked) {
            selected_gfacIDs.add(gfacID);
        } else {
            selected_gfacIDs.delete(gfacID);
        }
    });
    save_selection();
    restore_selection();
}

function toggle_job_selection(checkbox, gfacID) {
    gfacID = String(gfacID);
    if ($(checkbox).prop('checked')) {
        selected_gfacIDs.add(gfacID);
    } else {
        selected_gfacIDs.delete(gfacID);
    }
    save_selection();
    update_ui_from_selection();
}

function update_ui_from_selection() {
    // Update summary table checkboxes and counts based on selected_gfacIDs
    
    // Update global count and bulk delete button
    var total_selected = selected_gfacIDs.size;
    $('#global_select_count').text('Selected Jobs: ' + total_selected);
    if (total_selected > 0) {
        $('#bulk_delete_button').show();
    } else {
        $('#bulk_delete_button').hide();
    }

    // Update "Select All" count
    var total_all = $('.select_job').length;
    $('#count_all').text('(' + total_selected + '/' + total_all + ')');

    // 1. RunID+Anal+Status
    $('.select_runID_anal_status').each(function() {
        var runID = $(this).data('runid');
        var analType = $(this).data('analtype');
        var status = $(this).data('status');
        
        var total = $('.select_job[data-runid="' + runID + '"][data-analtype="' + analType + '"][data-status="' + status + '"]').length;
        var selected = 0;
        $('.select_job[data-runid="' + runID + '"][data-analtype="' + analType + '"][data-status="' + status + '"]').each(function() {
            if (selected_gfacIDs.has(String($(this).data('gfacid')))) selected++;
        });
        
        if (selected === 0) {
            $(this).prop('checked', false).prop('indeterminate', false);
        } else if (selected === total) {
            $(this).prop('checked', true).prop('indeterminate', false);
        } else {
            $(this).prop('checked', false).prop('indeterminate', true);
        }

        $('.count_runID_anal_status[data-runid="' + runID + '"][data-analtype="' + analType + '"][data-status="' + status + '"]').text('(' + selected + '/' + total + ')');
    });

    // 2. RunID+Anal
    $('.select_runID_anal').each(function() {
        var runID = $(this).data('runid');
        var analType = $(this).data('analtype');
        
        var total = $('.select_job[data-runid="' + runID + '"][data-analtype="' + analType + '"]').length;
        var selected = 0;
        $('.select_job[data-runid="' + runID + '"][data-analtype="' + analType + '"]').each(function() {
            if (selected_gfacIDs.has(String($(this).data('gfacid')))) selected++;
        });
        
        if (selected === 0) {
            $(this).prop('checked', false).prop('indeterminate', false);
        } else if (selected === total) {
            $(this).prop('checked', true).prop('indeterminate', false);
        } else {
            $(this).prop('checked', false).prop('indeterminate', true);
        }

        $('.count_runID_anal' + 
          '[data-runid="' + runID + '"]' + 
          '[data-analtype="' + analType + '"]').text('(' + selected + '/' + total + ')');
    });

    // 3. RunID
    $('.select_runID').each(function() {
        var runID = $(this).data('runid');
        
        var total = $('.select_job[data-runid="' + runID + '"]').length;
        var selected = 0;
        $('.select_job[data-runid="' + runID + '"]').each(function() {
            if (selected_gfacIDs.has(String($(this).data('gfacid')))) selected++;
        });
        
        if (selected === 0) {
            $(this).prop('checked', false).prop('indeterminate', false);
        } else if (selected === total) {
            $(this).prop('checked', true).prop('indeterminate', false);
        } else {
            $(this).prop('checked', false).prop('indeterminate', true);
        }

        $('.count_runID[data-runid="' + runID + '"]').text('(' + selected + '/' + total + ')');
    });

    // 4. All checkbox update
    if (total_all > 0) {
        if (total_selected === 0) {
            $('#select_all_jobs').prop('checked', false).prop('indeterminate', false);
        } else if (total_selected === total_all) {
            $('#select_all_jobs').prop('checked', true).prop('indeterminate', false);
        } else {
            $('#select_all_jobs').prop('checked', false).prop('indeterminate', true);
        }
    }
}

function bulk_delete_jobs() {
    if (selected_gfacIDs.size === 0) {
        alert("No jobs selected for deletion.");
        return;
    }
    
    if (confirm("Are you sure you want to delete/cancel the " + selected_gfacIDs.size + " selected jobs?")) {
        // Create a hidden form and submit it
        var form = $('<form action="queue_viewer.php" method="post"></form>');
        form.append('<input type="hidden" name="delete" value="1" />');
        selected_gfacIDs.forEach(function(gfacID) {
            var input = $('<input>', {
                type: 'hidden',
                name: 'gfacIDs[]',
                value: gfacID
            });
            form.append(input);
        });
        $('body').append(form);
        form.submit();
    }
}

function show_info( jobid )
{
  more_info  = document.getElementById( "more_info" + jobid );
  info       = document.getElementById( "info" + jobid );

  if ( info.style.display == 'block' ) 
  {  
    if ( document.all )  // old IE
      more_info.innerHTML = "More Info";
    else
      more_info.textContent = "More Info";

    info.style.display = 'none';
  }
  else
  {
    if ( document.all )  // old IE
      more_info.innerHTML = "Hide Info";
    else
      more_info.textContent = "Hide Info";

    info.style.display = 'block';
  }

  return false;
}

