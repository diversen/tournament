<?php


class tournament {
    
    public function updateTimezone () {
        
        if (intl::validTimezone($_POST['timezone'])) {
            if (session::isUser()) {
                cache::set('account_timezone', session::getUserId(), $_POST['timezone']);
            } else {
                session::setCookie('account_timezone', $_POST['timezone']);
            }
            
            
            session::setActionMessage(lang::translate('Timezone has been updated'));
            http::locationHeader($_SERVER['REQUEST_URI']);
        } else {
            session::setActionMessage(lang::translate('Timezone is not valid'));
            http::locationHeader($_SERVER['REQUEST_URI']);
        }

    }
    public function timezoneForm ($default) {
        $dropdown = intl::getTimezones();

        $f = new html();
        $f->formStart('timezone_form');
        $f->legend(lang::translate('Set timezone for your system'));
        $f->setSelectTopValue(array());
        $f->select('timezone', $dropdown, 'zone', 'id', $default, array(), null);
        $f->submit('timezone_submit', lang::system('system_submit'));
        $f->formEnd();
        echo $f->getStr();  
    }
    
    public function setTimezone () {
        echo '<div class="timezone_form">';
        
        moduleloader::includeModule('locales'); 
        echo locales_views::timezoneInfo();
        
        if (isset($_POST['timezone'])) {
            $this->updateTimezone();
            
        }

        $default = $this->getUserDefaultTimezone();
        $this->timezoneForm($default);
        echo '</div>';
    }
    
    public static function getUserDefaultTimezone () {
        $default = config::getMainIni('date_default_timezone');
        $user_default = cache::get('account_timezone', session::getUserId());
        if ($user_default) {
            $default = $user_default;
        } else {
            if (isset($_COOKIE['account_timezone'])) {
                $default = $_COOKIE['account_timezone'];
            }
        }
        return $default;
    }
    
    

    public function searchOptions () {
        
        $str = '<div class = "tournament_options">';
        $items = array (
            //array ('title' => 'Search', 'link' => '#', array('class' => 'search_toogle headline')),
            //array ('title' => 'Set timezone', 'link' => '#', array('class' => 'timezone_toogle headline')),
        );
        
        $items[] = html::createLink('#', 'Filter',array('class' => 'search_toogle'));
        //$str.= MENU_SUB_SEPARATOR;
        $items[] = html::createLink('#', 'Set timezone',  array('class' => 'timezone_toogle'));
        $str.= '</div>';
        layout::setModuleMenuExtra($items);

        return $str;

    }
    
    public function checkPreviousSearch() {        
        if (session::isUser()) {
            if (!isset($_GET['submit'])) {
                $_GET = cache::get('tournament_search', session::getUserId());
            } else {
                cache::set('tournament_search', session::getUserId(), $_GET);
            }
        }
    }
    
    public function indexAction() {
        $t = new tournament_search();
        $t->assets(array ('reload' => true));

        $this->checkPreviousSearch();
        echo $this->searchOptions();
        $this->setTimezone();
        echo $t->searchForm();
        
        //echo "<br />";
        
        $num_rows = $t->getNumRows();
        echo html::getHeadline("$num_rows tournaments found");
        
        $l = new tournament_lists_module();
        
        $limit = 100;
        $p = new paginate($num_rows, $limit);
        $rows = $t->getRows($p->from, $limit);
        


        $options = array();
        $options['list_options'] = 1;
        if (session::isUser()) { 
            $options['report_options'] = 1;
        }

        $t->displayRows($rows, $options);
        
        
        echo $p->getPagerHTML();
        echo "<br />";
        
        $env = config::getEnv();
        if ($env == 'development') {
            echo "<pre>" . tournament_search::$q . "</pre>";
        }
    }
    
    public function getBanner () { ?>
<!--
<script language="javascript" type="text/javascript">
   var p = document.location.protocol;
   if (!p || p == null) p = "";
   var s = (p.toLowerCase().indexOf("http") == 0 ? p : "http:") + "//banners.fulltiltpoker.com/en/ad/12084315/480x327.js";
   var r = Math.floor(Math.random()*999999)+''+Math.floor(Math.random()*999999);
   var c = document.createElement("script");
   c.type = "text/javascript";
   c.src = s+"?r="+r;
   c.id = ""+r;
   c.async = true;
   var a = document.getElementsByTagName("script");
   var t = a[a.length-1];
   t.parentNode.insertBefore(c, t);
</script>
<noscript><a href="http://banners.fulltiltpoker.com/en/ad/12084315/480x327ftfdb.gif.click?rq=noscript&vs="><img src="http://banners.fulltiltpoker.com/en/ad/12084315/480x327ftfdb.gif?rq=noscript&vs=" width="480" height="327" alt="" border="0"/></a></noscript>
-->
<?php
    }

    
    public function adminAction() {
        if (!session::checkAccess('admin')) {
            return;
        }
        
        $t = new tournament_search();
        $t->assets();

        echo $this->searchOptions();
        $this->setTimezone();
        echo $t->searchForm();
        
        //echo "<br />";
        
        

        $env = config::getEnv();
        if ($env == 'development') {
            $limit = 100;
        } else {
            $limit = 100;
        }
        
        $num_rows = $t->getNumRows();
        echo html::getHeadline("$num_rows tournaments found");
        $p = new paginate($num_rows, $limit);
        $rows = $t->getRows($p->from, $limit);

        $options = array();
        if (session::isUser()) {
            $options['admin_edit'] = 1;
            $options['admin_delete'] = 1;
        }

        $t->displayRows($rows, $options);
        echo $p->getPagerHTML();
        if ($env == 'development') {
            echo "<pre>" . tournament_search::$q . "</pre>";
        }
    }



    public function _aryToSelect ($ary) {
        $ret = array ();
        foreach ($ary as $key => $val) {
            $n = array ('id' => $key, 'title' => $val);
            $ret[] = $n;
        }
        return $ret;
    }
    
    public function _normalizePost($post) {
        $post['pricepool'] = tournament_import_module::_normalizeMoney($post['pricepool']);
        $post['buyin'] = tournament_import_module::_normalizeMoney($post['buyin']);
        $post['kobonus'] = tournament_import_module::_normalizeMoney($post['kobonus']);
        $post['rake'] = tournament_import_module::_normalizeMoney($post['rake']);
        $post['begin'] = $this->_timestampSimplify($post['begin']);
        $post['latereg'] = tournament_import_module::_normalizeInt($post['latereg']);
        if (empty($post['latereg'])) {
            $post['latereg'] = 0;
        }
        return $post;
    }
    
    /**
     * updates a tournament based on post
     * @return type
     */
    public function update () {

        db_rb::connect();
        $id = uri::fragment(3);
        $bean = db_rb::getBean('tournaments', 'id', $id);
        
        $ts = new tournament_search();
        $formats = $ts->getFormats();
        $post = db::prepareToPost();
        $post = $this->_normalizePost($post);
        $post['begin'] = $post['begin'] . ":00";
        $post = tournament_import_module::calculateDateTimes($post);
             
        foreach($formats as $key => $format) {
            if (!isset($_POST[$key])){
                $post[$key] = 0;     
            }
        }
        
        R::begin();
        $bean = db_rb::arrayToBean($bean, $post);
        R::store($bean);
        return R::commit();
        
    }
    
    /**
     * updates a tournament based on post
     * @return type
     */
    public function updateReplace () {

        db_rb::connect();
        $id = uri::fragment(3);
        $bean = db_rb::getBean('tournaments_replace', 'parent', $id);
        
        $ts = new tournament_search();
        $formats = $ts->getFormats();
        $_POST['parent'] = $id;
        $post = db::prepareToPost();
        
        $post = $this->_normalizePost($post);
        $post['begin'] = $post['begin'] . ":00";
        $post = tournament_import_module::calculateDateTimes($post);
             
        foreach($formats as $key => $format) {
            if (!isset($_POST[$key])){
                $post[$key] = 0;     
            }
        }
        
        R::begin();
        $bean = db_rb::arrayToBean($bean, $post);
        R::store($bean);
        return R::commit();
        
    }
    
