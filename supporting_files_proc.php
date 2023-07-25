<?php
/*
 * supporting_files_proc.php
 */
include_once 'checkinstance.php';
include 'config.php';
include 'db.php';
include 'lib/utility.php';

function get_subclass_info($cat_type) {
    global $link;
    $PID = $_SESSION['id'];
    if (strcmp($cat_type, "project") === 0){
        // $table = "supportingFileProject";
        $table = "imageProject";
        $column = "projectID";
    } else if (strcmp($cat_type, "solution") === 0) {
        // $table = "supportingFileSolution";
        $table = "imageSolution";
        $column = "solutionID";
    } else if (strcmp($cat_type, "buffer") === 0) {
        // $table = "supportingFileBuffer";
        $table = "imageBuffer";
        $column = "bufferID";
    } else if (strcmp($cat_type, "analyte") === 0) {
        // $table = "supportingFileAnalyte";
        $table = "imageAnalyte";
        $column = "analyteID";
    } else {
        return null;
    }
    // fetch supportingFileProject
    // $query = "SELECT tab.fileID, tab.$column " . 
    //          "FROM ( SELECT sf.fileID AS fileID " . 
    //                 "FROM supportingFile AS sf, supportingFilePerson AS sfp " .
    //                 "WHERE sf.fileID = sfp.fileID AND sfp.personID = $PID ) AS sel, " .
    //          "$table AS tab WHERE sel.fileID = tab.fileID";
    $query = "SELECT tab.imageID, tab.$column " . 
             "FROM ( SELECT I.imageID AS imageID " . 
                    "FROM image AS I, imagePerson AS IP " .
                    "WHERE I.imageID = IP.imageID AND IP.personID = $PID ) AS sel, " .
             "$table AS tab WHERE sel.imageID = tab.imageID";
    $sqlret = mysqli_query( $link, $query );
    $nrows = mysqli_num_rows($sqlret);
    $result = array("error" => null, "data" => null);

    if ($nrows > 0){
        $content = array();
        while($row = mysqli_fetch_assoc($sqlret)){
            array_push($content, $row);
        }
        $result["data"] = $content;
    } else {
        $result["error"] = mysqli_error($link);
    }

    return $result;
}

function get_image_info () {
    global $link;
    $PID = $_SESSION['id'];

    // fetch image info
    // $query = "SELECT sf.fileID, sf.fileGUID, sf.description, sf.filename " . 
    //          "FROM supportingFile as sf, supportingFilePerson as sfp " . 
    //          "WHERE sf.fileID = sfp.fileID AND sfp.personID = $PID";
    $query = "SELECT I.imageID, I.imageGUID, I.description, I.filename " . 
             "FROM image as I, imagePerson as IP " . 
             "WHERE I.imageID = IP.imageID AND IP.personID = $PID";
    $sqlret = mysqli_query( $link, $query );
    $nrows = mysqli_num_rows($sqlret);
    $image_info = array("error" => null, "data" => null);

    if ($nrows > 0){
        $content = array();
        while($row = mysqli_fetch_assoc($sqlret)){
            array_push($content, $row);
        }
        $image_info["data"] = $content;
    } else {
        $image_info["error"] = mysqli_error($link);
    }
    if (is_null($image_info["data"])){
        return $image_info;
    }

    $imageProject = get_subclass_info("project");
    $imageSolution = get_subclass_info("solution");
    $imageBuffer = get_subclass_info("buffer");
    $imageAnalyte = get_subclass_info("analyte");

    for ($ii = 0; $ii < count($image_info["data"]); $ii++ ){
        $row = $image_info["data"][$ii];
        // check imageProject
        $row["projectID"] = null;
        for ($jj = 0; $jj < count($imageProject["data"]); $jj++){
            $data = $imageProject["data"][$jj];
            if (intval($row["imageID"]) == intval($data["imageID"])) {
                $row["projectID"] = $data["projectID"];
                break;
            }
        }
        // check imageSolution
        $row["solutionID"] = null;
        for ($jj = 0; $jj < count($imageSolution["data"]); $jj++){
            $data = $imageSolution["data"][$jj];
            if (intval($row["imageID"]) == intval($data["imageID"])) {
                $row["solutionID"] = $data["solutionID"];
                break;
            }
        }
        // check imageBuffer
        $row["bufferID"] = null;
        for ($jj = 0; $jj < count($imageBuffer["data"]); $jj++){
            $data = $imageBuffer["data"][$jj];
            if (intval($row["imageID"]) == intval($data["imageID"])) {
                $row["bufferID"] = $data["bufferID"];
                break;
            }
        }
        // check imageAnalyte
        $row["analyteID"] = null;
        for ($jj = 0; $jj < count($imageAnalyte["data"]); $jj++){
            $data = $imageAnalyte["data"][$jj];
            if (intval($row["imageID"]) == intval($data["imageID"])) {
                $row["analyteID"] = $data["analyteID"];
                break;
            }
        }
        $image_info["data"][$ii] = $row;
    } 
    return $image_info;
}

