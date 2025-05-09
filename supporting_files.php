<?php
/*
 * supporting_files.php
 *
 * A place to edit/update the supporting files
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
$page_title = 'Supporting Files';
$css = 'css/supporting_files.css';
$js = 'js/supporting_files.js';
include 'header.php';

echo <<< HTML
<!-- Begin page content -->
<div id="sf_content">
  <div class='clear-both'></div>

  <div id="sf_navbar" class="sf_item">
    <div class="sf_navbar_item" id="sf_view" onclick="handle_mode(this)"> View / Edit</div>
    <div class="sf_navbar_item" id="sf_new" onclick="handle_mode(this)"> New </div>
  </div>

  <div id="sf_edit_item">
    <input type="checkbox" id="sf_edit" class='onchange-set-edit-mode' >
    <span id="sf_edit_label">Edit Document </span>
  </div>

  <fieldset id="sf_sel_proj_item" class="sf_item">
    <label for="sf_sel_proj">Project:</label>
    <select id="sf_sel_proj" class='onchange-select-project' >
      <option value="EMPTY">--- Empty ---</option>
    </select>
  </fieldset>

  <fieldset id="sf_sel_file_item" class="sf_item">
    <label for="sf_sel_file">Document:</label>
    <select id="sf_sel_file" class='onchange-select-document' >
      <option value="EMPTY">--- Empty ---</option>
    </select>
    <button id="sf_prev" type="button" onclick="sf_prev_doc()"> Previous </button>
    <button id="sf_next" type="button" onclick="sf_next_doc()"> Next </button>
  </fieldset>

  <div class="sf_item">
    <label for="sf_desc"> Description: </label>
    <input type="text" name="sf_desc" id="sf_desc" value="" oninput="filter_text(this)">
  </div>

  <div class="sf_item">
    <label for="sf_filename"> Filename: </label>
    <input type="text" name="sf_filename" id="sf_filename" value="" readonly>
    <input type="file" name="sf_browse" id="sf_browse" class='onchange-browse-document' >
  </div>

  <div class="sf_item" id="sf_class_item">
    <label for="sf_sel_class">Category:</label>
    <select id="sf_sel_class" class='onchange-select-class' >
      <option value="EMPTY">--- Empty ---</option>
    </select>
    <input type="text" id="sf_txt_class" value="" readonly>
  </div>

  <div class="sf_item" id="sf_subclass_item">
    <label for="sf_sel_subclass">Subcategory:</label>
    <select id="sf_sel_subclass">
      <option value="EMPTY">--- Empty ---</option>
    </select>
    <input type="text" id="sf_txt_subclass" value="" readonly>
  </div>

  <div id="sf_status_button_item">
    <div>
      <input type="text" id="sf_status" value="" readonly>
    </div>

    <div id="sf_button_item" class="sf_item">
      <button id="sf_save" type="button" onclick="save_document()"> Save File </button>
      <button id="sf_update" type="button" onclick="update_document()"> Update </button>
      <button id="sf_delete" type="button" onclick="delete_document()"> Delete </button>
      <button id="sf_upload" type="button" onclick="upload_document()"> Upload </button>
    </div>
  </div>

  <br>
  <object id="pdf_viewer" type="application/pdf" data="" width="800" height="600"></object>
  <img id="image_viewer" src="" alt="">
  <div>
    <textarea id="txt_viewer" rows="20" cols="100" readonly></textarea>
  </div>

</div>

<script>init_setup()</script>
<script>handle_mode(element_view)</script>

HTML;

echo "<!-- Footer -->";

include 'footer.php';
exit();



?>