    public function _timestampSimplify ($val) {
        $ary = explode(":", $val);
        $new = $ary[0] . ":" . $ary[1];
        if (!preg_match("/(2[0-3]|[01][0-9]):[0-5][0-9]/", $new)) {
            return '00:00';
        }
        return $new;
        
    }
    
    /**
     * edit tournament action
     */
    public function editAction() {
        
        if (!session::checkAccess('admin')) {
            return;  
        }
        date_default_timezone_set(config::getMainIni('date_default_timezone'));
        
        $id = uri::fragment(3);
        $values = $this->get($id);
        $values['begin'] = $this->_timestampSimplify($values['begin']);
        
        if (isset($_POST['submit'])) {
            $this->update();
            if (isset($_GET['return_to'])) {
                $return_to = rawurldecode($_GET['return_to']);
            } else {
                $return_to = $_SERVER['REQUEST_URI'];
            }
            http::locationHeader($return_to, 'Tournament updated!');
        }
        $this->editForm($values, array ('edit' => true));
        
    }
    
        /**
     * edit tournament action
     */
    public function replaceAction() {
        
        if (!session::checkAccess('admin')) {
            return;  
        }
        date_default_timezone_set(config::getMainIni('date_default_timezone'));
        
        $id = uri::fragment(3);
        
        $values = $this->getReplace($id);
        if (empty($values)) {
            $values = $this->get($id);
        }
        $values['begin'] = $this->_timestampSimplify($values['begin']);
        
        if (isset($_POST['submit'])) {
            $this->updateReplace();
            if (isset($_GET['return_to'])) {
                $return_to = rawurldecode($_GET['return_to']);
            } else {
                $return_to = $_SERVER['REQUEST_URI'];
            }
            http::locationHeader($return_to, 'Tournament updated!');
        }
        $this->editForm($values, array ('replace' => true));
        
    }
    
    public function getReplace ($id) {
        return db_q::select('tournaments_replace')->filter('parent =', $id)->fetchSingle();
    }
   
    
    public function displaySingle ($id, $options = array ()) {
        
        $t = new tournament_module();
        $row = $t->get($id);
        
        $rows = array ();
        $rows[] = $row;
        
        $ts = new tournament_search();
        $ts->assets();
        
        $ts->displayRows($rows, $options);
    }
   /**
     * updates a tournament based on post
     * @return type
     */
    public function add () {

        db_rb::connect();
        $bean = db_rb::getBean('tournaments');

        $post = db::prepareToPost();
        $post = $this->_normalizePost($post);
        
        $post['begin'] = $post['begin'] . ":00";
        
        
        $post = tournament_import_module::calculateDateTimes($post);
        //print_r($post); die;

        $bean = db_rb::arrayToBean($bean, $post);
        R::begin();
        R::store($bean);
        return R::commit();
        
    }

    
    /**
     * edit tournament action
     */
    public function addAction() {
        if (!session::checkAccess('admin')) {
            return;  
        }
        
        date_default_timezone_set(config::getMainIni('date_default_timezone'));
        
        if (isset($_POST['submit'])) {
            $this->add();
            if (isset($_GET['return_to'])) {
                $return_to = rawurldecode($_GET['return_to']);
            } else {
                $return_to = $_SERVER['REQUEST_URI'];
            }
            http::locationHeader($return_to, 'Tournament added!');
        }
        $this->editForm(array ());
        
    }
    
    public function editForm ($values, $options = array ()) {
        $t = new tournament_search();
        
        $f = new html();
        $f->init($values, 'submit');
        $f->setFieldSet(' ');
        echo $f->formStartClean('tournament', 'post');

        // GTD and buy-in
        $text_opt = array('size' => 8);
        
        echo '<table class="table_white"><tr>';
        echo '<td>';
        echo html::getHeadline('Name');
        echo $f->textClean('org_name', null, array ('size' => 32));
        echo "</td>";
        
        echo '<td>';
        if (isset($options['edit'])) {
             $replace = html::createLink("/tournament/admin/replace/$values[id]", 'Replace');
        } else {
            $replace = '';
        }
        echo html::getHeadline("Day $replace");
        $ary = array (
            array ('id' => 'any', 'title' => 'All'),
            array ('id' => 'mon', 'title' => 'Monday'),
            array ('id' => 'tue', 'title' => 'tuesday'),
            array ('id' => 'wed', 'title' => 'Wednesday'),
            array ('id' => 'thu', 'title' => 'thursday'),
            array ('id' => 'fri', 'title' => 'friday'),
            array ('id' => 'sat', 'title' => 'saturday'),
            array ('id' => 'sun', 'title' => 'sunday'),
            // 
        );
        
        //$f->setSelectTopValue(array('id' => 0, 'title' => 'Select'));
        echo $f->selectClean('day', $ary, 'title', 'id');
        echo "</td>"; 
        
        echo '<td>';
        echo html::getHeadline('hh:mm (CET)');
        echo $f->textClean('begin', null, array ('size' => 1, 'maxlength' => 5));
        echo "</td>";
        
        echo '<td>';
        echo html::getHeadline('Late Reg');
        echo $f->textClean('latereg', null, array ('size' => 1));
        echo "</td>";
        
        echo "</tr></table>";
        
        echo '<table class="table_white"><tr>';
        echo '<td>';
        echo html::getHeadline('Cur.');
        $ary = array (
            array ('id' => '$', 'title' => '$'),
            array ('id' => '€', 'title' => '€')
        );
        
        //$f->setSelectTopValue(array('id' => 0, 'title' => 'Select'));
        echo $f->selectClean('moneycode', $ary, 'title', 'id');
        echo "</td>"; 
        
        
        echo '<td>';
        echo html::getHeadline('GTD');
        echo $f->textClean('pricepool', null, $text_opt);
        echo "</td>";
        
        echo '<td>';
        echo html::getHeadline('Buy-in');
        echo $f->textClean('buyin', null, $text_opt);
        echo "</td>";
        
        echo '<td>';
        echo html::getHeadline('KO');
        echo $f->textClean('kobonus', null, $text_opt);
        echo "</td>";
        
        echo '<td>';
        echo html::getHeadline('Rake');
        echo $f->textClean('rake', null, $text_opt);
        echo "</td>";

        echo '<td>';
        echo html::getHeadline('Site');        

        $c_sites = $t->getSites();
        $f = new html();
        $sites = $this->_aryToSelect($c_sites);
        echo  $f->selectClean('site', $sites, 'title', 'id');
        echo "</td>";
        
        echo '<td>';
        echo html::getHeadline('Games');
        $c_games = $t->getGames();
        $games = $this->_aryToSelect($c_games);
        echo $f->selectClean('gametype', $games, 'title', 'id');
        echo "</td>"; 
        
        echo '<td>';
        echo html::getHeadline('Limits');
        $c_limits = $t->getLimits();
        $limits = $this->_aryToSelect($c_limits);
        echo $f->selectClean('limittype', $limits, 'title', 'id');
        echo "</td>";
        
        echo "</tr></table>";
        echo '<table class="table_white"><tr>';
        
        echo '<td>';
        echo html::getHeadline('Formats');
        $formats =  $t->getFormats();
        $f->disableBr();
        
        foreach ($formats as $key => $val) {
            echo $f->checkboxClean($key, 0);
            echo $val;
            echo "<br />";
        }
        //return $ret;
        
        echo "</td>"; 
        echo '<td>';
        echo html::getHeadline('Update');
        echo $f->submitClean('submit', 'Send');
        
        echo "</td>"; 
        
        echo "</tr></table>";
        echo $f->formEndClean();

    }
    
