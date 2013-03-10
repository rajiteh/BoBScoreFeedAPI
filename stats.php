<html>
<head>
    <title>Hello</title>
</head>
<body>
<?php
if ($_REQUEST['key'] != "rcftw") die("Unauthorized.");

get_connection_count();

function get_connection_count() {
    try {
        $mins = (!empty($_REQUEST['period']) && is_numeric($_REQUEST['period'])) ? $_REQUEST['period'] : 5;
        $db = new PDO('sqlite:cache_db.s3db');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $since = time() - (60 * $mins);

        $q = "SELECT * FROM conn_log WHERE [LAST_ACCESS] > " . $since . " ORDER BY [LAST_ACCESS] DESC"; // Look for cache.
        $q2 = "SELECT * FROM stat_log WHERE [name] = \"connection_count\"";
        $statement = $db->prepare($q);
        $statement->execute();
        $result = $statement->fetchAll(PDO::FETCH_CLASS);
        $statement = $db->prepare($q2);
        $statement->execute();
        $result2 = $statement->fetchAll(PDO::FETCH_CLASS);
        echo "<pre>Active connections within last ". $mins ." minutes :   " . sizeof($result) . " | Total requests : " . $result2[0]->VALUE ."\n";
        ?>
        <table>
            <tr>
                <th>IP</th>
                <th>Times accessed</th>
                <th>Last access</th>
            </tr>
            <?php 
                foreach ($result as $record) {
                    echo '<tr><td>' . $record->IP . '</td>';
                    echo '<td>' . $record->TIMES_ACCESSED . '</td>';
                    echo '<td>' .  date("Y-m-d H:i:s",$record->LAST_ACCESS) . '</td></tr>';
                }
            ?>
        </table>
        <?php
    } catch (Exception $e) {
        echo "error";
        var_dump($e);
    }
}
?>
</body>
</html>