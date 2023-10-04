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
            'items' => [
                [
                    0 => '',
                    1 => '',
                ]
            ],
        ]
    ],
];

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('tt_content', $tempColumns);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'tt_content',
    'tx_mfaprotect_enable',
    '',
    'before:starttime'
);