    public function delete ($id) {
        db_q::delete('report')->filter('tournament =', $id)->exec();
        db_q::delete('tournaments')->filter('id =', $id)->exec();
        db_q::delete('listids')->filter('listitemid =', $id)->exec();
        
    }
    

    public function deleteAction() {
        if (!session::checkAccess('admin')) {
            return;  
        }
        
        date_default_timezone_set(config::getMainIni('date_default_timezone'));
        
        $id = uri::fragment(3);
        if (isset($_POST['submit'])) {
            $this->delete($id);
            if (isset($_GET['return_to'])) {
                $return_to = rawurldecode($_GET['return_to']);
            } else {
                $return_to = $_SERVER['REQUEST_URI'];
            }
            http::locationHeader($return_to);
        }
        
        
        echo html::getHeadline('Delete tournament?');
        echo html_helpers::confirmDeleteForm('submit', '');
        $this->displaySingle($id, array ('admin_edit' => 1));
    }



    public function get($id) {
        return db_q::select('tournaments')->filter('id =', $id)->fetchSingle();
    }

    /**
     * get user timezone
     * @return string $timezone
     */
    public function getTimezone() {
        $default = config::getMainIni('date_default_timezone');
        $user_language = cache::get('account_timezone', session::getUserId());
        if ($user_language) {
            $default = $user_language;
        }
        return $default;
    }


    /**
     * get various time formats based on system time (date_default_timezone)
     * @return type
     */
    public static function getSystemTimeInfo() {
        $date = new DateTime('now', new DateTimeZone(config::getMainIni('date_default_timezone')));
        
        $ary['hour'] = $date->format('H');
        $ary['minute'] = $date->format('i');
        $ary['day'] = $date->format('D');
        $ary['day'] = strtolower($ary['day']);
        $ary['hm'] = $ary['hour'] . ':' . $ary['minute'];
        $ary['datetime'] = $date->format('Y-m-d H:i:s');
        $next = new DateTime('+1 day', new DateTimeZone(config::getMainIni('date_default_timezone')));
        
        $ary['date_tomorrow'] = $next->format('Y-m-d');
        $ary['datetime_tomorrow'] = $next->format('Y-m-d H:i:s');
        $ary['day_tomorrow'] = strtolower($next->format('D'));
        return $ary;
    }
    
    /**
     * transforms a dateTime from default timezone to user timezone
     * all dates are in CET. We use this when transforming 
     * @param string $timezone
     * @param string $date time (2014-04-10 10:00)
     * @param string $format format to use when formatting, defaults to 'H:i (D)'
     */
    public static function getDateTimeFromUserTimezone ($u_zone, $time, $format = 'H:i (D)') {
        
        $s_zone = config::getMainIni('date_default_timezone');
        $date = new DateTime($time, new DateTimeZone($s_zone));
        $date->setTimezone(new DateTimeZone($u_zone)); // +04
        return $date->format($format); // 2012-07-15 05:00:00 
    }

}

class tournament_module extends tournament {
    
}

class tournament_search extends tournament_module {

    public function getSites() {
        $ary = array();
        $ary['pokerstars'] = 'Pokerstars';
        $ary['partypoker'] = 'Partypoker';
        $ary['ipoker'] = 'Ipoker';
        $ary['ftp'] = 'Full Tilt Poker';
        $ary['888poker'] = '888';
        $ary['ongame'] = 'Ongame';
        $ary['winamax'] = 'Winamax';
        $ary['pkr'] = 'PKR';
        $ary['winning'] = 'Winning Poker';
        $ary['pokerstarsfr'] = 'Pokerstars.fr';
        //$ary['lockpoker'] = 'LockPoker';
        $ary['microgaming'] = 'Microgaming';
        return $ary;
    }
    
    public function getAffiliates () {
        return array (
            'ongame' => 'http://site.gotoredkings.com/index.cgi?aname=diversen72&cg=english',
            //'ongame' => 'http://site.redkings.com/index.cgi?aname=Grindplanner',
            '888poker' => 'https://mmwebhandler.888.com/C/33335?sr=1122342&',
            // ok: '888poker' => 'https://mmwebhandler.888.com/C/33335?sr=1111278',
            'partypoker' => 'https://mediaserver.bwinpartypartners.com/renderBanner.do?zoneId=1628461',
            // ok: 'partypoker' => 'https://mediaserver.bwinpartypartners.com/renderBanner.do?zoneId=1628461',
            'winning' => 'http://record.blackchippoker.eu/_Joxgf7mGcCg2uW858WQIo2Nd7ZgqdRLk/1', 
            // http://record.blackchippoker.eu/_Joxgf7mGcCg2uW858WQIo2Nd7ZgqdRLk/1
            'pkr' => 'http://wlpkr.adsrv.eacdn.com/C.ashx?btag=a_8018b_5c_&amp;affid=775&amp;siteid=8018&amp;adid=5&amp;c=',
            //'pokerstars' => 'http://www.pokerstars.com/?source=12084315',
            //'pokerstarsfr' => 'http://www.pokerstars.fr/en/?source=12084315',
            //'ftp' => 'http://www.fulltiltpoker.com/?source=12084315',
            'pokerstars' => 'http://www.pokerstars.com/?source=12105979',
            'pokerstarsfr' => 'http://www.pokerstars.fr/en/?source=12105979',
            'ftp' => 'http://www.fulltilt.com/?source=12105979',
            
            'winamax' => 'https://www.winamax.fr/en/account/create.php?banid=24985',
            
            'microgaming' => 'http://affiliate.igamefriends.com/processing/clickthrgh.asp?btag=a_9584b_2',
            // ok: 'microgaming' => 'http://affiliate.igamefriends.com/processing/clickthrgh.asp?btag=a_8211b_2',
            'ipoker' => 'http://ads.betfair.com/redirect.aspx?pid=1080263&amp;bid=8425',
            
            );
        /*
        return array (
            'ongame' => 'http://site.redkings.com/index.cgi?aname=Grindplanner',
            '888poker' => 'https://mmwebhandler.888.com/C/33335?sr=1111278',
            'partypoker' => 'https://mediaserver.bwinpartypartners.com/renderBanner.do?zoneId=1623282',
            'winning' => 'http://record.blackchippoker.eu/_RqgmGcUU4tg2uW858WQIo2Nd7ZgqdRLk/1',
            'pkr' => 'http://wlpkr.adsrv.eacdn.com/C.ashx?btag=a_8018b_5c_&affid=775&siteid=8018&adid=5&c=',
            'pokerstars' => 'http://www.pokerstars.com/?source=12084315',
            'pokerstarsfr' => 'http://www.pokerstars.fr/?source=12084315',
            'ftp' => 'http://www.fulltiltpoker.com/?source=12084315',
            'winamax' => 'https://www.winamax.fr/en/account/create.php?banid=24985',
            'microgaming' => 'http://affiliate.igamefriends.com/processing/clickthrgh.asp?btag=a_8211b_2',
            'ipoker' => 'http://ads.betfair.com/redirect.aspx?pid=1080263&bid=8425',
            
            );
         * 
         */
    }

    public function getSiteCheckboxes() {
        $ary = $this->getSites();
        $ret = array();
        $f = new html();
        $f->disableBr();
        $f->init(null, 'submit');
        $extra = array('class' => 'site');
        foreach ($ary as $key => $val) {
            $ret[$val] = $f->checkboxClean($key, 1, $extra);
        }
        return $ret;
    }

