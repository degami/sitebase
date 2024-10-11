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

namespace App\Base\Abstracts\Models;

use App\App;
use App\Base\Traits\WithLatLngTrait;
use App\Base\Traits\WithOwnerTrait;
use App\Base\Traits\WithWebsiteTrait;
use App\Base\Traits\WithRewriteTrait;
use App\Base\Traits\IndexableTrait;
use App\Base\Traits\FrontendModelTrait;
use Degami\Basics\Html\TagElement;

/**
 * A model with location
 *
 * @method float getLatitude();
 * @method float getLongitude();
 * @method float distance(static $other);
 */
abstract class ModelWithLocation extends FrontendModel
{
    use WithLatLngTrait;
    use WithOwnerTrait;
    use WithWebsiteTrait;
    use WithRewriteTrait;
    use IndexableTrait;
    use FrontendModelTrait;

    public static $headDependenciesAdded = false;

    public static function getCollection() : BaseCollection
    {
        $container = App::getInstance()->getContainer();
        return $container->make(ModelWithLocationCollection::class, ['className' => static::class]);
    }

    public static function addHeadDependencies() : void
    {
        if (!static::$headDependenciesAdded) {
            if (App::getInstance()->getEnv('GOOGLE_API_KEY')) {
                App::getInstance()->getAssets()->addHeadJs('https://maps.googleapis.com/maps/api/js?v=3.exp&amp&amp;libraries=geometry,places&amp;key='. $this->getEnv('GOOGLE_API_KEY'));
            } else if (App::getInstance()->getEnv('MAPBOX_API_KEY')) {
                App::getInstance()->getAssets()->addHeadJs('https://unpkg.com/leaflet@1.3.4/dist/leaflet.js', [
                    'integrity' => "sha512-nMMmRyTVoLYqjP9hrbed9S+FzjZHW5gY1TWCHA5ckwXZBadntCNs8kEqAWdrb9O7rxbCaA4lKTIWjDXZxflOcA==",
                    'crossorigin' => "1",
                ]);
    
                App::getInstance()->getAssets()->addHeadCss('https://unpkg.com/leaflet@1.3.4/dist/leaflet.css', [
                    'integrity' => "sha512-puBpdR0798OZvTTbP4A8Ix/l+A4dHDD0DGqYW6RQ+9jxkRFclaxxQb/SJAWZfWAkuyeQUytO7+7N4QKrDh+drA==",
                    'crossorigin' => "1",
                ]);

                static::$headDependenciesAdded = true;
            }

            static::$headDependenciesAdded = true;
        }
    }

    public function getMap(string|int $mapwidth = 400, string|int $mapheight = 200, $zoom = 18) : string
    {
        $id = 'mapElement'.$this->getId();
        $latitude = $this->getData('latitude');
        $longitude = $this->getData('longitude');

        $map = $this->containerMake(TagElement::class, ['options' => [
            'tag' => 'div',
            'id' => $id.'-map',
            'attributes' => [
                'class' => 'map-details',
            ],
            'text' => '',
        ]]);

        $css = ''; $script = '';

        if ($this->getEnv('GOOGLE_API_KEY') || $this->getEnv('MAPBOX_API_KEY')) {
            $css = $this->containerMake(TagElement::class, ['options' => [
                'tag' => 'style',
                'attributes' => [
                    'class' => '',
                ],
                'text' => "#{$id}-map {".implode(';', [
                    'width:'. $mapwidth . (is_int($mapwidth) ? 'px' : ''),
                    'height:'. $mapheight . (is_int($mapheight) ? 'px' : ''),
                ]) . '}',
            ]]);            
        }

        if ($this->getEnv('GOOGLE_API_KEY')) {
            $mapType = 'google.maps.MapTypeId.ROADMAP';

            $script = $this->containerMake(TagElement::class, ['options' => [
                'tag' => 'script',
                'type' => 'text/javascript',
                'attributes' => [
                    'class' => '',
                ],
                'text' => "
                var {$id}_latlng = {lat: ".$latitude.", lng: ".$longitude."};

                var {$id}_map = new google.maps.Map(document.getElementById('{$id}-map'), {
                  center: {$id}_latlng,
                  mapTypeId: {$mapType},
                  scrollwheel: true,
                  zoom: {$zoom}
                });
                var {$id}_marker = new google.maps.Marker({
                  map: {$id}_map,
                  draggable: true,
                  animation: google.maps.Animation.DROP,
                  position: {$id}_latlng,
                  title: '".(($this->getTitle() == null) ?
                            "lat: ".$latitude.", lng: ".$longitude :
                            $this->getTitle())."'
                });
                \$.data( \$('#{$id}-map')[0] , 'map_obj', {$id}_map);
                \$.data( \$('#{$id}-map')[0] , 'marker_obj', {$id}_marker);

               ",
            ]]);
        } else if ($this->getEnv('MAPBOX_API_KEY')) {
            $mapType = 'mapbox/streets-v12';
            $accesToken = $this->getEnv('MAPBOX_API_KEY');

            $script = $this->containerMake(TagElement::class, ['options' => [
                'tag' => 'script',
                'attributes' => [
                    'class' => '',
                ],
                'type' => 'text/javascript',
                'text' => "
                    var {$id}_latlng = {
                        lat: ".$latitude.",
                        lng: ".$longitude."
                    };
                    var {$id}_map = L.map('{$id}-map').setView([{$id}_latlng.lat,{$id}_latlng.lng],{$zoom});
                    L.tileLayer('https://api.mapbox.com/styles/v1/{id}/tiles/{z}/{x}/{y}?access_token={accessToken}', {
                        attribution:
                            'Map data &copy; <a href=\"https://www.openstreetmap.org/\">OpenStreetMap</a> contributors,'+
                            '<a href=\"https://creativecommons.org/licenses/by-sa/2.0/\">CC-BY-SA</a>,'+
                            ' Imagery © <a href=\"https://www.mapbox.com/\">Mapbox</a>',
                        maxZoom: 18,
                        id: '{$mapType}',
                        accessToken: '{$accesToken}'
                    }).addTo({$id}_map);
            
                    var {$id}_marker = L.marker([{$id}_latlng.lat, {$id}_latlng.lng],{
                        draggable: false
                    }).addTo({$id}_map);
            
                    \$.data( \$('#{$id}-map')[0] , 'map_obj', {$id}_map);
                    \$.data( \$('#{$id}-map')[0] , 'marker_obj', {$id}_marker);
                "
            ]]);    
        }

        return (string)$map.(string)$css.(string)$script;
    }    
}
