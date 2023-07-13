<?php
/*
 * edit_images_handle.php
 */
include_once 'checkinstance.php';
include 'config.php';
include 'db.php';
include 'lib/utility.php';

function get_subclass_info($cat_type) {
    global $link;
    $PID = $_SESSION['id'];
    if (strcmp($cat_type, "project") === 0){
        $table = "imageProject";
        $column = "projectID";
    } else if (strcmp($cat_type, "solution") === 0) {
        $table = "imageSolution";
        $column = "solutionID";
    } else if (strcmp($cat_type, "buffer") === 0) {
        $table = "imageBuffer";
        $column = "bufferID";
    } else if (strcmp($cat_type, "analyte") === 0) {
        $table = "imageAnalyte";
        $column = "analyteID";
    } else {
        return null;
    }
    // fetch imageProject
    $query = "SELECT tab.imageID, tab.$column " . 
             "FROM ( SELECT i.imageID AS imageID FROM image AS i, imagePerson AS ip " .
                    "WHERE i.imageID = ip.imageID AND ip.personID = $PID ) AS sel, " .
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
    $query = "SELECT i.imageID, i.description, i.filename FROM image as i, imagePerson as ip " . 
             "WHERE i.imageID = ip.imageID AND ip.personID = $PID";
    $sqlret = mysqli_query( $link, $query );
    $nrows = mysqli_num_rows($sqlret);
    $image = array("error" => null, "data" => null);

    if ($nrows > 0){
        $content = array();
        while($row = mysqli_fetch_assoc($sqlret)){
            array_push($content, $row);
        }
        $image["data"] = $content;
    } else {
        $image["error"] = mysqli_error($link);
    }
    if (is_null($image["data"])){
        return $image;
    }

    $imageProject = get_subclass_info("project");
    $imageSolution = get_subclass_info("solution");
    $imageBuffer = get_subclass_info("buffer");
    $imageAnalyte = get_subclass_info("analyte");

    for ($ii = 0; $ii < count($image["data"]); $ii++ ){
        $row = $image["data"][$ii];
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
        $image["data"][$ii] = $row;
    } 

    return $image;
}

if (isset($_POST['image_action'])) {
    $image_action = $_POST['image_action'];

    // Store data in MySQL database
    if ($image_action === 'NEW') {
        // Get the uploaded data
        $image_blob = addslashes(file_get_contents($_FILES['image_blob']['tmp_name']));
        $image_description = $_POST['image_description'];
        $image_filename = $_POST['image_filename'];
        $image_blob = addslashes($image_blob);
        $ID = $_SESSION['id'];
        $uuid = uuid();

        $query = "INSERT INTO image (imageGUID, description, gelPicture, filename) " .
                "VALUES ( '$uuid', '$image_description', '$image_blob', '$image_filename' );";
        if (mysqli_query( $link, $query )){
            echo "image: OK : ";
            $new_id = mysqli_insert_id($link);
        } else {
            echo "image: Error : " . mysqli_error($link);
            $new_id = -1;
        }

        if ($new_id != -1){
            // Add the ownership record
            $query  = "INSERT INTO imagePerson SET imageID = $new_id, personID  = $ID ";
            if (mysqli_query( $link, $query )){
                echo "imagePerson: OK : ";
            } else {
                echo "imagePerson: Error : " . mysqli_error($link);
            }
        }
    // GET project, solution, analyte, buffer information
    } else if ($image_action === 'GET_INIT_INFO'){
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

        // fetch image info
        $all_data["image"] = get_image_info();
        
        echo json_encode($all_data);

    }



    
    // Close connection
    mysqli_close($link);
  
}






?>