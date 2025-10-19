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

namespace App\Site\Models;

use App\Base\Abstracts\Models\BaseModel;
use App\Base\Traits\WithOwnerTrait;
use App\App;
use App\Base\Abstracts\Models\BaseCollection;
use App\Base\Exceptions\InvalidValueException;
use DateTime;
use Degami\Basics\Exceptions\BasicException;
use Exception;
use App\Base\Exceptions\PermissionDeniedException;
use Degami\Basics\Html\TagElement;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use App\Base\GraphQl\GraphQLExport;
use App\Base\Traits\WithChildrenTrait;
use App\Base\Exceptions\NotFoundException;
use Degami\SqlSchema\Exceptions\DuplicateException;
use RuntimeException;

/**
 * Media Element Model
 *
 * @method int getId()
 * @method string getPath()
 * @method string getFilename()
 * @method string getMimetype()
 * @method int getFilesize()
 * @method int getUserId()
 * @method bool getLazyload()
 * @method int getParentId()
 * @method DateTime getCreatedAt()
 * @method DateTime getUpdatedAt()
 * @method string getThumbUrl__200x200()
 * @method string getThumbUrl__300x200()
 * @method string getThumbUrl__640x480()
 * @method string getThumbUrl__800x600()
 * @method self setId(int $id)
 * @method self setPath(string $path)
 * @method self setFilename(string $filename)
 * @method self setMimetype(string $mimetype)
 * @method self setFilesize(int $filesize)
 * @method self setUserId(int $user_id)
 * @method self setLazyload(bool $lazyload)
 * @method self setParentId(int $parent_id)
 * @method self setCreatedAt(DateTime $created_at)
 * @method self setUpdatedAt(DateTime $updated_at)
 */
#[GraphQLExport]
class MediaElement extends BaseModel
{
    use WithOwnerTrait, WithChildrenTrait;

    public const TRANSPARENT_PIXEL = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    public const ORIGINAL_SIZE = 'originals';

    public const MIMETYPE_INODE_DIRECTORY = 'inode/directory';

    public const MIMETYPE_TEXT_PLAIN = 'text/plain';
    public const MIMETYPE_TEXT_HTML = 'text/html';
    public const MIMETYPE_TEXT_CSS = 'text/css';
    public const MIMETYPE_TEXT_CSV = 'text/csv';
    public const MIMETYPE_TEXT_RICHTEXT = 'text/richtext';
    public const MIMETYPE_TEXT_XML = 'text/xml';
    public const MIMETYPE_TEXT_MARKDOWN = 'text/markdown';
    public const MIMETYPE_TEXT_VCARD = 'text/vcard';
    public const MIMETYPE_TEXT_VTT = 'text/vtt';

    public const MIMETYPE_APPLICATION_JAVASCRIPT = 'application/javascript';
    public const MIMETYPE_APPLICATION_JSON = 'application/json';
    public const MIMETYPE_APPLICATION_XML = 'application/xml';
    public const MIMETYPE_APPLICATION_PDF = 'application/pdf';
    public const MIMETYPE_APPLICATION_ZIP = 'application/zip';
    public const MIMETYPE_APPLICATION_GZIP = 'application/gzip';
    public const MIMETYPE_APPLICATION_TAR = 'application/x-tar';
    public const MIMETYPE_APPLICATION_BZIP2 = 'application/x-bzip2';
    public const MIMETYPE_APPLICATION_MSWORD = 'application/msword';
    public const MIMETYPE_APPLICATION_VND_OPENXML_WORD = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
    public const MIMETYPE_APPLICATION_VND_MS_EXCEL = 'application/vnd.ms-excel';
    public const MIMETYPE_APPLICATION_VND_OPENXML_EXCEL = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    public const MIMETYPE_APPLICATION_VND_MS_POWERPOINT = 'application/vnd.ms-powerpoint';
    public const MIMETYPE_APPLICATION_VND_OPENXML_POWERPOINT = 'application/vnd.openxmlformats-officedocument.presentationml.presentation';
    public const MIMETYPE_APPLICATION_RTF = 'application/rtf';
    public const MIMETYPE_APPLICATION_SQL = 'application/sql';
    public const MIMETYPE_APPLICATION_YAML = 'application/x-yaml';
    public const MIMETYPE_APPLICATION_XHTML = 'application/xhtml+xml';
    public const MIMETYPE_APPLICATION_OGG = 'application/ogg';
    public const MIMETYPE_APPLICATION_MP4 = 'application/mp4';
    public const MIMETYPE_APPLICATION_OCTET_STREAM = 'application/octet-stream';
    public const MIMETYPE_APPLICATION_JAVA_ARCHIVE = 'application/java-archive';
    public const MIMETYPE_APPLICATION_PHP = 'application/x-httpd-php';
    public const MIMETYPE_APPLICATION_FONT_WOFF = 'application/font-woff';
    public const MIMETYPE_APPLICATION_FONT_WOFF2 = 'font/woff2';
    public const MIMETYPE_APPLICATION_FONT_TTF = 'font/ttf';
    public const MIMETYPE_APPLICATION_FONT_OTF = 'font/otf';