    public function getFormats() {
        $ary = array();
        $ary['freezeout'] = 'Freezeout';
        $ary['turbo'] = 'Turbo';
        $ary['superturbo'] = 'Super Turbo';
        $ary['headsup'] = 'Heads-Up';
        $ary['4max'] = '4-max';
        $ary['6max'] = '6-max';
        $ary['timecapped'] = 'Time Capped';
        $ary['breakthru'] = 'Break thru';
        $ary['wta'] = 'Winner Takes All';
        $ary['2chance'] = '2nd chance';
        $ary['3chance'] = '3rd chance';
        $ary['4chance'] = '4th chance';
        $ary['don'] = 'Double or Nothing';
        $ary['ton'] = 'Triple or Nothing';
        $ary['shootout'] = 'Shootout';
        $ary['deepstack'] = 'Deepstack';
        $ary['escalator'] = 'Escalator';
        $ary['capped'] = 'Capped';
        $ary['knockout'] = 'Knockout';
        $ary['flipout'] = 'Flipout';
        $ary['fastfold'] = 'Fast Fold Poker';
        $ary['reentry'] = 'Re-Entry';
        $ary['multientry'] = 'Multi Entry';
        $ary['cubed'] = 'Cubed';
        $ary['rebuy'] = 'Rebuy';
        return $ary;
    }

    public function getFormatCheckboxes() {
        $ary = $this->getFormats();
        $ret = array();
        $f = new html();
        $f->disableBr();
        $extra = array('class' => 'format');
        foreach ($ary as $key => $val) {
            $ret[$val] = $f->checkboxClean($key, 1, $extra);
        }
        return $ret;
    }

    public function getGames() {
        $ary = array();
        $ary['holdem'] = 'Holdem';
        $ary['omaha'] = 'Omaha';
        $ary['omaha_hl'] = 'Omaha H/L';
        $ary['triple_draw'] = '2-7 Triple Draw';
        $ary['8_game'] = '8 Game';
        $ary['2_7_single_draw'] = '2-7 Single Draw';
        $ary['5_card_omaha'] = '5 Card Omaha';
        $ary['courchevel'] = 'Courchevel';
        $ary['stud'] = 'Stud';
        $ary['razz'] = 'Razz';
        $ary['5_card_draw'] = '5 Card Draw';
        $ary['badugi'] = 'Badugi';
        $ary['horse'] = 'HORSE';
        $ary['irish'] = 'Irish';
        $ary['hose'] = 'HOSE';
        $ary['triple_stud'] = 'Triple Stud';
        $ary['ha'] = 'HA';
        $ary['10_game'] = '10 Game';
        $ary['5_card_stud'] = '5 Card Stud';
        $ary['7_card_stud'] = '7 Card Stud';
        $ary['5_card_draw'] = '5 Card Draw';
        return $ary;
    }

    public function getGameCheckboxes() {
        $ary = $this->getGames();
        $ret = array();
        $f = new html();
        $f->disableBr();
        $extra = array('class' => 'game');
        foreach ($ary as $key => $val) {
            $ret[$val] = $f->checkboxClean($key, 1, $extra);
        }
        return $ret;
    }

    public function getLimits() {
        $ary = array();
        $ary['NL'] = 'No Limit';
        $ary['PL'] = 'Pot Limit';
        $ary['FL'] = 'Fixed Limit';
        $ary['ML'] = 'Mixed Limit';
        return $ary;
    }

    public function getLimitCheckboxes() {
        $ary = $this->getLimits();
        $ret = array();
        $f = new html();

        $f->disableBr();
        $extra = array('class' => 'limit');
        foreach ($ary as $key => $val) {
            $ret[$val] = $f->checkboxClean($key, 1, $extra);
        }
        return $ret;
    }

    public function sanitizeFloat($val) {
        $val = preg_replace('/[^0-9.,]+/', '', $val);
        return $val;
    }

    public function _sanitizeFloats() {
        if (isset($_GET['gtd_min'])) {
            $_GET['gtd_min'] = $this->sanitizeFloat($_GET['gtd_min']);
        }
        if (isset($_GET['buyin_min'])) {
            $_GET['buyin_min'] = $this->sanitizeFloat($_GET['buyin_min']);
        }
        if (isset($_GET['buyin_max'])) {
            $_GET['buyin_max'] = $this->sanitizeFloat($_GET['buyin_max']);
        }
    }

    public function normalizeFloat($val) {
        $val = trim($val);
        $val = str_replace(',', '.', $val);
        $val = preg_replace('/[^0-9.]+/', '', $val);
        return $val;
    }

    public function getSearchParams() {

        $search = array();
        
        if (isset($_GET['free'])) {
            $search['free'] = $_GET['free'];
        }

        if (isset($_GET['gtd_min'])) {
            $search['gtd_min'] = $this->normalizeFloat($_GET['gtd_min']);
        }
        if (isset($_GET['buyin_min'])) {
            $search['buyin_min'] = $this->normalizeFloat($_GET['buyin_min']);
        }
        if (isset($_GET['buyin_max'])) {
            $search['buyin_max'] = $this->normalizeFloat($_GET['buyin_max']);
        }

        $sites = $this->getSites();

        $get_sites = array();
        foreach ($sites as $key => $val) {
            if (isset($_GET[$key]) && $_GET[$key] == 1) {
                $get_sites[] = $key;
            }
        }
        $search['sites'] = $get_sites;

        // formats
        $formats = $this->getFormats();
        $get_formats = array();
        foreach ($formats as $key => $val) {
            if (isset($_GET[$key]) && $_GET[$key] == 1) {
                $get_formats[] = $key;
            }
        }
        $search['formats'] = $get_formats;

        // games
        $games = $this->getGames();
        $get_games = array();
        foreach ($games as $key => $val) {
            if (isset($_GET[$key]) && $_GET[$key] == 1) {
                $get_games[] = $key;
            }
        }
        $search['games'] = $get_games;


        $limits = $this->getLimits();
        $get_limits = array();
        foreach ($limits as $key => $val) {
            if (isset($_GET[$key]) && $_GET[$key] == 1) {
                $get_limits[] = $key;
            }
        }

        $search['limits'] = $get_limits;
        return $search;
    }

    public function searchForm() {

        echo '<div class ="search_form">';

        if (!empty($_GET)) {
            $this->_sanitizeFloats();
        }

        $t = new tournament_search();

        $c_sites = $t->getSiteCheckboxes();
        $c_formats = $t->getFormatCheckboxes();
        $c_games = $t->getGameCheckboxes();
        $c_limits = $t->getLimitCheckboxes();

        $f = new html();
        $f->init(null, 'submit');
        $f->setAutoEncode(true);
        $f->disableBr();
        $f->setFieldSet(' ');
        echo $f->formStartClean('search', 'get', '#');

        // GTD and buy-in
        $free_opt= array('size' => 8);
        echo '<table>';
        
        echo '<tr>';
        
        //$style = ' style="width:140px" ';
        echo '<td>';
        echo html::getHeadline('Keyword');
        //echo "Min:";
        echo $f->textClean('free', null, $free_opt);
        echo "</td>";
        //echo '</tr>';
        //echo "</table>";
        
        
        $text_opt = array('size' => 8);
        //echo '<table>';
        //echo '<tr>';
        
        echo '<td>';
        echo html::getHeadline('Guaranteed');
        //echo "Min:";
        echo $f->textClean('gtd_min', null, $text_opt);
        echo "</td>";
        
        echo '<td>';
        echo html::getHeadline('Buy-in (min)');
        //echo "Min:";
        echo $f->textClean('buyin_min', null, $text_opt);
        echo "</td><td>\n";
        echo html::getHeadline('Buy-in (max)');
        //echo "Max: ";
        echo $f->textClean('buyin_max', null, $text_opt);
        echo "</td></tr></table>";

        echo '<table><tr><td>';

        echo html::getHeadline('Sites');
        echo $f->checkboxClean('sites_all', '1', array('class' => 'sites_all'));
        echo "Select / remove all";
        echo "<br />";

        foreach ($c_sites as $key => $val) {
            echo $val . " " . $key . "<br />";
        }

        echo '</td><td>';

        echo html::getHeadline('Formats');
        echo $f->checkboxClean('formats_all', '1', array('class' => 'formats_all'));
        echo "Select / remove all";
        echo "<br />";

        foreach ($c_formats as $key => $val) {
            echo $val . " " . $key . "<br />";
        }

        echo '</td><td>';

        echo html::getHeadline('Games');
        echo $f->checkboxClean('games_all', '1', array('class' => 'games_all'));
        echo "Select / remove all";
        echo "<br />";
        foreach ($c_games as $key => $val) {
            echo $val . " " . $key . "<br />";
        }

        echo '</td><td>';


        echo html::getHeadline('Limit');
        echo $f->checkboxClean('limits_all', '1', array('class' => 'limits_all'));
        echo "Select / remove all";
        echo "<br />";
        foreach ($c_limits as $key => $val) {
            echo $val . " " . $key . "<br />";
        }

        echo "</td></tr></table>";

        echo $f->submitClean('submit', 'Search');
        echo $f->formEndClean();
        echo '</div>';
    }
    
