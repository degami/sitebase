<?php

/**
 * SiteBase
 * PHP Version 8.3
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis <degami@github.com>
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */

namespace App\Site\Webdav;

use Sabre\DAV\File;
use App\Site\Models\MediaElement;

class MediaFile extends File
{
    public function __construct(
        protected MediaElement $element,
    ) { }

    /**
     * {@inheritdoc}
     */
    public function get()
    {
        return fopen($this->element->getPath(), 'rb');
    }

    /**
     * {@inheritdoc}
     */
    public function put($data)
    {
        if (is_string($data)) {
            file_put_contents($this->element->getPath(), $data);
        } else {
            file_put_contents($this->element->getPath(), stream_get_contents($data));
        }

        $this->element->persist();
    }

    /**
     * {@inheritdoc}
     */
    public function delete()
    {
        unlink($this->element->getPath());
        $this->element->remove();
    }

    /**
     * {@inheritdoc}
     */
    public function getSize()
    {
        return $this->element->getFilesize();
    }

    /**
     * {@inheritdoc}
     */
    public function getLastModified()
    {
        return (new \DateTimeImmutable("".$this->element->getUpdatedAt()))->format('U');
    }

    /**
     * {@inheritdoc}
     */
    public function getName() { 
        return $this->element->getFilename(); 
    }

    /**
     * {@inheritdoc}
     */
    public function setName($name)
    {
        $this->element->setFilename($name);
        $this->element->persist();
    }

    /**
     * {@inheritdoc}
     */
    public function getETag()
    {
        return '"' . md5_file($this->element->getPath()) . '"';
    }

    /**
     * {@inheritdoc}
     */
    public function getContentType()
    {
        return $this->element->getMimeType();
    }
}
