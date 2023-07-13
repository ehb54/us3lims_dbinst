// JavaScript routines for edit_images.php
let mode = "";
let curr_doc_blob = null;
let projects, solutions, buffers, analytes, images;
let init_data;
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


function handleMode(item) {
  if (mode === item.id){
    return;
  } else {
    mode = item.id;
  }

  document.getElementById('view_image').classList.remove('active');
  document.getElementById('edit_image').classList.remove('active');
  document.getElementById('new_image').classList.remove('active');
  document.getElementById('image_cat').classList.remove('active');
  document.getElementById('image_ins').classList.remove('active');
  document.getElementById('image_cat_txt').classList.remove('active');
  document.getElementById('image_ins_txt').classList.remove('active');

  document.getElementById('download_image').classList.remove('active');
  document.getElementById('update_image').classList.remove('active');
  document.getElementById('delete_image').classList.remove('active');
  document.getElementById('upload_image').classList.remove('active');

  document.getElementById('browse_image').disabled = false;
  document.getElementById('image_selection_item').disabled = false;
  document.getElementById('image_status').value = '';

  if (item.id === 'view_image') {
    document.getElementById('view_image').classList.add('active');
    document.getElementById('image_cat_txt').classList.add('active');
    document.getElementById('image_ins_txt').classList.add('active');
    document.getElementById('download_image').classList.add('active');
    document.getElementById('browse_image').disabled = true;
  } else if (item.id === 'edit_image') {
    document.getElementById('edit_image').classList.add('active');
    document.getElementById('image_cat').classList.add('active');
    document.getElementById('image_ins').classList.add('active');
    document.getElementById('update_image').classList.add('active');
    document.getElementById('delete_image').classList.add('active');
  } else if (item.id === 'new_image') {
    document.getElementById('new_image').classList.add('active');
    document.getElementById('image_cat').classList.add('active');
    document.getElementById('image_ins').classList.add('active');
    document.getElementById('upload_image').classList.add('active');
    document.getElementById('image_selection_item').disabled = true;
  }
}

function get_info() {
  let formData = new FormData();
  formData.append('image_action', 'GET_INIT_INFO');

  let xhr = new XMLHttpRequest();
  xhr.open('POST', 'edit_images_proc.php', true);
  xhr.onreadystatechange = function() {
    if (xhr.readyState === XMLHttpRequest.DONE && xhr.status === 200) {
      let data = JSON.parse(xhr.responseText);
      init_data = data;
      parse_init_info(data);
    } else if (xhr.readyState === 4 && xhr.status !== 200) {
      document.getElementById("image_status").style.color = "red";
      document.getElementById("image_status").value = "Error in calling database!";
    }
  };
  xhr.send(formData);
}

function parse_init_info (data) {
  if (data.project.data == null){
    projects = null;
  } else {
    let obj = {};
    for (let ii = 0; ii < data.project.data.length; ii++){
      let id = data.project.data[ii].projectID;
      obj["id_" + id] = {"description" : data.project.data[ii].description, "imageIDs" : null};
    }
    projects = obj;
  }

  if (data.solution.data == null){
    solutions = null;
  } else {
    let obj = {};
    for (let ii = 0; ii < data.solution.data.length; ii++){
      let id = data.solution.data[ii].solutionID;
      obj["id_" + id] = data.solution.data[ii].description;
    }
    solutions = obj;
  }

  if (data.buffer.data == null){
    buffers = null;
  } else {
    let obj = {};
    for (let ii = 0; ii < data.buffer.data.length; ii++){
      let id = data.buffer.data[ii].bufferID;
      obj["id_" + id] = data.buffer.data[ii].description;
    }
    buffers = obj;
  }

  if (data.analyte.data == null){
    analytes = null;
  } else {
    let obj = {};
    for (let ii = 0; ii < data.analyte.data.length; ii++){
      let id = data.analyte.data[ii].analyteID;
      obj["id_" + id] = data.analyte.data[ii].description;
    }
    analytes = obj;
  }

  parse_image_info(data.image);
  fill_select("image_project", projects);
  fill_select("image_cat", {"solution" : {"description" : "Solution"} ,
                            "buffer" : {"description" : "Buffer"} ,
                            "analyte" : {"description" : "Analyte"} ,});
}

