<?php

// SELECT * TOURNAMENTS THAT ARE older than date_dt_late
// and update them
moduleloader::includeModule('tournament/import');

function tournament_date_up($options = array()) {
    db_rb::connect();
    tournament_import_module::updateAllDatetime();
}

function tournament_import($options = array()) {
    db_rb::connect();
    $t = new tournament_import();
    $t->pokerAction();
}

mainCli::setCommand('tournament', array(
    'description' => 'Tournament commands',
));

mainCli::setOption('tournament_date_up', array(
    'long_name'   => '--date-up',
    'description' => 'Updates old dates',
    'action'      => 'StoreTrue'
));

mainCli::setOption('tournament_import', array(
    'long_name'   => '--import',
    'description' => 'Import all tounrmanets',
    'action'      => 'StoreTrue'
));
