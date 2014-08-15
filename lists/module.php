<?php

uri::$info['module_base'] = '/tournament/lists';
moduleloader::includeModule('tournament');
class tournament_lists  {
    
    
    /*
    public function addAction () {
        if (!session::isUser()) {
            $url = rawurlencode($_SERVER['REQUEST_URI']);
            $message = 'Login or create account in order to create lists';
            $message = rawurlencode($message);
            http::locationHeader("/account/index?return_to=$url&message=$message");
        }

        if (!isset($_GET['ids'])) {
            echo html::getHeadline('No tournaments selected');
            return;
        }
        
        $ids = array_keys($_GET['ids']);
        if (empty($ids)) {
            echo html::getHeadline('No tournaments selected');
            return;
        }
        
        
        echo html::getHeadline('Add to list');
        $lists = $this->getUserLists(session::getUserId());
        
        if (!empty($_POST)) {
            if (empty($_POST['title']) && $_POST['list'] == '0') {
                html::error('Please enter a title a select an existing list');
            } else {
                $this->addTolist ();
                http::locationHeader(rawurldecode($_GET['return_to']), 'Tournaments added to list!');
            }
        }
        
        echo $this->form($lists);
        
        $rows = $this->getTournamentsFromIds($ids);
        $ts = new tournament_search();
        $ts->assets();
        $ts->displayRows($rows);
        
    }
    */
    
    public function ajaxAction () {

        if (!session::checkAccess('user')) {
            die('Log in');
        }
                
        $list_id = $this->getSessionListId();
        
        if (!$list_id) {
            die('Error: No list');
        }
        
        $owner = user::ownID('lists', $list_id, session::getUserId());
        if (!$owner) {
            die( "Error: Priv");
        }

        if (!isset($_GET['id']) || !isset($_GET['option'])) {
            die('Error - id / opt');
        }
        
        //$id = array_keys($_GET['id']);
        $this->updateListItems($list_id, $_GET['id'], $_GET['option']);
        die();
        

    }
    
    public function additemAction () {
        /*
        if (!session::isUser()) {
            $url = rawurlencode($_SERVER['REQUEST_URI']);
            $message = 'Login or create account in order to create lists';
            $message = rawurlencode($message);
            http::locationHeader("/account/index?return_to=$url&message=$message");
        }*/
        if (!session::checkAccess('user')) {
            return;
        }
        
        
        
        $list_id = $this->getSessionListId();
        $owner = user::ownID('lists', $list_id, session::getUserId());
        if (!$owner) {
            html::error('Please select an existing list before adding tournaments');
            return;
        }

        if (!isset($_GET['ids'])) {
            echo html::getHeadline('No tournaments selected');
            return;
        }
        
        $ids = array_keys($_GET['ids']);
        
        if (empty($ids)) {
            echo html::getHeadline('No tournaments selected');
            return;
        }
       
        $id = $ids[0];
        if ($list_id == 0) {
            html::error('Please select an existing list before adding tournaments');
        } else {
            $this->updateListItems($list_id, $id);
            $location = rawurldecode($_GET['return_to']) . "#item-$id";
            http::locationHeader($location, 'Tournament added to list!');
        }
    }
      
    public function addAction () {
        if (!session::checkAccess('user')) {
            return;
        }
        
        echo html::getHeadline('Create list');
        $lists = $this->getUserLists(session::getUserId());
        
        if (!empty($_POST)) {
            if (empty($_POST['title'])) {
                html::error('Please enter a title, or select an existing list to work on');
            } else {
                $_POST = html::specialDecode($_POST);
                $list_id = $this->createList ();
                $this->setSessionListId($list_id);
                http::locationHeader('/tournament/overview/index', 'List created!');
            }
        }
        
        echo $this->form($lists);
        /*
        $rows = $this->getTournamentsFromIds($ids);
        $ts = new tournament_search();
        $ts->assets();
        $ts->displayRows($rows);
        */
    }
    
