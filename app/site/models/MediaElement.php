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
use DateTime;
use Degami\Basics\Exceptions\BasicException;
use Exception;
use App\Base\Exceptions\PermissionDeniedException;
use Degami\Basics\Html\TagElement;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use App\Base\GraphQl\GraphQLExport;

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
    use WithOwnerTrait;

    public const TRANSPARENT_PIXEL = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    public const ORIGINAL_SIZE = 'originals';

    /**
     * gets relative path
     *
     * @return string
     * @throws Exception
     */
    public function getRelativePath(): string
    {
        $this->checkLoaded();

        return str_replace(App::getDir(App::ROOT), "", $this->path);
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

        $thumb_path = App::getDir(App::WEBROOT) . DS . 'thumbs' . DS . $size . DS . preg_replace("#^".App::getDir(App::MEDIA)."#", "", $this->getPath());
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
                if ($this->getMimetype() == 'image/svg+xml') {
                    // copy file to destination, does not need resampling
                    if (!copy($this->getPath(), $thumb_path)) {
                        throw new Exception("Errors copying file " . $this->getPath() . " into " . $thumb_path);
                    }
                } else {
                    if (preg_match("/^image\/(.*?)/", $this->getMimetype()) && ($image = App::getInstance()->getImagine()->open($this->getPath()))) {
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
                    if (is_file($thumb_path . DS . $dirent . DS . preg_replace("#^".App::getDir(App::MEDIA)."#", "", $this->getPath()))) {
                        @unlink($thumb_path . DS . $dirent . DS . preg_replace("#^".App::getDir(App::MEDIA)."#", "", $this->getPath()));
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
     * check if media is an image
     * 
     * @return bool
     */
    public function isImage() : bool
    {
        $this->checkLoaded();

        return preg_match("/^image\/.*?/", $this->getMimetype());
    }

    /**
     * check if media is a directory
     * 
     * @return bool
     */
    public function isDirectory() : bool
    {
        $this->checkLoaded();

        return $this->getMimetype() == 'inode/directory';
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
                        if (is_dir($thumb_path . DS . $dirent . DS . preg_replace("#^".App::getDir(App::MEDIA)."#", "", $this->getPath()))) {
                            @rmdir($thumb_path . DS . $dirent . DS . preg_replace("#^".App::getDir(App::MEDIA)."#", "", $this->getPath()));
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
}
