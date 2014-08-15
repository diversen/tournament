<?php





if (session::isUser()) {
    $_MODULE_MENU[] = array(
        'title' => lang::translate('My lists', null, array ('no_translate' => true)),
        'url' => '/tournament/lists/me',

        'auth' => 'user'
    );

    $_MODULE_MENU[] = array(
        'title' => lang::translate('Top lists', null, array ('no_translate' => true)),
        'url' => '/tournament/lists/top',
        'auth' => 'user'
    );

}