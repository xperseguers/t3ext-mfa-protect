<?php
defined('TYPO3') or die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    'mfa_protect',
    'Configuration/TypoScript',
    'Protect MFA'   // TODO: use a LLL reference
);
