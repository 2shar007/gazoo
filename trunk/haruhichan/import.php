#!/usr/bin/php
<?php

require("sql.php");

function main() {

$html = file_get_contents("http://haruhichan.com/page/calendar/anime.html");
//    print_r($html);        
    $array = split("calentry", $html);
    
    $array[0] = "";
    $res = "";
    $i = 1;
    while (isset($array[$i])) {
        //      print_r($array[$i]);       
        $raw = find_day($array[$i], $res, $i);        
        split_events($raw, $res, $i);

//        print_r($res);
//        die();
//        fread(STDIN, 1);        
        $i++;
    }


  }

function clean_date($date) {
    //printf($date);
    $timestamp = strtotime($date . " 2013");
    if ($timestamp === false) {
        die("The date string ($str) is bogus");    
    }
    return date('Y-m-d', $timestamp);
    
    //return ($date);
}

function insert_events($date, $name) {
    print_r($name);
    if ($name != "") {
    $date = clean_date($date[0]);
    $sql = "INSERT INTO `gazoo`.`event` (`id`, `name`, `start`, `end`, `description`) VALUES (NULL, '"
        . mysql_real_escape_string($name[0]) 
        . "', '"
        . $date 
        . " 00:00:00', NULL, 'http://haruhichan.com/page/calendar/anime');";
    //echo $sql;
    $res = mysql_query($sql) OR die(mysql_error());
    //echo mysql_insert_id();
    
    $sql = "INSERT INTO `gazoo`.`subject_event` (`id_event`, `id_subject`) VALUES ('" . mysql_insert_id() . "', '4');";
    $res = mysql_query($sql) OR die(mysql_error());
    
    }
}

function split_events($raw, &$res, $i) {
    //print_r($raw[0]);
    $array = split("calcolormain", $raw[0]);
    //echo "EVENTS\n";
    for($j = 1; isset($array[$j]); $j++) {
        find_events($array[$j], $res, $i);
    }
}

function find_events($raw, &$res, $i) {
    //print_r($raw);
    $pattern = "/.*<small>(.*)<\/small>.*/";
    $num =  preg_match_all($pattern, $raw, $matches);
    if ($num == 0) {
        printf("no events found");
    } else {
        echo $num . " events found\n";
    }
    
    $res[$i]['events'][]  = $matches[1]; 
    //print_r($matches);
    //echo "Matches\n";
    //die();
    insert_events($res[$i]['date'], $matches[1]);
}




function find_day($raw, &$res, $i) {
    $pattern = "/height=\"22\" colspan=\"2\">(.*)<\/th><\/tr>(.*)/";
    preg_match_all($pattern, $raw, $matches);
    
    print_r($matches);
    $res[$i]['date']  = $matches[1]; 
    
    return ($matches[2]);
}

main();
?>