    public function form ($lists = array ()) {
        $_POST = html::specialEncode($_POST);
        $f = new html();
        $f->formStart();
        $f->init(null, 'add');
        $f->label('title', 'Enter new list title');
        $f->text('title');

        
        $f->submit('add', 'Add');
        $f->formEnd();
        return $f->getStr();
    }

    
    public function createList() {
        db_rb::connect();
        R::begin();
        $bean = db_rb::getBean('lists');
        $bean->user_id = session::getUserId();
        $bean->title = $_POST['title'];
        $bean->share = 0;
        $bean->vote = 0;
        $list_id = R::store($bean);
        R::commit();
        return $list_id;
    }

    
    public function updateListItems($list_id, $item, $option) {
        
        $img_add = html::createImage('/templates/gp/assets/front/addunselected.png', array('class' => 'icons', 'alt' => 'Add'));
        $img_delete = html::createImage('/templates/gp/assets/front/delete.png', array('class' => 'icons', 'alt' => 'Delete'));
        
        db_rb::connect();
        if ($option == 'Delete') {
            db_q::begin();
            $res = db_q::delete('listids')->
                            filter('listid = ', $list_id)->condition('AND')->
                            filter('listitemid = ', $item)->exec();

            db_q::commit();
            $attr = array('class' => 'item item-' . $_GET['id'], 'item-id'  => $_GET['id'], 'option' => 'Add', 'list-id' => $list_id, 'title' => 'Add');
            echo $add_hide = html::createLink("javascript:", $img_add, $attr);
            return;
        }

        if ($option == 'Add') {
            db_q::begin();
            $bean = db_rb::getBean('listids');
            $bean->listid = $list_id;
            $bean->listitemid = $item;
            $res = R::store($bean);
            db_q::commit();
            $attr = array ('class' => 'item item-' . $_GET['id'], 'item-id'  => $_GET['id'], 'option' => 'Delete', 'list-id' => $list_id, 'title' => 'Delete');
            echo html::createLink("javascript:", $img_delete, $attr);
            return;
        }
    }
    
    /**
     * return a single item from list
     * @param int $id
     */
    public function listItemInList ($listid, $itemid) {
        $row = db_q::select('listids')->
                filter('listid =', $listid)->condition('AND')->
                filter('listitemid =', $itemid)->
                fetchSingle();
        if (empty($row)) {
            return false;
        }
        return true;
    }
    
    /**
     * get users lists as an array 
     * @param int $user
     * @return array $ary
     */
    public function getUserLists ($user) {
        return db_q::select('lists')->filter('user_id =', $user)->fetch();
    }
    
    public function getDisplayCreateList () {
        $return_to = tournament_search::getReturnTo();
        return html::createLink("/tournament/lists/add?return_to=$return_to", 'Create new list', array ('class' => 'headline'));
    }
    
    public function getSelectList ($lists, $current = 0) {
        if (empty($lists)) {
            return '';
        }
        

        $lists = html::specialEncode($lists);
        $extra = array ('onchange' => "this.form.submit()");
        $f = new html();
        $str = $f->formStartClean('box_select_form', 'get', '/tournament/overview/index');
        $f->setSelectTopValue(array ('id' => 0, 'title' => 'Select'));
        $str.=$f->labelClean('box_select', 'Or use:');
        $str.=$f->selectClean('box_select', $lists, 'title', 'id', $current, $extra);
        $str.=$f->formEndClean();
        return $str;
        
    }
    
    /**
     * display box lists
     * @param array $lists
     * @return string $html
     */
    public function getDisplayBoxLists ($lists) {
        if (isset($_GET['box_select'])) {       
            $current = $this->setSessionListId($_GET['box_select']);
            
        }
        $current = $this->getSessionListId();


        
        $str = '';
        $str.= $this->getDisplayCreateList();
        $str.= $this->getSelectList($lists, $current);
        
        if (!$current) {
            return $str;
        }
        $list = $this->getList($current);   
        $str.=$this->getDisplayBoxList($list);
        return $str;
    }
    
