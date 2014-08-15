<?php


// normalize data:



class tournament_import {

    public static $test = 1;

    /**
     * normalize imported array
     * @param array $ary
     * @return array $ary
     */
    public static function base($ary) {
        //$norm = array ();
        foreach ($ary as $key => $val) {
            if (self::unsetVal($val)) {
                unset($ary[$key]);
                continue;
            }
        }
        return $ary;
    }
    
    public function pokerAction() {

        if (!config::isCli()) {
            if (!session::checkAccess('admin')) {
                return;
            }
        }
        
        set_time_limit(0);

        $vendor = dirname(__FILE__) . "/../vendor";

        set_include_path(get_include_path() . PATH_SEPARATOR . $vendor);
        require $vendor . "/autoload.php";

        $files = array ();
        //$files[] = dirname(__FILE__) . '/mttlistings.xlsx';
        //$files[] = dirname(__FILE__) . '/sunday.xlsx';
        $files[] = dirname(__FILE__) . '/GrindPlanner.xlsx';
        foreach ($files as $file) {
            $this->importExcel($file);
        }
    }
    

    
    public function importExcel ($file) {
        $objPHPExcel = PHPExcel_IOFactory::load($file);
        //$sheets = $objPHPExcel->listWorksheetNames($file);
        //print_r($sheets);
        //$sheets = $objPHPExcel->listWorksheetNames();
        //$sheet = $objPHPExcel->getSheetByName('Pokerstars')->toArray(null, true, true, true);
        // 0 pokerstars
        //               - removed -1 lockPoker
        // 1 Winning
        // 2 888Poker
        // 3 pokerstars.fr
        // 4 full tilt poker (ftp)
        // 5 iPoker
        // 6 Winamax
        // 7 ongame
        // 8 Partypoker
        // 9 Microgaming
        // 10 pkr
        // 11 abbrivations
        // 12 weekly
        // 13 monthly
        // 15 
        $i = array ('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10');
        //$i = array (  '6');
        foreach($i as $val) {
            
            echo "doing $val<br />";
            $ary = $objPHPExcel->getSheet($val)->toArray(null, true, true, true);

        //print_r($ary); die;
            
            tournament_import::$test = 0;
            $data = tournament_import::normalize($ary);
            //print_r($data); die;
            tournament_import::insert($data);
        }
    }

    public static function lower($ary) {

        foreach ($ary as $key => $val) {
            $ary[$key] = mb_convert_case($val, MB_CASE_LOWER);
        }
        return $ary;
    }

    /**
     * normalize imported array
     * @param array $ary
     * @return array $ary
     */
    public static function normalize($ary) {
        $norm = array();
        $i = 0;
        foreach ($ary as $key => $val) {
            if (self::unsetVal($val)) {
                unset($ary[$key]);
                continue;
            }

            //$val = self::lower($val);

            if (self::$test) {
                $norm[$i]['org'] = $val;
                $norm[$i]['norm'] = self::toAry($val);
            } else {
                $norm[] = self::toAry($val);
            }
            $i++;
        }
        return $norm;

        // $25.40 Stud [Hyper-Turbo, Knockout
    }

    public static function insert($ary, $rows = 0) {
        db_rb::connect();
        $i = 0;
        foreach ($ary as $val) {

            $i++;
            if ($rows && ($i > $rows)) {
                break;
            }

            $bean = db_rb::getBean('tournaments');
            $bean = db_rb::arrayToBean($bean, $val);
            R::store($bean);
        }
    }

    /**
     * normalize time 
     * @param string $time
     * @return string $time in format e.g. 10:00
     */
    public static function getTime($time) {
        $time = trim($time);
        $ary = explode(" ", $time);
        return $ary[0] . ":00";
    }

    /**
     * get day
     * @param string $time
     * @return string $day 'mon', 'sun' etc. In anyday return 'any
     */
    public static function getBeginDay($time) {
        $time = trim($time);
        $time = strtolower($time);
        $ary = explode(" ", $time);
        if (isset($ary[1])) {
            return $ary[1];
        }
        return 'any';
    }

    /**
     * normlize money value, remove $, ' ', and replace ',' with '.'
     * @param string $val
     * @return string $val
     */
    public static function _normalizeMoney($val) {
        $val = trim($val);
        $val = str_replace(' ', '', $val);
        $val = str_replace('$', '', $val);
        $val = str_replace('€', '', $val);
        $val = str_replace(',', '.', $val);
        $val = preg_replace('/[^0-9.]+/', '', $val);
        return $val;
    }
    