function parse_image_info (image) {
  if (image.data == null){
    images = null;
  } else {
    let obj = {};
    for (let ii = 0; ii < image.data.length; ii++){
      let id = image.data[ii].imageID;
      obj["id_" + id] = {"description" : image.data[ii].description,
                        "filename" : image.data[ii].filename,
                        "projectID" : image.data[ii].projectID,
                        "solutionID" : image.data[ii].solutionID,
                        "bufferID" : image.data[ii].bufferID,
                        "analyteID" : image.data[ii].analyteID,
                        "blob" : null};
    }
    images = obj;

    //
    if (projects != null){
      let imageID_list = Object.keys(images);
      for (let pro_id in projects){
        let imageIDs = [];
        for (let i in imageID_list){
          let image_id = imageID_list[i];
          let chk = image_id != null && images[image_id].projectID != null;
          if ( chk && images[image_id].projectID === pro_id){
            imageIDs.push(image_id);
            imageID_list[i] = null;
          }
        }
        if (imageIDs.length == 0){
          projects[pro_id].imageIDs = null;
        } else {
          projects[pro_id].imageIDs = imageIDs;
        }
      }
      // check for unk
      let imageIDs = [];
      for (let i in imageID_list){
        if (imageID_list[i] != null){
          imageIDs.push(imageID_list[i]);
        }
      }
      if (imageIDs.length > 0){
        let txt = "--- Unknown --- ( " + imageIDs.length.toString() + " )";
        projects['UNK'] = {"description" : txt, "imageIDs" : imageIDs};
      }
    }
  }
}

function fill_select(tag_id, options) {
  let select_element = document.getElementById(tag_id);
  select_element.innerHTML = "";
  let option_element = document.createElement('option');
  option_element.value = "";
  option_element.text = "--- Select One ---";
  select_element.appendChild(option_element);

  let unk_val = null;
  for (id in options){
    if (id == "UNK") {
      unk_val = options.UNK;
      continue;
    }
    let option_element = document.createElement('option');
    option_element.value = id;
    let txt = options[id].description;
    if ( Object.keys(options[id]).includes("imageIDs") ){
      if (options[id].imageIDs == null){
        txt += "  ( 0 )";
      }else{
        txt += "  ( " + options[id].imageIDs.length.toString() + " )";
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


function choose_document() {
  let fileInput = document.getElementById('browse_image');
  let file = fileInput.files[0];

  const ext = file.name.split('.').pop().toLowerCase();
  // alert(ext);

  let chk = false;
  for (let i = 0; i < image_pdf_ext.length; i++){
    if (image_pdf_ext[i].name === ext){
      chk = true;
      break;
    }
  }

  if (! chk){
    for (let i = 0; i < doc_ext.length; i++){
      if (doc_ext[i].name === ext){
        chk = true;
        break;
      }
    }
  }

  if (! chk){
    alert("Invalid file type!");
    return;
  }

  const reader = new FileReader();
  reader.onload = function(e) {
    const fileContent = e.target.result;
    curr_doc_blob = new Blob([fileContent], { type: file.type });
    document.getElementById('image_filename').value = file.name;
    display_document();
  };
  reader.readAsArrayBuffer(file);

}

function display_document() {
  document.getElementById('pdf_viewer').classList.remove('active');
  document.getElementById('pdf_viewer').data = "";
  document.getElementById('image_viewer').classList.remove('active');
  document.getElementById('image_viewer').src = "";
  if (curr_doc_blob == null){
    return;
  }

  let state = false;
  let show_pdf = false;
  for (let i = 0; i < image_pdf_ext.length; i++){
    if (image_pdf_ext[i].type === curr_doc_blob.type){
      state = true;
      if (image_pdf_ext[i].name === "pdf"){
        show_pdf = true;
      }
      break;
    }
  }
  
  if (state) {
    const fileURL = URL.createObjectURL(curr_doc_blob);
    if (show_pdf){
      document.getElementById('pdf_viewer').classList.add('active');
      document.getElementById('pdf_viewer').data = fileURL;
    } else {
      document.getElementById('image_viewer').classList.add('active');
      document.getElementById('image_viewer').src = fileURL;
    }
  } else {
    alert('Document cannot be shown on the screen!');
  }
}

function upload_document() {
  const blobData = curr_doc_blob;
  const description = document.getElementById('image_desc').value;
  const filename = document.getElementById('image_filename').value;
  if (! description){
    alert("Description section is empty!")
    return;
  }
  if (! filename){
    alert("No document found!")
    return;
  }

  let formData = new FormData();
  formData.append('image_blob', blobData);
  formData.append('image_description', description);
  formData.append('image_filename', filename);
  formData.append('image_action', 'NEW');

  let xhr = new XMLHttpRequest();
  xhr.open('POST', 'edit_images_proc.php', true);
  xhr.onreadystatechange = function() {
    if (xhr.readyState === XMLHttpRequest.DONE && xhr.status === 200) {
      document.getElementById("image_status").style.color = "green";
      document.getElementById("image_status").value = "Document uploaded successfully!";
    } else if (xhr.readyState === 4 && xhr.status !== 200) {
      document.getElementById("image_status").style.color = "red";
      document.getElementById("image_status").value = "Error occurred while uploading the data!";
    }
  };
  xhr.send(formData);
}