    public function getDisplayBoxList ($list) {
        if ($list['share'] == 1) {
            $text = " (shared) ";
        } else {
            $text = ' (not shared) ';
        }
        
        $str = html::createLink(
                "/tournament/lists/view/$list[id]", 
                html::specialEncode($list['title']) . $text);
        $str.= "<div class =\"box-outer\">";
        $str.= $this->getAjaxBoxDiv($list['id']);
        $str.= "</div>";
        //$str.= "<hr />";
        return $str;
    }
    
    public function getAjaxBoxDiv ($list_id) {
        $str = '';
        
        $str.= "<div class =\"box-list\" id=\"box-list-$list_id\">";
        $str.= $this->getBoxCurrentItems($list_id);
        
        $str.= "</div>";
        return $str;
    }
    
    public function ajaxlistAction () {
        $list_id = uri::fragment(3);
        echo $this->getAjaxBoxDiv($list_id);
        die();
    }
    
    public function toolTip () { ?>
<script>
$(function() {
    $( document ).tooltip();
});
 </script><?php
    }
    
    public function getBoxCurrentItems($list_id, $options = array ()) {
        
        $l = new tournament_lists_module();
        $list_id = $l->getSessionListId();
        if (!$list_id) {
            $ids = array ();
        } else {
            $ids = $l->getListIds($list_id);
        }

        
        
        $rows = $this->getListBoxRows($list_id, 0 , 100);
        
        //print_r($rows);
        
        // get user timezone info
        $u_zone = tournament::getUserDefaultTimezone();
        $ts = new tournament_search();
        //$system_time_info = tournament_module::getSystemTimeInfo();
        
        $str = '<table>';
        
        $total = 0;
        $i = 0;
        
        // display all rows
        $sites = $ts->getSites();
        foreach ($rows as $row) {

            
            
            $style = tournament_search::getRowStyle($row, $i);
            $i++;
            $str.= "<tr $style>";
            
            $title = html::specialEncode($row['org_name']);
            //$title_short = strings::substr2($title, 5, false);
            if (isset($options['admin_edit'])) {
                $row = $ts->getAdminTimesRow ($row);
            } else {
                $row = $ts->getUserTimesRow ($row, $u_zone);
            }
            
            //$attr = array('class' => 'item', 'item-id' => $row['id'], 'option' => 'Delete', 'list-id' => $list_id);
            //$add_hide = html::createLink("javascript:", 'Delete', $attr);
            
            $add_hide = $ts->getAjaxLinks($row['id'], $ids, $list_id);
            $str.= "<td id=\"item-$row[id]\">" . $add_hide . "</td>";
            
            //$str.= "<td>" . $add_hide . "</td>";
            $str.= "<td>" . $sites[$row['site']]  . "</td>";
            $str.= "<td>" . $row['begin'] . "</td>";
            $str.= "<td title=\"$title\">$title</td>";
                
            $str.= "</tr>\n";
            $total+=$this->getTotalBuyin($row);
        }
        
        $str.="</tr>";
        $str.= $this->getTotalTable($total);
        return $str;
    }
    
    public function getTotalBuyin ($row) {
        return $row['buyin'] + $row['kobonus'] + $row['rake'];
    }
        
    
    
    /**
     * action: display user lists controller
     */
    public function meAction () {
        $rows = db_q::select('lists')->filter('user_id =', session::getUserId())->fetch();
        if (empty($rows)) {
            http::locationHeader('/tournament/lists/add', 'No lists. Create one.');
        }
        $this->displayLists($rows);
    }
    
    /**
     * display user lists
     * @param type $lists
     */
    public function displayLists ($lists) {
        foreach ($lists as $list) {
            echo $this->displayList($list);
        }
    }
    
    /**
     * display single list
     * @param type $list
     * @return string
     */
    public function displayList ($list) {
        if ($list['share'] == 1) {
            $text = " (shared) ";
        } else {
            $text = ' (not shared) ';
        }
        
        $title = $list['title'] . $text;
        $str = html::getHeadlineLinkEncoded(
                "/tournament/lists/view/$list[id]", 
                $title);
        $host = config::getSchemeWithServerName();

        if ($list['share'] == 1) {
            $str.= "Direct link: " . "$host/tournament/lists/view/$list[id]<br />";
        }
        $str.= $this->getListOptions($list);
        $str.= "<hr />";
        return $str;
    }
    
