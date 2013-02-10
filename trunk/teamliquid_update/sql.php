<?php

$host = "213.251.169.168";
$user = "gazoo";
$db = "gazoo";
$pass = "swparisgazoo";
//echo "PLEASE ADD DB PASSWORD then comment this line\n"; fread(STDIN, 1);



$link = mysql_connect($host, $user, $pass);
if (!$link) {
  die('Could not connect: ' . mysql_error());
 }
else {
    echo "DB: Connected\n";
}

mysql_select_db($db);

?>
