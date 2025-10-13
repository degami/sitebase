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

use Sabre\DAV\Collection;
use App\Site\Models\MediaElement;

class MediaDirectory extends Collection
{
    public function __construct(
        protected MediaElement $element,
    ) { }

    /**
     * {@inheritdoc}
     */
    public function getChildren(): array
    {
        $collection = MediaElement::getCollection();
        $collection->addCondition(['parent_id' => $this->element->getId()]);
        $collection->addSelect('*')->addSelect('IF(mimetype =\'inode/directory\', 1, 0) AS is_dir');
        $collection->addOrder(['is_dir' => 'DESC'], 'start');

        return array_map(
            fn($el) => $el->mimetype === 'inode/directory' ? new MediaDirectory($el): new MediaFile($el), 
            $collection->getItems()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getChild($name)
    {
        $collection = MediaElement::getCollection();
        $collection->addCondition(['parent_id' => $this->element->getId()]);
        $collection->addCondition(['filename' => $name]);

        /** @var MediaElement $el */
        $el = $collection->getFirst();

        if (!$el) {
            throw new \Sabre\DAV\Exception\NotFound('Not found');
        }

        return $el->isDirectory() ? new MediaDirectory($el) : new MediaFile($el);
    }

    /**
     * {@inheritdoc}
     */
    public function createFile($name, $data = null)
    {
        $media = MediaElement::new();

        $media->setFilename(basename($name));
        $media->setPath($this->element->getPath() . DS . $media->getFilename());
        $media->setParentId($this->element->getId());

        if (is_string($data)) {
            file_put_contents($media->getPath(), $data);
        } else {
            file_put_contents($media->getPath(), stream_get_contents($data));
        }

        $userId = $_SESSION['webdav_userid'] ?? null;
        $media->setUserId($userId);

        $media->setMimetype(mime_content_type($media->getPath()));
        $media->setFilesize(filesize($media->getPath()));

        $media->persist();
    }

    /**
     * {@inheritdoc}
     */
    public function createDirectory($name)
    {
        $folder = MediaElement::new();
        $folder->setParentId($this->element->getId());
        $folder->setFilename($name);

        $folder->setPath($this->element->getPath() . DS . $folder->getFilename());

        @mkdir($folder->getPath(), 0755, true);
        
        $userId = $_SESSION['webdav_userid'] ?? null;
        $folder->setUserId($userId);

        $folder->setFilesize(0);
        $folder->setMimetype('inode/directory');

        $folder->persist();
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
        $this->element->name = $name;
        $this->element->persist();
    }

    /**
     * {@inheritdoc}
     */
    public function delete()
    {
        foreach ($this->element->children as $child) {
            $child->delete();
        }
        $this->element->delete();
    }

    /**
     * {@inheritdoc}
     */
    public function getLastModified()
    {
        return (new \DateTimeImmutable("".$this->element->getUpdatedAt()))->format('U');
    }

}