    public function getList ($id) {
        return db_q::select('lists')->filter('id =', $id)->fetchSingle();
    }
    
    public function getListRows ($id) {
        $rows = db_q::select('listids', 'listitemid as id')->filter('listid =', $id)->fetch();
        return $rows;
    }
    
    public function getListIds ($id) {
        $rows = $this->getListRows($id);
        $ary = array ();
        foreach ($rows as $val) {
            $ary[] = $val['id'];
        }
        return $ary;
    }
    
    /**
     * deletes a list
     * CHECK is done is deleteAction
     * @param int $id
     */
    public function deleteList ($id) {  
        db_q::begin();
        db_q::delete('listids')->filter('listid =', $id)->exec();
        db_q::delete('lists')->filter('id =', $id)->exec();
        if (db_q::commit()) {
            $s_id = $this->getSessionListId();
            if ($s_id == $id) {
                $this->setSessionListId(0);
            }
            http::locationHeader('/tournament/lists/me', 'List deleted');
        } else {
            db_q::rollback();
            http::locationHeader('/tournament/lists/me', 'Something went wrong - please try again');
        }
    }
    
    /**
     * copy a list
     * CHECK is done is copyAction
     * @param int $id
     */
    public function deleteAction () {
        $id = uri::fragment(3);
        
        // CHECK is owner of list id
        if (!user::ownID('lists', $id, session::getUserId())) {
            moduleloader::setStatus(403);
            return;
        }
        
        if (isset($_POST['Delete'])) {
            // delete list
            $this->deleteList($id);
        }
        
        $list = $this->getList($id);
        $title = html::specialEncode($list['title']);
        echo html_helpers::confirmDeleteForm('Delete', "Delete tournament list: $title");
    }
    
        /**
     * deletes a list
     * CHECK is done is deleteAction
     * @param int $id
     */
    public function copyList ($id) {  
        db_q::begin();
        
        $ids = db_q::select('listids')->filter('listid =', $id)->fetch();
        $list = db_q::select('lists')->filter('id =', $id)->fetchSingle();
        
        $list['user_id'] = session::getUserId();
        $list['share'] = 0;
        $list['title'] = $list['title'] . ' I';
        $list['vote'] = 0;
        unset($list['id']);
        db_q::insert('lists')->values($list)->exec();
        $id = db_q::lastInsertId();
        
        
        foreach ($ids as $row) {
            unset($row['id']);
            $row['listid'] = $id;    
            db_q::insert('listids')->values($row)->exec();
        }
        
        if (db_q::commit()) {       
            http::locationHeader('/tournament/lists/me', 'List copied. ');
        } else {
            db_q::rollback();
            http::locationHeader('/tournament/lists/me', 'Something went wrong - please try again');
        }
    }
    
    /**
     * action : copy list
     * @return void
     */
    public function copyAction () {
        $id = uri::fragment(3);
        $list = $this->getList($id);
        
        $error = 1;
        
        // User and list can be shared
        if (session::isUser() && $list['share'] == 1 ) {
            $error = 0;
        }
            
        // Owner of list can always copy own list
        $owner = user::ownID('lists', $id, session::getUserId());
        if ($owner) {
            $error = 0;
        }
        
        if ($error) {
            moduleloader::setStatus(403);
            return;
        }
        
        if (isset($_POST['submit'])) {
            $this->copyList($id);
        }
        
        
        $title = html::specialEncode($list['title']);
        echo html_helpers::confirmForm( "Copy tournament list: $title", 'Copy');
    }
    
