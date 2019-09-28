<?php
declare(strict_types=1);
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

use \Aws\Rekognition\RekognitionClient;
use \SchamsNet\AwsImageRecognition\Domain\Repository\SysFileMetadataRepository;
use \TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use \TYPO3\CMS\Core\Database\Connection;
use \TYPO3\CMS\Core\Database\ConnectionPool;
use \TYPO3\CMS\Core\Database\DatabaseConnection;
use \TYPO3\CMS\Core\Resource\FileInterface;
use \TYPO3\CMS\Core\Utility\GeneralUtility;
use \TYPO3\CMS\Extbase\Object\ObjectManager;

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
     * @var RekognitionClient
     */
    private $client;

    /**
     * @access private
     * @var FileInterface
     */
    private $file;

    /**
     * Database connection
     *
     * @access private
     * @var DatabaseConnection
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
     * @var SysFileMetadataRepository
     */
    protected $sysFileMetadataRepository = null;

    /**
     * Constructor
     *
     * @access public
     */
    public function __construct()
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->sysFileMetadataRepository = $objectManager->get(SysFileMetadataRepository::class);

        $this->database = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->table);
    }

    /**
     * Process image
     *
     * @access public
     * @param FileInterface $file
     */
    public function processImage(FileInterface $file): void
    {
        // ...
        $this->file = $file;

        // ...
        $this->options = $this->initializeOptions();

        /** @var RekognitionClient $client */
        $this->client = GeneralUtility::makeInstance(RekognitionClient::class, $this->options);

        $mapping = [
            'detectObjectsEnabled' => 'detectObjects',
            'detectFacesEnabled' => 'detectFaces',
            'recognizeCelebritiesEnabled' => 'recognizeCelebrities'
        ];

        foreach ($mapping as $enabled => $function) {
            if (GeneralUtility::makeInstance(ExtensionConfiguration::class)
                ->get($this->extensionKey, $enabled)) {
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
                    $keywords = [];
                    foreach ($result['Labels'] as $key => $object) {
                        $keywords[] = $object['Name'];
                    }
                    if (count($keywords) > 0) {
                        $data['keywords'] = implode(", ", $keywords);

                        $this->database->update(
                            $this->table,
                            $data,
                            ['uid' => (int)$this->file->getUid()],
                            [Connection::PARAM_STR]
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
        try {
            $result = $this->client->DetectFaces([
                'Image' => [
                    'Bytes' => $this->loadImage()
                ],
                'Attributes' => [
                    'ALL'
                ]
            ]);
            $featuresBlacklist = ["Landmarks", "Emotions", "Pose", "Quality", "BoundingBox", "Confidence"];
            $minConfidence = 40;

            if (is_object($result)) {
                if (isset($result['FaceDetails'])) {
                    $description = [];

                    foreach ($result['FaceDetails'][0] as $key => $details) {
                        if (!in_array($key, $featuresBlacklist)) {
                            if ($details['Value'] == 1 && $details['Confidence'] > $minConfidence) {
                                $description[] = $key;
                            }
                        }
                        if ($key == 'EMOTIONS') {
                            foreach ($details as $keyEmotion => $emotion) {
                                $description[] = $keyEmotion;
                            }
                        }
                    }

                    if (count($description) > 0) {
                        $data['description'] = implode(", ", $description);
                        $this->database->update(
                            $this->table,
                            $data,
                            ['uid' => (int)$this->file->getUid()],
                            [Connection::PARAM_STR]
                        );
                    }
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
                            'title' => $object['Name'],
                        ],
                        ['uid' => (int)$this->file->getUid()],
                        [Connection::PARAM_STR]
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
            'region' => GeneralUtility::makeInstance(ExtensionConfiguration::class)
                ->get($this->extensionKey, 'awsRegion'),
            'version' => 'latest',
            'credentials' => [
                'key' => GeneralUtility::makeInstance(ExtensionConfiguration::class)
                    ->get($this->extensionKey, 'awsAccessKeyId'),
                'secret' => GeneralUtility::makeInstance(ExtensionConfiguration::class)
                    ->get($this->extensionKey, 'awsAccessSecretKey')
            ],
//          'http' => [
//              'connect_timeout' => 5,
//              'timeout' => 5,
//              'proxy' => [
//                  'http' => 'tcp://192.168.16.1:10',
//                  'https' => 'tcp://192.168.16.1:11',
//              ]
//          ]
//          'debug' => true
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
        $file = $this->file->getForLocalProcessing();
        if (is_readable($file)) {
            $stream = @fopen($file, 'r');

            return @fread($stream, filesize($file));
        }
        return null;
    }
}
