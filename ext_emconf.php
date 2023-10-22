<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "mfa_protect".
 *
 * Auto generated 04-10-2023 16:41
 ***************************************************************/

$EM_CONF[$_EXTKEY] = [
    'title' => 'MFA Protect',
    'description' => 'Protect rendering of sensitive content elements with MFA.',
    'category' => 'services',
    'version' => '0.2.0',
    'state' => 'beta',
    'createDirs' => '',
    'author' => 'Xavier Perseguers',
    'author_email' => 'xavier@causal.ch',
    'author_company' => 'Causal Sàrl',
    'constraints' => [
        'depends' => [
            'php' => '7.4.0-8.2.99',
            'typo3' => '10.4.0-12.4.99'
        ],
        'conflicts' => [],
        'suggests' => [
            'mfa_frontend' => ''
        ],
    ],
];
