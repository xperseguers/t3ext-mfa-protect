<?php

defined('TYPO3') || die();

(static function (string $_EXTKEY) {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content_content.php']['modifyDBRow'][] =
        \Causal\MfaProtect\Hooks\ContentContentObject::class;
})('mfa_protect');
