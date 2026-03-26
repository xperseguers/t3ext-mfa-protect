<?php
defined('TYPO3') || die();

$tempColumns = [
    'tx_mfaprotect_enable' => [
        'exclude' => true,
        'label' => 'LLL:EXT:mfa_protect/Resources/Private/Language/locallang_db.xlf:tt_content.tx_mfaprotect_enable',
        'description' => 'LLL:EXT:mfa_protect/Resources/Private/Language/locallang_db.xlf:tt_content.tx_mfaprotect_enable.description',
        'l10n_mode' => 'exclude',
        'config' => [
            'type' => 'check',
            'renderType' => 'checkboxToggle',
        ]
    ],
];

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('tt_content', $tempColumns);
if ((new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() >= 13) {
    // There is an issue with auto-created system TCA columns since TYPO3 v13.
    // See https://forge.typo3.org/issues/109375
    // As such, we hack the "hidden" palette to add our field to have a similar UX
    // as in previous TYPO3 versions.
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToPalette(
        'tt_content',
        'hidden',
        '--linebreak--,tx_mfaprotect_enable',
        'after:hidden'
    );
} else {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
        'tt_content',
        'tx_mfaprotect_enable',
        '',
        'before:starttime'
    );
}
