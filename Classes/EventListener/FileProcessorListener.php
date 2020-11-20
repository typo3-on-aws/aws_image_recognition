<?php
declare(strict_types=1);
namespace Typo3OnAws\AwsImageRecognition\EventListener;

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
 * @link        https://t3rrific.com/typo3-on-aws/
 * @link        https://github.com/typo3-on-aws/aws_image_recognition
 */

use \Typo3OnAws\AwsImageRecognition\Services\AmazonRekognition;
use \TYPO3\CMS\Core\Resource\Event\AfterFileAddedEvent;
use \TYPO3\CMS\Core\Resource\Event\AfterFileReplacedEvent;
use \TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use \TYPO3\CMS\Core\Resource\File;
use \TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Event listener class to process added/replaced files on upload.
 */
class FileProcessorListener
{
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
     * @var AmazonRekognition
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
     * Method is executed when a new file is added
     * See: Configuration/Services.yaml
     *
     * @access public
     * @param AfterFileAddedEvent $event
     */
    public function invokeAfterFileAdded(AfterFileAddedEvent $event): void
    {
        if ($event->getFile() instanceof File) {
            if ($this->isValidImage($event->getFile())) {
                $this->recognition->processImage($event->getFile());
            }
        }
    }

    /**
     * Method is executed when a file is replaced
     * See: Configuration/Services.yaml
     *
     * @access public
     * @param AfterFileReplacedEvent $event
     */
    public function invokeAfterFileReplaced(AfterFileReplacedEvent $event): void
    {
        if ($event->getFile() instanceof File) {
            if ($this->isValidImage($event->getFile())) {
                $this->recognition->processImage($event->getFile());
            }
        }
    }

    /**
     * Check if uploaded file meets requirements for image proccesing
     *
     * @access private
     * @param File $file
     * @return bool
     */
    private function isValidImage(File $file): bool
    {
        // Get valid image types from extension configuration
        $validMimeTypes = GeneralUtility::makeInstance(ExtensionConfiguration::class)
            ->get($this->extensionKey, 'validImageTypes');
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
            ->get($this->extensionKey, 'maxFileSize');
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
