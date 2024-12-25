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

namespace App\Site\Controllers\Admin;

use App\Base\Abstracts\Controllers\AdminJsonPage;
use App\Site\Models\MediaElement;
use Degami\Basics\Exceptions\BasicException;

/**
 * "MinipaintSave" Admin Page
 */
class MinipaintSave extends AdminJsonPage
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getAccessPermission(): string
    {
        return 'administer_site';
    }

    public function getRoutePath() : string 
    {
        return 'minipaint_save/{media_id:\d+}';    
    }

    public function getJsonData(): array
    {
        $route_data = $this->getRouteData();
        $body = json_decode($this->getRequest()->getContent(), true);

        $image = $body['image']; 
        $data = $body['image'];

        list($type, $data) = explode(';', $data);
        list($encoding, $data) = explode(',', $data);
        $data = base64_decode($data);

        /** @var MediaElement $media */
        $media = MediaElement::load($route_data['media_id']);
        $filePath = $media->getPath();


        if (!file_put_contents($filePath, $data)) {
            throw new BasicException("errors on saving ".$filePath);
        }

        $mimetype = mime_content_type($filePath);
        $filesize = filesize($filePath);

        $media->setMimetype($mimetype)->setFilesize($filesize)->persist();

        // delete thumbnails if existing
        $media->clearThumbs();

        return [
            'image' => $image,
            'redirect' => $this->getUrl('admin.media') . '?' . http_build_query(['media_id' => $media->getId(), 'action' => 'edit'])
        ];
    }
}