    /**
     * returns empty sql if no search has been performed
     * @return array array ('params' => $params, 'sql' => $sql)
     */
    public function getEmptySql() {
        
        $sql = array();

        $sites = $this->getSites();
        $s = count($sites);
        $sql['sites'] = '';
        foreach ($sites as $key => $value) {
            $sql['sites'].= " site = " . db_q::$dbh->quote($key) . ' ';
            $s--;
            if ($s) {
                $sql['sites'].= ' OR ';
            }
        }
        
        $sql['sites'] = '(' . $sql['sites'] . ')';


        $formats = $this->getFormats();    
        $f = count($formats);

        $sql['formats'] = '';
        foreach ($formats as $key => $value) {
            $sql['formats'].= "$key = " . db_q::$dbh->quote(1) . ' ';
            $f--;
            if ($f) {
                $sql['formats'].= ' OR ';
            }
        }

        $sql['formats'] = '(' . $sql['formats'] . ')';
        
        $games = $this->getGames();
        $g = count($games);
        $sql['games'] = '';
        foreach ($games as $key => $value) {
            $sql['games'].= " gametype = " . db_q::$dbh->quote($key) . ' ';
            $g--;
            if ($g) {
                $sql['games'].= ' OR ';
            }
        }

        $sql['games'] = '(' . $sql['games'] . ')';

        $limits = $this->getLimits();
        $l = count($limits);
        $sql['limits'] = '';
        foreach ($limits as $key => $value) {
            $sql['limits'].= " limittype = " . db_q::$dbh->quote($key) . ' ';
            $l--;
            if ($l) {
                $sql['limits'].= ' OR ';
            }
        }

        $sql['limits'] = '(' . $sql['limits'] . ')';

        // put it all together
        $final = count($sql);
        foreach ($sql as $value) {
            if (!isset($sql['sql'])) {
                $sql['sql'] = '';
            }

            $sql['sql'].= $value;
            $final--;
            if ($final) {
                $sql['sql'].= ' AND ';
            }
        }
        return $sql;
    }
    
    /*
    public function getFreeNumRows () {
        $free = $_GET['free'];
        $free = strtolower($free);
        //$games = $this->getGames(); 
        $free = db_q::$dbh->quote($params['free']);
        $q = "SELECT count(*) as num_rows FROM tournaments WHERE gametype LIKE '%$free%' OR org_name LIKE '%$free%'";
        $row = db_q::sqlClean($q)->fetchSingle();
        return $row['num_rows'];
        
    }
    
    public function getFreeSearchSql ($from = 0,$limit = 100) {
        $free = $_GET['free'];
        $free = strtolower($free);
        $free = db_q::$dbh->quote($params['free']);
        $q = "SELECT count(*) as num_rows FROM tournaments WHERE gametype LIKE '%$free%' OR org_name LIKE '%$free%'";
        $row = db_q::sqlClean($q)->orderlimit($from, $limit)->fetchSingle();
        return $row['num_rows'];
    }*/

    /**
     * get base search params and the sql created as an array
     * @return array $ary with params and SQL
     */
    public function getSearchSql() {
        
        $params = $this->getSearchParams();        
        $sql = array();

        // free
        if (!empty($params['free'])) {
            $params['free'] = db_q::$dbh->quote("%".$params['free']."%", PDO::PARAM_STR );
            $sql['free'] = " (gametype LIKE $params[free] OR org_name LIKE $params[free]) ";
        }
        
        // sites
        if (!empty($params['gtd_min'])) {
            $sql['gtd_min'] = " (pricepool >= " . db_q::$dbh->quote($params['gtd_min']) . ')';
        }
        if (!empty($params['buyin_min'])) {
            $sql['buyin_min'] = " (buyin >= " . db_q::$dbh->quote($params['buyin_min']) . ') ';
        }
        if (!empty($params['buyin_max'])) {
            $sql['buyin_max'] = " (buyin <= " . db_q::$dbh->quote($params['buyin_max']) . ') ';
        }

        $s = count($params['sites']);
        $sql['sites'] = '';
        foreach ($params['sites'] as $value) {
            $sql['sites'].= " site = " . db_q::$dbh->quote($value) . ' ';
            $s--;
            if ($s) {
                $sql['sites'].= ' OR ';
            }
        }

        if (!empty($sql['sites'])) {
            $sql['sites'] = '(' . $sql['sites'] . ')';
        } else {
            unset($sql['sites']);
        }

        // formats     
        $f = count($params['formats']);

        $sql['formats'] = '';
        foreach ($params['formats'] as $value) {
            $sql['formats'].= "$value = " . db_q::$dbh->quote(1) . ' ';
            $f--;
            if ($f) {
                $sql['formats'].= ' OR ';
            }
        }

        if (!empty($sql['formats'])) {
            $sql['formats'] = '(' . $sql['formats'] . ')';
        } else {
            unset($sql['formats']);
        }

        // games
        $g = count($params['games']);
        $sql['games'] = '';
        foreach ($params['games'] as $value) {
            $sql['games'].= " gametype = " . db_q::$dbh->quote($value) . ' ';
            $g--;
            if ($g) {
                $sql['games'].= ' OR ';
            }
        }

        if (!empty($sql['games'])) {
            $sql['games'] = '(' . $sql['games'] . ')';
        } else {
            unset($sql['games']);
        }

        // limts
        $l = count($params['limits']);
        $sql['limits'] = '';
        foreach ($params['limits'] as $value) {
            $sql['limits'].= " limittype = " . db_q::$dbh->quote($value) . ' ';
            $l--;
            if ($l) {
                $sql['limits'].= ' OR ';
            }
        }

        if (!empty($sql['limits'])) {
            $sql['limits'] = '(' . $sql['limits'] . ')';
        } else {
            unset($sql['limits']);
        }

        // put it all together
        $final = count($sql);
        foreach ($sql as $value) {
            if (!isset($sql['sql'])) {
                $sql['sql'] = '';
            }

            $sql['sql'].= $value;
            $final--;
            if ($final) {

                $sql['sql'].= ' AND ';
            }
        }

        if (empty($sql['sql'])) {
            unset($sql['sql']);
        }

        $sql['params'] = $params;
        return $sql;
    }

    /**
     * return num rows from a search
     * @return int $num_rows number of rows in a result set
     */
    public function getNumRows() {
        
        $sql = $this->getSearchSql();       
        if (!empty($sql['sql'])) {
            $q = $this->getUnionNumRows($sql);
            $row = db_q::sqlClean($q)->fetchSingle();
            return $row['num_rows'];
        } else {
            $sql = $this->getEmptySql();
            $q = $this->getUnionNumRows($sql);
            $row = db_q::sqlClean($q)->fetchSingle();
            return $row['num_rows'];
        }
    }
    
