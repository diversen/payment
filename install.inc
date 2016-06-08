<?php

use diversen\lang;

// Example route
$_INSTALL['ROUTES'][] = array ('#/userinfo/payment/index#'=>
    array ('method' => 'payment::indexAction')
);

// Just to add translation
lang::translate('Payment');
