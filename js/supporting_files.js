// JavaScript routines for supporting_files.php
let mode = null;
let doc_blob = {};
let projects = {};
let solutions = {};
let buffers = {};
let analytes = {};
let all_documents = {};
let all_blobs = {};
let element_view;
let element_new;
const timeout = 15;

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
  document.getElementById('sf_download').disabled = false;
  document.getElementById('sf_update').classList.remove('active');
  document.getElementById('sf_update').disabled = false;
  document.getElementById('sf_delete').classList.remove('active');
  document.getElementById('sf_delete').disabled = false;
  document.getElementById('sf_upload').classList.remove('active');
  document.getElementById('sf_upload').disabled = false;

  document.getElementById('sf_browse').classList.remove('active');
  document.getElementById("sf_browse").value = null;
  document.getElementById('sf_sel_file_item').classList.remove('active');
  document.getElementById('sf_status').value = '';
  document.getElementById('sf_desc').value = '';
  document.getElementById('sf_filename').value = '';
  document.getElementById("sf_edit").checked = false;
  document.getElementById('sf_txt_class').value = '';
  document.getElementById('sf_txt_subclass').value = '';
  doc_blob.url = null;
  doc_blob.type = null;
  display_document(null);

  if (item === element_view) {
    set_view_mode();
  } else if (item === element_new) {
    set_new_mode();
  }
}

function set_new_mode() {
  document.getElementById('sf_new').classList.add('active');
  document.getElementById('sf_browse').classList.add('active');
  document.getElementById('sf_sel_class').classList.add('active');
  document.getElementById('sf_sel_subclass').classList.add('active');
  document.getElementById('sf_upload').classList.add('active');
  document.getElementById('sf_sel_file_item').classList.remove('active');
  document.getElementById("sf_browse").value = null;
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
}

function set_view_mode() {
  document.getElementById('sf_edit_item').classList.add('active');
  document.getElementById('sf_view').classList.add('active');
  document.getElementById('sf_txt_class').classList.add('active');
  document.getElementById('sf_txt_subclass').classList.add('active');
  document.getElementById('sf_download').classList.add('active');
  document.getElementById('sf_browse').classList.remove('active');
  document.getElementById("sf_browse").value = null;
  set_edit_mode(document.getElementById("sf_edit"))
}