    /**
     * normlize money value, remove $, ' ', and replace ',' with '.'
     * @param string $val
     * @return string $val
     */
    public static function _normalizeInt($val) {
        $val = trim($val);
        $val = preg_replace('/[^0-9]+/', '', $val);
        return $val;
    }

    /**
     * normalize buyin
     * @param string $buyin
     * @return string $buyin
     */
    public static function getBuyin($buyin) {
        $buyin = trim($buyin);
        $ary = explode('+', $buyin);
        
        $num = count($ary);
        $buy = trim($ary[0]);
        $fee = trim($ary[1]);
        
        $buy =  self::_normalizeMoney($buy);
        $fee = self::_normalizeMoney($fee);
        
        // if knockout
        if ($num == 3) {
            $extra = self::_normalizeMoney($ary[2]);
        } else {
            $extra = 0;
        }
        
        
        
        return $buy;
    }

    /**
     * get ante
     * @param type $buyin
     * @return mixed $ante
     */
    public static function getRake($buyin) {
        $buyin = trim($buyin);
        $ary = explode('+', $buyin);
        if (count($ary) == 2) {
            $val = trim($ary[1]);
            return self::_normalizeMoney($val);
        }

        if (count($ary) == 3) {
            $val = trim($ary[2]);
            return self::_normalizeMoney($val);
        }
        echo "No rake";
        print_r($buyin);
        return 0;
    }

    /**
     * get re buyin 0 if not rebuy
     * @param type $buyin
     * @return int
     */
    public static function getKOBonus($buyin) {
        $buyin = trim($buyin);
        $ary = explode('+', $buyin);

        if (count($ary) == 3) {
            $val = trim($ary[1]);
            return self::_normalizeMoney($val);
        }

        return 0;
    }

    public static function getMoneyCode($val) {
        if (strstr($val, '€')) {
            return '€';
        }

        if (strstr($val, '$')) {
            return '$';
        }

        // default to dollars
        return '$';
    }

    public static function getLimitFromType($value) {
        $value = strtoupper($value);
        $value = trim($value);

        $ary = explode(' ', $value);

        foreach ($ary as $val) {
            // NL

            if (
                    $val == 'NLH' ||
                    $val == 'NLSD' ||
                    $val == 'NLO' ||
                    $val == 'NLSD' ||
                    $val == 'NL'
            ) {
                return 'NL';
            }

            // ML

            if (
                    $val == '8-GAME' ||
                    $val == '8G' ||
                    $val == 'ML' ||
                    $val == 'MHL' ||
                    $val == '10G' ||
                    $val == 'NLH,PLO' ||
                    $val == 'NLH/PLO' ||
                    $val == 'HORSE' ||
                    $val == 'HOSE'
            ) {
                return 'ML';
            }

            if (
            //$val == '8-GAME' ||
            //$val == '8G' ||
                    $val == 'PLH/PLO' ||
                    $val == 'PLH,PLO' ||
                    $val == 'PL' ||
                    $val == 'PLH' ||
                    $val == 'PLO'

            //$val == 'PL 5O'
            ) {
                return 'PL';
            }

            if (
                    $val == 'STUD' ||
                    $val == 'TS' || // triple stud
                    $val == 'FLH' ||
                    $val == 'FLTD' ||
                    $val == 'FL' ||
                    $val == 'FLO' ||
                    $val == 'MLH' ||
                    $val == 'RAZZ' ||
                    $val == 'FLTD' ||
                    $val == 'LR'  // razz
            ) {
                return 'FL';
            }

            // not know yet

            if (
                    $val == 'IRISH'

            //$val == 'TD' // triple draw
            ) {
                return "NK ($val)";
            }
        }
        echo "Wrong limit = $value";
        //die('value ' . $val);



        // NN = Not known
        /*
          if (
          $val == '5-C' ||
          $val == 'IRISH' ||
          $val == 'HORSE' ||
          $val == '2-7 SD' ||
          $val == '2-7 TD' ||
          $val == 'STUD' ||
          $val == '5 STUD' ||
          $val == '5 Draw' ||
          $val == 'FL BADUGI'
          ) {
          return 'NK';
          }
         * 
         */
    }

