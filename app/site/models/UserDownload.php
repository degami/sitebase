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
use Exception;
use App\Base\Models\MediaElement;

/**
 * User Download Model
 *
 * @method int getId()
 * @method string getPath()
 * @method string getFilename()
 * @method string getMimetype()
 * @method int getFilesize()
 * @method int getUserId()
 * @method int getDownloadAvailableCount()
 * @method DateTime getCreatedAt()
 * @method DateTime getUpdatedAt()
 * @method self setId(int $id)
 * @method self setPath(string $path)
 * @method self setFilename(string $filename)
 * @method self setMimetype(string $mimetype)
 * @method self setFilesize(int $filesize)
 * @method self setUserId(int $user_id)
 * @method self setDownloadAvailableCount(int $download_available_count)
 * @method self setCreatedAt(DateTime $created_at)
 * @method self setUpdatedAt(DateTime $updated_at)
 */
class UserDownload extends BaseModel
{
    use WithOwnerTrait;

    public const UNLIMITED_DOWNLOADS = -1;

    /**
     * {@inheritdoc}
     */
    public static function canBeDuplicated() : bool
    {
        return false;
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

        return str_replace(App::getDir(App::ROOT), "", $this->path);
    }

    public static function createByMediaElement(MediaElement $media_element) : static
    {
        /** @var UserDownload */
        $out = new self();

        $out
            ->setPath($media_element->getPath())
            ->setFilename($media_element->getFilename())
            ->setFilesize($media_element->getFilesize())
            ->setMimetype($media_element->getMimetype());

        return $out;
    }

    public static function isExportable() : bool
    {
        return false;
    }
}