    public function getUnionNumRows ($sql) {
        $t = tournament::getSystemTimeInfo();
        $q = "SELECT count(*) as num_rows FROM (";
        $q.=    "SELECT *, 1 as info from tournaments WHERE ('$t[datetime]' >= begin_dt AND '$t[datetime]' <= begin_dt_late) AND $sql[sql] ";
        $q.=    "UNION ALL ";
        $q.=    "SELECT *, 2 as info from tournaments WHERE (begin_dt > '$t[datetime]') AND $sql[sql] ";
        $q.=  ") as x";
        return $q;
    }
    
    public static function getUnionSql ($sql, $from, $limit, $order_by = array ()) {
        $t = tournament::getSystemTimeInfo();
        
        

        $q = "SELECT * FROM ";
        $q.= "(SELECT *, 1 as info from tournaments WHERE ('$t[datetime]' >= begin_dt AND '$t[datetime]' <= begin_dt_late) AND $sql[sql] ORDER BY begin_dt ASC) ";
        $q.= " AS a ";
        $q.= "UNION ALL ";
        $q.= "SELECT * FROM ";
        $q.= "(SELECT *, 2 as info from tournaments WHERE (begin_dt > '$t[datetime]') AND $sql[sql] ORDER BY begin_dt ASC) ";
        $q.= " AS b ";
        
        $q.=  "LIMIT $from, $limit";
        return $q;
    }   
    
    
    public static function getListSql ($sql, $from, $limit, $order_by = array ()) {
        $t = tournament::getSystemTimeInfo();

        $q = "SELECT * FROM ";
        $q.= "(SELECT *, 1 as info from tournaments WHERE (begin >= '06:00:00' AND begin <= '24:00:00') AND $sql[sql] ORDER BY begin ASC) ";
        $q.= " AS a ";
        $q.= "UNION ALL ";
        $q.= "SELECT * FROM ";
        $q.= "(SELECT *, 1 as info from tournaments WHERE (begin >= '00:00:00' AND begin < '06:00:00') AND $sql[sql] ORDER BY begin ASC) ";
        $q.= " AS b ";
        
        $q.=  "LIMIT $from, $limit";
        return $q;
    }  

    public static $q = null;
    public function getRows($from, $limit) {
        $sql = $this->getSearchSql();
        
        // (SELECT begin FROM tournaments WHERE begin >= '23:00' order by begin ASC) Union (SELECT begin FROM tournaments WHERE begin < '01:00' ORDER by begin DESC
        
        
        
        if (!empty($sql['sql'])) {
            $q = $this->getUnionSql($sql, $from, $limit);
            $rows = db_q::sqlClean($q)->fetch();
        } else {
            $sql = $this->getEmptySql();
            $q = $this->getUnionSql($sql, $from, $limit);
            
            $rows = db_q::sqlClean($q)->fetch();
        }
        self::$q = $q;
        return $rows;
    }

    /**
     * display search result header
     * @param array $options ('no_report' => true)
     */
    public function displayHeader($options) {
        echo "<tr>";
        //$style = "style=\"vertical-align:top\"";
        if (isset($options['list_options'])) {
            echo "<th>Add</th>";
        }
        if (isset($options['delete_options'])) {
            echo "<th>Delete</th>";
        }
        if (isset($options['admin_edit'])) {
            echo "<th>Edit</th>";
        }
        if (isset($options['admin_delete'])) {
            echo "<th>Delete</th>";
        }
        
        //$create = html::createLink("/account/login/create", 'Register');
        
        echo "<th>Site</th>";
        
        if (isset($options['admin_edit'])) {
            echo "<th>Start (CET)</th>";
        } else {
            echo "<th>Start</th>";
        }
        
        
        
        echo "<th>Late Reg<br />(Remaining)</th>\n";
        
        echo "<th>Tournament</th>";
        echo "<th>Buyin</th>";
        echo "<th>Limit</th>";
        echo "<th>Game</th>";
        echo "<th>Format</th>";
        echo "<th>GTD</th>";
        if (isset($options['report_options'])) {
            echo "<th>Report</th>";
        }
        

        echo "</tr>";
    }
    
    public function ajaxAddDelete() {
        ?>
<script>
$( document ).ready(function() {
    $.ajaxSetup ({
        // Disable caching of AJAX responses
        cache: false,
        async: false
    });
});
    
$( document ).ready(function() {    
    $(document).on('click', '.item', function(e){

        //e.preventDefault();//in this way you have no redirect
        var id = $(this).attr('item-id');
        var option = $(this).attr('option');
        var list_id = $(this).attr('list-id');
        var td_id = ".item-" + id;
        var unique = new Date().getTime();
        //alert(id);
        
        // update td
        $(td_id).load("/tournament/lists/ajax?id="+ id + "&option=" + option + "&unique=" + unique, function(response, status, xhr ){
            //alert(status);
        });

        // update box list
        $('.box-outer').load("/tournament/lists/ajaxlist/" + list_id + "?unique=" + unique, function(response, status, xhr ){
            //alert(response);
        });
        e.preventDefault();
        return false; // avoid to execute the actual submit of the form.
    });
});
</script><?php

    }
    
    public static function getReturnTo () {
        $return_to = 'return_to=';
        $return_to.= rawurlencode($_SERVER['REQUEST_URI']);
        return $return_to;
    }
    
    

    /**
     * display search results rows
     * @param array $rows
     * @param array $options ('no_report' => true)
     */
    public function displayRows($rows, $options = array(), $action = '', $method = 'get') {
        
        // get list id. An list items if any.
        $l = new tournament_lists_module();
        $list_id = $l->getSessionListId();
        if (!$list_id) {
            $ids = array ();
        } else {
            $ids = $l->getListIds($list_id);
        }

            //$t = new tournament_lists_module();
        //$lists = $l->getUserLists(session::getUserId());

        if (session::isUser() && isset($options['list_options']) && isset($_GET['select_list'])) {
            echo html::getError('Select a list or create a new one before adding tournaments');
        }
        
        $return_to = $this->getReturnTo();
        
        // add jquery ajax script
        $this->ajaxAddDelete(); 
        echo '<div id="search_results">';
        echo "<table>";
        $this->displayHeader($options);
        $games = $this->getGames();
        
        // get user timezone info
        $u_zone = tournament::getUserDefaultTimezone();
        $system_time_info = tournament_module::getSystemTimeInfo();

        $i = 0;
        // display all rows
        $sites = $this->getSites();
        $affiliates = $this->getAffiliates();

        foreach ($rows as $row) {
            
            $style = $this->getRowStyle($row, $i);
            $i++;
            //echo "<tr>";
            echo "<tr $style>";
            if (isset($options['list_options'])) {
                echo $this->getListOptions ($row['id'], $ids, $list_id);
                
            }
            
            if (isset($options['admin_edit'])) {
                echo '<td class="td_center">' . 
                        html::createLink(
                                "/tournament/admin/edit/$row[id]?$return_to", 
                                'Edit', array ('class' => 'item_login')) . "</td>";
            }
            if (isset($options['admin_delete'])) {
                echo '<td class="td_center">' . 
                        html::createLink(
                                "/tournament/admin/delete/$row[id]?$return_to", 
                                'Delete', array ('class' => 'item_login')) . "</td>";
            }

            $site = $sites[$row['site']];
            $aff_link = $affiliates[$row['site']];
            $aff_attr = array ('target' => '_blank');
            $link = html::createLink($aff_link, $site, $aff_attr);
            
            echo '<td class="td_center">' . $link . "</td>";
            if (isset($options['admin_edit'])) {
                $row = $this->getAdminTimesRow ($row);
            } else {
                $row = $this->getUserTimesRow ($row, $u_zone);
            }
                
            echo '<td class="td_center">' . $row['begin'] . "</td>";
            $late = $this->getMinutesBeginLate($row, $system_time_info) ;
            echo '<td class="td_center">' . $late . "</td>";
            echo "<td>" . html::specialEncode($row['org_name']) ."</td>";
            echo '<td class="td_center">'. self::getBuyinFormat($row) . "</td>";
            echo '<td class="td_center">' . $row['limittype'] . "</td>";
            $game_key = $row['gametype'];
            //$game = $games[$game_key];
            
            if (!isset($games[$game_key])) {
                $game = 'No type';
            } else {
                $game = $games[$game_key];
            }
            
            echo '<td class="td_center">' . $game . '</td>';
            echo '<td class="td_center">' . $this->getFormatLineFromRow($row). "</td>";
            echo '<td class="td_center">' . $row['moneycode'] . self::numberFormat($row['pricepool'], 0) . "</td>";
            if (isset($options['report_options'])) {
                echo '<td class="td_center">' . $this->reportLink($row['id']) . "</td>";
            }
            echo "</tr>";
        }

        echo "</table>";
        echo '</div>';
        /*
        ?>        
        <script>
$( "tr:odd" ).css( "background-color", "#ebe7e6");
$( "tr:even" ).css( "background-color", "#ffffff" );
</script>
<?php */
    }
    
