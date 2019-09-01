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

defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function () {
        /** @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher */
        $signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class
        );

        $signalSlotDispatcher->connect(
            \TYPO3\CMS\Core\Resource\ResourceStorage::class,
            \TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFileAdd,
            \SchamsNet\AwsImageRecognition\Slots\FileProcessor::class,
            \SchamsNet\AwsImageRecognition\Slots\FileProcessor::SIGNAL_PROCESS_FILE
        );

        $signalSlotDispatcher->connect(
            \TYPO3\CMS\Core\Resource\ResourceStorage::class,
            \TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFileReplace,
            \SchamsNet\AwsImageRecognition\Slots\FileProcessor::class,
            \SchamsNet\AwsImageRecognition\Slots\FileProcessor::SIGNAL_PROCESS_REPLACE_FILE
        );
    }
);

// Configure logging
$logging = [
    // configuration for ERROR level log entries
    \TYPO3\CMS\Core\Log\LogLevel::INFO => [
        // add a FileWriter
        'TYPO3\\CMS\\Core\\Log\\Writer\\FileWriter' => [
            // configuration for the writer
            'logFile' => 'typo3temp/var/logs/' . date('Ymd') . '.' . $extensionName . '.log'
        ]
    ]
];

// Activate logging
$GLOBALS['TYPO3_CONF_VARS']['LOG']['SchamsNet']['AwsImageRecognition']['Slots']['writerConfiguration'] = $logging;
$GLOBALS['TYPO3_CONF_VARS']['LOG']['SchamsNet']['AwsImageRecognition']['Services']['writerConfiguration'] = $logging;
$GLOBALS['TYPO3_CONF_VARS']['LOG']['SchamsNet']['AwsImageRecognition']['Utilities']['writerConfiguration'] = $logging;

unset($logging);
