<?php

include 'config.php';
global $dbhost, $dbusername, $dbpasswd, $dbname;

$link = mysqli_connect($dbhost, $dbusername, $dbpasswd, $dbname) or die("Could not connect to $dbname on database server.");

function mysqli_prep_query($link, $sql, $typeDef = false, $params = false, $asGenerator = false) {
    $executeQuery = function() use ($link, $sql, $typeDef, $params) {
        $result = false;
        $multiQuery = true;
        $bindParams = array();
        $bindParamsReferences = array();

        if ($stmt = mysqli_prepare($link, $sql)) {
            if (count($params) === count($params, COUNT_RECURSIVE)) {
                $params = array($params);
                $multiQuery = false;
            }

            if ($typeDef) {

                $bindParams = array_pad($bindParams,
                    (count($params, COUNT_RECURSIVE) - count($params)) / count($params), "");
                foreach ($bindParams as $key => $value) {
                    $bindParamsReferences[$key] = &$bindParams[$key];
                }
                array_unshift($bindParamsReferences, $typeDef);
                if (method_exists($stmt, 'bind_param')) {
                    $bindParamsMethod = new ReflectionMethod('mysqli_stmt', 'bind_param');
                    try {
                        $bindParamsMethod->invokeArgs($stmt, $bindParamsReferences);
                    } catch (ReflectionException $e) {
                        error_log("Error: Unable to bind the parameters. " . $e->getMessage());
                        mysqli_stmt_close($stmt);
                        return false;
                    }
                } else {
                    error_log("Error: bind_param method does not exist.");
                    mysqli_stmt_close($stmt);
                    return false;
                }
            }

            $result = array();
            foreach ($params as $queryKey => $query) {
                foreach ($bindParams as $paramKey => $value) {
                    $bindParams[$paramKey] = $query[$paramKey];
                }
                if (mysqli_stmt_execute($stmt)) {
                    $resultMetaData = mysqli_stmt_result_metadata($stmt);
                    if ($resultMetaData) {
                        $stmtRow = array();
                        $rowReferences = array();
                        while ($field = mysqli_fetch_field($resultMetaData)) {
                            $rowReferences[] = &$stmtRow[$field->name];
                        }
                        mysqli_free_result($resultMetaData);
                        if (method_exists($stmt, 'bind_result')) {
                            $bindResultMethod = new ReflectionMethod('mysqli_stmt', 'bind_result');
                            try {
                                $bindResultMethod->invokeArgs($stmt, $rowReferences);
                            } catch (ReflectionException $e) {
                                error_log("Error: Unable to bind the results. " . $e->getMessage());
                                mysqli_stmt_close($stmt);
                                return false;
                            }
                            while (mysqli_stmt_fetch($stmt)) {
                                $row = array();
                                foreach ($stmtRow as $key => $value) {
                                    $row[$key] = $value;
                                }
                                $row['AFFECTED'] = mysqli_stmt_affected_rows($stmt);
                                $row['AUTOID'] = mysqli_stmt_insert_id($stmt);
                                $result[] = $row; // Store the row in the result list
                                yield $row; // Yield the row for generator use
                            }
                            mysqli_stmt_free_result($stmt); // Frees the memory associated with the statement result.
                        } else {
                            error_log("Error: bind_result method does not exist.");
                            mysqli_stmt_close($stmt);
                            return false;
                        }
                    } else {
                        $row = array();
                        $row['AFFECTED'] = mysqli_stmt_affected_rows($stmt);
                        $row['AUTOID'] = mysqli_stmt_insert_id($stmt);
                        yield $row; // Yield the row for generator use
                        $result[] = $row; // Store affected rows in the result list
                    }
                } else {
                    error_log("Execute statement failed: " . mysqli_stmt_error($stmt));
                    yield false;
                    $result[] = false; // Store the failure in the result list
                }
            }

            mysqli_stmt_close($stmt);
        } else {
            error_log("Prepare statement failed: " . mysqli_error($link));
        }

        return $multiQuery ? $result : ($result[0] ?? $result);
    };

    if ($asGenerator) {
        return $executeQuery();
    } else {
        return iterator_to_array($executeQuery());
    }
}

?>