    public const MIMETYPE_IMAGE_JPEG = 'image/jpeg';
    public const MIMETYPE_IMAGE_PNG = 'image/png';
    public const MIMETYPE_IMAGE_GIF = 'image/gif';
    public const MIMETYPE_IMAGE_SVG = 'image/svg+xml';
    public const MIMETYPE_IMAGE_WEBP = 'image/webp';
    public const MIMETYPE_IMAGE_BMP = 'image/bmp';
    public const MIMETYPE_IMAGE_TIFF = 'image/tiff';
    public const MIMETYPE_IMAGE_X_ICON = 'image/x-icon';
    public const MIMETYPE_IMAGE_HEIC = 'image/heic';
    public const MIMETYPE_IMAGE_AVIF = 'image/avif';

    public const MIMETYPE_AUDIO_MPEG = 'audio/mpeg';
    public const MIMETYPE_AUDIO_MP4 = 'audio/mp4';
    public const MIMETYPE_AUDIO_OGG = 'audio/ogg';
    public const MIMETYPE_AUDIO_WEBM = 'audio/webm';
    public const MIMETYPE_AUDIO_WAV = 'audio/wav';
    public const MIMETYPE_AUDIO_AAC = 'audio/aac';
    public const MIMETYPE_AUDIO_FLAC = 'audio/flac';
    public const MIMETYPE_AUDIO_MID = 'audio/midi';
    public const MIMETYPE_AUDIO_X_MATROSKA = 'audio/x-matroska';

    public const MIMETYPE_VIDEO_MP4 = 'video/mp4';
    public const MIMETYPE_VIDEO_OGG = 'video/ogg';
    public const MIMETYPE_VIDEO_WEBM = 'video/webm';
    public const MIMETYPE_VIDEO_X_FLV = 'video/x-flv';
    public const MIMETYPE_VIDEO_X_MSVIDEO = 'video/x-msvideo';
    public const MIMETYPE_VIDEO_X_MATROSKA = 'video/x-matroska';
    public const MIMETYPE_VIDEO_QUICKTIME = 'video/quicktime';

    public const MIMETYPE_MULTIPART_FORMDATA = 'multipart/form-data';
    public const MIMETYPE_MULTIPART_MIXED = 'multipart/mixed';
    public const MIMETYPE_MULTIPART_ALTERNATIVE = 'multipart/alternative';
    public const MIMETYPE_MULTIPART_RELATED = 'multipart/related';

    public const MIMETYPE_MESSAGE_RFC822 = 'message/rfc822';

    public const MIMETYPE_APPLICATION_X_SHOCKWAVE_FLASH = 'application/x-shockwave-flash';
    public const MIMETYPE_APPLICATION_X_MS_DOWNLOAD = 'application/x-msdownload';
    public const MIMETYPE_APPLICATION_X_DEB = 'application/vnd.debian.binary-package';
    public const MIMETYPE_APPLICATION_X_RAR = 'application/vnd.rar';
    public const MIMETYPE_APPLICATION_X_7Z = 'application/x-7z-compressed';
    public const MIMETYPE_APPLICATION_X_APPIMAGE = 'application/x-appimage';

    public const MIMETYPE_CHEMICAL_X_PDB = 'chemical/x-pdb';
    public const MIMETYPE_CHEMICAL_X_CIF = 'chemical/x-cif';

    public const MIMETYPE_MODEL_STL = 'model/stl';
    public const MIMETYPE_MODEL_OBJ = 'model/obj';
    public const MIMETYPE_MODEL_GLB = 'model/gltf-binary';
    public const MIMETYPE_MODEL_GLTF = 'model/gltf+json';


