<?php

/**
 * generate search table 
 */

$rows = db_q::select('tournaments')->fetch();
$t = new tournament();

/**
 * calculate current hour
 */

foreach ($rows as $row) {
    $info = $t->getSystemTimeInfo();
    print_r($info); die;
    
    $row['perioddate'] = 
    $b = db_rb::getBean('tournaments', 'id', $row['id']);
    
    
    echo date::getDateNow();
    
}
