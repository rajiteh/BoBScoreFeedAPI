<?php

/*
    Score feed parser and API for Battle of the Blues Android App.

    Originally Written by Chamath Palihawadana
    Improved by Rajitha Perera <me@rajiteh.com>
*/



include "count.php";    //Logging
log_connection(); 
init_db();


header("Refresh: 05;"); 
header('Content-Type: application/json; charset=utf8');
echo json_encode(get_score());



/**
    Get the score from cache or specified URL
    @return JSON Object containing the score data.
**/
function get_score() {
    $SCORE_URL = 'http://royalthomian.info/current_score2.html';    
	$SCORE_URL_DEBUG = 'http://DEBUG_URL_GOES_HERE!!';    
	$DEBUG_MODE = FALSE;
    
    $scores = _get_cached_scores();

    if ($scores === FALSE ) { //Cache is not available.

        libxml_use_internal_errors(true);
        $page = new DOMDocument();
        $page->strictErrorChecking = false;
        if ($DEBUG_MODE === TRUE) {
			$page->loadHTML(_file_get_curl($SCORE_URL_DEBUG)); //Download using Curl, seems more reliable 
		} else {
			$page->loadHTML(_file_get_curl($SCORE_URL)); //Download using Curl, seems more reliable 
		}
        
        $xml = simplexml_import_dom($page);
        $paths = array('//b[text()="', '"]/../text()[1]');
        $properties = array('TOTAL', 'WICKETS', 'OVERS');
        $output = array();

        foreach ($properties as $property) {
          $results = $xml->xpath($paths[0] . $property . $paths[1]);
          $output[$property] = trim($results[0]);
        }
        _set_cached_scores($output); //Update our cache.
        $scores = $output;
    }
    return (array)$scores;
    
}


/**
    Initializes the tables required for cache storage.
    @return NULL
**/
function init_db() {
    $db = new PDO('sqlite:cache_db.s3db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec("CREATE TABLE IF NOT EXISTS [scores] (
               [ID] INTEGER UNIQUE NOT NULL,
               [TOTAL] TEXT  NOT NULL,
               [WICKETS]  TEXT  NOT NULL, 
               [OVERS] TEXT NOT NULL,
               [TIMESTAMP] INTEGER NOT NULL
               )
    ");
}



/**
    Queries the database for cached version of scores.
    @return Array containing cached scores.
**/
function _get_cached_scores() {
    $CACHE_ENABLED = TRUE;
    $CACHE_EXPIRY = "10"; //in seconds. Increase this for better perfomance.

    if ($CACHE_ENABLED) {
        try {
            $db = new PDO('sqlite:cache_db.s3db');
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $cached_since = time() - $CACHE_EXPIRY;
            $q = "SELECT total, wickets, overs FROM scores WHERE id = 1 AND timestamp > " . $cached_since; // Look for cache.
            $statement = $db->prepare($q);
            $statement->execute();
            $result = $statement->fetchAll(PDO::FETCH_CLASS);
            if (sizeof($result) > 0) //We have cache;
                return $result[0];
            else //cache is old or non-existant. 
                return false;
        } catch (Exception $e) {
            //Fail safe, in case database craps out we force disable caching.
            return false;
        }       
    } else { //CACHING IS DISABLED.
        return false;
    }
}


/**
    Stores cached scores to the database with timestamp.
    @return TRUE on successful store, FALSE on failure
    @param $scores array
**/
function _set_cached_scores($scores) {
    try {
        $db = new PDO('sqlite:cache_db.s3db');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $q = "INSERT OR REPLACE INTO [SCORES] ([TOTAL], [WICKETS], [OVERS], [TIMESTAMP], [ID]) VALUES ( \"" . $scores["TOTAL"] . "\",\"".$scores["WICKETS"]."\", \"" . $scores["OVERS"] . "\", ". time() .", 1 )"; //Store cache.
        $statement = $db->prepare($q);
        if ($statement->execute()) {
            //Stored cache
            return true;
        } else {
            //Store failed.
            return false;
        }
    } catch (Exception $e) {
        return false;       
    }
    
}


/**
    Gets the specified URL content as a string.
    @return string of data or FALSE on error.
    @param $url string
**/
function _file_get_curl($url){
    $ch = curl_init();
    //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE); //This line gave the error because php is running safemode in 000webhost. Shouldn't affect any other.
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $url);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}


?>