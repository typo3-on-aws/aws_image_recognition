<?php
namespace SchamsNet\AwsImageRecognition\Services;

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

use \TYPO3\CMS\Core\Database\ConnectionPool;
use \TYPO3\CMS\Core\Log\LogManager;
use \TYPO3\CMS\Core\Utility\GeneralUtility;
use \TYPO3\CMS\Extbase\Object\ObjectManager;
use \SchamsNet\AwsImageRecognition\Domain\Repository\SysFileMetadataRepository;
use \SchamsNet\AwsImageRecognition\Utilities\Extension;

// use Aws\S3\S3Client;
// use Aws\S3\StreamWrapper;

/**
 * Amazon Rekognition Service Class
 */
class AmazonRekognition
{
    /**
     * TYPO3 extension key
     *
     * @access public
     * @var string
     */
    public $extensionKey = 'aws_image_recognition';

    /**
     * AWS options
     *
     * @access private
     * @var array
     */
    private $options = [];

    /**
     * AWS access credentials (access key and secret)
     *
     * @access private
     * @var string
     */
    private $credentials = null;

    /**
     * @access private
     * @var \Aws\Rekognition\RekognitionClient
     */
    private $client;

    /**
     * @access private
     * @var \TYPO3\CMS\Core\Resource\FileInterface
     */
    private $file;

    /**
     * Database connection
     *
     * @access private
     * @var \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    private $database = null;

    /**
     * Database table "sys_file_metadata"
     *
     * @access private
     * @var string
     */
    private $table = 'sys_file_metadata';

    /**
     * Prospects Repository
     *
     * @access protected
     * @var \Schams\AwsImageRecognition\Domain\Repository\SysFileMetadataRepository
     */
    protected $sysFileMetadataRepository = null;

    /**
     * @access private
     * @var \TYPO3\CMS\Core\Log\LogManager
     */
    private $logger;

    /**
     * Constructor
     *
     * @access public
     */
    public function __construct()
    {
        /** @var Logger $logger */
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->sysFileMetadataRepository = $objectManager->get(SysFileMetadataRepository::class);

        $this->database = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->table);
    }

    /**
     * Process image
     *
     * @access public
     * @param \TYPO3\CMS\Core\Resource\FileInterface $file
     */
    public function processImage($file): void
    {
        // http://docs.aws.amazon.com/aws-sdk-php/v3/api/api-rekognition-2016-06-27.html#recognizecelebrities
        $this->logger->info(__METHOD__ . ':' . __LINE__);

        // ...
        $this->file = $file;

        // ...
        $this->options = $this->initializeOptions();

        /** @var \Aws\Rekognition\RekognitionClient $client */
        $this->client = GeneralUtility::makeInstance('Aws\\Rekognition\\RekognitionClient', $this->options);

        $mapping = [
            'enable_detect_objects' => 'detectObjects',
            'enable_detect_faces' => 'detectFaces',
            'enable_recognize_celebrities' => 'recognizeCelebrities'
        ];

        foreach ($mapping as $enabled => $function) {
            if (Extension::getExtensionConfigurationValue($this->extensionKey, $enabled)) {
                $this->$function();
            }
        }
    }

    /**
     * Detect objects
     *
     * Detects instances of real-world labels within an image (JPEG or PNG) provided as input.
     * This includes objects like flower, tree, and table; events like wedding, graduation, and
     * birthday party; and concepts like landscape, evening, and nature.
     *
     * @access private
     * @throws RekognitionException
     */
    public function detectObjects(): void
    {
        $this->logger->info(__METHOD__ . ':' . __LINE__);
        try {
            $result = $this->client->DetectLabels([
                'Image' => [
                    'Bytes' => $this->loadImage()
                ],
                'MaxLabels' => 12,
                'MinConfidence' => 40
            ]);

            if (is_object($result)) {
                if (isset($result['Labels'])) {
                    $data = [];
                    foreach ($result['Labels'] as $key => $object) {
                        $data['object' . ($key + 1)] = $object['Name'] . ' (' . floor($object['Confidence']) . '%)';
                    }
                    if (count($data) > 0) {
                        $this->database->update(
                            $this->table,
                            $data,
                            ['uid' => (int)$this->file->getUid()],
                            [Connection::PARAM_INT]
                        );
                    }
                }
            }
        } catch (RekognitionException $e) {
        }
    }

    /**
     * Detect faces in images
     *
     * For each face detected, the operation returns face details including a bounding box of the
     * face, a confidence value (that the bounding box contains a face), and a fixed set of
     * attributes such as facial landmarks (for example, coordinates of eye and mouth), gender,
     * presence of beard, sunglasses, etc.
     *
     * The face-detection algorithm is most effective on frontal faces. For non-frontal or obscured
     * faces, the algorithm may not detect the faces or might detect faces with lower confidence.
     *
     * @access private
     * @throws RekognitionException
     */
    public function detectFaces(): void
    {
        $this->logger->info(__METHOD__ . ':' . __LINE__);
        try {
            $result = $this->client->DetectFaces([
                'Image' => [
                    'Bytes' => $this->loadImage()
                ]
            ]);

            if (is_object($result)) {
                if (isset($result['FaceDetails'])) {
                    // @TODO not implemented yet
                }
            }
        } catch (RekognitionException $e) {
        }
    }

    /**
     * Recognize celebrities
     *
     * For each celebrity recognized, the API returns a Celebrity object. The Celebrity object
     * contains the celebrity name, ID, URL links to additional information, match confidence, and
     * a ComparedFace object that you can use to locate the celebrity's face on the image.
     *
     * @access private
     * @throws RekognitionException
     */
    public function recognizeCelebrities(): void
    {
        $this->logger->info(__METHOD__ . ':' . __LINE__);
        try {
            $result = $this->client->recognizeCelebrities([
                'Image' => [
                    'Bytes' => $this->loadImage()
                ]
            ]);

            if (is_object($result)) {
                if (isset($result['CelebrityFaces'][0])) {
                    $object = $result['CelebrityFaces'][0];
                    $this->database->update(
                        $this->table,
                        [
                            'celebrity_id' => $object['Id'],
                            'celebrity_name' => $object['Name'],
                            'celebrity_match_confidence' => $object['MatchConfidence']
                        ],
                        [Connection::PARAM_INT]
                    );
                }
            }
        } catch (RekognitionException $e) {
        }
    }

    /**
     * Initialize AWS options
     *
     * @access public
     * @return array
     */
    public function initializeOptions(): array
    {
        // configuration options
        // @see http://docs.aws.amazon.com/aws-sdk-php/v3/guide/guide/configuration.html
        return [
            'region' => Extension::getExtensionConfigurationValue($this->extensionKey, 'aws_region'),
            'version' => 'latest',
            'credentials' => [
                'key' => Extension::getExtensionConfigurationValue($this->extensionKey, 'access_key'),
                'secret' => Extension::getExtensionConfigurationValue($this->extensionKey, 'access_secret')
            ],
//          'http' => [
//              'connect_timeout' => 5,
//              'timeout' => 5,
//              'proxy' => [
//                  'http' => 'tcp://192.168.16.1:10',
//                  'https' => 'tcp://192.168.16.1:11',
//              ]
//          ]
            //'debug' => true
        ];
    }

    /**
     * Returns binary-safe file content
     *
     * @access private
     * @return string Binary-safe file content
     */
    private function loadImage(): string
    {
        $this->logger->info(__METHOD__ . ':' . __LINE__);

        $file = $this->file->getForLocalProcessing();
        if (is_readable($file)) {
            $stream = @fopen($file, 'r');

            return @fread($stream, filesize($file));
        }
        return null;
    }
}
