<?php

use diversen\lang;
use diversen\conf;

// Route
$_INSTALL = [];

if (conf::getModuleIni('payment_enabled')) {
    $_INSTALL['ROUTES'][] = array('#/userinfo/payment/index#' =>
        array('method' => 'payment::indexAction')
    );
}

// Just to add translation
lang::translate('Payment');
