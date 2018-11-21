<?php
namespace SchamsNet\AwsImageRecognition\Utilities;

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

use \TYPO3\CMS\Core\Utility\GeneralUtility;
use \TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Extension Utility Class
 */
class Extension implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * Returns the version of a specific extension
     *
     * @access public
     * @param string Extension key
     * @return string Extension version, e.g. "1.2.999"
     */
    public static function getExtensionVersion($extensionKey)
    {
        return ExtensionManagementUtility::getExtensionVersion($extensionKey);
    }

    /**
     * Returns the entire configuration array of a specific extension (unknown keys/values are filtered).
     *
     * @access public
     * @param string Extension key
     * @return array Extension configuration
     */
    public static function getExtensionConfiguration($extensionKey)
    {
        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
        $objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');

        /** @var \TYPO3\CMS\Extensionmanager\Utility\ConfigurationUtility $configurationUtility */
        $configurationUtility = $objectManager->get('TYPO3\CMS\Extensionmanager\Utility\ConfigurationUtility');

        $validConfigurationKeys = array(
            'access_key',
            'access_secret',
            'aws_region',
            'image_types',
            'max_file_size',
            'enable_detect_objects',
            'enable_detect_faces',
            'enable_recognize_celebrities'
        );

        $extensionConfiguration = $configurationUtility->getCurrentConfiguration($extensionKey);
        if (is_array($extensionConfiguration)) {
            foreach ($extensionConfiguration as $key => $configuration) {
                if (!in_array($key, $validConfigurationKeys)) {
                    unset($extensionConfiguration[$key]);
                } else {
                    $key = GeneralUtility::underscoredToLowerCamelCase($key);
                    $extensionConfiguration[$key] = $configuration;
                }
            }
            return $extensionConfiguration;
        }
        return [];
    }

    /**
     * Returns a specific configuration value
     * https://docs.typo3.org/typo3cms/TyposcriptSyntaxReference/TypoScriptTemplates/TheConstantEditor/Index.html#type
     *
     * @access public
     * @param string Extension key
     * @param string Configuration key
     * @return mixed Configuration value or null if key does not exist
     */
    public static function getExtensionConfigurationValue($extensionKey, $configurationKey)
    {
        $extensionConfiguration = self::getExtensionConfiguration($extensionKey);
        if (array_key_exists($configurationKey, $extensionConfiguration)) {
            if ($extensionConfiguration[$configurationKey]['type'] == 'boolean') {
                return (boolean)$extensionConfiguration[$configurationKey]['value'];
            }
            if ($extensionConfiguration[$configurationKey]['type'] == 'int+') {
                return intval($extensionConfiguration[$configurationKey]['value']);
            }
            return trim($extensionConfiguration[$configurationKey]['value']);
        }
        return null;
    }
}