function set_edit_mode(input) {
  document.getElementById('sf_sel_file_item').classList.add('active');
  if (input.checked){
    if (get_sel_value("sf_sel_file") == null){
      display_message("Failed: Select a Document", "red", timeout);
      input.checked = false;
      return;
    }
    display_message("");
    document.getElementById('sf_txt_class').classList.remove('active');
    document.getElementById('sf_txt_subclass').classList.remove('active');
    document.getElementById('sf_sel_class').classList.add('active');
    document.getElementById('sf_sel_subclass').classList.add('active');
    document.getElementById('sf_update').classList.add('active');
    document.getElementById('sf_delete').classList.add('active');
    document.getElementById('sf_download').classList.remove('active');
    document.getElementById('sf_browse').classList.add('active');
    document.getElementById('sf_sel_file_item').disabled = true;
  } else {
    document.getElementById('sf_txt_class').classList.add('active');
    document.getElementById('sf_txt_subclass').classList.add('active');
    document.getElementById('sf_sel_class').classList.remove('active');
    document.getElementById('sf_sel_subclass').classList.remove('active');
    document.getElementById('sf_update').classList.remove('active');
    document.getElementById('sf_delete').classList.remove('active');
    document.getElementById('sf_download').classList.add('active');
    document.getElementById('sf_browse').classList.remove('active');
    select_project(document.getElementById("sf_sel_proj"));
    document.getElementById('sf_sel_file_item').disabled = false;
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
  if (document.getElementById("sf_edit").checked){
    return;
  }
  let option = input.options[input.selectedIndex];
  let value = option.value;

  if (value == "EMPTY" || value == "SELECT"){
    fill_sel_proj_file("sf_sel_file", null);
  } else {
    let doc_IDs = projects[value].doc_IDs;
    if (doc_IDs == null){
      fill_sel_proj_file("sf_sel_file", null);
    } else {
      let options = {};
      for (let i in doc_IDs){
        let desc = all_documents[doc_IDs[i]].description;
        options[doc_IDs[i]] = {"description" : desc};
      }
      fill_sel_proj_file("sf_sel_file", options);
    }
  }
  document.getElementById("sf_desc").value = "";
  document.getElementById("sf_filename").value = "";
  display_document(null);
  document.getElementById("sf_txt_class").value = "";
  document.getElementById("sf_txt_subclass").value = "";
}

async function select_document(input) {
  if (mode == "sf_new"){
    return;
  }
  let option = input.options[input.selectedIndex];
  let value = option.value;
  doc_blob.url = null;
  doc_blob.type = null;

  if (value == "EMPTY" || value == "SELECT") {
    document.getElementById("sf_desc").value = "";
    document.getElementById("sf_filename").value = "";
    document.getElementById("sf_txt_class").value = "";
    document.getElementById("sf_txt_subclass").value = "";
    display_document(null);
  } else {
    let docID = value;
    let guid = all_documents[docID].guid;
    let description = all_documents[docID].description;
    let filename = all_documents[docID].filename;
    let solutionID = all_documents[docID].solutionID;
    let bufferID = all_documents[docID].bufferID;
    let analyteID = all_documents[docID].analyteID;

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

    if (all_blobs[docID] == null || all_blobs[docID].url == null){
      // let ext_chk = check_extension(filename);
      // fetch_blob(docID.replace("id_", ""), guid, ext_chk.type, b64toBlob);
      fetch_blob(docID.replace("id_", ""), guid);
    } else {
      display_document(all_blobs[docID]);
    }
  }
}

async function fetch_blob(doc_id, doc_guid) {
  let form_get = new FormData();
  form_get.append('action', 'GIVE_BLOB');
  form_get.append('docID', doc_id);
  form_get.append('docGUID', doc_guid);

  let res_finfo = await fetch('supporting_files_proc.php', {method: 'POST', body: form_get});
  if (! res_finfo.ok){
    display_message("Connection Failed: Error in Fetching Document Path Request", "red", timeout);
    throw new Error("FAILED");
  }
  let file_info = await res_finfo.json();
  const file_path = file_info.path;

  if (file_path == null){
    display_message("Failed: Document is not found on the server", "red", timeout);
    throw new Error("FAILED");
  }
  display_message("Please wait! Downloading ...", "red");

  fetch(file_path).then(
    res_blob => res_blob.blob(),
    () => {
      delete_local_blob(file_path).catch(
        () => display_message("Error: Temporary File Wasn't Cleaned From Server")
      );
      display_message("Connection Failed: Error in Fetching Document File Request", "red", timeout);
      throw new Error("FAILED");
    }
  ).then(
    blob => {
      all_blobs["id_" + doc_id].url = URL.createObjectURL(blob);
      all_blobs["id_" + doc_id].type = blob.type;
      display_message("Document is successfully received", "green", timeout);
      display_document(all_blobs["id_" + doc_id]);
      delete_local_blob(file_path).catch(
        () => display_message("Error: Temporary File Wasn't Cleaned From Server")
      );
    },
    () => {
      display_message("Connection Failed: Error in Fetching Document Blob", "red", timeout);
      delete_local_blob(file_path).catch(
        () => display_message("Error: Temporary File Wasn't Cleaned From Server")
      );
      throw new Error("FAILED");
    }
  )
}

function delete_local_blob(file_path){
  return new Promise(function(resolve, reject){
    let form_del = new FormData();
    form_del.append('action', 'DEL_FILE');
    form_del.append('filepath', file_path);
    fetch('supporting_files_proc.php', {method: 'POST', body: form_del}).then(
      response => response.text(),
      () => reject(new Error("FAILED"))
    ).then(
      state => {
        if (state == "OK"){
          resolve("OK");
        } else {
          reject(new Error("FAILED"));
        }
      }
    )
  })
}

function init_setup() {
  element_view = document.getElementById("sf_view");
  element_new = document.getElementById("sf_new");
  doc_blob['url'] = null;
  doc_blob['type'] = null;
  // Get constant information
  let form_data = new FormData();
  form_data.append('action', 'GIVE_INIT_INFO');
  fetch('supporting_files_proc.php', {method: 'POST', body: form_data}).then(
    response => response.json(), () => {
      display_message("Connection Failed: Error in Fetching Initial Information Request", 'red', timeout);
      throw new Error("FAILED");
    }
  ).then(
    init_info => {
      // parse constant information
      if (init_info.project.data == null){
        projects = null;
      } else {
        let obj = {};
        for (let ii = 0; ii < init_info.project.data.length; ii++){
          let id = init_info.project.data[ii].projectID;
          obj["id_" + id] = {"description" : init_info.project.data[ii].description, "doc_IDs" : null};
        }
        projects = obj;
      }

      if (init_info.solution.data == null){
        solutions = null;
      } else {
        let obj = {};
        for (let ii = 0; ii < init_info.solution.data.length; ii++){
          let id = init_info.solution.data[ii].solutionID;
          obj["id_" + id] = init_info.solution.data[ii].description;
        }
        solutions = obj;
      }

      if (init_info.buffer.data == null){
        buffers = null;
      } else {
        let obj = {};
        for (let ii = 0; ii < init_info.buffer.data.length; ii++){
          let id = init_info.buffer.data[ii].bufferID;
          obj["id_" + id] = init_info.buffer.data[ii].description;
        }
        buffers = obj;
      }

      if (init_info.analyte.data == null){
        analytes = null;
      } else {
        let obj = {};
        for (let ii = 0; ii < init_info.analyte.data.length; ii++){
          let id = init_info.analyte.data[ii].analyteID;
          obj["id_" + id] = init_info.analyte.data[ii].description;
        }
        analytes = obj;
      }

      // parse document information
      parse_doc_info(init_info.image_info);

      fill_sel_class("sf_sel_class", {"solution" : "Solution" ,
      "buffer"   : "Buffer" ,
      "analyte"  : "Analyte"});
    }
  )  
}

function get_doc_info(){
  return new Promise(function(resolve, reject){
        // Get document information
    form = new FormData();
    form.append('action', 'GIVE_DOC_INFO');
    fetch('supporting_files_proc.php', {method: 'POST', body: form})
    .then(response => response.json(), () => reject(new Error("FAILED")))
    .then(result => resolve(result), () => reject(new Error("FAILED")));
  }) 
}

function parse_doc_info (doc_info) {
  for (const key in all_documents) {
    delete all_documents[key];
  }
  if (doc_info.data != null){
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
      if (!all_blobs.hasOwnProperty(id)){
        all_blobs[id] = {"type": null, "url": null};
      }
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
  fill_sel_proj_file("sf_sel_proj", projects);
  fill_sel_proj_file("sf_sel_file", null);
}

function fill_sel_proj_file(tag_id, options) {
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
  const max_size = 52428800;
  if (file.size > max_size){
    display_message("Error: Files exceeding 50MB are not allowed to be uploaded to the database", "red", timeout);
    input.value = null;
    return;
  }
  const ext = file.name.split('.').pop().toLowerCase();

  let ext_chk = check_extension(file.name);
  if (! ext_chk.state){
    display_message("Error: File type is not supported", "red", timeout);
    return;
  }

  const reader = new FileReader();
  reader.readAsArrayBuffer(file);
  reader.onload = function(e) {
    const fileContent = e.target.result;
    let blob = new Blob([fileContent], { type: file.type });
    document.getElementById('sf_filename').value = file.name;
    if (doc_blob.url != null){
      URL.revokeObjectURL(doc_blob.url);
    }
    doc_blob.url = URL.createObjectURL(blob);
    doc_blob.type = blob.type;
    display_document(doc_blob);
  }
}

function display_document(blob) {
  let pdf_viewer = document.getElementById('pdf_viewer');
  let img_viewer = document.getElementById('image_viewer');
  pdf_viewer.classList.remove('active');
  pdf_viewer.data = '';
  img_viewer.classList.remove('active');
  img_viewer.src = '';
  if (blob == null){
    return;
  }

  if (blob.type === "application/pdf"){
    pdf_viewer.classList.add('active');
    pdf_viewer.data = blob.url;
  } else if (blob.type.split("/")[0] === "image") {
    img_viewer.classList.add('active');
    img_viewer.src = blob.url;
  } else {
    display_message("Document is loaded properly but cannot be shown on the screen.", "red", timeout);
  }
}

function display_message(message, color="black", timeout=-1) {
  document.getElementById("sf_status").style.color = color;
  document.getElementById("sf_status").value = message;
  if (timeout > 0 && document.getElementById("sf_status").value === message){
    setTimeout(() => {document.getElementById("sf_status").value = '';}, timeout * 1000)
  }
}

async function upload_document() {
  const description = document.getElementById('sf_desc').value;
  const filename = document.getElementById('sf_filename').value;
  
  if (! filename || doc_blob.url == null){
    display_message("Failed: Choose File", "red", timeout);
    return;
  }
  if (! description){
    display_message("Failed: Description line is empty", "red", timeout);
    return;
  }
  let projectID = get_sel_value("sf_sel_proj")
  if (projectID == null || projectID == "UNK") {
    display_message("Failed: Select a Project", "red", timeout);
    return;
  } else{
    projectID = projectID.replace("id_", "");
  }
  
  let class_val = get_sel_value("sf_sel_class")
  let subclass_val = get_sel_value("sf_sel_subclass")
  if (class_val != null){
    if (subclass_val == null){
      display_message("Failed: Select a Subcategory", "red", timeout);
      return;
    } else {
      subclass_val = subclass_val.replace("id_", "");
    }
  } else {
    subclass_val = null;
  }
  document.getElementById('sf_upload').disabled = true;

  let date = new Date();
  let yy = date.getFullYear();
  let mm = date.getMonth();
  let dd = date.getDay();
  let h = date.getHours();
  let m = date.getMinutes();
  let s = date.getSeconds();
  let ms = date.getMilliseconds();
  let rn = Math.floor(Math.random() * 1e6);
  let up_filename = `${yy}${mm}${dd}${h}${m}${s}${ms}${rn}`;
  let state = await upload_blob(doc_blob, up_filename);
  if (state != "OK"){
    document.getElementById('sf_upload').disabled = false;
    display_message("Connection Failed: Error in Uploading Document File to the Server", "red", timeout);
    throw new Error("FAILED");
  }

  let form_data = new FormData();
  form_data.append('action', 'NEW_DOC');
  form_data.append('description', description);
  form_data.append('filename', filename);
  form_data.append('local_filename', up_filename);
  form_data.append('projectID', projectID);
  form_data.append('class', class_val);
  form_data.append('subclassID', subclass_val);

  let response = await fetch('supporting_files_proc.php', {method: 'POST', body: form_data});
  if (! response.ok){
    display_message("Connection Failed: Error in Adding a New Document Request", 'red', timeout);
    document.getElementById('sf_upload').disabled = false;
    throw new Error("FAILED");
  }
  res_json = await response.json();
  document.getElementById('sf_upload').disabled = false;
  let err_msg = null;
  if (res_json.blob != "OK"){
    err_msg = res_json.blob;
  }
  if (res_json.image != "OK"){
    err_msg += "\n\n" + res_json.image;
  }
  if (res_json.imagePerson != "OK"){
    err_msg += "\n\n" + res_json.imagePerson;
  }
  if (res_json.imageProject != "OK"){
    err_msg += "\n\n" + res_json.imageProject;
  }
  if (res_json.imageClass != "OK"){
    err_msg += "\n\n" + res_json.imageClass;
  }
  if (err_msg == null){
    let doc_info = await get_doc_info();
    if (doc_info == "FAILED"){
      display_message("Connection Failed: Error in Fetching Document Information Request", 'red', timeout);
    } else {
      parse_doc_info(doc_info);
      mode = null;
      handle_mode(element_new);
      display_message("Document is successfully uploaded to the database", 'green', timeout);
    }    
  } else {
    display_message("Failed: Error in Document Information", 'red', timeout);
    alert(err_msg);
  }
}

function delete_document() {

  let projectID = get_sel_value("sf_sel_proj")
  if (projectID == null) {
    display_message("Failed: Select a Project", "red", timeout);
    return;
  } else if (projectID == "UNK"){
    projectID = null;
  } else {
    projectID = projectID.replace("id_", "");
  }

  let docID = get_sel_value("sf_sel_file")
  if (docID == null) {
    display_message("Failed: Select a Document", "red", timeout);
    return;
  } else{
    docID = docID.replace("id_", "");
  }

  let class_val = get_sel_value("sf_sel_class")
  let subclass_val = get_sel_value("sf_sel_subclass")
  if (class_val != null){
    if (subclass_val == null){
      display_message("Failed: Select a Subcategory", "red", timeout);
      return;
    } else {
      subclass_val = subclass_val.replace("id_", "");
    }
  } else {
    subclass_val = null;
  }
  document.getElementById('sf_delete').disabled = true;

  let form_data = new FormData();
  form_data.append('action', 'DEL_DOC');
  form_data.append('docID', docID);
  form_data.append('projectID', projectID);
  form_data.append('class', class_val);
  form_data.append('subclassID', subclass_val);

  fetch('supporting_files_proc.php', {method: 'POST', body: form_data})
  .then(response => response.json(), () => {
    document.getElementById('sf_delete').disabled = false;
    display_message("Connection Failed: Error in Deleting Document Request", "red", timeout);})
  .then(result => {
    document.getElementById('sf_delete').disabled = false;
    let err_msg = null;
    if (result.image != "OK"){
      err_msg = result.image;
    }
    if (result.imagePerson != "OK"){
      err_msg += "\n\n" + result.imagePerson;
    }
    if (result.imageProject != "OK"){
      err_msg += "\n\n" + result.imageProject;
    }
    if (result.imageClass != "OK"){
      err_msg += "\n\n" + result.imageClass;
    }

    get_doc_info().then(
      doc_info => {
        parse_doc_info(doc_info);
        URL.revokeObjectURL(all_blobs["id_" + docID].url);
        delete all_blobs["id_" + docID];
        mode = null;
        handle_mode(element_view);
      }
    )

    if (err_msg == null){
      display_message("Document was deleted successfully", 'green', timeout);
    } else {
      display_message("Failed: Error in Deleting Document", "red", timeout)
      alert(err_msg);
    }
  })
}

async function update_document() {
  let description = document.getElementById('sf_desc').value;
  let filename = document.getElementById('sf_filename').value;
  let curr_document;

  let docID = get_sel_value("sf_sel_file")
  if (docID == null) {
    display_message("Failed: Select a Document", "red", timeout);
    return;
  } else {
    curr_document = all_documents[docID];
    docID = docID.replace("id_", "");
  }

  let projectID = get_sel_value("sf_sel_proj");
  if (projectID == null) {
    display_message("Failed: Select a Project", "red", timeout);
    return;
  } else if (projectID == "UNK"){
    display_message("Failed: Select a Project other than ' --- Unknown --- '", "red", timeout);
    return;
  } else {
    let doc_projectID = curr_document.projectID;
    if (doc_projectID === projectID){
      projectID = null;
    } else if (doc_projectID == null){
      projectID = "null_" + projectID.replace("id_", "");
    } else {
      projectID = doc_projectID.replace("id_", "") + "_" + projectID.replace("id_", "");
    }
  }

  if (! description){
    display_message("Failed: Description line is empty", "red", timeout);
    return;
  }
  if (description.localeCompare(curr_document.description) == 0){
    description = null;
  }

  if (! filename || doc_blob.url == null){
    filename = null;
    doc_blob.url = null;
    doc_blob.type = null;
  }
  
  let class_val = get_sel_value("sf_sel_class")
  let subclass_val = get_sel_value("sf_sel_subclass")
  if (class_val != null){
    if (subclass_val == null){
      display_message("Failed: Select a Subcategory", "red", timeout);
      return;
    } else {
      subclass_val = subclass_val.replace("id_", "");
    }
  } else {
    subclass_val = null;
  }

  let prev_class_val, prev_subclass_val;
  if (curr_document.solutionID != null){
    prev_class_val = "solution";
    prev_subclass_val = curr_document.solutionID.replace("id_", "");
  } else if (curr_document.bufferID != null) {
    prev_class_val = "buffer";
    prev_subclass_val = curr_document.bufferID.replace("id_", "");
  } else if (curr_document.analyteID != null) {
    prev_class_val = "analyte";
    prev_subclass_val = curr_document.analyteID.replace("id_", "");
  } else {
    prev_class_val = null;
    prev_subclass_val = null;
  }

  if (class_val === prev_class_val && subclass_val === prev_subclass_val){
    class_val = null;
    subclass_val = null;
  } else if (prev_class_val == null && class_val != null){
    class_val = "null_" + class_val;
  } else if (prev_class_val != null && class_val != null){
    class_val = prev_class_val + "_" + class_val;
    subclass_val = prev_subclass_val + "_" + subclass_val;
  } else if (prev_class_val != null && class_val == null){
    class_val = prev_class_val + "_null";
    subclass_val = prev_subclass_val;
  }

  let check = projectID == null && description == null && filename == null && doc_blob == null;
  check = check && class_val == null && subclass_val == null;
  if (check) {
    display_message("Failed: Nothing was found to edit", "red", timeout);
    return;
  }

  document.getElementById('sf_update').disabled = true;

  // upload blob
  let up_filename = null;
  if (doc_blob.url != null){
    let date = new Date();
    let yy = date.getFullYear();
    let mm = date.getMonth();
    let dd = date.getDay();
    let h = date.getHours();
    let m = date.getMinutes();
    let s = date.getSeconds();
    let ms = date.getMilliseconds();
    let rn = Math.floor(Math.random() * 1e6);
    up_filename = `${yy}${mm}${dd}${h}${m}${s}${ms}${rn}`;
    let state = await upload_blob(doc_blob, up_filename);
    if (state != "OK"){
      document.getElementById('sf_update').disabled = false;
      display_message("Connection Failed: Error in Uploading the Document File to the Server", "red", timeout);
      throw new Error("FAILED");
    }
  }

  let form_data = new FormData();
  form_data.append('action', 'UPDATE_DOC');
  form_data.append('docID', docID);
  form_data.append('description', description);
  form_data.append('filename', filename);
  form_data.append('local_filename', up_filename);
  form_data.append('projectID', projectID);
  form_data.append('class', class_val);
  form_data.append('subclassID', subclass_val);

  fetch('supporting_files_proc.php', {method: 'POST', body: form_data})
  .then(response => response.json(), () => {
    document.getElementById('sf_update').disabled = false;
    display_message('Connection Failed: Error in Updating Document Request', 're', timeout);
    throw new Error('FAILED');})
  .then(result => {
    document.getElementById('sf_update').disabled = false;
    let err_msg = null;
    if (result.blob != "OK"){
      err_msg = result.blob;
    }
    if (result.image != "OK"){
      err_msg += "\n\n" + result.image;
    }
    if (result.imageProject != "OK"){
      err_msg += "\n\n" + result.imageProject;
    }
    if (result.imageClass != "OK"){
      err_msg += "\n\n" + result.imageClass;
    }
    if (err_msg == null){
      get_doc_info().then(
        doc_info => {
          parse_doc_info(doc_info);
          document.getElementById("sf_edit").checked = false;
          URL.revokeObjectURL(all_blobs["id_" + docID].url);
          all_blobs["id_" + docID].url = null;
          all_blobs["id_" + docID].type = null;
          // delete all_blobs["id_" + docID];
          mode = null;
          handle_mode(element_view);
          display_message("Document is successfully updated", 'green', timeout);
        },
        () => display_message("Connection Failed: Error in Fetching Document Information Request", 'red', timeout)
      )
    } else {
      alert(err_msg);
    }
  })
}


function get_sel_value(select_id){
  let element = document.getElementById(select_id);
  let option = element.options[element.selectedIndex];
  let value = option.value;
  if (value == "EMPTY" || value == "SELECT") {
    return null;
  } else{
    return value;
  }
}

function download_document() {
  let docID = get_sel_value("sf_sel_file");
  if (docID == null) {
    display_message("Select a document", "red", timeout);
    return;
  }
  let blob = all_blobs[docID];
  if (blob == null || blob.url == null){
    display_message("Document is not downloaded yet", "red", timeout);
    return;
  }
  let filename = document.getElementById("sf_filename").value;
  let a = document.createElement('a');
  a.href = blob.url;
  a.download = filename;
  a.style.display = 'none';
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
}

function filter_text(input){
  input.value = input.value.replace(/[^a-zA-Z0-9 .,_-]/g, '');
}


async function upload_blob(blob_obj, filename){
  if (blob_obj.url == null){
    return;
  }
  let response = await fetch(blob_obj.url);
  if (! response.ok){
    display_message("Failed: Revoked Document URL! Please Choose File.", "red", timeout);
    throw new Error("FAILED");
  }
  let blob = await response.blob();
  let base64 = await blob2base64(blob);
  const slice_size = 1024 * 1000 * 2;
  let offset = 0;
  let attempt = 0;
  const attempt_max = 5;
  let chunk = base64.slice(offset, offset + slice_size);
  const length = base64.length;
  let sum = chunk.length;
  display_message(`"Uploading: 0 %"`, 'green', timeout);
  while (offset < base64.length){
    
    const state = await upload_chunk(chunk, filename);
    if (state == "OK"){
      attempt = 0;
      offset += slice_size;
      chunk = base64.slice(offset, offset + slice_size);
      sum += chunk.length;
      const perc = ((sum / length) * 100).toFixed(1);
      display_message(`"Uploading: ${perc} %"`, 'green', timeout);
    } else {
      if (++attempt > attempt_max){
        display_message("File Uploading Failed: Exceeded Maximum Attempts", "red", timeout);
        throw new Error("FAILED");
      }
    }
  }
  return Promise.resolve("OK");
}

function blob2base64(blob){
  return new Promise(function(resolve, reject){
    const reader = new FileReader();
    reader.readAsDataURL(blob);
    reader.onload = function(e){
      let base64 = e.target.result.slice(e.target.result.indexOf(',') + 1);
      resolve(base64);
    }
    reader.onerror = () => reject(new Error("FAILED"));
  }); 
}


function upload_chunk(chunk, filename){
  
  return new Promise(function(resolve, reject){
    let xhr = new XMLHttpRequest();
    // xhr.responseType = 'json';
    xhr.open('POST', 'supporting_files_proc.php', true);
    let formData = new FormData();
    formData.append('action', 'GET_BLOB');
    formData.append('data', chunk);
    formData.append('filename', filename);
    xhr.send(formData);
    xhr.onload = function() {
      if (xhr.status != 200) {
        reject(new Error("FAILED"));
      } else {
        if (xhr.responseText == "OK"){
          resolve("OK");
        } else {
          reject(new Error("FAILED"));
        }
      }
    }
    xhr.onerror = function() {
      reject(new Error("FAILED"));
    }
  });
}