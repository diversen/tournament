<?php

moduleloader::includeModule('tournament');
function block_tournament_user () { ?>
<div class ="block"><?php

moduleloader::includeModule('locales'); 
echo locales_views::timezoneInfo();

$tour = new tournament();
//$banner = 
    
?>    
</div>
<div class="block" id="gp-block" >

            <?php
            
            $str = '<div class="block_black">';
            $str.= html::getHeadline('GP-Lists');
            if (!session::isUser()) {
                $create_link =html::createLink('/account/login/create', 'create an account', array ('class' => ''));
                $login_link = html::createLink('/account/login/index', 'login', array ('class' => ''));
                
                $str.= "$create_link or $login_link ";
                $str.= " and make your own personal GP-List";
                
            } else {
                $t = new tournament_lists_module();
                $lists = $t->getUserLists(session::getUserId());
                $str.=$t->getDisplayBoxLists($lists);
            }
            
            $str.="</div>";
            echo $str;
            
            echo "<br />";
            echo "<br />";
            echo "<br />";
            $tour->getBanner(); 
            
            ?>
            
        </div>
<?php }

function tournament_get_lists ($user_id) {
    
    return $ary;
}