    /**
     * {@inheritdoc}
     */
    public static function canBeDuplicated() : bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function canSaveVersions() : bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public static function isExportable() : bool
    {
        return true;
    }

    /**
     * gets relative path
     *
     * @return string
     * @throws Exception
     */
    public function getRelativePath(): string
    {
        $this->checkLoaded();

        return preg_replace("#^".App::getDir(App::MEDIA)."#", "", $this->getPath());
    }

    /**
     * gets thumbnail img html tag
     *
     * @param string $size
     * @param string|null $mode
     * @param string|null $class
     * @param array $img_attributes
     * @return string
     * @throws PermissionDeniedException
     * @throws BasicException
     */
    public function getThumb(string $size, ?string $mode = null, ?string $class = null, array $img_attributes = []): string
    {
        $this->checkLoaded();

        if (!$this->isImage()) {
            throw new BasicException('Not an image');
        }

        $w = $h = null;
        if (preg_match("/^([0-9]+)x([0-9]+)$/i", $size, $thumb_sizes)) {
            $w = $thumb_sizes[1];
            $h = $thumb_sizes[2];
        }

        if (boolval($this->getLazyload()) && !isset($img_attributes['for_admin'])) {
            $img_attributes['data-src'] = $this->getThumbUrl($size, $mode);
        }

        return App::getInstance()->containerMake(TagElement::class, ['options' => [
            'tag' => 'img',
            'attributes' => [
                    'src' => boolval($this->getLazyload()) && !isset($img_attributes['for_admin']) ? static::TRANSPARENT_PIXEL : $this->getThumbUrl($size, $mode),
                    'class' => $class,
                    'style' => preg_match('/img-fluid/i', (string) $class) ? '' : "max-width:{$w}px;max-height:{$h}px;",
                    'border' => 0,
                ] + $img_attributes,
        ]])->renderTag();
    }

    /**
     * gets thumbnail url
     *
     * @param string $size
     * @param string|null $mode
     * @return string
     * @throws BasicException
     * @throws PermissionDeniedException
     * @throws Exception
     */
    public function getThumbUrl(string $size, ?string $mode = null): string
    {
        $this->checkLoaded();

        if (!$this->isImage()) {
            throw new BasicException('Not an image');
        }

        $thumb_sizes = null;
        if ($size != self::ORIGINAL_SIZE) {
            if (!preg_match("/^([0-9]+)x([0-9]+)$/i", $size, $thumb_sizes)) {
                return $this->getPath();
            }
        }

        if (is_dir($this->getPath())) {
            $this->setPath(rtrim($this->getPath(), DS) . DS . $this->getFilename());
        }

        $thumb_path = App::getDir(App::WEBROOT) . DS . 'thumbs' . DS . $size . DS . $this->getRelativePath();
        if (!preg_match("/^image\/(.*?)/", $this->getMimetype())) {
            $thumb_path .= '.svg';
        }
        if (!file_exists($thumb_path)) {
            if (!is_dir(dirname($thumb_path))) {
                if (!mkdir(dirname($thumb_path), 0755, true)) {
                    throw new PermissionDeniedException("Errors creating directory structure for thumbnail", 1);
                }
            }

            try {
                if ($this->getMimetype() == self::MIMETYPE_IMAGE_SVG) {
                    // copy file to destination, does not need resampling
                    if (!copy($this->getPath(), $thumb_path)) {
                        throw new Exception("Errors copying file " . $this->getPath() . " into " . $thumb_path);
                    }
                } else {
                    if ($this->isImage() && ($image = App::getInstance()->getImagine()->open($this->getPath()))) {
                        if ($thumb_sizes) {
                            $w = $thumb_sizes[1];
                            $h = $thumb_sizes[2];
                        } else {
                            $sizes = $image->getSize();
                            $w = $sizes->getWidth();
                            $h = $sizes->getHeight();
                        }

                        $size = new Box($w, $h);

                        if (!in_array($mode, [ImageInterface::THUMBNAIL_INSET, ImageInterface::THUMBNAIL_OUTBOUND])) {
                            $mode = ImageInterface::THUMBNAIL_INSET;
                            // or
                            // $mode    = Imagine\Image\ImageInterface::THUMBNAIL_OUTBOUND;
                        }

                        App::getInstance()->getImagine()
                            ->open($this->path)
                            ->thumbnail($size, $mode)
                            ->save($thumb_path);
                    } else {
                        // @todo thumb in base a mimetype
                        $type = explode('/', $this->getMimetype());
                        if (is_array($type)) {
                            return App::getInstance()->getHtmlRenderer()->getFAIcon('file-'. array_pop($type), 'regular');
                        } else {
                            return App::getInstance()->getHtmlRenderer()->getIcon('file');
                        }
                    }
                }
            } catch (Exception $e) {
                echo $e->getMessage();
            }
        }

        return App::getInstance()->getAssets()->assetUrl(str_replace(App::getDir(App::WEBROOT), "", $thumb_path));
    }