    public static function getGameFromType($value) {
        //echo $value; 
        //return;
        $value = strtoupper($value);
        $value = trim($value);

        $ary = explode(' ', $value);

        foreach ($ary as $val) {
            //echo $value . "<br />";
            if ($val == '8-GAME' || $val == '8G') {
                return '8_game';
            }

            if ($val == '10-GAME' || $val == '10G') {
                return '10_game';
            }

            if ($val == 'NLH' || $val == 'FLH' || $val == 'MLH' || $val == 'PLH') {
                return "holdem";
            }

            // OMAHA 5-card
            if (strstr($value, 'PLO 5-C')) {
                return '5_card_omaha';
            }

            if (strstr($value, 'PLO 5C')) {
                return '5_card_omaha';
            }

            // 5 cards omaha: NLO 5-C
            if (strstr($value, 'NLO 5-C')) {
                return '5_card_omaha';
            }

            // omaha
            if ($val == 'FLO' || $val == 'NLO' || $val == 'PLO') {
                return 'omaha';
            }

            // draw
            if (strstr($value, 'NLSD 2-7')) {
                return '2_7_single_draw';
            }

            if ($val == 'DRAW') {
                return '5_card_draw';
            }

            if ($val == 'FLTD') {
                return 'triple_draw';
            }

            if ($val == 'HORSE') {
                return 'horse';
            }

            if ($val == 'HOSE') {
                return 'hose';
            }

            if ($val == 'IRISH') {
                return 'irish';
            }

            if ($val == 'COURCHEVEL') {
                return 'courchevel';
            }

            if ($val == 'PLH/PLO' || $val == 'PLH,PLO') {
                return 'ha';
            }


            if ($val == 'NLH,PLO' || $val == 'NLH/PLO') {
                return 'ha';
            }

            if ($val == 'BADUGI') {
                return 'badugi';
            }


            if ($val == 'RAZZ' || $val == 'LR') {
                return 'razz';
            }

            if (strstr($value, 'TRIPLE STUD')) {
                return 'triple_stud';
            }

            if ($val == 'TS') {
                return 'triple_stud';
            }

            if ($val == 'STUD') {
                return 'stud';
            }
        }
        echo "Wrong game value $value";
        //die('value ' . $val);



        // NN = Not known
        /*
          if (
          $val == '5-C' ||
          $val == 'IRISH' ||
          $val == 'HORSE' ||
          $val == '2-7 SD' ||
          $val == '2-7 TD' ||
          $val == 'STUD' ||
          $val == '5 STUD' ||
          $val == '5 Draw' ||
          $val == 'FL BADUGI'
          ) {
          return 'NK';
          }
         * 
         */
    }

    public static function getSpeedFromFormat($value) {
        $value = strtoupper($value);
        $ary = explode(' ', $value);
        foreach ($ary as $val) {
            if ($val == 'ST') {
                return 'ST';
            }
            if ($val == 'T') {
                return 'T';
            }
            if ($val == 'DS') {
                return 'DS';
            }
        }
        return 'NORMAL';
    }

