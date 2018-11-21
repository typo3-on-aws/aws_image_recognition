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

$EM_CONF[$_EXTKEY] = array(
    'title' => 'AWS Image Recognition',
    // @codingStandardsIgnoreLine
    'description' => 'Uses Amazon Web Service to detect objects, scenes, faces, recognize celebrities in images uploaded at the TYPO3 backend.',
    'category' => 'backend',
    'version' => '1.0.0',
    'module' => '',
    'state' => 'alpha',
    'createDirs' => '',
    'clearcacheonload' => 0,
    'author' => 'Michael Schams (schams.net)',
    'author_email' => 'schams.net',
    'author_company' => 'https://schams.net',
    'constraints' => array(
        'depends' => array(
            'typo3' => '8.7.0-8.7.999',
            'php' => '7.0.0-7.0.999',
            'aws_sdk_php' => '3.32.0-3.999.999',
        ),
        'conflicts' => array(
        ),
        'suggests' => array(
        ),
    )
);
