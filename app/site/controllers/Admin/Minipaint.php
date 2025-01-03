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

use App\App;
use App\Base\Abstracts\Controllers\AdminPage;
use App\Base\Exceptions\NotFoundException;
use App\Site\Models\MediaElement;
use App\Site\Routing\RouteInfo;
use Symfony\Component\HttpFoundation\Response;

/**
 * "Minipaint" Admin Page
 */
class Minipaint extends AdminPage
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getTemplateName(): string
    {
        return 'minipaint';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getAccessPermission(): string
    {
        return 'administer_site';
    }

    /**
     * {@inheritdoc}
     *
     * @return array|null
     */
    public Function getAdminPageLink() : array|null
    {
        return null;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    protected function getTemplateData(): array
    {
        return [];
    }


    public function getRoutePath() : string 
    {
        return 'minipaint/{path:.*}';    
    }

    public function process(?RouteInfo $route_info = null, $route_data = []): Response
    {
        $filepath = 'index.html';
        if ($route_data['path']) {
            $filepath = str_replace("/", DS, $route_data['path']);
        }

        $filepath = App::getDir(App::ASSETS) . DS . 'minipaint' . DS . $filepath;
        if (!file_exists($filepath)) {
            throw new NotFoundException("missing $filepath");
        }

        $mimetype = mime_content_type($filepath);
        $content = file_get_contents($filepath);

        if (basename($filepath) == 'index.html') {
            $media = MediaElement::load($this->getRequest()->get('media_id'));
            $mediaMimeType = $media->getMimetype();
            $minipaintSaveUrl = $this->getUrl('crud.app.site.controllers.admin.minipaintsave', ['media_id' => $media->getId()]);
            $backUrl = $this->getUrl('admin.media'). '?' . http_build_query(['action' => 'edit', 'media_id' => $media->getId()]);

            $saveMessage = $this->getUtils()->translate("Save on server");
            $backMessage = $this->getHtmlRenderer()->getIcon('rewind', ['height' => 16]) . '&nbsp;' . $this->getUtils()->translate("Back");
            $errorMessage = $this->getUtils()->translate("Errors on image save.");

            $content = str_replace("</body>",<<<EOF
<script type="text/javascript">
function getQueryParam(param) {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(param);
}

window.onload = function () {
    const imageUrl = getQueryParam('imageUrl');

    if (FileOpen) {
        FileOpen.file_open_data_url_handler(imageUrl);
    } else {
        console.error('MiniPaint non è inizializzato correttamente.');
    }
};

window.addEventListener('load', () => {
    if (window.Layers && window.FileSave) {
        const backButton = document.createElement('a');
        backButton.href = '$backUrl';
        backButton.innerHTML = '$backMessage';
        backButton.style.display = 'flex';
        backButton.style.alignItems = 'center';
        backButton.style.textDecoration = 'none';
        backButton.style.position = 'absolute';
        backButton.style.top = '5px';
        backButton.style.right = '150px';
        backButton.style.zIndex = 1000;
        backButton.style.padding = '2px 10px';
        backButton.style.color = '#fff';
        backButton.style.backgroundColor = '#666';
        backButton.style.border = 'solid 1px #cecece';

        document.body.appendChild(backButton);

        const saveButton = document.createElement('button');
        saveButton.textContent = '$saveMessage';
        saveButton.style.position = 'absolute';
        saveButton.style.top = '7px';
        saveButton.style.right = '10px';
        saveButton.style.zIndex = 1000;

        saveButton.addEventListener('click', () => {
            const canvas = document.getElementById('canvas_minipaint');
            if (!canvas) {
                console.error('Canvas not found.');
                return;
            }

            const imageData = canvas.toDataURL('$mediaMimeType');

            // Invia l'immagine al server
            fetch('$minipaintSaveUrl', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ image: imageData }),
            })
            .then(response => response.json())
            .then(data => {
                // avoid confirmation popup
                window.addEventListener('beforeunload', function(event) {
                    event.stopImmediatePropagation();
                }, true);

                document.location = data.redirect;
            })
            .catch(error => {
            console.log(error);
                alert('$errorMessage');
            });
        });

        document.body.appendChild(saveButton);
    } else {
        console.error('MiniPaint not initialized.');
    }
});

</script>
</body>
EOF, $content);
        }

        $this->getResponse()->headers->set('Content-Type', $mimetype);
        return $this
            ->getResponse()
            ->prepare($this->getRequest())
            ->setContent($content);
    }
}
