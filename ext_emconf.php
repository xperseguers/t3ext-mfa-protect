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
    'version' => '0.3.0-dev',
    'state' => 'beta',
    'createDirs' => '',
    'author' => 'Xavier Perseguers',
    'author_email' => 'xavier@causal.ch',
    'author_company' => 'Causal Sàrl',
    'constraints' => [
        'depends' => [
            'php' => '8.1.0-8.5.99',
            'typo3' => '12.4.0-14.3.99'
        ],
        'conflicts' => [],
        'suggests' => [
            'mfa_frontend' => ''
        ],
    ],
];
