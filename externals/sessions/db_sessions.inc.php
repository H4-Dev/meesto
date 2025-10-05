<?php
/*
 * Custom session handler using MySQL
 * Docker-ready version for Meesto
 */

$dbc = NULL;

// Open connection
function open_session() {
    global $dbc;

    $dbc = mysqli_connect('db', 'meesto_user', 'meesto_pass', 'meesto');
    if (!$dbc) {
        error_log("Session DB connect error: " . mysqli_connect_error());
        return false;
    }

    session_cache_expire(30);
    return true;
}

// Close connection
function close_session() {
    global $dbc;
    return mysqli_close($dbc);
}

// Read session
function read_session($sid) {
    global $dbc;
    $sid = mysqli_real_escape_string($dbc, $sid);
    $q = "SELECT data FROM sessions WHERE id='$sid' LIMIT 1";
    $r = mysqli_query($dbc, $q);

    if ($r && mysqli_num_rows($r) === 1) {
        list($data) = mysqli_fetch_array($r, MYSQLI_NUM);
        return $data;
    }
    return '';
}

// Write session
function write_session($sid, $data) {
    global $dbc;
    $sid = mysqli_real_escape_string($dbc, $sid);
    $data = mysqli_real_escape_string($dbc, $data);

    $q = "REPLACE INTO sessions (id, data, last_accessed) VALUES ('$sid', '$data', NOW())";
    if (!mysqli_query($dbc, $q)) {
        error_log("Session write error: " . mysqli_error($dbc));
        return false;
    }

    if (isset($_SESSION['user_id'])) {
        $uid = (int)$_SESSION['user_id'];
        mysqli_query($dbc, "UPDATE sessions SET u_id=$uid WHERE id='$sid'");
    }

    if (isset($_SESSION['client'])) {
        $client = mysqli_real_escape_string($dbc, $_SESSION['client']);
        mysqli_query($dbc, "UPDATE sessions SET client='$client' WHERE id='$sid'");
    }

    return true;
}

// Destroy session
function destroy_session($sid) {
    global $dbc;
    $sid = mysqli_real_escape_string($dbc, $sid);
    mysqli_query($dbc, "DELETE FROM sessions WHERE id='$sid'");
    $_SESSION = array();
    return true;
}

// Clean old sessions
function clean_session($expire) {
    global $dbc;
    $expire = (int)$expire;
    $q = "DELETE FROM sessions WHERE DATE_ADD(last_accessed, INTERVAL $expire SECOND) < NOW()";
    mysqli_query($dbc, $q);
    return true;
}

// Register handlers
session_set_save_handler(
    'open_session',
    'close_session',
    'read_session',
    'write_session',
    'destroy_session',
    'clean_session'
);

// Start session
session_start();
?>
