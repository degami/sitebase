<?php

namespace App\Site\Webdav;

use Sabre\DAV\Collection;
use App\App;
use App\Site\Models\MediaElement;
use Sabre\DAV\Server;

class MediaRootDirectory extends Collection
{
    public function __construct() { }

    public function getChildren(): array
    {
        $collection = MediaElement::getCollection();
        $collection->addCondition(['parent_id' => null]);
        $collection->addSelect('*')->addSelect('IF(mimetype =\'inode/directory\', 1, 0) AS is_dir');
        $collection->addOrder(['is_dir' => 'DESC'], 'start');

        return array_map(
            fn($el) => $el->mimetype === 'inode/directory' ? new MediaDirectory($el): new MediaFile($el), 
            $collection->getItems()
        );
    }

    public function getChild($name)
    {
        $collection = MediaElement::getCollection();
        $collection->addCondition(['parent_id' => null]);
        $collection->addCondition(['filename' => $name]);

        /** @var MediaElement $el */
        $el = $collection->getFirst();

        if (!$el) {
            throw new \Sabre\DAV\Exception\NotFound('Not found');
        }

        return $el->isDirectory() ? new MediaDirectory($el) : new MediaFile($el);
    }

    public function createFile($name, $data = null)
    {
        /** @var MediaElement $media */
        $media = MediaElement::new();
        $media->setParentId(null);
        $media->setFilename(basename($name));
        $media->setPath(App::getDir(App::MEDIA) . DS . $media->getFileName());

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

    public function createDirectory($name)
    {
        $folder = MediaElement::new();
        $folder->setParentId(null);
        $folder->setFilename($name);
        $folder->setPath(App::getDir(App::MEDIA) . DS . $folder->getFilename());
        $folder->setMimetype('inode/directory');

        $userId = $_SESSION['webdav_userid'] ?? null;
        $folder->setUserId($userId);

        $folder->setUserId($userId);

        @mkdir($folder->getPath(), 0755, true);
        
        $folder->setFilesize(0);
        $folder->persist();
    }

    public function getName() { 
        return '/'; 
    }
}