    /**
     * return database ready array 
     * @param array $val
     * @return array $ary
     */
    public static function toAry($val) {
        $ary = array();

        // org values
        $ary['org_type'] = $val['C'];
        $ary['org_format'] = $val['D'];
        $ary['org_name'] = $val['E'];
        
        if (isset($val['I'])) {
            $ary['org_note'] = $val['I'];
        } else {
            $ary['org_note'] = '';
        }

        // site
        $ary['site'] = strtolower($val['A']);
        $ary['site'] = str_replace('.', '', $ary['site']);

        // date and time
        $ary['begin'] = self::getTime($val['B']);
        $ary['day'] = self::getBeginDay($val['B']);

        // table size
        // $ary['tablesize'] = self::getTableSizeFromFormat($val['D']); // [D] => 6max ST or 6 ST
        
        // get all formats
        $ary['freezeout'] = self::getFreezeout($val['D']);
        $ary['turbo'] = self::getTurbo($val['D']);
        $ary['superturbo'] = self::getSuperTurbo($val['D']);
        $ary['headsup'] = self::getHeadsUp($val['D']);
        $ary['4max'] = self::get4Max($val['D']);
        $ary['6max'] = self::get6Max($val['D']);
        $ary['timecapped'] = self::getTimeCapped($val['D']);
        $ary['breakthru'] = self::getBreakThru($val['D']);
        $ary['wta'] = self::getWinnerTakesAll($val['D']);
        $ary['2chance'] = self::get2Chance($val['D']);
        $ary['3chance'] = self::get3Chance($val['D']);
        $ary['4chance'] = self::get4Chance($val['D']);
        $ary['don'] = self::getDoubleOrNothing($val['D']);
        $ary['ton'] = self::getTripleOrNothing($val['D']);
        $ary['shootout'] = self::getShootout($val['D']);
        $ary['deepstack'] = self::getDeepStack($val['D']);
        $ary['escalator'] = self::getEscalator($val['D']);
        $ary['capped'] = self::getCapped($val['D']);
        $ary['knockout'] = self::getKnockout($val['D']);
        $ary['flipout'] = self::getFlipout($val['D']);
        $ary['fastfold'] = self::getFastFoldPoker($val['D']);
        $ary['reentry'] = self::getReEntry($val['D']);
        $ary['multientry'] = self::getMultiEntry($val['D']);
        $ary['cubed'] = self::getCubed($val['D']);
        $ary['rebuy']= self::getRebuy($val['D']);
        
        
        // limits NL, PL etc. 
        $ary['limittype'] = self::getLimitFromType($val['C']);

        // game type omaha - holdem - stud - etc
        $ary['gametype'] = self::getGameFromType($val['C']);

        // speed
        $ary['speed'] = self::getSpeedFromFormat($val['D']); // [D] => 6max ST or 6 ST
        // money info
        $code = self::getMoneyCode($val['F']);
        if (!$code) {
            print_r($val);
            die;
        }

        $ary['moneycode'] = $code;
        $ary['buyin'] = self::getBuyin($val['F']);
        $ary['rake'] = self::getRake($val['F']);
        $ary['kobonus'] = self::getKOBonus($val['F']);
        $ary['pricepool'] = self::_normalizeMoney($val['G']);

        // late reg
        $minutes =  self::_normalizeInt($val['H']);
        $minutes = trim($minutes);
        if (empty($minutes)) {
            $minutes = '0';
        }
        $ary['latereg'] = $minutes;
        $ary = self::calculateDateTimes($ary);
        //print_r($ary); die;
        return $ary;
        
    }
    
    /**
     * calculates datetimes from day, begin, latereg: 
     * begin_dt, begin_dt_late
     * @param array $ary
     * @return array $ary
     */
    public static function calculateDateTimes ($ary) {
        $today = date('Y-m-d');
        $day = strtolower(date('D'));
        
        
        $ary['begin_dt'] = "$today $ary[begin]";
        $ary['begin_dt_late'] = self::addMinutesToDate($ary['begin_dt'], $ary['latereg']);
 
        $late_ts = strtotime($ary['begin_dt_late']);
        $now_ts = strtotime('now');

        // tournament has been run - opdate to next tournament date
        // 
        // we push forward to next datestamp
        // + 24 hours = 24 * 60 minutes OR a week
        if ($now_ts > $late_ts) {
            $day_m = 24 * 60;

            if ($ary['day'] == 'any') {
                $ary['begin_dt'] = self::addMinutesToDate($ary['begin_dt'], $day_m);
                $ary['begin_dt_late'] = self::addMinutesToDate($ary['begin_dt_late'], $day_m);
            } else {
                // push forward a week
                $week_ts = strtotime("next $ary[day] $ary[begin]");
                $ary['begin_dt'] = date('Y-m-d H:i:s', $week_ts);
                $ary['begin_dt_late'] = date('Y-m-d H:i:s', strtotime("$ary[begin_dt] +$ary[latereg] minutes"));
            }
            return $ary;   
        }
        
        // tournament has not been run
        //
        //
        if ( ($ary['day'] == 'any') OR ( $ary['day'] == $day) ) {
            // ok current day
        } else {
            // only on week day - push forward
            $week_ts = strtotime("next $ary[day] $ary[begin]");
            $ary['begin_dt'] = date('Y-m-d H:i:s', $week_ts);
            $ary['begin_dt_late'] = date('Y-m-d H:i:s', strtotime("$ary[begin_dt] +$ary[latereg] minutes"));
        }

        return $ary;
    }
    
