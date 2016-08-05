<?php

namespace modules\payment;

use diversen\conf;
use diversen\date;
use diversen\db\q;
use diversen\db\rb;
use diversen\html;
use diversen\html\table;
use diversen\http;
use diversen\lang;
use diversen\moduleloader;
use diversen\session;
use diversen\uri\manip;
use R;

moduleloader::setModuleIniSettings('payment');

class module {
    
    /**
     * Connect to DB
     */
    public function __construct() {
        rb::connectExisting();
    }
    
    /**
     * Store user based on user_id
     * @param int $user_id
     * @return boolean
     */
    public function enableUser($user_id) {
        $user = rb::getBean('payment', 'user_id', $user_id);
        $user->pays = 1;
        $user->user_id = $user_id;
        $user->pay_date = date::getDateNow();
        $user->pay_date_end = date::addDaysToTimestamp(date::getDateNow(), 30);
        return R::store($user);
    }
    
    /**
     * Disable user based on user_id
     * @param int $user_id
     * @return boolean
     */
    public function disableUser($user_id) {
        $user = rb::getBean('payment', 'user_id', $user_id);
        $user->pays = 0;
        $user->user_id = $user_id;
        return R::store($user);
    }

    /**
     * Parse GET
     */
    public function parseGET () {
        if (isset($_GET['enable'])) {
            
            $res = $this->enableUser(session::getUserId());
            if ($res) {
                session::setActionMessage(lang::translate('Payment enabled')); 
            } else {
                session::setActionMessage(lang::translate('Payment could not be enabled'), 'system_error');
            }
            $url = manip::deleteQueryPart($_SERVER['REQUEST_URI'], 'enable');
            http::locationHeader($url);
        }
        
        if (isset($_GET['disable'])) {
            
            $res = $this->disableUser(session::getUserId());
            if ($res) {
                session::setActionMessage(lang::translate('Payment disabled')); 
            } else {
                session::setActionMessage(lang::translate('Payment could not be disabled'), 'system_error');
            }
            $url = manip::deleteQueryPart($_SERVER['REQUEST_URI'], 'disable');
            http::locationHeader($url);
        }
    }
    
    public function indexAction () {
        
        if (!session::checkAccess('user', true) ) {           
            return;
        }

        if (!conf::getModuleIni('payment_enabled')) {
            moduleloader::setStatus(404);
            return;
        }
        
        $this->parseGET();
        
        $user_id = session::getUserId();
        if (!$this->userPaymentEnabled($user_id)) {
            $url = manip::deleteQueryPart($_SERVER['REQUEST_URI'], 'enable');
            echo html::createLink($url ."?enable=1", lang::translate('Enable payment'));
        } else {
            $url = manip::deleteQueryPart($_SERVER['REQUEST_URI'], 'disable');
            echo html::createLink($url ."?disable=1", lang::translate('Disable payment'));
        }
        
        if ($this->userPays($user_id)) {
            
            $row = $this->get($user_id);
            $row = $this->mergeHumanDates($row);
            $str = table::tableBegin(array('class' => 'uk-table'));
            $str.= "<tr>";
            $str.= table::th(lang::translate('Period begin: '), array ('class' => 'uk-width-2-10'));
            $str.= table::th(lang::translate('Period ends: '));
            $str.= "</tr>";
            $str.= "<tr>";
            $str.= table::td($row['pay_date_human']);
            $str.= table::td($row['pay_date_end_human']);
            $str.= "</tr>";
            $str.= "</table>";
            
            echo $str;
        }
    }
    
    /**
     * Adds pay_date_human and pay_date_end_human to $row
     * @param array $row
     * @return array $row
     */
    public function mergeHumanDates($row) {
        
        $pay_time = strtotime($row['pay_date']);
        $pay_time_end = strtotime($row['pay_date_end']);
        $row['pay_date_human'] = ucfirst(strftime(conf::getMainIni('date_format_short'), $pay_time));
        $row['pay_date_end_human'] = ucfirst(strftime(conf::getMainIni('date_format_short'), $pay_time_end));
        
        return $row;
    }

    /**
     * Checks if user pays. Based on 'pay_date' and pays
     * @param int $user_id
     * @return boolean
     */
    public function userPays($user_id) {
        $row = $this->get($user_id);
        
        if (empty($row)) {
            return false;
        }

        if (date::inRange($row['pay_date'], $row['pay_date_end'], date::getDateNow())) {
            return true;
        }        
        return false;
    }
    
    public function userPaymentEnabled ($user_id) {
        $row = $this->get($user_id);
        if (empty($row)) {
            return false;
        }
        
        if ($row['pays'] == 1) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Get a payment row based on user_id
     * @param int $user_id
     * @return array $row
     */
    public function get ($user_id) {
        return q::select('payment')->filter('user_id =', $user_id)->fetchSingle();
    }
    
    
    public function form () {
        
    }
}
