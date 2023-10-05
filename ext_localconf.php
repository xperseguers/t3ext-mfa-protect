<?php

defined('TYPO3') || die();

(static function (string $_EXTKEY) {
    // Register hook for \TYPO3\CMS\Core\DataHandling\DataHandler
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] =
        \Causal\MfaProtect\Hooks\DataHandler::class;
    // Register hook for \TYPO3\CMS\Frontend\ContentObject\ContentContentObject
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content_content.php']['modifyDBRow'][] =
        \Causal\MfaProtect\Hooks\ContentContentObject::class;

    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        $_EXTKEY,
        'Overlay',
        [
            \Causal\MfaProtect\Controller\OverlayController::class => 'show',
        ],
        [
            \Causal\MfaProtect\Controller\OverlayController::class => 'show',
        ]
    );
})('mfa_protect');
