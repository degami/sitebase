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

use App\App;
use App\Base\Tools\Assets\Manager as AssetsManager;
use App\Base\Tools\Utils\HtmlPartsRenderer;
use App\Base\Tools\Utils\Globals;
use Sabre\DAV\Browser\Plugin as BrowserPlugin;
use Sabre\DAV;
use Sabre\HTTP;;
use Sabre\Uri;
use Sabre\DAV\Browser\HtmlOutputHelper;
use Sabre\DAV\Browser\HtmlOutput;
use Sabre\DAV\Browser\PropFindAll;

class Browser extends BrowserPlugin
{
    public function generateDirectoryIndex($path)
    {
        $html = $this->generateHeader($path ?: '/', $path);

        $node = $this->server->tree->getNodeForPath($path);

        /** @var Globals $utils */
        $utils = App::getInstance()->getUtils();

        if ($node instanceof DAV\ICollection) {
            $html .= "<section>\n";
            $html .= '<table class="nodeTable table table-striped">';

            $subNodes = $this->server->getPropertiesForChildren($path, [
                '{DAV:}displayname',
                '{DAV:}resourcetype',
                '{DAV:}getcontenttype',
                '{DAV:}getcontentlength',
                '{DAV:}getlastmodified',
            ]);

            foreach ($subNodes as $subPath => $subProps) {
                $subNode = $this->server->tree->getNodeForPath($subPath);
                $fullPath = $this->server->getBaseUri().HTTP\encodePath($subPath);
                list(, $displayPath) = Uri\split($subPath);

                $subNodes[$subPath]['subNode'] = $subNode;
                $subNodes[$subPath]['fullPath'] = $fullPath;
                $subNodes[$subPath]['displayPath'] = $displayPath;
            }
            uasort($subNodes, [$this, 'compareNodes']);

            foreach ($subNodes as $subProps) {
                $type = [
                    'string' => 'Unknown',
                    'icon' => 'cog',
                ];
                if (isset($subProps['{DAV:}resourcetype'])) {
                    $type = $this->mapResourceType($subProps['{DAV:}resourcetype']->getValue(), $subProps['subNode']);
                }

                $html .= '<tr>';
                $html .= '<td class="nameColumn"><a href="'.$this->escapeHTML($subProps['fullPath']).'"><span class="oi" data-glyph="'.$this->escapeHTML($type['icon']).'"></span> '.$this->escapeHTML($subProps['displayPath']).'</a></td>';
                $html .= '<td class="typeColumn">'.$this->escapeHTML($type['string']).'</td>';
                $html .= '<td class="sizeColumn">';
                if (isset($subProps['{DAV:}getcontentlength'])) {
                    $html .= $this->escapeHTML($utils->formatBytes($subProps['{DAV:}getcontentlength']));
                }
                $html .= '</td><td class="lastModifiedColumn">';
                if (isset($subProps['{DAV:}getlastmodified'])) {
                    $lastMod = $subProps['{DAV:}getlastmodified']->getTime();
                    $html .= $this->escapeHTML($lastMod->format('F j, Y, g:i a'));
                }
                $html .= '</td>';
                if (isset($subProps['{DAV:}displayname'])) {
                    $html .= '<td>' . $this->escapeHTML($subProps['{DAV:}displayname']) . '</td>';
                }

                $buttonActions = '';
                $this->server->emit('browserButtonActions', [$subProps['fullPath'], $subProps['subNode'], &$buttonActions]);

                if (!empty($buttonActions)) {
                    $html .= '<td>'.$buttonActions.'</td>';
                }
                $html .= '</tr>';
            }

            $html .= '</table>';
        }

        $html .= '</section>';

        /* Start of generating actions */

        $output = '';
        if ($this->enablePost) {
            $this->server->emit('onHTMLActionsPanel', [$node, &$output, $path]);
        }

        if ($output) {

            $output = str_replace("input type=\"submit\"", "input class=\"btn btn-primary m-1\" type=\"submit\"", $output);
            $output = str_replace("<br />", "", $output);

            $html .= '<section class="actionsPanel"><h1>Actions</h1>';
            $html .= "<div class=\"actions\">\n";
            $html .= $output;
            $html .= "</div>\n";
            $html .= "</section>\n";
        }

        $html .= $this->generateFooter();

        $this->server->httpResponse->setHeader('Content-Security-Policy', "default-src 'none'; img-src 'self'; style-src 'self'; font-src 'self';");

        return $html;
    }

    public function generatePluginListing()
    {
        // Custom implementation can be added here

        return parent::generatePluginListing();
    }