    public static function checkReplace ($ary) {

        // get day of begin_dt
        $late_ts = strtotime($ary['begin_dt']);
        
        $day = date('D', $late_ts);
        
        $row = db_q::select('tournaments_replace')->
                filter('parent =', $ary['id'])->condition('AND')->
                filter('day =', $day)->
                fetchSingle();
        
        //print_r($row);
        if (!empty($row)) {
            $ary['org_name'] = $row['org_name'];
            $ary['pricepool'] = $row['pricepool'];
        }
        
        //print_r($ary); die;
        
        return $ary;
    }
    
    public static function updateAllDatetime() {

        $now = date('Y-m-d H:i:s');
        $rows = db_q::select('tournaments')->filter('begin_dt_late <', $now)->fetch();

        foreach ($rows as $ary) {

            
            $ary = self::calculateDateTimes($ary);
            $ary = self::checkReplace($ary);
            //print_r($ary); die;
            $bean = db_rb::getBean('tournaments', 'id', $ary['id']);
            $bean = db_rb::arrayToBean($bean, $ary);

            R::begin();
            R::store($bean);
            R::commit();
        }
    }

    /**
     * add minutes to datetime
     * @param string $date
     * @param int $minutes
     * @return string $datetime
     */
    public static function addMinutesToDate ($date, $minutes) {
        return date('Y-m-d H:i:s', strtotime($date . " +$minutes minute"));
    }

    /*
      [1]=>
      array(9) {
      ["A"]=>
      string(4) "Site"
      ["B"]=>
      string(15) " Start time CET"
      ["C"]=>
      string(4) "Type"
      ["D"]=>
      string(6) "format"
      ["E"]=>
      string(4) "name"
      ["F"]=>
      string(5) "Buyin"
      ["G"]=>
      string(20) "guaranteed prizepool"
      ["H"]=>
      string(27) "late registration (minutes)"
      ["I"]=>
      string(5) "Notes"
      }
     */

    public static function getTurbo($val) {
        $val = strtoupper($val);
        $ary = preg_split("/[\s-]+/", $val);
        foreach ($ary as $val) {
            if ($val == 'T') {
                return 1;
            }
        }
        return 0;
    }

    public static function getSuperTurbo($val) {
        $val = strtoupper($val);
        $ary = preg_split("/[\s-]+/", $val);
        foreach ($ary as $val) {
            if ($val == 'ST') {
                return 1;
            }
        }
        return 0;
    }

    /**
     * get HU
     * @param type $val
     * @return int
     */
    public static function getHeadsUp($val) {
        $val = strtoupper($val);
        if (strstr($val, 'HU')) {
            return 1;
        }
        return 0;
    }

    /**
     * get max4
     * @param type $val
     * @return int
     */
    public static function get4Max($val) {
        $val = strtoupper($val);
        if (strstr($val, '4MAX') || strstr($val, '4')) {
            return 1;
        }
        return 0;
    }

    /**
     * get max6
     * @param type $val
     * @return int
     */
    public static function get6Max($val) {
        $val = strtoupper($val);
        if (strstr($val, '6MAX') || strstr($val, '6')) {
            return 1;
        }
        return 0;
    }

    public static function getTimeCapped($val) {
        $val = strtoupper($val);
        $ary = preg_split("/[\s-]+/", $val);
        foreach ($ary as $val) {
            if ($val == 'TIME') {
                return 1;
            }
        }
        return 0;
    }

    public static function getBreakThru($val) {
        $val = strtoupper($val);
        $ary = preg_split("/[\s-]+/", $val);
        foreach ($ary as $val) {
            if ($val == 'BT') {
                return 1;
            }
        }
        return 0;
    }

    
    public static function getWinnerTakesAll($val) {
        $val = strtoupper($val);
        $ary = preg_split("/[\s-]+/", $val);
        foreach ($ary as $val) {
            if ($val == 'WTA') {
                return 1;
            }
        }
        return 0;
    }
    
    public static function get2Chance($val) {
        $val = strtoupper($val);
        if (strstr($val, '2C')) {
            return 1;
        }
        return 0;
    }

    public static function get3Chance($val) {
        $val = strtoupper($val);
        if (strstr($val, '3C')) {
            return 1;
        }
        return 0;
    }

    public static function get4Chance($val) {
        $val = strtoupper($val);
        if (strstr($val, '4C')) {
            return 1;
        }
        return 0;
    }


    public static function getDoubleOrNothing($val) {
        $val = strtoupper($val);
        $ary = preg_split("/[\s-]+/", $val);
        foreach ($ary as $val) {
            if ($val == 'DON') {
                return 1;
            }
        }
        return 0;
    }