    /**
     * edits a list
     */
    public function editList() {
        $id = uri::fragment(3);
        db_rb::connect();
        $bean = db_rb::getBean('lists', 'id', $id);
        $bean->title = html::specialDecode($_POST['title']);
        if (isset($_POST['share'])) {
            $bean->share = 1;           
            $message = 'Updated. List is shared';
        } else {
            $bean->share = 0;
            $message = 'Updated. List is not shared anymore';
        }
        R::store($bean);        
        http::locationHeader("/tournament/lists/view/$id", $message );
    }

    
    public function editAction () {
        $id = uri::fragment(3);
        
        if (!user::ownID('lists', $id, session::getUserId())) {
            moduleloader::setStatus(403);
            return;
        }
        
        if (isset($_POST['Update'])) {
            $this->editList($id);
        }
        
        $list = $this->getList($id);
        $title = html::specialEncode($list['title']);
        
        $f = new html();
        $f->formStart();
        if ($list['share'] == 1) {
            $f->legend ("You are sharing this list ($title). Uncheck to not share it anymore");
        } else {
            $f->legend("Check to share this list ($title)");
        }

        $f->label('title', 'Edit title');
        $f->text('title', html::specialEncode($list['title']));
        if ($list['share'] == 1) {
            $f->label('share', 'Click to stop sharing');
            $f->checkbox('share', 1);
        } else {
            $f->label('share', 'Click to share');
            $f->checkbox('share');
        }
        $f->submit('Update', 'Update');
        $f->formEnd();
        echo $f->getStr();
    }
    
    public function getReturnTo () {
        return rawurlencode($_SERVER['REQUEST_URI']);
    }
    
    public function getListOptions ($list) {
        $owner = user::ownID('lists', $list['id'], session::getUserId());
        $str = '';
        if ($owner || ( session::isUser() && $list['share'] == 1) ) {
            $str.= html::createLink("/tournament/lists/copy/$list[id]", 'Copy');
        }
        
        
        
        if ($owner) {
            $str.= MENU_SUB_SEPARATOR;
            $str.= html::createLink("/tournament/lists/edit/$list[id]", 'Edit');
            $str.= MENU_SUB_SEPARATOR;
            $str.= html::createLink("/tournament/lists/delete/$list[id]", 'Delete');
        }
        return $str;
        
    }
    
    public function getUnionSql ($sql, $from, $limit) {
        $t = tournament::getSystemTimeInfo();

        $q = "SELECT * FROM ";
        $q.=    "(SELECT *, 1 as today FROM tournaments WHERE  (begin >= '$t[hm]') AND (day ='any' OR day = '$t[day]') AND $sql[sql] ORDER BY begin ASC) "; 
        $q.=    "as a ";
        $q.=    "UNION ALL ";
        $q.= "SELECT * FROM "; 
        $q.=    "(SELECT *, 0 as today from tournaments WHERE (begin < '$t[hm]') AND (day ='any' OR day = '$t[day_tomorrow]') AND $sql[sql] ORDER BY begin ASC) ";
        $q.=    "as b ";
        $q.=  "LIMIT $from, $limit";
        return $q;
    }
    
    public function getNumRows ($list_id) {
        
        return db_q::numRows('listids')->filter('listid =', $list_id)->fetch();
    }
    
    public function getListTournamentRows($list_id, $from, $limit) {

        $sql = array ();
        $list_id = filter_var($list_id, FILTER_VALIDATE_INT);
        $sql['sql'] = "id IN 
            (select listitemid from listids where listid = $list_id) ";
        $q = tournament_search::getListSql($sql, $from, $limit);

        return $rows = db_q::sqlClean($q)->fetch();

    }
    
    public function getListBoxRows($list_id, $from, $limit) {

        $sql = array ();
        $sql['sql'] = "id IN 
            (select listitemid from listids where listid = $list_id) ";
        $q = tournament_search::getListSql($sql, $from, $limit);

        return $rows = db_q::sqlClean($q)->fetch();

    }
    
    public function getTournamentsFromIds ($ary) {
        $sql = array ();
 
        $x= $i = count($ary);
        $ids = '';
        foreach($ary as $id) {
            $ids.= "$id";
            $i--;
            if ($i) {
                $ids.=',';
            }
        }

        $sql['sql'] = "id IN ($ids)";
        $q = tournament_search::getUnionSql($sql, 0, $x);
        return $rows = db_q::sqlClean($q)->fetch();
    }
   
