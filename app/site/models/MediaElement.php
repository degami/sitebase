<?php

/**
 * SiteBase
 * PHP Version 8.0
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
 * @method DateTime getCreatedAt()
 * @method DateTime getUpdatedAt()
 * @method self setId(int $id)
 * @method self setPath(string $path)
 * @method self setFilename(string $filename)
 * @method self setMimetype(string $mimetype)
 * @method self setFilesize(int $filesize)
 * @method self setUserId(int $user_id)
 * @method self setLazyload(bool $lazyload)
 * @method self setCreatedAt(DateTime $created_at)
 * @method self setUpdatedAt(DateTime $updated_at)
 */
class MediaElement extends BaseModel
{
    use WithOwnerTrait;

    public const TRANSPARENT_PIXEL = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

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
    public function getThumb(string $size, $mode = null, $class = null, $img_attributes = []): string
    {
        $w = $h = null;
        if (preg_match("/^([0-9]+)x([0-9]+)$/i", $size, $thumb_sizes)) {
            $w = $thumb_sizes[1];
            $h = $thumb_sizes[2];
        }

        if (boolval($this->getLazyload()) && !isset($img_attributes['for_admin'])) {
            $img_attributes['data-src'] = $this->getThumbUrl($size, $mode);
        }

        return $this->containerMake(TagElement::class, ['options' => [
            'tag' => 'img',
            'attributes' => [
                    'src' => boolval($this->getLazyload()) && !isset($img_attributes['for_admin']) ? static::TRANSPARENT_PIXEL : $this->getThumbUrl($size, $mode),
                    'class' => $class,
                    'style' => preg_match('/img-fluid/i', $class) ? '' : "max-width:{$w}px;max-height:{$h}px;",
                    'border' => 0,
                ] + $img_attributes,
        ]]);
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
    public function getThumbUrl(string $size, $mode = null): string
    {
        $this->checkLoaded();

        $thumb_sizes = null;
        if ($size != 'originals') {
            if (!preg_match("/^([0-9]+)x([0-9]+)$/i", $size, $thumb_sizes)) {
                return $this->getPath();
            }
        }

        if (is_dir($this->getPath())) {
            $this->setPath(rtrim($this->getPath(), DS) . DS . $this->getFilename());
        }

        $thumb_path = App::getDir(App::WEBROOT) . DS . 'thumbs' . DS . $size . DS . $this->getFilename();
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
                    if (preg_match("/^image\/(.*?)/", $this->getMimetype()) && ($image = $this->getImagine()->open($this->getPath()))) {
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

                        $this->getImagine()
                            ->open($this->path)
                            ->thumbnail($size, $mode)
                            ->save($thumb_path);
                    } else {
                        // @todo thumb in base a mimetype
                        $type = explode('/', $this->getMimetype());
                        if (is_array($type)) {
                            return $this->getHtmlRenderer()->getIcon(array_pop($type));
                        }
                    }
                }
            } catch (Exception $e) {
                echo $e->getMessage();
            }
        }

        return $this->getAssets()->assetUrl(str_replace(App::getDir(App::WEBROOT), "", $thumb_path));
    }

    /**
     * gets original image img tag
     *
     * @param string $class
     * @return string
     * @throws PermissionDeniedException
     * @throws BasicException
     */
    public function getImage(string $class = 'img-fluid'): string
    {
        return $this->getThumb('originals', null, $class);
    }

    /**
     * gets original image url
     *
     * @return string
     * @throws PermissionDeniedException
     * @throws BasicException
     */
    public function getImageUrl(): string
    {
        return $this->getThumbUrl('originals');
    }
}
