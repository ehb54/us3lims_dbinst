<?php
/*
 * edit_images.php
 *
 * A place to edit/update the image table
 *
 */
include_once 'checkinstance.php';

if ( $_SESSION['userlevel'] < 1 )
{
  header('Location: index.php');
  exit();
}

include 'config.php';
include 'db.php';
include 'lib/utility.php';

// Start displaying page
$page_title = 'Edit Images';
$css = 'css/edit_images.css';
$js = 'js/edit_images.js';
include 'header.php';

echo <<< HTML
<!-- Begin page content -->
<div id="image_content">
  <div style="clear: both"></div>

  <div id="image_navbar" class="image_item">
    <div class="image_navbar_item" id="view_image" onclick="handleMode(this)"> View</div>
    <div class="image_navbar_item" id="edit_image" onclick="handleMode(this)"> Edit </div>
    <div class="image_navbar_item" id="new_image" onclick="handleMode(this)"> New </div>
  </div>

  <fieldset id="image_project_item" class="image_item">
    <label for="image_project">Project:</label>
    <select id="image_project">
      <option value="">-- Select One --</option>
    </select>
  </fieldset>

  <fieldset id="image_selection_item" class="image_item">
    <label for="image_id">Image:</label>
    <select id="image_id">
      <option value="">-- Select One --</option>
    </select>
    <button id="prev_image" type="button" onclick=""> Previous </button>
    <button id="next_image" type="button" onclick=""> Next </button>
  </fieldset>

  <div class="image_item">
    <label for="image_desc"> Description: </label>
    <input type="text" name="image_desc" id="image_desc" value="">
  </div>

  <div class="image_item">
    <label for="image_filename"> Filename: </label>
    <input type="text" name="image_filename" id="image_filename" value="" readonly>
    <input type="file" name="browse_image" id="browse_image" onchange="choose_document()">
  </div>

  <div class="image_item" id="image_cat_item">
    <label for="image_cat">Category:</label>
    <select id="image_cat">
      <option value="">-- Select One --</option>
    </select>
    <input type="text" id="image_cat_txt" value="" readonly>
  </div>

  <div class="image_item" id="image_ins_item">
    <label for="image_ins">Instance:</label>
    <select id="image_ins">
      <option value="">-- Select One --</option>
    </select>
    <input type="text" id="image_ins_txt" value="" readonly>
  </div>

  <div id="image_status_handle_item">
    <div>
      <input type="text" id="image_status" value="" readonly>
    </div>

    <div id="image_handle_item" class="image_item">
      <button id="download_image" type="button" onclick=""> Download </button>
      <button id="update_image" type="button" onclick=""> Update </button>
      <button id="delete_image" type="button" onclick=""> Delete </button>
      <button id="upload_image" type="button" onclick="upload_document()"> Upload </button>
    </div>
  </div>
  
  <br>
  <object id="pdf_viewer" type="application/pdf" data="" width="800" height="600"></object>
  <img id="image_viewer" src="" alt="">

</div>

<script>get_info()</script>
<script>handleMode(document.getElementById("view_image"))</script>
 
HTML;

echo "<!-- Footer -->";

include 'footer.php';
exit();

?>