    public function viewAction () {

        $list_id = uri::fragment(3);
        $list = $this->getList($list_id);
        if (empty($list)) {
            echo html::getError('No such list');
            return;
        }
        
        $owner = user::ownID('lists', $list_id, session::getUserId());
        if ($list['share'] == '0' && !$owner) {
            moduleloader::setStatus(403);
            return;
        }

        // if delete
        /*
        if (isset($_POST['ids'])) {
            if (!$owner) {
                moduleloader::setStatus(403);
                return;
            }
            $vals = array_keys($_POST['ids']);
            db_q::delete('listids')->filter('listid =', $list_id)->condition('AND')->filterIn('listitemid IN', $vals)->exec();
            http::locationHeader("/tournament/lists/view/$list_id", "Selected tournaments was removed");
        }*/

        $num_rows = $this->getNumRows($list_id);
        $rows = $this->getListTournamentRows($list_id, 0 , $num_rows);

        $ts = new tournament_search();
        $ts->assets();
        $options = array ();

        if ($owner) {
            if (!isset($_GET['set_current'])) {
                $this->setSessionListId($list_id);
                http::locationHeader($_SERVER['REQUEST_URI'] . "?set_current=1");
            }
            
            
            //$this->setSessionListId($list_id);
            $options['list_options'] = 1;
        }
        if (session::isUser()) { 
            $options['report_options'] = 1;
        }

        echo $this->getViewHeadline($list);
        echo $this->getListOptions($list);

        if (empty($rows)) {
            echo html::getHeadline('No tournaments yet!');
            return;
        }
        
        $ts->displayRows(
                $rows, 
                $options,
                "/tournament/lists/view/$list_id",
                'post');
        
        $total = 0;
        foreach ($rows as $row) {
            $total+=$this->getTotalBuyin($row);
        }
        
        echo $this->getTotalTable($total);
        
    }
    
    public function getTotalTable($total) {
        $str = "<table>\n";
        $style = tournament_search::getRowStyle(0);
        $str.="<tr $style>";
        $str.="<th>Total buyin:</td><td>$" . number_format($total, 2,  '.', ',') . "</td>";
        $str.="</tr>";
        $str.= "</table>\n";
        return $str;
    }
    
    /**
     * set list id
     * @param int $list_id
     */
    public function setSessionListId($list_id) {
        $_SESSION['list_id'] = $list_id;
    }
    
    /**
     * get list id
     * @return int $list_id
     */
    public function getSessionListId() {
        if (!isset($_SESSION['list_id']) OR empty($_SESSION['list_id'])) {
            return 0;
        }
        
        $list = $this->getList($_SESSION['list_id']);
        if (empty($list)) {
            return 0;
        }
        
        return $_SESSION['list_id'];
    }
    
    public function getViewHeadline ($list) {
        $str = '';
        if ($list['share'] == 1) {
            $text = " (shared) ";
        } else {
            $text = ' (not shared) ';
        }
        $str.= html::getHeadline(
                html::specialEncode($list['title']) . $text);
        $host = config::getSchemeWithServerName();

        $str.= "Direct link: " . "$host/tournament/lists/view/$list[id]<br />";
        return $str;
    }
    
    public function topAction () {
        
        //template::setTitle('Grindpokers top list');
        //template_meta::setMeta(array ('title' => 'Top List', 'description' => 'Get all poker tournament top lists'));
        
        moduleloader::includeModule('vote');
        $rows = db_q::select('lists')->filter('share = ', 1)->order('vote', 'DESC')->fetch();
        $v = new vote();
        $host = config::getSchemeWithServerName();
        foreach ($rows as $row) {
            echo html::getHeadlineLinkEncoded("/tournament/lists/view/$row[id]", $row['title']);
            echo "Direct link: " . "$host/tournament/lists/view/$row[id]<br />";
            echo user::getProfileSimple($row['user_id']);
            echo "<br />";
            echo $v->buttons($row['id'], 'lists', $row['vote']);
            echo "<hr />";
        }
    }
}

class tournament_lists_module extends tournament_lists {}