    public function generateHeader($title, $path = null)
    {
        $version = '';
        if (DAV\Server::$exposeVersion) {
            $version = DAV\Version::VERSION;
        }

        /** @var AssetsManager $assets */
        $assets = App::getInstance()->getAssets();

        /** @var HtmlPartsRenderer $htmlRendered */
        $htmlRendered = App::getInstance()->getHtmlRenderer();

        $vars = [
            'title' => $this->escapeHTML($title),
            'favicon' => $this->escapeHTML($this->getAssetUrl('favicon.ico')),
            'style' => $this->escapeHTML($assets->assetUrl('css/site.css')),
            'webdav_style' => $this->escapeHTML($assets->assetUrl('css/webdav.css')),
            'iconstyle' => $this->escapeHTML($this->getAssetUrl('openiconic/open-iconic.css')),
            'logo' => (string) $htmlRendered->renderSiteLogo(),
            'baseUrl' => $this->server->getBaseUri(),
        ];

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>$vars[title] - sabre/dav $version</title>
    <link rel="shortcut icon" href="$vars[favicon]"   type="image/vnd.microsoft.icon" />
    <link rel="stylesheet"    href="$vars[webdav_style]" type="text/css" />
    <link rel="stylesheet"    href="$vars[style]"     type="text/css" />
    <link rel="stylesheet"    href="$vars[iconstyle]" type="text/css" />

</head>
<body>
    <header>
        <div class="logo">
            $vars[logo]
        </div>
    </header>

    <nav class="mb-5">
HTML;

        // If the path is empty, there's no parent.
        if ($path) {
            list($parentUri) = Uri\split($path);
            $fullPath = $this->server->getBaseUri().HTTP\encodePath($parentUri);
            $displayFullPath = "/ " . implode(" / ", explode("/", ltrim($path, '/')));
            $html .= '<a href="'.$fullPath.'" class="btn btn-light mr-2">⇤ Go to parent</a>' . $displayFullPath;
        } else {
            $html .= '<span class="btn disabled">⇤ Go to parent</span>';
        }

        $html .= '</nav>';

        return $html;
    }

    public function generateFooter()
    {
        $version = '';
        if (DAV\Server::$exposeVersion) {
            $version = DAV\Version::VERSION;
        }
        $year = date('Y');

        return <<<HTML
<footer class="text-center mt-3">
    <small>Generated by SabreDAV $version (c)2007-$year <a href="http://sabre.io/">http://sabre.io/</a></small>
</footer>
</body>
</html>
HTML;
    }



    /**
     * Maps a resource type to a human-readable string and icon.
     *
     * @param DAV\INode $node
     *
     * @return array
     */
    private function mapResourceType(array $resourceTypes, $node)
    {
        if (!$resourceTypes) {
            if ($node instanceof DAV\IFile) {
                return [
                    'string' => 'File',
                    'icon' => 'file',
                ];
            } else {
                return [
                    'string' => 'Unknown',
                    'icon' => 'cog',
                ];
            }
        }

        $types = [
            '{http://calendarserver.org/ns/}calendar-proxy-write' => [
                'string' => 'Proxy-Write',
                'icon' => 'people',
            ],
            '{http://calendarserver.org/ns/}calendar-proxy-read' => [
                'string' => 'Proxy-Read',
                'icon' => 'people',
            ],
            '{urn:ietf:params:xml:ns:caldav}schedule-outbox' => [
                'string' => 'Outbox',
                'icon' => 'inbox',
            ],
            '{urn:ietf:params:xml:ns:caldav}schedule-inbox' => [
                'string' => 'Inbox',
                'icon' => 'inbox',
            ],
            '{urn:ietf:params:xml:ns:caldav}calendar' => [
                'string' => 'Calendar',
                'icon' => 'calendar',
            ],
            '{http://calendarserver.org/ns/}shared-owner' => [
                'string' => 'Shared',
                'icon' => 'calendar',
            ],
            '{http://calendarserver.org/ns/}subscribed' => [
                'string' => 'Subscription',
                'icon' => 'calendar',
            ],
            '{urn:ietf:params:xml:ns:carddav}directory' => [
                'string' => 'Directory',
                'icon' => 'globe',
            ],
            '{urn:ietf:params:xml:ns:carddav}addressbook' => [
                'string' => 'Address book',
                'icon' => 'book',
            ],
            '{DAV:}principal' => [
                'string' => 'Principal',
                'icon' => 'person',
            ],
            '{DAV:}collection' => [
                'string' => 'Folder',
                'icon' => 'folder',
            ],
        ];

        $info = [
            'string' => [],
            'icon' => 'cog',
        ];
        foreach ($resourceTypes as $k => $resourceType) {
            if (isset($types[$resourceType])) {
                $info['string'][] = $types[$resourceType]['string'];
            } else {
                $info['string'][] = $resourceType;
            }
        }
        foreach ($types as $key => $resourceInfo) {
            if (in_array($key, $resourceTypes)) {
                $info['icon'] = $resourceInfo['icon'];
                break;
            }
        }
        $info['string'] = implode(', ', $info['string']);

        return $info;
    }

    /**
     * Draws a table row for a property.
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return string
     */
    private function drawPropertyRow($name, $value)
    {
        $html = new HtmlOutputHelper(
            $this->server->getBaseUri(),
            $this->server->xml->namespaceMap
        );

        return '<tr><th>'.$html->xmlName($name).'</th><td>'.$this->drawPropertyValue($html, $value).'</td></tr>';
    }

    /**
     * Draws a table row for a property.
     *
     * @param HtmlOutputHelper $html
     * @param mixed            $value
     *
     * @return string
     */
    private function drawPropertyValue($html, $value)
    {
        if (is_scalar($value)) {
            return $html->h($value);
        } elseif ($value instanceof HtmlOutput) {
            return $value->toHtml($html);
        } elseif ($value instanceof \Sabre\Xml\XmlSerializable) {
            // There's no default html output for this property, we're going
            // to output the actual xml serialization instead.
            $xml = $this->server->xml->write('{DAV:}root', $value, $this->server->getBaseUri());
            // removing first and last line, as they contain our root
            // element.
            $xml = explode("\n", $xml);
            $xml = array_slice($xml, 2, -2);

            return '<pre>'.$html->h(implode("\n", $xml)).'</pre>';
        } else {
            return '<em>unknown</em>';
        }
    }
}