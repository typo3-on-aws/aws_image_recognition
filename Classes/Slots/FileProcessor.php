<?php
namespace SchamsNet\AwsImageRecognition\Slots;

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

use SchamsNet\AwsImageRecognition\Utilities\Extension;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility;

/**
 * Slot implementation when a file is uploaded but before it is processed
 * by \TYPO3\CMS\Core\Resource\ResourceStorage
 */
class FileProcessor
{
    /**
     * Must match method name in this class. Used in ext_localconf.php
     *
     * @var string
     */
    const SIGNAL_PROCESS_FILE = 'processFile';
    const SIGNAL_PROCESS_REPLACE_FILE = 'processReplaceFile';

    /**
     * TYPO3 extension key
     *
     * @access public
     * @var string
     */
    public $extensionKey = 'aws_image_recognition';

    /**
     * Default: valid file mime types for image processing
     *
     * @access private
     * @var string
     */
    private $defaultValidMimeTypes = 'jpg,jpeg,png';

    /**
     * Default: maximum file size for image processing
     *
     * @access private
     * @var int
     */
    private $defaultMaxFileSize = 2048000;

    /**
     * @access protected
     * @var \TYPO3\CMS\Core\Log\LogManager
     */
    private $logger;

    /**
     * @access protected
     * @var \SchamsNet\AwsImageRecognition\Services\AmazonRecognition
     */
    private $recognition;

    /**
     * Constructor
     */
    public function __construct()
    {
        /** @var $logger \TYPO3\CMS\Core\Log\Logger */
        $this->logger = GeneralUtility::makeInstance('TYPO3\CMS\Core\Log\LogManager')->getLogger(__CLASS__);

        /** @var $recognition SchamsNet\AwsImageRecognition\Services\AmazonRekognition */
        $this->recognition = GeneralUtility::makeInstance('SchamsNet\AwsImageRecognition\Services\AmazonRekognition');
    }

    /**
     * [...]
     *
     * @param \TYPO3\CMS\Core\Resource\FileInterface $file
     * @param \TYPO3\CMS\Core\Resource\Folder $folder
     * @return void
     */
    public function processFile(\TYPO3\CMS\Core\Resource\FileInterface $file, $folder = null)
    {
        $this->logger->info(__METHOD__ . ':' . __LINE__);
        $this->logFileDetails($file);
        if ($this->isValidImage($file)) {
            $this->recognition->processImage($file);
        }
    }

    /**
     * [...]
     *
     * @param \TYPO3\CMS\Core\Resource\FileInterface $file
     * @param string $temporaryFile
     * @return void
     */
    public function processReplaceFile(\TYPO3\CMS\Core\Resource\FileInterface $file, $temporaryFile = null)
    {
        $this->logger->info(__METHOD__ . ':' . __LINE__);
        $this->processFile($file, null);
    }

    /**
     * Write some details about the uploaded file to TYPO3's log
     *
     * @param \TYPO3\CMS\Core\Resource\FileInterface $file
     * @return void
     */
    public function logFileDetails(\TYPO3\CMS\Core\Resource\FileInterface $file)
    {
        $this->logger->info(__METHOD__ . ':' . __LINE__);

//        $this->logger->info(print_r($file, 1));
        $this->logger->info('FAL resource UID: ' . $file->getUid());
        $this->logger->info('File name: ' . $file->getName());
        $this->logger->info('Temporary path/file: ' . $file->getForLocalProcessing());
        $this->logger->info('File mime type: ' . $file->getMimeType());
        $this->logger->info('File size: ' . $file->getSize());
    }

    /**
     * Check if uploaded file meets requirements for image proccesing
     *
     * @param \TYPO3\CMS\Core\Resource\FileInterface $file
     * @return void
     */
    public function isValidImage(\TYPO3\CMS\Core\Resource\FileInterface $file)
    {
        $this->logger->info(__METHOD__ . ':' . __LINE__);

        // Get valid image types from extension configuration
        $validMimeTypes = Extension::getExtensionConfigurationValue($this->extensionKey, 'image_types');
        if (empty($validMimeTypes)) {
            // Set default values, if no configuration is set
            $validMimeTypes = $this->defaultValidMimeTypes;
        }

        // Clean configuration and convert comma-separated values into an array
        $validMimeTypes = preg_replace('/[^a-z,]/', '', trim(strtolower($validMimeTypes)));
        $validMimeTypes = explode(',', $validMimeTypes);

        // Split mime type value (e.g. "image/png")
        $fileMimeType = explode('/', $file->getMimeType());

        if (count($fileMimeType) != 2 || $fileMimeType[0] !== 'image' || !in_array($fileMimeType[1], $validMimeTypes)) {
            $this->logger->info('Invalid image type: ' . $fileMimeType[1]);
            return false;
        }

        // Get maximum file size from extension configuration
        $maxFileSize = Extension::getExtensionConfigurationValue($this->extensionKey, 'max_file_size');
        if ($maxFileSize == 0) {
            // Set default values, if no configuration is set or configuration is invalid (e.g. not numeric)
            $maxFileSize = $this->defaultMaxFileSize;
        }

        if ($file->getSize() == 0 || $file->getSize() > $maxFileSize) {
            $this->logger->info('Invalid file size: ' . $file->getSize() . ' bytes');
            return false;
        }

        // Image characteristics meet requirements for image proccesing
        return true;
    }
}
