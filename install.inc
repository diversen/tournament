<?php

$_INSTALL['VERSION'] = 2.12; 
$_INSTALL['MAIN_MENU_ITEMS'][] = array (
    'title' => lang::translate('Tourney overview', null, array ('no_translate' => true)),
    'url' => '/tournament/overview/index',
    'module_name' => 'tournament',
    'parent' => 0,

    'weight' => 1,
);

$_INSTALL['MAIN_MENU_ITEMS'][] = array (
    'title' => lang::translate('Gp-Lists', null, array ('no_translate' => true)),
    'url' => '/tournament/lists/top',
    'module_name' => 'tournament',
    'parent' => 0,
    'weight' => 1,
);

$_INSTALL['MAIN_MENU_ITEMS'][] = array (
    'title' => lang::translate('Tournaments', null, array ('no_translate' => true)),
    'url' => '/tournament/admin/admin',
    'module_name' => 'tournament',
    'parent' => 0,
    'auth' => 'admin',
    'weight' => 1,
    'section' => 'content',
    'admin_only' => 1
);

$_INSTALL['MAIN_MENU_ITEMS'][] = array (
    'title' => lang::translate('Reports', null, array ('no_translate' => true)),
    'url' => '/tournament/report/view',
    'module_name' => 'tournament',
    'parent' => 0,
    'auth' => 'admin',
    'weight' => 1,
    'section' => 'content',
    'admin_only' => 1
);

/*
$_INSTALL['ROUTES'][] = array ('#/tournament/lists/[0-9a-z/]#'=> 
    array ('method' => 'tournament::index',
           'module' => 'blog',
           'controller' => '/blog/index')
); */

$_INSTALL['IS_SHELL'] = 1;
