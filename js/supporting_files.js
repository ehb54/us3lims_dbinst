// JavaScript routines for supporting_files.php
let mode = null;
let doc_blob = null;
let projects = {};
let solutions = {};
let buffers = {};
let analytes = {};
let all_documents = {};
let all_blobs = {};

const image_pdf_ext = [{name: "bmp",  type: "image/bmp"},  {name: "gif" , type: "image/gif"},
                       {name: "jpeg", type: "image/jpeg"}, {name: "jpg", type: "image/jpeg"},
                       {name: "png" , type: "image/png"},  {name: "tiff", type: "image/tiff"},
                       {name: "webp", type: "image/webp"}, {name: "svg" , type: "image/svg+xml"}, 
                       {name: "pdf" , type: "application/pdf"} ];

const doc_ext = [ {name: "odp",  type: "application/vnd.oasis.opendocument.presentation"},
                  {name: "ods",  type: "application/vnd.oasis.opendocument.spreadsheet"},
                  {name: "odt",  type: "application/vnd.oasis.opendocument.text"},
                  {name: "doc",  type: "application/msword"},
                  {name: "docx", type: "application/vnd.openxmlformats-officedocument.wordprocessingml.document"},
                  {name: "ppt",  type: "application/vnd.ms-powerpoint"},
                  {name: "pptx", type: "application/vnd.openxmlformats-officedocument.presentationml.presentation"},
                  {name: "xls",  type: "application/vnd.ms-excel"},
                  {name: "xlsx", type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"},
                ];


function handle_mode(item) {
  if (mode === item.id){
    return;
  } else {
    mode = item.id;
  }

  document.getElementById('sf_view').classList.remove('active');
  document.getElementById('sf_new').classList.remove('active');
  document.getElementById('sf_edit_item').classList.remove('active');
  document.getElementById('sf_sel_class').classList.remove('active');
  document.getElementById('sf_sel_subclass').classList.remove('active');
  document.getElementById('sf_txt_class').classList.remove('active');
  document.getElementById('sf_txt_subclass').classList.remove('active');

  document.getElementById('sf_download').classList.remove('active');
  document.getElementById('sf_update').classList.remove('active');
  document.getElementById('sf_delete').classList.remove('active');
  document.getElementById('sf_upload').classList.remove('active');

  // document.getElementById('sf_browse').disabled = false;
  document.getElementById('sf_browse').classList.remove('active');
  document.getElementById('sf_sel_file_item').disabled = false;
  document.getElementById('sf_status').value = '';

  if (item.id === 'sf_view') {
    set_view_mode();
  } else if (item.id === 'sf_new') {
    set_new_mode();
  }
}

function set_new_mode() {
  document.getElementById('sf_new').classList.add('active');
  document.getElementById('sf_browse').classList.add('active');
  document.getElementById('sf_sel_class').classList.add('active');
  document.getElementById('sf_sel_subclass').classList.add('active');
  document.getElementById('sf_upload').classList.add('active');
  document.getElementById('sf_sel_file_item').disabled = true;
  document.getElementById('sf_desc').value = '';
  document.getElementById('sf_filename').value = '';
  if (Object.keys(projects).length == 0){
    document.getElementById('sf_sel_proj').value = 'EMPTY';
  } else {
    document.getElementById('sf_sel_proj').value = 'SELECT';
  }
  if (Object.keys(all_documents).length == 0){
    document.getElementById('sf_sel_file').value = 'EMPTY';
  } else {
    document.getElementById('sf_sel_file').value = 'SELECT';
  }
  document.getElementById('sf_sel_class').value = 'SELECT';
  fill_sel_class("sf_sel_subclass", null);
  document.getElementById('sf_txt_class').value = '';
  document.getElementById('sf_txt_subclass').value = '';
  doc_blob = null;
  display_document(doc_blob);
}

function set_view_mode() {
  document.getElementById('sf_edit_item').classList.add('active');
  document.getElementById('sf_view').classList.add('active');
  document.getElementById('sf_txt_class').classList.add('active');
  document.getElementById('sf_txt_subclass').classList.add('active');
  document.getElementById('sf_download').classList.add('active');
  document.getElementById('sf_browse').classList.remove('active');
  // document.getElementById('sf_browse').disabled = true;
  set_edit_mode(document.getElementById("sf_edit"))
}

function set_edit_mode(input) {
  if (input.checked){
    document.getElementById('sf_txt_class').classList.remove('active');
    document.getElementById('sf_txt_subclass').classList.remove('active');
    document.getElementById('sf_sel_class').classList.add('active');
    document.getElementById('sf_sel_subclass').classList.add('active');
    document.getElementById('sf_update').classList.add('active');
    document.getElementById('sf_delete').classList.add('active');
    document.getElementById('sf_download').classList.remove('active');
    // document.getElementById('sf_browse').disabled = false;
    document.getElementById('sf_browse').classList.add('active');
  } else {
    document.getElementById('sf_txt_class').classList.add('active');
    document.getElementById('sf_txt_subclass').classList.add('active');
    document.getElementById('sf_sel_class').classList.remove('active');
    document.getElementById('sf_sel_subclass').classList.remove('active');
    document.getElementById('sf_update').classList.remove('active');
    document.getElementById('sf_delete').classList.remove('active');
    document.getElementById('sf_download').classList.add('active');
    // document.getElementById('sf_browse').disabled = true;
    document.getElementById('sf_browse').classList.remove('active');
  }
  
}

function select_class(input) {
  let option = input.options[input.selectedIndex];
  let value = option.value;

  if (value === "solution"){
    fill_sel_class("sf_sel_subclass", solutions);
  } else if (value === "buffer"){
    fill_sel_class("sf_sel_subclass", buffers);
  } else if (value === "analyte"){
    fill_sel_class("sf_sel_subclass", analytes);
  } else {
    fill_sel_class("sf_sel_subclass", null);
  }
}

function select_project(input) {
  if (mode == "sf_new"){
    return;
  }
  let option = input.options[input.selectedIndex];
  let value = option.value;

  if (value == "EMPTY" || value == "SELECT"){
    fill_sel_file("sf_sel_file", null);
  } else {
    let doc_IDs = projects[value].doc_IDs;
    if (doc_IDs == null){
      fill_sel_file("sf_sel_file", null);
    } else {
      let options = {};
      for (let i in doc_IDs){
        let desc = all_documents[doc_IDs[i]].description;
        options[doc_IDs[i]] = {"description" : desc};
      }
      fill_sel_file("sf_sel_file", options);
    }
  }
}

function select_file(input) {
  if (mode == "sf_new"){
    return;
  }
  let option = input.options[input.selectedIndex];
  let value = option.value;

  if (value == "EMPTY" || value == "SELECT") {
    document.getElementById("sf_desc").value = "";
    document.getElementById("sf_filename").value = "";
    document.getElementById("sf_txt_class").value = "";
    document.getElementById("sf_txt_subclass").value = "";
    doc_blob = null;
    display_document(doc_blob);
  } else {
    let docID = value;
    let guid = all_documents[docID].guid;
    let description = all_documents[docID].description;
    let filename = all_documents[docID].filename;
    let solutionID = all_documents[docID].solutionID;
    let bufferID = all_documents[docID].bufferID;
    let analyteID = all_documents[docID].analyteID;
    let blob = all_blobs[docID];

    let class_txt, subclass_txt;
    let class_sel, subclass_sel;
    if (solutionID != null){
      class_txt = "Solution";
      subclass_txt = solutions[solutionID];
      fill_sel_class("sf_sel_subclass", solutions);
      class_sel = "solution";
      subclass_sel = solutionID;
    } else if (bufferID != null){
      class_txt = "Buffer";
      subclass_txt = buffers[bufferID];
      fill_sel_class("sf_sel_subclass", buffers);
      class_sel = "buffer";
      subclass_sel = bufferID;
    } else if (analyteID != null){
      class_txt = "Analyte";
      subclass_txt = analytes[analyteID];
      fill_sel_class("sf_sel_subclass", analytes);
      class_sel = "analyte";
      subclass_sel = analyteID;
    } else {
      class_txt = "--- Uncategorized ---";
      subclass_txt = "--- Uncategorized ---";
      fill_sel_class("sf_sel_subclass", null);
      class_sel = null;
      subclass_sel = null;
    }

    document.getElementById("sf_desc").value = description;
    document.getElementById("sf_filename").value = filename;
    document.getElementById("sf_txt_class").value = class_txt;
    document.getElementById("sf_txt_subclass").value = subclass_txt;
    if (class_sel == null){
      document.getElementById("sf_sel_class").value = "SELECT";
    } else {
      document.getElementById("sf_sel_class").value = class_sel;
    }
    if (subclass_sel == null){
      document.getElementById("sf_sel_subclass").value = "EMPTY";
    } else {
      document.getElementById("sf_sel_subclass").value = subclass_sel;
    }

    if (blob == null){
      let ext_chk = check_extension(filename);
      fetch_doc_data(docID.replace("id_", ""), guid, ext_chk.type, b64toBlob)
    } else {
      display_document(blob);
    }
  }
}


function fetch_doc_data(doc_id, doc_guid, file_type, b64toBlob) {
  let xhr = new XMLHttpRequest();
  // xhr.responseType = 'json';
  xhr.open('POST', 'supporting_files_proc.php', true);
  let formData = new FormData();
  formData.append('action', 'GET_DOC');
  formData.append('docID', doc_id);
  formData.append('docGUID', doc_guid);
  xhr.send(formData);
  xhr.onload = function() {
    if (xhr.status != 200) {
      display_message(`AJAX Error: ${xhr.status}: ${xhr.statusText}`, 'red', 5000);
    } else {
      const file_data = JSON.parse(xhr.responseText);
      if (file_data.data == null){
        display_message(`DB Error: ${file_data.error}`, 'red', 10000);
        b64toBlob(null);
      } else {
        display_message("Successfully fetched document !", 'green', 3000);
        let blob = b64toBlob(file_data.data, file_type, display_document);
        all_blobs["id_" + doc_id] = blob;
      }
    }
  }
  xhr.onprogress = function() {
    display_message("Downloading the document !!!", 'red', 10000);
  }
  xhr.onerror = function() {
    let msg = `Request failed! Check your network please:${xhr.status}: ${xhr.statusText}`;
    display_message(msg, 'red', 5000);
  }
}


function b64toBlob(b64_date, file_type, display_document, slice_size=512){
  const byte_chars = atob(b64_date);

  const byte_arrays = [];
  for (let offset = 0; offset < byte_chars.length; offset += slice_size) {
    const slice = byte_chars.slice(offset, offset + slice_size);

    const byte_codes = new Array(slice.length);
    for (let i = 0; i < slice.length; i++) {
      byte_codes[i] = slice.charCodeAt(i);
    }

    const byte_array = new Uint8Array(byte_codes);
    byte_arrays.push(byte_array);
  }

  doc_blob = new Blob(byte_arrays, {type: file_type});
  display_document(doc_blob);
  return doc_blob;
}

function get_init_info(parse_init_info) {
  let formData = new FormData();
  formData.append('action', 'GET_INIT_INFO');
  let xhr = new XMLHttpRequest();
  xhr.open('POST', 'supporting_files_proc.php', true);
  xhr.send(formData);
  xhr.onload = function() {
    if (xhr.status != 200) {
      display_message(`AJAX Error: ${xhr.status}: ${xhr.statusText}`, 'red', 5000);
    } else {
      let data = JSON.parse(xhr.responseText);
      parse_init_info(data, parse_doc_info);
    }
  }
  xhr.onerror = function() {
    display_message("Request failed! Check your network please!", 'red', 5000);
  }
}

function parse_init_info (info, parse_doc_info) {
  if (info.project.data == null){
    projects = null;
  } else {
    let obj = {};
    for (let ii = 0; ii < info.project.data.length; ii++){
      let id = info.project.data[ii].projectID;
      obj["id_" + id] = {"description" : info.project.data[ii].description, "doc_IDs" : null};
    }
    projects = obj;
  }

  if (info.solution.data == null){
    solutions = null;
  } else {
    let obj = {};
    for (let ii = 0; ii < info.solution.data.length; ii++){
      let id = info.solution.data[ii].solutionID;
      obj["id_" + id] = info.solution.data[ii].description;
    }
    solutions = obj;
  }

  if (info.buffer.data == null){
    buffers = null;
  } else {
    let obj = {};
    for (let ii = 0; ii < info.buffer.data.length; ii++){
      let id = info.buffer.data[ii].bufferID;
      obj["id_" + id] = info.buffer.data[ii].description;
    }
    buffers = obj;
  }

  if (info.analyte.data == null){
    analytes = null;
  } else {
    let obj = {};
    for (let ii = 0; ii < info.analyte.data.length; ii++){
      let id = info.analyte.data[ii].analyteID;
      obj["id_" + id] = info.analyte.data[ii].description;
    }
    analytes = obj;
  }

  parse_doc_info(info.image_info);
  
  fill_sel_class("sf_sel_class", {"solution" : "Solution" ,
                                  "buffer"   : "Buffer" ,
                                  "analyte"  : "Analyte"});
}

function get_doc_info(parse_doc_info){
  let formData = new FormData();
  formData.append('action', 'GET_DOC_INFO');
  let xhr = new XMLHttpRequest();
  xhr.open('POST', 'supporting_files_proc.php', true);
  xhr.send(formData);
  xhr.onload = function() {
    if (xhr.status != 200) {
      display_message(`AJAX Error: ${xhr.status}: ${xhr.statusText}`, 'red', 5000);
    } else {
      let doc_info = JSON.parse(xhr.responseText);
      parse_doc_info(doc_info);
    }
  }
  xhr.onerror = function() {
    display_message("Request failed! Check your network please!", 'red', 5000);
  }  
}

function clear_documents(){
  for (const key in all_documents) {
    delete all_documents[key];
  }
}

function parse_doc_info (doc_info) {
  clear_documents();
  if (doc_info.data == null){
    return;
  }
  for (let ii = 0; ii < doc_info.data.length; ii++){
    let id = "id_" + doc_info.data[ii].imageID;
    let guid = doc_info.data[ii].imageGUID;
    let description = doc_info.data[ii].description;
    let filename = doc_info.data[ii].filename;
    let projectID = doc_info.data[ii].projectID;
    if (projectID != null){projectID = "id_" + projectID;}
    let solutionID = doc_info.data[ii].solutionID;
    if (solutionID != null){solutionID = "id_" + solutionID;}
    let bufferID = doc_info.data[ii].bufferID;
    if (bufferID != null){bufferID = "id_" + bufferID;}
    let analyteID = doc_info.data[ii].analyteID;
    if (analyteID != null){analyteID = "id_" + analyteID;}

    if (guid == null){
      continue;
    }
    all_documents[id] = {"guid" : guid,
                         "description" : description,
                         "filename" : filename,
                         "projectID" : projectID,
                         "solutionID" : solutionID,
                         "bufferID" : bufferID,
                         "analyteID" : analyteID
                        };
    if (! id in all_blobs){
      all_blobs[id] = null;
    }
  }

  if (projects != null){
    let doc_keys = Object.keys(all_documents);
    for (let pro_id in projects){
      let doc_IDs = [];
      for (let i in doc_keys){
        let doc_id = doc_keys[i];
        let chk = doc_id != null && all_documents[doc_id].projectID != null;
        if ( chk && all_documents[doc_id].projectID === pro_id){
          doc_IDs.push(doc_id);
          doc_keys[i] = null;
        }
      }
      if (doc_IDs.length == 0){
        projects[pro_id].doc_IDs = null;
      } else {
        projects[pro_id].doc_IDs = doc_IDs;
      }
    }
    // check for unk
    let doc_IDs = [];
    for (let i in doc_keys){
      if (doc_keys[i] != null){
        doc_IDs.push(doc_keys[i]);
      }
    }
    if (doc_IDs.length > 0){
      let txt = "--- Unknown --- ( " + doc_IDs.length.toString() + " )";
      projects['UNK'] = {"description" : txt, "doc_IDs" : doc_IDs};
    }
  }
  fill_sel_file("sf_sel_proj", projects);
}

function fill_sel_file(tag_id, options) {
  let select_element = document.getElementById(tag_id);
  select_element.innerHTML = "";
  let flag = false;
  let value = "SELECT";
  let text = "--- Select One ---";
  if (options == null){
    flag = true;
    value = "EMPTY";
    text = "--- Empty ---";
  }

  let option_element = document.createElement('option');
  option_element.value = value;
  option_element.text = text;
  select_element.appendChild(option_element);

  if (flag){
    return;
  }

  let unk_val = null;
  for (let id in options){
    if (id == "UNK") {
      unk_val = options.UNK;
      continue;
    }
    let option_element = document.createElement('option');
    option_element.value = id;
    let txt = options[id].description;
    if ( Object.keys(options[id]).includes("doc_IDs") ){
      if (options[id].doc_IDs == null){
        txt += "  ( 0 )";
      }else{
        txt += "  ( " + options[id].doc_IDs.length.toString() + " )";
      }
    }
    option_element.text = txt;
    select_element.appendChild(option_element);
  }
  if (unk_val != null){
    let option_element = document.createElement('option');
    option_element.value = "UNK";
    option_element.text = unk_val.description;
    select_element.appendChild(option_element);
  }
}

function fill_sel_class(tag_id, options) {
  let select_element = document.getElementById(tag_id);
  select_element.innerHTML = "";
  let flag = false;
  let value = "SELECT";
  let text = "--- Select One ---";
  if (options == null){
    flag = true;
    value = "EMPTY";
    text = "--- Empty ---";
  }

  let option_element = document.createElement('option');
  option_element.value = value;
  option_element.text = text;
  select_element.appendChild(option_element);

  if (flag){
    return;
  }

  for (let id in options){
    let option_element = document.createElement('option');
    option_element.value = id;
    option_element.text = options[id];
    select_element.appendChild(option_element);
  }
}


function check_extension(file_name) {
  const ext = file_name.split('.').pop().toLowerCase();
  let state = false;
  let ftype = null;
  for (let i = 0; i < image_pdf_ext.length; i++){
    if (image_pdf_ext[i].name === ext){
      state = true;
      ftype = image_pdf_ext[i].type;
      break;
    }
  }

  if (! state){
    for (let i = 0; i < doc_ext.length; i++){
      if (doc_ext[i].name === ext){
        state = true;
        ftype = doc_ext[i].type;
        break;
      }
    }
  }
  let output = {state: state, type : ftype};
  return output;
}


function browse_document(input) {
  let file = input.files[0];
  const ext = file.name.split('.').pop().toLowerCase();
  // alert(ext);

  let ext_chk = check_extension(file.name);
  if (! ext_chk.state){
    display_message("Invalid file type!", "red", 5000);
    return;
  }

  const reader = new FileReader();
  reader.readAsArrayBuffer(file);
  reader.onload = function(e) {
    const fileContent = e.target.result;
    let blob = new Blob([fileContent], { type: file.type });
    document.getElementById('sf_filename').value = file.name;
    doc_blob = blob;
    display_document(blob);
  };

}

function display_document(blob) {
  document.getElementById('pdf_viewer').classList.remove('active');
  document.getElementById('pdf_viewer').data = "";
  document.getElementById('image_viewer').classList.remove('active');
  document.getElementById('image_viewer').src = "";
  if (blob == null){
    return;
  }

  const fileURL = URL.createObjectURL(blob);
  if (blob.type === "application/pdf"){
    document.getElementById('pdf_viewer').classList.add('active');
    document.getElementById('pdf_viewer').data = fileURL;
  } else if (blob.type.split("/")[0] === "image") {
    document.getElementById('image_viewer').classList.add('active');
    document.getElementById('image_viewer').src = fileURL;
  } else {
    display_message("Document cannot be shown on the screen!", "red", 5000);
  }
  
}

function display_message(message, color, timeout=-1) {
  document.getElementById("sf_status").style.color = color;
  document.getElementById("sf_status").value = message;
  if (timeout > 0){
    setTimeout(() => {
      if (document.getElementById("sf_status").value === message){
        document.getElementById("sf_status").value = '';
      }}, timeout)
  }
}

function upload_document() {
  const blobData = doc_blob;
  const description = document.getElementById('sf_desc').value;
  const filename = document.getElementById('sf_filename').value;
  
  let timeout = 5000;

  if (! filename || blobData == null){
    display_message("No documents found!", "red", timeout);
    return;
  }
  if (! description){
    display_message("Description section cannot be empty!", "red", timeout);
    return;
  }
  let projectID = get_sel_value("sf_sel_proj")
  if (projectID == null) {
    display_message("Select the project that the document belongs to!", "red", timeout);
    return;
  } else{
    projectID = projectID.replace("id_", "");
  }
  
  let class_val = get_sel_value("sf_sel_class")
  let subclass_val = get_sel_value("sf_sel_subclass")
  if (class_val != null){
    if (subclass_val == null){
      display_message("Subcategory not found!", "red", timeout);
      return;
    } else {
      subclass_val = subclass_val.replace("id_", "");
    }
  } else {
    subclass_val = null;
  }
  document.getElementById('sf_upload').disabled = true;

  let formData = new FormData();
  formData.append('action', 'NEW');
  formData.append('description', description);
  formData.append('filename', filename);
  formData.append('blob', blobData);
  formData.append('projectID', projectID);
  formData.append('class', class_val);
  formData.append('subclassID', subclass_val);

  let xhr = new XMLHttpRequest();
  xhr.open('POST', 'supporting_files_proc.php', true);
  xhr.send(formData);
  xhr.onload = function() {
    if (xhr.status != 200) {
      display_message(`AJAX Error: ${xhr.status}: ${xhr.statusText}`, 'red', 5000);
    } else {
      let data = JSON.parse(xhr.responseText);
      let err_msg = null;
      if (data.image != "OK"){
        err_msg = data.image;
      }
      if (data.imagePerson != "OK"){
        err_msg += "\n\n" + data.imagePerson;
      }
      if (data.imageProject != "OK"){
        err_msg += "\n\n" + data.imageProject;
      }if (data.imageClass != "OK"){
        err_msg += "\n\n" + data.imageClass;
      }
      if (err_msg == null){
        display_message("Document successfully uploaded!", 'green', 5000);
        get_doc_info(parse_doc_info);
        document.getElementById('sf_desc').value = '';
        document.getElementById('sf_filename').value = '';
        document.getElementById('sf_sel_class').value = 'SELECT';
        fill_sel_class("sf_sel_subclass", null);
        display_document(null);
      } else {
        alert(err_msg);
      }
    }
    document.getElementById('sf_upload').disabled = false;
  }
  xhr.upload.onloadstart = function(){
    display_message("1- upload started", 'red', 5000);
  }
  xhr.upload.onprogress = function(){
    display_message("2- uploading ...", 'red', 5000);
  }
  xhr.upload.onload = function(){
    display_message("3- upload finished", 'red', 5000);
  }
  xhr.upload.onabort = function(){
    display_message("upload aborted !!!", 'red', 5000);
  }
  xhr.onprogress = function(){
    display_message("4- Database processing ...", 'red', 5000);
  }
  xhr.onerror = function() {
    let msg = `Request failed! Check your network please! : ${xhr.status}: ${xhr.statusText}`;
    display_message(msg, 'red', 5000);
  }
}

function delete_document() {

  let projectID = get_sel_value("sf_sel_proj")
  if (projectID == null) {
    display_message("Select the project that the document belongs to!", "red", timeout);
    return;
  } else{
    if (projectID == "UNK"){
      projectID = null;
    } else {
      projectID = projectID.replace("id_", "");
    }
  }

  let docID = get_sel_value("sf_sel_file")
  if (docID == null) {
    display_message("Select the document!", "red", timeout);
    return;
  } else{
    docID = docID.replace("id_", "");
  }

  let class_val = get_sel_value("sf_sel_class")
  let subclass_val = get_sel_value("sf_sel_subclass")
  if (class_val != null){
    if (subclass_val == null){
      display_message("Subcategory not found!", "red", timeout);
      return;
    } else {
      subclass_val = subclass_val.replace("id_", "");
    }
  } else {
    subclass_val = null;
  }
  document.getElementById('sf_upload').disabled = true;

  let formData = new FormData();
  formData.append('action', 'DEL_DOC');
  formData.append('docID', docID);
  formData.append('projectID', projectID);
  formData.append('class', class_val);
  formData.append('subclassID', subclass_val);

  let xhr = new XMLHttpRequest();
  xhr.open('POST', 'supporting_files_proc.php', true);
  xhr.send(formData);
  xhr.onload = function() {
    if (xhr.status != 200) {
      display_message(`AJAX Error: ${xhr.status}: ${xhr.statusText}`, 'red', 5000);
    } else {
      let data = JSON.parse(xhr.responseText);
      let err_msg = null;
      if (data.image != "OK"){
        err_msg = data.image;
      }
      if (data.imagePerson != "OK"){
        err_msg += "\n\n" + data.imagePerson;
      }
      if (data.imageProject != "OK"){
        err_msg += "\n\n" + data.imageProject;
      }if (data.imageClass != "OK"){
        err_msg += "\n\n" + data.imageClass;
      }
      if (err_msg == null){
        display_message("Document successfully uploaded!", 'green', 5000);
        get_doc_info(parse_doc_info);
        document.getElementById('sf_desc').value = '';
        document.getElementById('sf_filename').value = '';
        document.getElementById('sf_sel_class').value = 'SELECT';
        fill_sel_class("sf_sel_subclass", null);
        display_document(null);
      } else {
        alert(err_msg);
      }
    }
    document.getElementById('sf_upload').disabled = false;
  }
  xhr.upload.onloadstart = function(){
    display_message("1- upload started", 'red', 5000);
  }
  xhr.upload.onprogress = function(){
    display_message("2- uploading ...", 'red', 5000);
  }
  xhr.upload.onload = function(){
    display_message("3- upload finished", 'red', 5000);
  }
  xhr.upload.onabort = function(){
    display_message("upload aborted !!!", 'red', 5000);
  }
  xhr.onprogress = function(){
    display_message("4- Database processing ...", 'red', 5000);
  }
  xhr.onerror = function() {
    let msg = `Request failed! Check your network please! : ${xhr.status}: ${xhr.statusText}`;
    display_message(msg, 'red', 5000);
  }
}


function get_sel_value(select_id){
  let element = document.getElementById(select_id);
  let option = element.options[element.selectedIndex];
  let value = option.value;
  if (value == "EMPTY" || value == "SELECT"){
    return null;
  } else {
    return value;
  }
}

function download_document() {
  if (doc_blob == null){
    return;
  }
  let filename = document.getElementById("sf_filename").value;
  let a = document.createElement('a');
  a.href = window.URL.createObjectURL(doc_blob);
  a.download = filename;
  a.style.display = 'none';
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
}