<?php
/*
 * grant_integrity.php
 *
 * functions to add/remove grants for config.php: $enable_PAM systems
 * primarily used by edit_users.php
 * code modified from uslims_permissions.php
 */

## collect config info

function db_obj_result( $db_handle, $query, $expectedMultiResult = false, $emptyok = false ) {
    $result = @mysqli_query( $db_handle, $query );

    if ( !$result || ( is_object( $result ) && !$result->num_rows ) ) {
        if ( $result ) {
            # $result->free_result();
        }
        if ( $emptyok ) {
            return false;
        }
        error_log( "db query failed : $query\ndb query error: " . mysqli_error($db_handle) . "\n" );
        if ( $result ) {
            debug_json( "query result", $result );
        }
        exit;
    }

    if ( is_object( $result ) && $result->num_rows > 1 && !$expectedMultiResult ) {
        error_log( "WARNING: db query returned " . $result->num_rows . " rows : $query\n" );
    }    

    if ( $expectedMultiResult ) {
        return $result;
    } else {
        if ( is_object( $result ) ) {
            return mysqli_fetch_object( $result );
        } else {
            return $result;
        }
    }
}

function do_user_delete( $db_handle, $user ) {
    return db_obj_result( $db_handle, "DROP USER '$user'" );
}
 
function do_user_add( $db_handle, $user ) {
    return db_obj_result( $db_handle, "CREATE USER IF NOT EXISTS '$user'@'%' IDENTIFIED VIA PAM USING 'mariadb' REQUIRE SSL", true );
}

function do_db_add( $db_handle, $db, $user ) {
    return db_obj_result( $db_handle, "GRANT USAGE,SELECT,INSERT,UPDATE,DELETE,EXECUTE ON $db.* TO '$user'@'%' IDENTIFIED VIA PAM USING 'mariadb' REQUIRE SSL", true );
}

function do_db_remove( $db_handle, $db, $user ) {
    return db_obj_result( $db_handle, "REVOKE SELECT,INSERT,UPDATE,DELETE,EXECUTE ON $db.* from '$user'@'%'", true );
}

function get_grants( $db_handle, $user, $host = '%' ) {

    $res2 = db_obj_result( $db_handle, "show grants for '$user'@'$host'", true, true );

    # build grant info tables for user
    $grants                         = (object)[];

    $grants->usage                  = (object)[];
    $grants->usage->exists          = false;
    $grants->usage->pam             = false;
    $grants->usage->ssl             = false;
    $grants->usage->expected_format = false;

    $grants->dbs                    = (object)[];

    $grants->unknown_lines          = 0;

    if ( $res2 ) {
        while( $row2 = mysqli_fetch_array($res2) ) {
            foreach ( $row2 as $v ) {
                if ( preg_match( '/^GRANT USAGE/', $v ) ) {
                    $grants->usage->exists = true;
                    if ( preg_match( '/IDENTIFIED VIA PAM/', $v ) ) {
                        $grants->usage->pam = true;
                    }
                    if ( preg_match( '/IDENTIFIED VIA PAM/', $v ) ) {
                        $grants->usage->ssl = true;
                    }
                    if ( preg_match( '/REQUIRE SSL/', $v ) ) {
                        $grants->usage->ssl = true;
                    }
                    if ( strstr( $v, "USAGE ON *.* TO ", $v ) ) {
                        $grants->usage->expected_format = true;
                    }
                }
                else if ( preg_match( '/^GRANT SELECT, INSERT, UPDATE, DELETE, EXECUTE ON `([^`]+)`/', $v, $matches ) ) {
                    $grants->dbs->{$matches[1]} = (object)[];
                } else {
                    ++$grants->unknown_lines;
                }
            }
        }
    }

    return $grants;
}

function analyze_grants( $db, $grants, $authenticatePAM = false, $userlevel = 0 ) {

    $tofix         = (object)[];
    $tofix->errors = "";
    
    if ( $authenticatePAM && $userlevel >= 2 ) {
        if ( !$grants->usage->exists ) {
            $tofix->errors .= " Missing USAGE;";
            $tofix->add_usage = true;
        }
        if ( !isset( $grants->dbs->{$db} ) ) {
            $tofix->errors .= " Missing GRANTS;";
            $tofix->add_grants = true;
        }
    } else {
        if ( $grants->usage->exists
             && count( (array) $grants->dbs ) - ( isset( $grants->dbs->{$db} ) ? 1 : 0 ) <= 0
            ) {
            $tofix->errors .= " Has USAGE;";
            $tofix->remove_usage = true;
        }
        if ( isset( $grants->dbs->{$db} ) ) {
            $tofix->errors .= " Has GRANTS;";
            $tofix->remove_grants = true;
        }
    }

    if ( $grants->unknown_lines ) {
        $tofix->errors .= sprintf( " %3 unknown lines;", $grants->unknown_lines );
    }

    $tofix->errors = trim( $tofix->errors, "; " );

    return $tofix;
}


function grant_integrity( $user, $authenticatePAM, $userlevel ) {
    global $dbname;

    global $dbhost;
    global $configs;

    $db_handle = mysqli_connect( $dbhost, $configs[ 'grants' ][ 'user' ], $configs[ 'grants' ][ 'password' ] );
    if ( !$db_handle ) {
        return "Error contacting the database when trying to check GRANT permissions!";
    }

    $tofix = analyze_grants(
        $dbname
        ,get_grants( $db_handle, $user )
        ,$authenticatePAM
        ,$userlevel
        );

    if ( empty( $tofix->errors ) ) {
        return "";
    }

    $errors = "";

    $anywork = false;

    if ( isset( $tofix->remove_usage ) ) {
        $anywork = true;
        if ( !do_user_delete( $db_handle, $user ) ) {
            $errors .= "ERRORS encounterd when trying to delete user '$user'<br>";
        }
    } else if ( isset( $tofix->remove_grants ) ) {
        $anywork = true;
        if ( !do_db_remove( $db_handle, $dbname, $user ) ) {
            $errors .= "ERRORS encounterd when trying to remove user '$user' from db '$dbname'<br>";
        }
    }

    if ( isset( $tofix->add_usage ) ) {
        $anywork = true;
        if ( !do_user_add( $db_handle, $user ) ) {
            $errors .= "ERRORS encounterd when trying to add user '$user'<br>";
        }
    }
    if ( isset( $tofix->add_grants ) ) {
        $anywork = true;
        if ( !do_db_add( $db_handle, $dbname, $user ) ) {
            $errors .= "ERRORS encounterd when trying to add user '$user' to db '$dbname'<br>";
        }
    }

    mysqli_close( $db_handle );

    if ( empty( $errors ) && $anywork ) {
        return "GRANTS successfully updated for '$user'";
    }
}
