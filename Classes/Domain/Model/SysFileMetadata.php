<?php
namespace SchamsNet\AwsImageRecognition\Domain\Model;

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

/**
 * Model: SysFileMetadata
 */
class SysFileMetadata extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    /**
     * Object 1
     *
     * @var string
     */
    protected $object1 = null;

    /**
     * Set object 1
     *
     * @param string $object1
     * @return void
     */
    public function setObject1($object1)
    {
        $this->object1 = $object1;
    }

    /**
     * Return object 1
     *
     * @return string $object1
     */
    public function getObject1()
    {
        return $this->object1;
    }
}