    /**
     * deletes mediaElement thumbnails
     * 
     * @return void
     */
    public function clearThumbs() : void
    {
        $this->checkLoaded();

        if (!$this->isImage()) {
            return;
        }

        $thumb_path = App::getDir(App::WEBROOT) . DS . 'thumbs';
        if ($dir = opendir($thumb_path)) {

            while($dirent = readdir($dir)) {
                if ($dirent == '.' || $dirent == '..') {
                    continue;
                }
                if (is_dir($thumb_path . DS . $dirent)) {
                    $thumbFilePath = $thumb_path . DS . $dirent . DS . ltrim($this->getRelativePath(), DS);
                    if (is_file($thumbFilePath)) {
                        @unlink($thumbFilePath);
                    }
                }
            }

            closedir($dir);
        }
    }

    /**
     * gets Image Box
     */
    public function getImageBox() : ?Box
    {
        $this->checkLoaded();

        if (empty($this->getPath())) {
            return null;
        }

        if (!$this->isImage()) {
            return null;
        }

        $image = App::getInstance()->getImagine()->open($this->getPath());
        $sizes = $image->getSize();
        $w = $sizes->getWidth();
        $h = $sizes->getHeight();

        return new Box($w, $h);
    }

    /**
     * gets original image img tag
     *
     * @param string $class
     * @return string
     * @throws PermissionDeniedException
     * @throws BasicException
     */
    #[GraphQLExport]
    public function getImage(string $class = 'img-fluid'): string
    {
        $this->checkLoaded();

        if (!$this->isImage()) {
            throw new BasicException('Not an image');
        }

        return $this->getThumb(self::ORIGINAL_SIZE, null, $class);
    }

    /**
     * gets original image url
     *
     * @return string
     * @throws PermissionDeniedException
     * @throws BasicException
     */
    #[GraphQLExport]
    public function getImageUrl(): string
    {
        $this->checkLoaded();

        if (!$this->isImage()) {
            throw new BasicException('Not an image');
        }

        return $this->getThumbUrl(self::ORIGINAL_SIZE);
    }

    /**
     * Checks if the current mimetype matches the given pattern.
     * Accepts either a full mimetype string (e.g. "image/png")
     * or a partial regex (e.g. "^image/").
     *
     * @param string $pattern MIME type or regular expression fragment
     * @return bool
     */
    public function isMimetype(string $regexp) : bool
    {
        $this->checkLoaded();

        return boolval(preg_match("/" . str_replace("/", "\\/", trim(str_replace('\/', '/', $regexp), '/')) . "/", (string) $this->getMimetype()));
    }

    /**
     * check if media is an image
     * 
     * @return bool
     */
    public function isImage() : bool
    {
        return $this->isMimetype("/^image\/.*?/");
    }

    /**
     * check if media is an audio
     * 
     * @return bool
     */
    public function isAudio() : bool
    {
        return $this->isMimetype("/^audio\/.*?/");
    }

    /**
     * check if media is a video
     * 
     * @return bool
     */
    public function isVideo() : bool
    {
        return $this->isMimetype("/^video\/.*?/");
    }

    /**
     * check if media is a directory
     * 
     * @return bool
     */
    public function isDirectory() : bool
    {
        return $this->isMimetype(self::MIMETYPE_INODE_DIRECTORY);
    }

    /**
     * gets the icon for the media element
     *
     * @param string $theme
     * @return string
     */
    #[GraphQLExport]
    public function getMimeIcon($theme = 'regular') : string
    {
        $type = explode('/', $this->getMimetype());
        if (is_array($type)) {
            $type = 'file-' . array_pop($type);

            if ($this->isDirectory()) {
                $type = 'folder';
            }

            return App::getInstance()->getHtmlRenderer()->getFAIcon($type, $theme);
        }
        
        return App::getInstance()->getHtmlRenderer()->getIcon('file');
    }

