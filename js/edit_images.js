// JavaScript routines for edit_images.php
let mode = "";
let curr_doc_blob = null;
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
  var formData = new FormData();
  formData.append('image_action', 'GET_INFO');

  var xhr = new XMLHttpRequest();
  xhr.open('POST', 'edit_images_proc.php', true);
  xhr.onreadystatechange = function() {
    if (xhr.readyState === XMLHttpRequest.DONE && xhr.status === 200) {
      document.getElementById("image_status").style.color = "green";
      document.getElementById("image_status").value = "";
    } else if (xhr.readyState === 4 && xhr.status !== 200) {
      document.getElementById("image_status").style.color = "red";
      document.getElementById("image_status").value = "Error in calling database!";
    }
  };
  xhr.send(formData);
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
  document.getElementById('pdf_viewer').classList.remove('active');
  document.getElementById('pdf_viewer').data = "";
  document.getElementById('image_viewer').classList.remove('active');
  document.getElementById('image_viewer').src = "";
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

  var formData = new FormData();
  formData.append('image_blob', blobData);
  formData.append('image_description', description);
  formData.append('image_filename', filename);
  formData.append('image_action', 'NEW');

  var xhr = new XMLHttpRequest();
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

