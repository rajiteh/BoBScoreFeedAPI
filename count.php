<?php

/*
    Statistics storage module for Battle of the Blues score API.

    Written by.
    -- Chamath Palihawadana
    -- Rajitha Perera <me@rajiteh.com>
*/


/**
    Log current connection.
    @return NULL
**/
function log_connection() {
    $USE_FILE = FALSE;

    if ($USE_FILE === TRUE) {
        _conn_log_file();
    } else {
        _conn_log_db();
    }
}


/**
    Updates total connection count information to a file.
    @return NULL
**/
function _conn_log_file() {
    if (file_exists('count_feed.txt')) 
        {
            $fil = fopen('count_feed.txt', 'r');
            $dat = fread($fil, filesize('count_feed.txt')); 
            //echo $dat+1;
            fclose($fil);
            $fil = fopen('count_feed.txt', 'w');
            fwrite($fil, $dat+1);
        }

        else
        {
            $fil = fopen('count_feed.txt', 'w');
            fwrite($fil, 1);
            //echo '1';
            fclose($fil);
        }
}

/**
    Logs current connection IP, timestamp and times accessed to database. 
    @return NULL
**/
function _conn_log_db() {
    try {
        $db = new PDO('sqlite:cache_db.s3db');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->exec("CREATE TABLE IF NOT EXISTS [conn_log] (
                   [IP] TEXT UNIQUE NOT NULL,
                   [TIMES_ACCESSED] INTEGER NULL,
                   [LAST_ACCESS]  TEXT NULL
                   )
        ");
        $db->exec("CREATE TABLE IF NOT EXISTS [stat_log] (
                   [NAME] TEXT UNIQUE NOT NULL,
                   [VALUE]  TEXT  NOT NULL
                   )
        ");


        $q11 = "INSERT OR IGNORE INTO [conn_log] ([IP], [TIMES_ACCESSED], [LAST_ACCESS]) VALUES ( \"" . $_SERVER['REMOTE_ADDR'] . "\", 0, \"". time() . "\" )"; 
        $q12 = "UPDATE [conn_log] SET [TIMES_ACCESSED] = [TIMES_ACCESSED] + 1, [LAST_ACCESS] = \"" . time() . "\" WHERE [IP]  = \"" . $_SERVER['REMOTE_ADDR'] . "\""; 
        $q21 = "INSERT OR IGNORE INTO [stat_log] ([NAME], [VALUE]) VALUES ('connection_count', 0)";
        $q22 = "UPDATE [stat_log] SET [VALUE] = [VALUE] + 1 WHERE [NAME] = 'connection_count'";


        $db->exec($q11);
        $db->exec($q12);
        $db->exec($q21);
        $db->exec($q22);
    } catch (Exception $e) {
        _conn_log_file();
    }
}
?>