    public static function getMinutesBeginLate ($row, $system) {
        //if ($row['info'] != '1') {
        //    return '';
        //}
        
        $now = strtotime($system['datetime']);
        $late = strtotime($row['begin_dt_late']);
        $begin = strtotime($row['begin_dt']);
        
        if ($begin < $now && $late > $begin) {
            $interval = ceil(($late - $now) / 60);
            if ($interval > 0) {
                return "<b>$interval</b>" . " mins";
            }
        }
        return $row['latereg'] . ' mins';
        
    }
    
    /**
     * returns tournament array with admin times set
     * @param array $row
     * @return array $row
     */
    public function getAdminTimesRow($row) {

        $row['begin'] = tournament_module::getDateTimeFromUserTimezone(
                        config::getMainIni('date_default_timezone'), $row['begin_dt']);
        $row['late'] = tournament_module::getDateTimeFromUserTimezone(
                        config::getMainIni('date_default_timezone'), $row['begin_dt_late']);
        return $row;
    }

    /**
     * returns tournament array with user times set
     * @param array $row
     * @param string $timezone for user 
     * @return array $row
     */
    public function getUserTimesRow($row, $u_zone) {

        
        if ($row['day'] == 'any') {
            $row['begin'] = tournament_module::getDateTimeFromUserTimezone($u_zone, $row['begin_dt'], 'H:i');
            $row['late'] = tournament_module::getDateTimeFromUserTimezone($u_zone, $row['begin_dt_late'], 'H:i');
        } else {
            $row['begin'] = tournament_module::getDateTimeFromUserTimezone($u_zone, $row['begin_dt']);
            $row['late'] = tournament_module::getDateTimeFromUserTimezone($u_zone, $row['begin_dt_late']);
        }
        
        return $row;
    }

    public function getDisplayLate($row) {
        if ($row['day'] != 'any') {
            $extra = " ($row[day])";
        } else {
            $extra = '';
        }
        
        //if (isset($row['info']) && $row['info'] == '1') {
        //    $str = "<td>$row[late] $extra (Late)</td>";
        //} else {
            $str = "$row[begin] $extra";
        //}
        return $str;
    }
    
    public function getDisplayNoLate($row) {
        if ($row['day'] != 'any') {
            $extra = " ($row[day])";
        } else {
            $extra = '';
        }
        
        //if (isset($row['info']) && $row['info'] == '1') {
       //     $str = "$row[late] $extra";
       // } else {
            $str = "$row[begin] $extra";
        //}
        return $str;
    }

    /**
     * get list option add / hide. 
     * if user is not loged in - send him to login / create
     * if user is logged in but has no lists - send him to create list
     * if user is logged in and has a list - let him add items to list. 
     * @param int $id item id
     * @param type $ids lists current items
     * @return string $links
     */
    public function getListOptions($id, $ids, $list_id) {        
        
        static $has_lists = null;
        static $rows = null;
        if (!$has_lists) {
            $t = new tournament_lists_module();
            $rows = $t->getUserLists(session::getUserId());
        }
        
        // login create
        
        $img_add = html::createImage('/templates/gp/assets/front/addunselected.png', array('class' => 'icons', 'alt' => 'Add'));
        
        
        if (!session::isUser()) {
            $message = 'Login or create account in order to create lists';
            $add_hide = $this->getLoginCreateLink($img_add, $message);
        }
        
        // logged in but no lists
        else if (empty($rows)) {
            $message = rawurlencode('You have no lists. Create one.');
            $return_to = $this->getReturnTo();
            
            
            $add_hide = html::createLink(
                    "/tournament/lists/add?message=$message&amp;return_to=$return_to", 
                    $img_add, array ('class' => 'item_login'));
        }
        
        // logged in has lists - but has not a selected list
        else if (!empty($rows) && !$list_id) {
            //$message = rawurlencode('Select an existing list or create a new one');

            $add_hide = html::createLink(
                    "/tournament/overview/index?select_list=1", 
                    $img_add, array ('class' => 'item_login'));
        }
        
        // user and he has a selected list
        else {
            $add_hide = $this->getAjaxLinks($id, $ids, $list_id);
        }
        
        
        $str = "<td id=\"item-$id\" class=\"td_center item-$id\">" . $add_hide . "</td>";
        return $str;
    }
    


    /**
     * get login create link with link title and message to display on login
     * @param string $title
     * @param string $message
     * @return string $link
     */
    public function getLoginCreateLink ($title, $message) {
        $url = rawurlencode($_SERVER['REQUEST_URI']);
        $message = rawurlencode($message);
        return html::createLink("/account/index?return_to=$url&amp;message=$message", $title, array ('class' => 'item_login'));
    }
    
    

    /**
     * get ajax links for adding hiding to basket
     * @param int $id
     * @param array $ids
     * @return string $link
     */
    public function getAjaxLinks($id, $ids, $list_id) {
        
        $img_add = html::createImage('/templates/gp/assets/front/addunselected.png', array('class' => 'icons', 'alt' => 'Add'));
        $img_delete = html::createImage('/templates/gp/assets/front/delete.png', array('class' => 'icons', 'alt' => 'Delete'));
        
        if (!in_array($id, $ids)) {
            $attr = array('class' => 'item item-' . $id, 'item-id' => $id, 'option' => 'Add', 'list-id' => $list_id, 'title' => 'Add');
            $add_hide = html::createLink("javascript:", $img_add, $attr);
        } else {
            $attr = array('class' => 'item item-' . $id, 'item-id' => $id, 'option' => 'Delete', 'list-id' => $list_id, 'title' => 'Delete');
            $add_hide = html::createLink("javascript:", $img_delete, $attr);
        }
        return $add_hide;
    }
    
    /**
     * get table row style
     * @param array $row
     * @return string $style
     */
    public static function getRowStyle($row, $i = 0) {
        if ($i % 2 == 0) {
            $style = " style=\"color:#000; background-color:#ddd;\"";
        } else {
            $style = " style=\"color:#000; background-color:#fff;\"";
        }
        return $style;
    }

    /**
     * format numbers so that e.g. 0.03 becomes 0,03
     * and 20000 becomes 20.000
     * @param float $price
     * @return float $price formatet float
     */
    public static function numberFormat($price) {
        // format as decimal
        //var_dump($price);// $price;
        
        if ($price == 0) {
            return 0;
        }
        //if (filter_var($price, FILTER_VALIDATE_INT)) {
        //    $price = (int)$price;
        //} else {
            $price = (float)$price;
        //}
        
        if ($price < 1) {
            $price = number_format($price, 2,  '.', ',');
        } else {
            
            if (preg_match('/^\d+$/D',$price) ) {

                $price = number_format($price, 0,  '.', ',');
            } else {
                $price = number_format($price, 2,  '.', ',');
            }
            
            
        }
        

        return  $price;
    }
    
