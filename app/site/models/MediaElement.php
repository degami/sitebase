<?php
/**
 * SiteBase
 * PHP Version 7.0
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis <degami@github.com>
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */
namespace App\Site\Models;

use \App\Base\Abstracts\Models\BaseModel;
use \App\Base\Traits\WithOwnerTrait;
use \App\App;
use \Exception;
use \App\Base\Exceptions\PermissionDeniedException;
use \Degami\Basics\Html\TagElement;

/**
 * Media Element Model
 *
 * @method int getId()
 * @method string getPath()
 * @method string getFilename()
 * @method string getMimetype()
 * @method int getFilesize()
 * @method int getUserId()
 * @method boolean getLazyload()
 * @method \DateTime getCreatedAt()
 * @method \DateTime getUpdatedAt()
 */
class MediaElement extends BaseModel
{
    use WithOwnerTrait;

    const TRANSPARENT_PIXEL = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    /**
     * gets relative path
     *
     * @return string
     */
    public function getRelativePath()
    {
        $this->checkLoaded();

        return str_replace(App::getDir(App::ROOT), "", $this->path);
    }

    /**
     * gets thumbnail img html tag
     *
     * @param  string $size
     * @param  string $mode
     * @param  string $class
     * @param  array  $img_attributes
     * @return string
     */
    public function getThumb($size, $mode = null, $class = null, $img_attributes = [])
    {
        $style = "";
        if (preg_match("/^([0-9]+)x([0-9]+)$/i", $size, $thumb_sizes)) {
            $w = $thumb_sizes[1];
            $h = $thumb_sizes[2];
            $style = "style=\"max-width:{$w}px;max-height:{$h}px;\" ";
        }

        if (boolval($this->lazyload) && !isset($img_attributes['for_admin'])) {
            $img_attributes['data-src'] = $this->getThumbUrl($size, $mode);
        }

        return (string)(new TagElement(
            [
            'tag' => 'img',
            'attributes' => [
                'src' => boolval($this->lazyload) && !isset($img_attributes['for_admin']) ? static::TRANSPARENT_PIXEL : $this->getThumbUrl($size, $mode),
                'class' => $class,
                'style' =>  preg_match('/img-fluid/i', $class) ? '' : "max-width:{$w}px;max-height:{$h}px;",
                'border' => 0,
            ] + $img_attributes,
            ]
        ));
    }

    /**
     * gets thumbnail url
     *
     * @param  string $size
     * @param  string $mode
     * @return string
     */
    public function getThumbUrl($size, $mode = null)
    {
        $this->checkLoaded();

        $thumb_sizes = null;
        if ($size != 'originals') {
            if (!preg_match("/^([0-9]+)x([0-9]+)$/i", $size, $thumb_sizes)) {
                return $this->path;
            }
        }

        if (is_dir($this->path)) {
            $this->path = rtrim($this->path, DS).DS.$this->filename;
        }

        $thumb_path = App::getDir(App::WEBROOT).DS.'thumbs'.DS.$size.DS.$this->filename;
        if (!preg_match("/^image\/(.*?)/", $this->mimetype)) {
            $thumb_path .= '.svg';
        }
        if (!file_exists($thumb_path)) {
            if (!is_dir(dirname($thumb_path))) {
                if (!mkdir(dirname($thumb_path), 0755, true)) {
                    throw new PermissionDeniedException("Errors creating directory structure for thumbnail", 1);
                }
            }

            try {
                if ($this->mimetype == 'image/svg+xml') {
                    // copy file to destination, does not need resampling
                    if (!copy($this->path, $thumb_path)) {
                        throw new Exception("Errors copying file ".$this->path." into ".$thumb_path);
                    }
                } else {
                    if (preg_match("/^image\/(.*?)/", $this->mimetype) && ($image = $this->getImagine()->open($this->path))) {
                        if ($thumb_sizes) {
                            $w = $thumb_sizes[1];
                            $h = $thumb_sizes[2];
                        } else {
                            $sizes = $image->getSize();
                            $w = $sizes->getWidth();
                            $h = $sizes->getHeight();
                        }

                        $size = new \Imagine\Image\Box($w, $h);

                        if (!in_array(
                            $mode,
                            [
                            \Imagine\Image\ImageInterface::THUMBNAIL_INSET,
                            \Imagine\Image\ImageInterface::THUMBNAIL_OUTBOUND
                            ]
                        )
                        ) {
                            $mode    = \Imagine\Image\ImageInterface::THUMBNAIL_INSET;
                            // or
                            // $mode    = Imagine\Image\ImageInterface::THUMBNAIL_OUTBOUND;
                        }

                        $this->getImagine()
                            ->open($this->path)
                            ->thumbnail($size, $mode)
                            ->save($thumb_path);
                    } else {
                        // @todo thumb in base a mimetype
                        $type = explode('/', $this->mimetype);
                        if (is_array($type)) {
                            $svg = $this->getUtils()->getIcon(array_pop($type));
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
     * @param  string $class
     * @return string
     */
    public function getImage($class = 'img-fluid')
    {
        return $this->getThumb('originals', null, $class);
    }

    /**
     * gets original image url
     *
     * @return string
     */
    public function getImageUrl()
    {
        return $this->getThumbUrl('originals');
    }
}
