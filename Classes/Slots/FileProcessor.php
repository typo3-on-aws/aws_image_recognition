<?php
declare(strict_types=1);
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

use \TYPO3\CMS\Core\Resource\FileInterface;
use \TYPO3\CMS\Core\Utility\GeneralUtility;
use \SchamsNet\AwsImageRecognition\Services\AmazonRekognition;
use \TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

/**
 * Slot implementation when a file is uploaded/replaced but before it is processed
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
     * @var \SchamsNet\AwsImageRecognition\Services\AmazonRecognition
     */
    private $recognition;

    /**
     * Constructor
     */
    public function __construct()
    {
        /** @var AmazonRekognition $recognition */
        $this->recognition = GeneralUtility::makeInstance(AmazonRekognition::class);
    }

    /**
     * Process file when uploaded by BE user
     *
     * @access public
     * @param \TYPO3\CMS\Core\Resource\FileInterface $file
     * @param \TYPO3\CMS\Core\Resource\Folder $folder
     */
    public function processFile(FileInterface $file, $folder = null): void
    {
        if ($this->isValidImage($file)) {
            $this->recognition->processImage($file);
        }
    }

    /**
     * Process file when replaced by BE user
     *
     * @access public
     * @param \TYPO3\CMS\Core\Resource\FileInterface $file
     * @param string $temporaryFile
     */
    public function processReplaceFile(FileInterface $file, $temporaryFile = null): void
    {
        $this->processFile($file, null);
    }

    /**
     * Check if uploaded file meets requirements for image proccesing
     *
     * @access private
     * @param FileInterface $file
     * @return bool
     */
    private function isValidImage(FileInterface $file): bool
    {
        // Get valid image types from extension configuration
        $validMimeTypes = GeneralUtility::makeInstance(ExtensionConfiguration::class)
            ->get($this->extensionKey, 'image_types');
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
            // Invalid image type
            return false;
        }

        // Get maximum file size from extension configuration
        $maxFileSize = GeneralUtility::makeInstance(ExtensionConfiguration::class)
            ->get($this->extensionKey, 'max_file_size');
        if ($maxFileSize == 0) {
            // Set default values, if no configuration is set or configuration is invalid (e.g. not numeric)
            $maxFileSize = $this->defaultMaxFileSize;
        }

        if ($file->getSize() == 0 || $file->getSize() > $maxFileSize) {
            // Invalid file size
            return false;
        }

        // Image characteristics meet requirements for image proccesing
        return true;
    }
}