    /**
     * returns buyin format as buyin + ko + rake
     * @param array $row
     * @return string $buyin formatted
     */
    public static function getBuyinFormat ($row) {
        
        $str = '';
        $row['buyin'] = self::numberFormat($row['buyin']);        
        $str.= "$row[moneycode]$row[buyin] + ";
        if ($row['kobonus'] != '0') {
            $row['bobonus'] = self::numberFormat($row['kobonus']);
            $str.= "$row[moneycode]$row[kobonus] + ";
        }
        
        $row['rake'] = self::numberFormat($row['rake']);
        $str.="$row[moneycode]$row[rake]";
        return $str;
    }
    
    /**
     * checks if a float is a system decimal number, e.g. 20.30
     * @param mixed $val
     * @return boolean $res
     */
    public static function isDecimal( $val ){
        return preg_match('/^\d+\.\d+$/',$val);
    }
    

    /**
     * get format abbrivations from all booleans
     * @param array $row
     * @return string $abbri
     */
    
    public function getFormatLineFromRow ($row) {
        $format = $this->getFormats();
        $str = '';
        //$attr = array ( 'class' => 'icons');
        //$empty = array ('height' => 20, 'width' => 0);
        foreach ($format as $key => $val) {
            
            
            if ($row[$key] == 1) {
                $attr = array ( 'class' => 'icons', 'title' => $val, 'alt' => $val);
                //if ($key == 'freezeout'){
                //    $str.=html::createImage('/templates/gp/assets/formats/trans.png', $empty) . "&nbsp;";
                //} 
                if ($key == '2chance' OR $key == '3chance' OR $key == '4chance') {
                    $str.=html::createImage('/templates/gp/assets/formats/rebuy.jpg', $attr) . "&nbsp;";
                    
                } else {
                    $str.=html::createImage('/templates/gp/assets/formats/' . $key . ".jpg", $attr) . "&nbsp;";
                }
                //$abb$this->getFormatAbbrivation($key);
                //$str.= $this->getFormatAbbrivation($key) . " ";
            }
        }
        //return '';
        return $str;
    }
    
    
    public function getFormatAbbrivation ($format) {
        static $ary = null;
        if ($ary) {
            
            return $ary[$format];

        } else {
        
            $ary = array();
            
            $ary['4max'] = '4-max';
            $ary['6max'] = '6-max';
            $ary['breakthru'] = 'Breakthru';
            $ary['capped'] = 'Capped';
            $ary['cubed'] = 'Cubed';
            $ary['deepstack'] = 'Deepstack';
            $ary['don'] = 'Double or Nothing';
            $ary['escalator'] = 'Escalator';
            $ary['fastfold'] = 'Fastfold';
            $ary['flipout'] = 'Flipout';
            $ary['headsup'] = 'Heads-up';
            $ary['knockout'] = 'Knockout';
            $ary['multientry'] = 'Multientry';
            $ary['rebuy'] = 'Rebuy';
            $ary['reentry'] = 'Reentry';
            $ary['shootout'] = 'Shootout';
            $ary['superturbo'] = 'Super Turbo';
            $ary['timecapped'] = 'Time Capped';
            $ary['ton'] = 'Triple or Nothing';
            $ary['turbo'] = 'Turbo';
            $ary['wta'] = 'Winner Takes All';
            
            
            $ary['freezeout'] = 'Freezeout';
            $ary['2chance'] = '2nd chance';
            $ary['3chance'] = '3rd chance';
            $ary['4chance'] = '4th chance';
            
            
            
            
            
            
            
            
            
            
            
            
            
            return $ary[$format];
            //return $ary;
        }
    }

    public function displayAddSubmit() {
        $str = '<tr>';
        
        $str.= '<td>';
        //$str.= html::hiddenClean('return_to', rawurlencode($_SERVER['REQUEST_URI']));
        //$str.= html::submitClean('send', 'Send');
        $str.= '</td>';
        
        $str.= '<td colspan="9"></td>';
        
        $str.= '</tr>';
        echo $str;
    }

    public function displayDeleteSubmit() {
        $str = '<tr>';
        $str.= '<td>';
        //$str.= html::hiddenClean('return_to', rawurlencode($_SERVER['REQUEST_URI']));
        //$str.= html::submitClean('send', 'Send');
        $str.= '</td>';
        $str.= '<td colspan="9"></td>';
        $str.= '</tr>';
        echo $str;
    }

    public function reportLink($id) {

        $options = array('class' => 'report');
        $return_to = rawurlencode($_SERVER['REQUEST_URI']);
        $str = html::createLink("/tournament/report/index/$id?return_to=$return_to", 'Report', $options);
        return $str;
    }

    public function getSql($from, $limit) {
        $sql = $this->getSearchSql();
        if (!empty($sql['sql'])) {
            return "SELECT * FROM tournaments WHERE $sql[sql] limit $from, $limit";
        } else {
            return "SELECT * FROM tournaments limit $from, $limit";
        }
    }

    public function assets($options = array ()) {
        
        if (isset($options['reload'])) { ?>
<?php } ?>
        


        <script type="text/javascript" src="/js/purl.js"></script>
        <script>

            $(document).ready(function() {
                //var posted = $.url().param('submit');
//html body
                $('.search_toogle').on('click', function() {
                    $('.search_form').slideToggle("slow");
                    //$('meta[http-equiv="Refresh"]').remove();
                    //$('meta[http-equiv="Refresh"]').attr('content',"10000" );
                    //$('meta[name=description]').remove();
    //$('head').append( '<meta http-equiv="Refresh" content="0">' );
                    //$('#main_body').on('click', '#but', function() {
                    //    alert( "bla bla" );
                    //});
                });
            });
            
            $(document).ready(function() {


                $('.timezone_toogle').click(function() {
                    $('.timezone_form').slideToggle("slow");
                });

            });
            
        </script>
        <script>
            $(document).ready(function() {
                $('#sites_all').on("click", function() {
                    if (this.checked) { // check select status
                        $('.site').each(function() { //loop through each checkbox
                            this.checked = true;  //select all checkboxes with class "checkbox1"              
                        });
                    } else {
                        $('.site').each(function() { //loop through each checkbox
                            this.checked = false; //deselect all checkboxes with class "checkbox1"                      
                        });
                    }
                });

                $('#formats_all').on("click", function() {
                    if (this.checked) { // check select status
                        $('.format').each(function() { //loop through each checkbox
                            this.checked = true;  //select all checkboxes with class "checkbox1"              
                        });
                    } else {
                        $('.format').each(function() { //loop through each checkbox
                            this.checked = false; //deselect all checkboxes with class "checkbox1"                      
                        });
                    }
                });

                $('#games_all').on("click", function() {
                    if (this.checked) { // check select status
                        $('.game').each(function() { //loop through each checkbox
                            this.checked = true;  //select all checkboxes with class "checkbox1"              
                        });
                    } else {
                        $('.game').each(function() { //loop through each checkbox
                            this.checked = false; //deselect all checkboxes with class "checkbox1"                      
                        });
                    }
                });

                $('#limits_all').on("click", function() {
                    if (this.checked) { // check select status
                        $('.limit').each(function() { //loop through each checkbox
                            this.checked = true;  //select all checkboxes with class "checkbox1"              
                        });
                    } else {
                        $('.limit').each(function() { //loop through each checkbox
                            this.checked = false; //deselect all checkboxes with class "checkbox1"                      
                        });
                    }
                });

            });
        </script><?php

    }

}
