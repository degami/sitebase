<?php

namespace App\Site\Webdav;

use Sabre\DAV\File;
use Sabre\DAV\Server;
use App\Site\Models\MediaElement;

class MediaFile extends File
{
    public function __construct(
        protected MediaElement $element,
    ) { }

    public function get()
    {
        return fopen($this->element->getPath(), 'rb');
    }

    public function put($data)
    {
        if (is_string($data)) {
            file_put_contents($this->element->getPath(), $data);
        } else {
            file_put_contents($this->element->getPath(), stream_get_contents($data));
        }

        $this->element->persist();
    }

    public function delete()
    {
        unlink($this->element->getPath());
        $this->element->remove();
    }

    public function getSize()
    {
        return $this->element->getFilesize();
    }

    public function getLastModified()
    {
        return (new \DateTimeImmutable("".$this->element->getUpdatedAt()))->format('U');
    }

    public function getName() { 
        return $this->element->getFilename(); 
    }

    public function setName($name)
    {
        $this->element->setFilename($name);
        $this->element->persist();
    }

    public function getETag()
    {
        return '"' . md5_file($this->element->getPath()) . '"';
    }

    public function getContentType()
    {
        return $this->element->getMimeType();
    }
}