    /**
     * {@inheritdoc}
     */    
    public function preRemove() : BaseModel
    {
        if ($this->isDirectory()) {

            // try removing directory also under thumbs directory
            $thumb_path = App::getDir(App::WEBROOT) . DS . 'thumbs';
            if ($dir = opendir($thumb_path)) {
                while($dirent = readdir($dir)) {
                    if ($dirent == '.' || $dirent == '..') {
                        continue;
                    }
                    if (is_dir($thumb_path . DS . $dirent)) {
                        if (is_dir($thumb_path . DS . $dirent . DS . $this->getRelativePath())) {
                            @rmdir($thumb_path . DS . $dirent . DS . $this->getRelativePath());
                        }
                    }
                }
    
                closedir($dir);
            }

            @rmdir($this->getPath());
        } else {
            if ($this->isImage()) {
                $this->clearThumbs();
            }

            @unlink($this->getPath());
        }

        return $this;
    }

    /**
     * ensure directory / file is existing
     * 
     * @return self
     */
    public function ensureConsistency() : self
    {
        $this->checkLoaded();

        if ($this->isDirectory()) {
            @mkdir($this->getPath(), 0777, true);
        } else {
            @mkdir(dirname($this->getPath()), 0777, true);
            @touch($this->getPath());
        }

        return $this;
    }

    /**
     * return file / directory stream
     * 
     * @return resource|false
     */
    public function getStream() : mixed
    {
        $this->ensureConsistency();

        if ($this->isDirectory()) {
            return opendir($this->getPath());
        }

        return fopen($this->getPath(), 'rb');
    }

    /**
     * returns a collection of MEdiaElements contained into the current subtree
     * 
     * @return BaseCollection|null
     */
    public function getSubTree() : ?BaseCollection
    {
        $this->checkLoaded();

        if (!$this->isDirectory()) {
            return null;
        }

        $collection = static::getCollection();
        $collection->orWhere(['`path` LIKE ?' => rtrim($this->getPath(), DS) . DS . '%', 'parent_id' => $this->getId()]);
        return $collection;
    }

    /**
     * checks if element is plain text file
     * 
     * @return bool
     */
    public function isPlainText() : bool
    {
        return !$this->isDirectory() && $this->isMimetype(self::MIMETYPE_TEXT_PLAIN);
    }

    /**
     * checks if element is csv file
     * 
     * @return bool
     */
    public function isCsv() : bool
    {
        return !$this->isDirectory() && $this->isMimetype(self::MIMETYPE_TEXT_CSV);
    }

    /**
     * checks if element is and archive file
     * 
     * @return bool
     */
    public function isArchive() : bool
    {
        $archiveTypes = [
            self::MIMETYPE_APPLICATION_BZIP2,
            self::MIMETYPE_APPLICATION_GZIP,
            self::MIMETYPE_APPLICATION_TAR,
            self::MIMETYPE_APPLICATION_X_7Z,
            self::MIMETYPE_APPLICATION_X_RAR,
            self::MIMETYPE_APPLICATION_ZIP,
        ];
        return $this->isMimetype("(".implode("|", $archiveTypes).")");
    }