    public static function getTripleOrNothing($val) {
        $val = strtoupper($val);
        $ary = preg_split("/[\s-]+/", $val);
        foreach ($ary as $val) {
            if ($val == 'TON') {
                return 1;
            }
        }
        return 0;
    }

    public static function getShootout($val) {
        $val = strtoupper($val);
        $ary = preg_split("/[\s-]+/", $val);
        foreach ($ary as $val) {
            if ($val == 'SO') {
                return 1;
            }
        }
        return 0;
    }

    public static function getDeepStack($val) {
        $val = strtoupper($val);
        $ary = preg_split("/[\s-]+/", $val);
        foreach ($ary as $val) {
            if ($val == 'DS') {
                return 1;
            }
        }
        return 0;
    }

    public static function getEscalator($val) {
        $val = strtoupper($val);
        $ary = preg_split("/[\s-]+/", $val);
        foreach ($ary as $val) {
            if ($val == 'ES') {
                return 1;
            }
        }
        return 0;
    }

    public static function getCapped($val) {
        $val = strtoupper($val);
        $ary = preg_split("/[\s-]+/", $val);
        foreach ($ary as $val) {
            if ($val == 'CAP') {
                return 1;
            }
        }
        return 0;
    }

    public static function getKnockout($val) {
        $val = strtoupper($val);
        $ary = preg_split("/[\s-]+/", $val);
        foreach ($ary as $val) {
            if ($val == 'KO') {
                return 1;
            }
        }
        return 0;
    }

    public static function getFlipout($val) {
        $val = strtoupper($val);
        $ary = preg_split("/[\s-]+/", $val);
        foreach ($ary as $val) {
            if ($val == 'F') {
                return 1;
            }
        }
        return 0;
    }
    
 
    public static function getFastFoldPoker($val) {
        $val = strtoupper($val);
        $ary = preg_split("/[\s-]+/", $val);
        foreach ($ary as $val) {
            if ($val == 'S') {
                return 1;
            }
        }
        return 0;
    }
    
    
    public static function getReEntry($val) {
        $val = strtoupper($val);
        $ary = preg_split("/[\s-]+/", $val);
        foreach ($ary as $val) {
            if ($val == 'RE') {
                return 1;
            }
        }
        return 0;
    }
    
    public static function getMultiEntry($val) {
        $val = strtoupper($val);
        $ary = preg_split("/[\s-]+/", $val);
        foreach ($ary as $val) {
            if ($val == 'ME') {
                return 1;
            }
        }
        return 0;
    }
    
    
    public static function getCubed($val) {
        $val = strtoupper($val);
        $ary = preg_split("/[\s-]+/", $val);
        foreach ($ary as $val) {
            if ($val == 'C') {
                return 1;
            }
        }
        return 0;
    }
    
        
    public static function getRebuy($val) {
        $val = strtoupper($val);
        $ary = preg_split("/[\s-]+/", $val);
        foreach ($ary as $val) {
            if ($val == 'R') {
                return 1;
            }
        }
        return 0;
    }
    
    public static function getFreezeout($val) {
        $val = strtoupper($val);
        $ary = preg_split("/[\s-]+/", $val);
        foreach ($ary as $val) {
            if ($val == 'FO') {
                return 1;
            }
        }
        return 0;
        
    }

    public static function getTableSizeFromFormat($val) {

        // [D] => 6max ST or 6 ST
        $ary = explode(' ', $val);
        foreach ($ary as $val) {

            $val = trim($val);
            if (strstr($val, '6max') || strstr($val, '6')) {
                return 6;
            }

            if (strstr($val, '4max')) {
                return 4;
            }

            if (strstr($val, 'HU')) {
                return 2;
            }

            if (strstr($val, '8max')) {
                return 8;
            }
        }
        return 0;

        // 8-Max
        // Heads-Up
    }

    /**
     * unset values before import
     * @param array $val
     * @return boolean $res 
     */
    public static function unsetVal($val) {
        if (!isset($val['A'])) {
            return true;
        }

        $val['A'] = trim($val['A']);
        if (empty($val['A'])) {
            return true;
        }

        if (strtolower($val['A']) == 'site') {
            return true;
        }
        return false;
    }

}

class tournament_import_module extends tournament_import {}