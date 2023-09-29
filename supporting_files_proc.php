<?php
/*
 * supporting_files_proc.php
 */
include_once 'checkinstance.php';
include 'config.php';
include 'db.php';
include 'lib/utility.php';

function get_tab_col_name($class) {
    $output = array("table" => null, "column" => null);
    if ($class == "solution"){
        $output["table"]  = "imageSolution";
        $output["column"] = "solutionID";
    } else if ($class == "buffer"){
        $output["table"]  = "imageBuffer";
        $output["column"] = "bufferID";
    } else if ($class == "analyte"){
        $output["table"]  = "imageAnalyte";
        $output["column"] = "analyteID";
    } 
    return $output;
}

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

$sf_dir = dirname(__FILE__) . '/sf_data/';
if (! is_dir($sf_dir)){
    mkdir($sf_dir);
}
$sf_dir = 'sf_data/';
$delete_file = null;

if (isset($_POST['action'])) {
    $action = $_POST['action'];

    // Store data in MySQL database
    if ($action === 'NEW_DOC') {
        // Get the uploaded data
        $output = array("blob" => "OK", "image" => "OK", "imagePerson" => "OK",
                        "imageProject" => "OK", "imageClass" => "OK");

        $filepath = $sf_dir . $_POST['local_filename'];
        $description = $_POST['description'];
        $filename = $_POST['filename'];
        $projectID = $_POST["projectID"];
        $class = $_POST["class"];
        $subclassID = $_POST["subclassID"];
        $ID = $_SESSION['id'];
        $uuid = uuid();

        if (file_exists($filepath)){
            $delete_file = $filepath;
            $blob = file_get_contents($filepath);
        } else {
            $output["blob"] = "Error: File not found !";
            mysqli_close($link);
            echo json_encode($output);
            exit();
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

        $query = "UPDATE image SET description = ? , filename = ? , gelPicture = ? WHERE imageID = ?";
            $smt = mysqli_prepare($link, $query);
            $smt->bind_param('ssbi', $description, $filename, $blob, $imageID);
            $smt->send_long_data(2, $blob);
            if ($smt != null){
                if (! $smt->execute()){
                    $output["image"] = "Error : " . $smt->error;
                }
                $smt->close();
            }
        
        $query = "INSERT INTO image (imageGUID, description, gelPicture, filename) VALUES ( ?, ?, ?, ? )";
        $smt = mysqli_prepare($link, $query);
        $smt->bind_param('ssbs', $uuid, $description, $blob, $filename);
        $smt->send_long_data(2, $blob);
        if ($smt->execute()){
            $new_id = $smt->insert_id;
        } else {
            $output["image"] = "Error : " . $smt->error;
            $new_id = -1;
        }
        $smt->close();

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
    } else if ($action === 'GIVE_INIT_INFO'){
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
    } else if ($action === 'GIVE_DOC_INFO'){
        $image_info = get_image_info();
        echo json_encode($image_info);
    
    // fetch Data
    } else if ($action === 'GIVE_BLOB'){
        $PID = $_SESSION['id'];
        $imageID = $_POST['docID'];
        $imageGUID = $_POST['docGUID'];

        $query = "SELECT gelPicture, filename FROM image " .
                 "WHERE imageID = '$imageID' AND imageGUID = '$imageGUID'";
        $sqlret = mysqli_query( $link, $query );
        $nrows = mysqli_num_rows($sqlret);
        $doc = array("error" => null, "path" => null, "size" => null);
        if ($nrows > 0){
            $row = mysqli_fetch_assoc($sqlret);
            $blobData = $row["gelPicture"];
            $filename = $row["filename"];
            $fpath = $sf_dir . $imageGUID;
            $state = is_file($fpath) && strlen($blobData) != filesize($fpath);
            $state = $state || (! is_file($fpath));
            if ($state){
                file_put_contents($fpath, $blobData);
                $doc["path"] = $fpath;
                $doc["size"] = filesize(realpath($fpath));
            }
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

        $query = "UPDATE image SET imageGUID = NULL, description = '', gelPicture = '', filename = '' " . 
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
            $query  = "DELETE FROM $tab WHERE imageID = $imageID AND $col  = $tabID ";
            if (! mysqli_query( $link, $query )){
                $output["imageClass"] = "Error : " . mysqli_error($link);
            }
        }

        echo json_encode($output);
    } else if ($action === 'UPDATE_DOC'){
        $output = array("blob" => "OK", "image" => "OK", "imageProject" => "OK", "imageClass" => "OK");
        $PID = $_SESSION['id'];
        $imageID = $_POST['docID'];
        $projectID = $_POST["projectID"];
        $description = $_POST["description"];
        $filename = $_POST["filename"];
        $class = $_POST["class"];
        $subclassID = $_POST["subclassID"];
        $filepath = $sf_dir . $_POST['local_filename'];
        $blob = null;
        if ($_POST['local_filename'] != 'null'){
            if (file_exists($filepath)){
                $delete_file = $filepath;
                $blob = file_get_contents($filepath);
            } else {
                $output["blob"] = "Error: File not found !";
                mysqli_close($link);
                echo json_encode($output);
                exit();
            }
        }

        $smt = null;
        if($description != 'null' && $blob != null){
            $query = "UPDATE image SET description = ? , filename = ? , gelPicture = ? WHERE imageID = ?";
            $smt = mysqli_prepare($link, $query);
            $smt->bind_param('ssbi', $description, $filename, $blob, $imageID);
            $smt->send_long_data(2, $blob);
        } else if ($description == 'null' && $blob != null){
            $query = "UPDATE image SET filename = ? , gelPicture = ? WHERE imageID = ?";
            $smt = mysqli_prepare($link, $query);
            $smt->bind_param('sbi', $filename, $blob, $imageID);
            $smt->send_long_data(1, $blob);
        } else if ($description != 'null' && $blob == null){
            $query = "UPDATE image SET description = ? WHERE imageID = ?";
            $smt = mysqli_prepare($link, $query);
            $smt->bind_param('si', $description, $imageID);
        }
        if ($smt != null){
            if (! $smt->execute()){
                $output["image"] = "Error : " . $smt->error;
            }
            $smt->close();
        }
        
        if ($projectID != 'null'){
            $projectID = explode('_', $projectID);
            if ($projectID[0] == "null"){
                $query  = "INSERT INTO imageProject SET imageID = $imageID, projectID = $projectID[1] ";
                if (! mysqli_query( $link, $query )){
                    $output["imageProject"] = "Error : " . mysqli_error($link);
                }
            } else {
                $query  = "UPDATE imageProject SET projectID = $projectID[1] " . 
                          "WHERE imageID = $imageID AND projectID = $projectID[0]";
                if (! mysqli_query( $link, $query )){
                    $output["imageProject"] = "Error : " . mysqli_error($link);
                }
            }
        }

        if ($class != "null" && $subclassID != "null"){
            $class = explode('_', $class);
            if ($class[0] == 'null'){
                $tab_col = get_tab_col_name($class[1]);
                $table = $tab_col["table"];
                $column = $tab_col["column"];
                $query  = "INSERT INTO $table SET imageID = $imageID, $column = $subclassID ";
                if (! mysqli_query( $link, $query )){
                    $output["imageProject"] = "Error : " . mysqli_error($link);
                }
            } else if ($class[1] == 'null'){
                $tab_col = get_tab_col_name($class[0]);
                $table = $tab_col["table"];
                $column = $tab_col["column"];
                $query  = "DELETE FROM $table WHERE imageID = $imageID AND $column = $subclassID ";
                if (! mysqli_query( $link, $query )){
                    $output["imageProject"] = "Error : " . mysqli_error($link);
                }

            } else if ($class[0] != 'null' && $class[1] != 'null' && $class[0] == $class[1]){
                $subclassID = explode('_', $subclassID);
                $tab_col = get_tab_col_name($class[0]);
                $table = $tab_col["table"];
                $column = $tab_col["column"];
                $tID_0 = $subclassID[0];
                $tID_1 = $subclassID[1];
                $query  = "UPDATE $table SET $column = $tID_1 WHERE imageID = $imageID AND $column = $tID_0 ";
                if (! mysqli_query( $link, $query )){
                    $output["imageProject"] = "Error : " . mysqli_error($link);
                }
            } else if ($class[0] != 'null' && $class[1] != 'null' && $class[0] != $class[1]){
                $subclassID = explode('_', $subclassID);
                $msg = '';
                $tab_col = get_tab_col_name($class[0]);
                $table = $tab_col["table"];
                $column = $tab_col["column"];
                $tID = $subclassID[0];
                $query  = "DELETE FROM $table WHERE imageID = $imageID AND $column = $tID ";
                if (! mysqli_query( $link, $query )){
                    $msg = $msg . mysqli_error($link) . "\n";
                }

                $tab_col = get_tab_col_name($class[1]);
                $table = $tab_col["table"];
                $column = $tab_col["column"];
                $tID = $subclassID[1];
                $query  = "INSERT INTO $table SET imageID = $imageID, $column = $tID ";
                if (! mysqli_query( $link, $query )){
                    $output["imageProject"] = $msg . "Error : " . mysqli_error($link);
                }
            }
        } 

        echo json_encode($output);
    }
    else if ($action === 'GET_BLOB') {
        $data = $_POST['data'];
        $filename = $_POST['filename'];
        $filepath = $sf_dir . $filename;
        $file = fopen($filepath, 'a+');
        fwrite($file, base64_decode($data));
        fclose($file);
        echo "OK";
    }
    else if ($action === 'DEL_FILE'){
        $filepath = $sf_dir . $_POST['filename'];
        if (realpath($filepath)){
            $delete_file = realpath($filepath);
            echo "OK";
        } else {
            echo "Not found";
        }
    }

}

// Close connection
mysqli_close($link);
if ($delete_file != null){
    unlink($delete_file);
}

// Garbage collector
$sf_dir = realpath($sf_dir);
$sf_files = scandir($sf_dir);
$sf_files = array_diff($sf_files, array('.', '..'));
foreach ($sf_files as $file) {
    $file_path = $sf_dir . '/' . $file;
    if (is_file($file_path)) {
        $file_time = filemtime($file_path);
        $current_time = time();
        $threshold = 30 * 60; // delete files existing over 30 minutes that has not been deleted so far for any reason
        if ($current_time - $file_time >= $threshold) {
            unlink($file_path);
        }
    }
}


?>