    public function copy(string $destination): MediaElement
    {
        $this->checkLoaded();

        $destination = rtrim($destination, DS);
        $mediaRoot = App::getDir(App::MEDIA);

        if ($destination === $mediaRoot) {
            $destinationObj = null;
            $parentPath = $mediaRoot;
            $destinationFilename = $this->getFilename();
        } else {
            try {
                $destinationObj = static::loadBy('path', $destination);

                if (!$destinationObj->isDirectory()) {
                    throw new DuplicateException(App::getInstance()->getUtils()->translate("%s is already existing into destination path", [$destination]));
                }

                $parentPath = $destinationObj->getPath();
                $destinationFilename = $this->getFilename();
            } catch (NotFoundException $e) {
                $parentDir = dirname($destination);

                if ($parentDir === $mediaRoot) {
                    $destinationObj = null;
                } else {
                    try {
                        $destinationObj = MediaElement::loadByPath($parentDir);
                    } catch (NotFoundException $e2) {
                        throw new NotFoundException(App::getInstance()->getUtils()->translate("Destination folder %s is not existing", [$parentDir]));
                    }
                }

                if ($destinationObj && !$destinationObj->isDirectory()) {
                    throw new InvalidValueException(App::getInstance()->getUtils()->translate("%s is not a folder", [$parentDir]));
                }

                $parentPath = $destinationObj ? $destinationObj->getPath() : $mediaRoot;
                $destinationFilename = basename($destination);
            }
        }

        $existing = null;
        try {
            $existing = static::loadBy('path', $parentPath . DS . $destinationFilename);
        } catch (Exception $e) {
            // if not existing, is fine
        }

        if ($existing) {
            throw new DuplicateException(App::getInstance()->getUtils()->translate("%s is already existing into destination path", [$destinationFilename]));
        }

        $media = new static();
        $media->setUserId(App::getInstance()->getAuth()->getCurrentUser()?->getId());
        $media->setParentId($destinationObj?->getId() ?? null);
        $media->setPath($parentPath . DS . $destinationFilename);
        $media->setFilename($destinationFilename);
        $media->setMimetype($this->getMimetype());
        $media->setFilesize($this->getFilesize());
        $media->setLazyload($this->getLazyload());
        $media->persist();

        if ($this->isDirectory()) {
            if (!@mkdir($media->getPath(), 0777, true)) {
                throw new RuntimeException(App::getInstance()->getUtils()->translate("Error creating directory %s into %s", [$destinationFilename, $$parentPath]));
            }
            foreach ($this->getChildren() as $child) {
                /** @var MediaElement $child */
                $child->copy($media->getPath() . DS . $child->getFilename());
            }
        } else {
            if (!@copy($this->getPath(), $media->getPath())) {
                throw new RuntimeException(App::getInstance()->getUtils()->translate("Error copying %s into %s", [$this->getPath(), $media->getPath()]));
            }
        }

        $media->ensureConsistency();
        return $media;
    }

    public function move(string $destination): self
    {
        $this->checkLoaded();

        $destination = rtrim($destination, DS);
        $mediaRoot = App::getDir(App::MEDIA);

        if ($this->isImage()) {
            $this->clearThumbs();
        }

        if ($destination === $mediaRoot) {
            $destinationObj = null;
            $parentPath = $mediaRoot;
            $destinationFilename = $this->getFilename();
        } else {
            try {
                $destinationObj = static::loadBy('path', $destination);

                if (!$destinationObj->isDirectory()) {
                    throw new DuplicateException(App::getInstance()->getUtils()->translate("%s is already existing into destination path", [$destination]));
                }

                $parentPath = $destinationObj->getPath();
                $destinationFilename = $this->getFilename();
            } catch (NotFoundException $e) {
                $parentDir = dirname($destination);

                if ($parentDir === $mediaRoot) {
                    $destinationObj = null;
                } else {
                    try {
                        $destinationObj = MediaElement::loadByPath($parentDir);
                    } catch (NotFoundException $e2) {
                        throw new NotFoundException(App::getInstance()->getUtils()->translate("Destination folder %s is not existing", [$parentDir]));
                    }
                }

                if ($destinationObj && !$destinationObj->isDirectory()) {
                    throw new InvalidValueException(App::getInstance()->getUtils()->translate("%s is not a folder", [$parentDir]));
                }

                $parentPath = $destinationObj ? $destinationObj->getPath() : $mediaRoot;
                $destinationFilename = basename($destination);
            }
        }

        $existing = null;
        try {
            $existing = static::loadBy('path', $parentPath . DS . $destinationFilename);
        } catch (Exception $e) {
            // if not existing, is fine
        }

        if ($existing) {
            throw new DuplicateException(App::getInstance()->getUtils()->translate("%s is already existing into destination path", [$destinationFilename]));
        }

        $newPath = $parentPath . DS . $destinationFilename;

        if (!@rename($this->getPath(), $newPath)) {
            throw new RuntimeException(App::getInstance()->getUtils()->translate("Error moving %s into %s", [$this->getPath(), $newPath]));
        }

        $oldPath = $this->getPath();

        $this->setPath($newPath);
        $this->setFilename($destinationFilename);
        $this->setParentId($destinationObj?->getId() ?? null);
        $this->persist();

        if ($this->isDirectory()) {
            $subTree = $this->getSubTree();
            if ($subTree) {
                $subTree->map(function(MediaElement $child) use ($oldPath, $newPath) {
                    $relative = ltrim(str_replace($oldPath, '', $child->getPath()), DS);
                    $child->setPath($newPath . DS . $relative);
                    $child->persist();
                });
            }
        }

        return $this;
    }

}
