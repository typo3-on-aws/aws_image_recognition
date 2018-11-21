<?php

/*
 * This file is part of the TYPO3 CMS Extension "AWS Image Recognition"
 * Extension author: Michael Schams - https://schams.net
 *
 * For copyright and license information, please read the LICENSE.txt
 * file distributed with this source code.
 *
 * @package     TYPO3
 * @subpackage  aws_image_recognition
 * @author      Michael Schams <schams.net>
 * @link        https://schams.net
 */

defined('TYPO3_MODE') || die();

$extensionKey = 'aws_image_recognition';
$tableName = 'sys_file_metadata';
$languageFile = $extensionKey . '/Resources/Private/Language/locallang_tca.xlf';

// Add 12x object fields
for ($i = 1; $i <= 12; $i++) {
    $attributeName = 'object' . $i;
    $field = [
        $attributeName => [
            'label' => 'LLL:EXT:' . $languageFile . ':' . $tableName . '.' . $attributeName,
            'exclude' => 1,
            'config' => [
                'type' => 'input',
                'size' => 20,
                'eval' => 'trim',
                'readOnly' => 1
            ]
        ]
    ];

    // ...
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns($tableName, $field);
}

// Add celebrity fields (id, name and match confidence)
$fields = [
    [
        'celebrity_id' => [
            'label' => 'LLL:EXT:' . $languageFile . ':' . $tableName . '.celebrity_id',
            'exclude' => 1,
            'config' => [
                'type' => 'input',
                'size' => 20,
                'eval' => 'trim',
                'readOnly' => 1
            ]
        ]
    ],
    [
        'celebrity_name' => [
            'label' => 'LLL:EXT:' . $languageFile . ':' . $tableName . '.celebrity_name',
            'exclude' => 1,
            'config' => [
                'type' => 'input',
                'size' => 20,
                'eval' => 'trim',
                'readOnly' => 1
            ]
        ]
    ],
    [
        'celebrity_match_confidence' => [
            'label' => 'LLL:EXT:' . $languageFile . ':' . $tableName . '.celebrity_match_confidence',
            'exclude' => 1,
            'config' => [
                'type' => 'input',
                'size' => 20,
                'eval' => 'trim',
                'readOnly' => 1
            ]
        ]
    ]
];

foreach ($fields as $field) {
    // ...
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns($tableName, $field);
}

// Make fields visible in TCEforms:
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    $tableName,
    '
        --div--;LLL:EXT:' . $languageFile . ':tabs.image_recognition,
        --palette--;LLL:EXT:' . $languageFile . ':palette.objects;objects,
        --palette--;LLL:EXT:' . $languageFile . ':palette.celebrities;celebrities,
    ',
    '',
    ''
);

// Add the object palette:
$GLOBALS['TCA'][$tableName]['palettes']['objects'] = array(
    'showitem' => implode(',', [
        'object1',
        'object2',
        'object3',
        '--linebreak--',
        'object4',
        'object5',
        'object6',
        '--linebreak--',
        'object7',
        'object8',
        'object9',
        '--linebreak--',
        'object10',
        'object11',
        'object12'
    ])
);

// Add the celebrities palette:
$GLOBALS['TCA'][$tableName]['palettes']['celebrities'] = array(
    'showitem' => implode(',', [
        'celebrity_id',
        'celebrity_name',
        'celebrity_match_confidence'
    ])
);
