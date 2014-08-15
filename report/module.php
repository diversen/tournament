<?php

class tournament_report {
    
    public function indexAction () {
        
        if (!session::checkAccess('user')) {
            return;
        }
        
        $id = uri::fragment(3);

        $t = new tournament_module();
        $row = $t->get($id);
        
        $return_to = rawurldecode($_GET['return_to']);
        
        if (empty($row)) {
            echo html::getHeadline('No such tournament');
            return;
        } else {
            echo html::getHeadline('File a report');
        }
        
                
        $rows = array ();
        $rows[] = $row;
        
        $ts = new tournament_search();
        $ts->assets();
        $ts->displayRows($rows);
        
        if (!empty($_POST)) {
            $this->add ();
            http::locationHeader(
                    $_GET['return_to'], 
                    'Thanks for your report. We will look into it!'
                );
        }
        
        echo $this->form($id);
    }
    
    public function add () {
        db_rb::connect();
        $bean = db_rb::getBean('report');
        
        $bean->note = $_POST['note'];
        $bean->tournament = $_POST['id'];
        $user = session::getUserId();
        if (!$user) {
            $bean->user = 0;
        } else {
            $bean->user = $user;
        }
        
        return R::store($bean);
        
        
    }
    
    public function form ($id) {
        
        echo html::getHeadline('Enter a note');
        $f = new html();
        $f->formStart();
        $f->hidden('id', $id);
        //$f->label('note', 'Enter a note');
        $f->textareaMed('note');
        $f->submit('submit', 'Send');
        $f->formEnd();
        return $f->getStr();
    
    }
    
    public function viewAction () {
        if (!session::checkAccess('admin')) {
            return;
        }
        
        
        $sql = "select *, count(tournament) as num_rows from report group by (tournament)";
        $rows = db_q::sqlClean($sql)->fetch();
        echo html::getHeadline('Reports');
        
        $t = new tournament_module();
        $options = array ('admin_edit' => 1, 'admin_delete' => 1);
        foreach ($rows as $row) {
            $str = $row['num_rows'] . " report(s) on this tournament: ";
            echo html::getHeadline($str);
            $t->displaySingle($row['tournament'], $options);
            $notes = $this->getNotes($row['tournament']);
            $i = 1;
            foreach ($notes as $note) {
                echo $i . ': ' . html::specialEncode($note['note']);
                echo user::getProfileAdminLink($note['user']);
                echo "<br />";
                $i++;
            }
            echo $this->displayOptions ($row['tournament']);
            echo "<hr />";
        }
    }
    
    public function deleteAction () {
        if (!session::checkAccess('admin')) {
            return;
        }
        
        $id = uri::fragment(3);
        $t = new tournament_module();
        $row = $t->get($id);
        if (isset($_POST['Submit'])) {
            db_q::delete('report')->filter('tournament =', $id)->exec();
            http::locationHeader(rawurldecode($_GET['return_to']), 'Report deleted');
        }
        
        echo html::getHeadline('Delete report(s) connected to this tournament?');
        echo html_helpers::confirmDeleteForm('Submit', 'Confirm delete');
        
        $rows = array ();
        $rows[] = $row;
        //$options = array ('admin_edit' => 1, 'admin_delete' => 1);
        $t->displaySingle($id );
    }
    
    public function displayOptions ($id) {
        
        $return_to = rawurlencode($_SERVER['REQUEST_URI']);
        $ext = "return_to=$return_to";
        $str = '';
        $str.= html::createLink("/tournament/report/delete/$id?$ext", 'Delete report(s)');
        //$str.= MENU_SUB_SEPARATOR;
        //$str.= html::createLink("/tournament/delete/$id?$ext", 'Delete');
        return $str;
    }
    
    public function getNotes ($id) {
        return db_q::select('report')->filter('tournament =', $id)->fetch();
    }
    
    public function displaySingle ($id) {
        
        $t = new tournament_module();
        $row = $t->get($id);
        
        $rows = array ();
        $rows[] = $row;
        
        $ts = new tournament_search();
        $ts->assets();
        $ts->displayRows($rows);
    }
}