if (isset($_POST['action'])) {
    $action = $_POST['action'];

    // Store data in MySQL database
    if ($action === 'NEW') {
        // Get the uploaded data
        $blob = addslashes(file_get_contents($_FILES['blob']['tmp_name']));
        $description = $_POST['description'];
        $filename = $_POST['filename'];
        $projectID = $_POST["projectID"];
        $class = $_POST["class"];
        $subclassID = $_POST["subclassID"];
        $ID = $_SESSION['id'];
        $uuid = uuid();
        if ($class != "null" && $subclassID != "null"){
            $tabID = $subclassID;
            if ($class == "solution"){
                $tab = "imageSolution";
                $col = "solutionID";
            } else if ($class == "buffer"){
                $tab = "imageBuffer";
                $col = "bufferID";
            } else if ($class == "analyte"){
                $tab = "imageAnalyte";
                $col = "analyteID";
            } else {
                $tab = null;
            }
        } else {
            $tab = null;
        }

        $output = array("image" => "OK", "imagePerson" => "OK",
                        "imageProject" => "OK", "imageClass" => "OK");
        $query = "INSERT INTO image (imageGUID, description, gelPicture, filename) " .
                "VALUES ( '$uuid', '$description', '$blob', '$filename' );";
        if (mysqli_query( $link, $query )){
            $new_id = mysqli_insert_id($link);
        } else {
            $output["image"] = "Error : " . mysqli_error($link);
            $new_id = -1;
        }

        if ($new_id != -1){
            // Add the ownership record
            $query  = "INSERT INTO imagePerson SET imageID = $new_id, personID = $ID ";
            if (! mysqli_query( $link, $query )){
                $output["imagePerson"] = "Error : " . mysqli_error($link);
            }

            // Add the projectID
            $query  = "INSERT INTO imageProject SET imageID = $new_id, projectID = $projectID ";
            if (! mysqli_query( $link, $query )){
                $output["imageProject"] = "Error : " . mysqli_error($link);
            }

            // Add class
            if ($tab != null){
                $query  = "INSERT INTO $tab SET imageID = $new_id, $col  = $tabID ";
                if (! mysqli_query( $link, $query )){
                    $output["imageClass"] = "Error : " . mysqli_error($link);
                }
            }
        }
        echo json_encode($output);

    // GET project, solution, analyte, buffer information
    } else if ($action === 'GET_INIT_INFO'){
        $all_data = array();
        $PID = $_SESSION['id'];

        // fetch project info
        $query = "SELECT p.projectID, p.description FROM project AS p, projectPerson AS pp " .
                 "WHERE pp.projectID = p.projectID AND pp.personID = '$PID'";
        $sqlret = mysqli_query( $link, $query );
        $nrows = mysqli_num_rows($sqlret);
        $project = array("error" => null, "data" => null);
    
        if ($nrows > 0){
            $content = array();
            while($row = mysqli_fetch_assoc($sqlret)){
                array_push($content, $row);
            }
            $project["data"] = $content;
        } else {
            $project["error"] = mysqli_error($link);
        }

        $all_data["project"] = $project;

        // fetch solution info
        $query = "SELECT s.solutionID, s.description FROM solution AS s, solutionPerson AS sp " .
                 "WHERE sp.solutionID = s.solutionID AND sp.personID = '$PID'";
        $sqlret = mysqli_query( $link, $query );
        $nrows = mysqli_num_rows($sqlret);
        $solution = array("error" => null, "data" => null);
    
        if ($nrows > 0){
            $content = array();
            while($row = mysqli_fetch_assoc($sqlret)){
                array_push($content, $row);
            }
            $solution["data"] = $content;
        } else {
            $solution["error"] = mysqli_error($link);
        }

        $all_data["solution"] = $solution;

        // fetch buffer info
        $query = "SELECT b.bufferID, b.description FROM buffer AS b, bufferPerson AS bp " .
                 "WHERE bp.bufferID = b.bufferID AND bp.personID = '$PID'";
        $sqlret = mysqli_query( $link, $query );
        $nrows = mysqli_num_rows($sqlret);
        $buffer = array("error" => null, "data" => null);
    
        if ($nrows > 0){
            $content = array();
            while($row = mysqli_fetch_assoc($sqlret)){
                array_push($content, $row);
            }
            $buffer["data"] = $content;
        } else {
            $buffer["error"] = mysqli_error($link);
        }

        $all_data["buffer"] = $buffer;

        // fetch analyte info
        $query = "SELECT a.analyteID, a.description FROM analyte AS a, analytePerson AS ap " .
                 "WHERE ap.analyteID = a.analyteID AND ap.personID = '$PID'";
        $sqlret = mysqli_query( $link, $query );
        $nrows = mysqli_num_rows($sqlret);
        $analyte = array("error" => null, "data" => null);
    
        if ($nrows > 0){
            $content = array();
            while($row = mysqli_fetch_assoc($sqlret)){
                array_push($content, $row);
            }
            $analyte["data"] = $content;
        } else {
            $analyte["error"] = mysqli_error($link);
        }

        $all_data["analyte"] = $analyte;

        // fetch document info
        $all_data["image_info"] = get_image_info();
        
        echo json_encode($all_data);

    // fetch image info
    } else if ($action === 'GET_DOC_INFO'){
        $image_info = get_image_info();
        echo json_encode($image_info);
    
    // fetch Data
    } else if ($action === 'GET_DOC'){
        $PID = $_SESSION['id'];
        $imageID = $_POST['docID'];
        $imageGUID = $_POST['docGUID'];

        // fetch project info
        // $query = "SELECT fileData FROM supportingFile " .
        //          "WHERE fileID = '$fileID' AND fileGUID = '$fileGUID'";
        $query = "SELECT gelPicture, filename FROM image " .
                 "WHERE imageID = '$imageID' AND imageGUID = '$imageGUID'";
        $sqlret = mysqli_query( $link, $query );
        $nrows = mysqli_num_rows($sqlret);
        $doc = array("error" => null, "data" => null);
        if ($nrows > 0){
            $row = mysqli_fetch_assoc($sqlret);
            $blobData = $row["gelPicture"];
            $filename = $row["filename"];
            $blobEncoded = base64_encode($blobData);
            $doc["data"] = $blobEncoded;

            // header("Content-type: application/octet-stream");
            // header("Content-Disposition: attachment; filename=$filename");

            // echo $blobData;
        } else {
            $doc["error"] = mysqli_error($link);
        }
        echo json_encode($doc);
    } else if ($action === 'DEL_DOC'){
        $PID = $_SESSION['id'];
        $imageID = $_POST['docID'];
        $projectID = $_POST["projectID"];
        $class = $_POST["class"];
        $subclassID = $_POST["subclassID"];

        if ($projectID == 'null'){
            $projectID = null;
        }

        if ($class != "null" && $subclassID != "null"){
            $tabID = $subclassID;
            if ($class == "solution"){
                $tab = "imageSolution";
                $col = "solutionID";
            } else if ($class == "buffer"){
                $tab = "imageBuffer";
                $col = "bufferID";
            } else if ($class == "analyte"){
                $tab = "imageAnalyte";
                $col = "analyteID";
            } else {
                $tab = null;
            }
        } else {
            $tab = null;
        }


        $output = array("image" => "OK", "imagePerson" => "OK",
                        "imageProject" => "OK", "imageClass" => "OK");

        $query = "UPDATE image SET imageGUID=NULL, description = '', gelPicture = NULL, filename = '' " . 
                 "WHERE imageID = $imageID";
        if (! mysqli_query( $link, $query )){
            $output["image"] = "Error : " . mysqli_error($link);
        }

        $query = "DELETE FROM imagePerson WHERE imageID = $imageID AND personID = $PID";
        if (! mysqli_query( $link, $query )){
            $output["imagePerson"] = "Error : " . mysqli_error($link);
        }

        if ($projectID != null){
            $query = "DELETE FROM imageProject WHERE imageID = $imageID AND projectID = $projectID";
            if (! mysqli_query( $link, $query )){
                $output["imageProject"] = "Error : " . mysqli_error($link);
            }
        }

        if ($tab != null){
            $query  = "DELETE FROM $tab WHERE imageID = $imageID, $col  = $tabID ";
            if (! mysqli_query( $link, $query )){
                $output["imageClass"] = "Error : " . mysqli_error($link);
            }
        }

        echo json_encode($output);


    } 


    // Close connection
    mysqli_close($link);
  
}






?>