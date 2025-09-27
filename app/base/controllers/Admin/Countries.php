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

namespace App\Base\Controllers\Admin;

use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use App\Base\Abstracts\Controllers\AdminManageModelsPage;
use App\Base\Models\Country;
use Degami\PHPFormsApi as FAPI;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Base\Routing\RouteInfo;

/**
 * "Countries" Admin Page
 */
class Countries extends AdminManageModelsPage
{
    public function __construct(
        protected ContainerInterface $container, 
        protected ?Request $request = null, 
        protected ?RouteInfo $route_info = null
    ) {
        parent::__construct($container, $request, $route_info);

        if ($this->getEnvironment()->getVariable('GOOGLE_API_KEY')) {
            $this->getAssets()->addHeadJs('https://maps.googleapis.com/maps/api/js?v=3.exp&amp&amp;libraries=geometry,places&amp;key='. $this->getEnvironment()->getVariable('GOOGLE_API_KEY'));
        } else if ($this->getEnvironment()->getVariable('MAPBOX_API_KEY')) {
            $this->getAssets()->addHeadJs('https://unpkg.com/leaflet@1.3.4/dist/leaflet.js', [
                'integrity' => "sha512-nMMmRyTVoLYqjP9hrbed9S+FzjZHW5gY1TWCHA5ckwXZBadntCNs8kEqAWdrb9O7rxbCaA4lKTIWjDXZxflOcA==",
                'crossorigin' => "1",
            ]);

            $this->getAssets()->addHeadCss('https://unpkg.com/leaflet@1.3.4/dist/leaflet.css', [
                'integrity' => "sha512-puBpdR0798OZvTTbP4A8Ix/l+A4dHDD0DGqYW6RQ+9jxkRFclaxxQb/SJAWZfWAkuyeQUytO7+7N4QKrDh+drA==",
                'crossorigin' => "1",
            ]);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getTemplateName(): string
    {
        return 'base_admin_page';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getAccessPermission(): string
    {
        return 'administer_countries';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getObjectClass(): string
    {
        return Country::class;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getObjectIdQueryParam(): string
    {
        return 'country_id';
    }

    /**
     * {@inheritdoc}
     *
     * @return array|null
     */
    public static function getAdminPageLink() : array|null
    {
        return [
            'permission_name' => static::getAccessPermission(),
            'route_name' => static::getPageRouteName(),
            'icon' => 'globe',
            'text' => 'Countries',
            'section' => 'system',
            'order' => 2,
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return FAPI\Form
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getFormDefinition(FAPI\Form $form, array &$form_state): FAPI\Form
    {
        $type = $this->getRequest()->get('action') ?? 'list';
        $country = $this->getObject();

        $form->addField('action', [
            'type' => 'value',
            'value' => $type,
        ]);

        switch ($type) {
            case 'edit':
            case 'new':
                $this->addBackButton();

                $country_iso2 = $country_iso3 = $country_name_en = $country_name_native = $country_capital = $country_latitude = $country_longitude = '';
                $capital_location = ['latitude' => null, 'longitude' => null];
                if ($country->isLoaded()) {
                    $country_iso2 = $country->iso2;
                    $country_iso3 = $country->iso3;
                    $country_name_en = $country->name_en;
                    $country_name_native = $country->name_native;
                    $country_capital = $country->capital;
                    $capital_location = $country->getCapitalLocation();
                }

               $locationFieldType = 'geolocation'; $mapOptions = [];
                if ($this->getEnvironment()->getVariable('GOOGLE_API_KEY')) {
                    $locationFieldType = 'gmaplocation';
                }
                else if ($this->getEnvironment()->getVariable('MAPBOX_API_KEY')) {
                    $locationFieldType = 'leafletlocation';
                    $mapOptions = [
                        'accessToken' => $this->getEnvironment()->getVariable('MAPBOX_API_KEY'),
                        'scrollwheel' => true,
                        'maptype' => 'mapbox/streets-v12',
                    ];
                }

                $form->addField('iso2', [
                    'type' => 'textfield',
                    'title' => 'ISO 2',
                    'default_value' => $country_iso2,
                    'validate' => ['required'],
                ])->addField('iso3', [
                    'type' => 'textfield',
                    'title' => 'ISO 3',
                    'default_value' => $country_iso3,
                    'validate' => ['required'],
                ])->addField('name_en', [
                    'type' => 'textfield',
                    'title' => 'Name EN',
                    'default_value' => $country_name_en,
                    'validate' => ['required'],
                ])->addField('name_native', [
                    'type' => 'textfield',
                    'title' => 'Name Native',
                    'default_value' => $country_name_native,
                    'validate' => ['required'],
                ])
                ->addField('capital', [
                    'type' => 'textfield',
                    'title' => 'Capital',
                    'default_value' => $country_capital,
                ])
                ->addField('location', [
                    'type' => $locationFieldType,
                    'title' => 'Capital Location',
                    'default_value' => $capital_location,
                    'lat_lon_type' => 'textfield', // use textfield for latitude and longitude inputs
                ] + $mapOptions);

                $form->addCss(".latitude, .longitude {
                    margin: 10px;
                    border: 1px solid #ccc;
                    padding: 4px 8px;
                }")
                ->addCss(".dark-mode .latitude, .dark-mode .longitude {
                    background-color: #333;
                    border-color: #555;
                    color: #f9f9f9;
                }");


                $this->addSubmitButton($form);
                break;

            case 'delete':
                $this->fillConfirmationForm('Do you confirm the deletion of the selected element?', $form);
                break;
        }

        return $form;
    }

    /**
     * {@inheritdoc}
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return bool|string
     */
    public function formValidate(FAPI\Form $form, &$form_state): bool|string
    {
        //$values = $form->values();
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return mixed
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function formSubmitted(FAPI\Form $form, &$form_state): mixed
    {
        /**
         * @var Country $country
         */
        $country = $this->getObject();

        $values = $form->values();
        switch ($values['action']) {
            case 'new':
            case 'edit':
                $country->setIso2($values['iso2']);
                $country->setIso3($values['iso3']);
                $country->setNameEn($values['name_en']);
                $country->setNameNative($values['name_native']);
                $country->setCapital($values['capital']);
                $country->setLatitude($values['latitude']);
                $country->setLongitude($values['longitude']);

                $this->setAdminActionLogData($country->getChangedData());

                $this->addSuccessFlashMessage($this->getUtils()->translate("Country Saved."));
                $country->persist();
                break;
            case 'delete':
                $country->delete();

                $this->setAdminActionLogData('Deleted country ' . $country->getId());

                $this->addInfoFlashMessage($this->getUtils()->translate("Country Deleted."));

                break;
        }

        return $this->refreshPage();
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    protected function getTableHeader(): ?array
    {
        return [
            'ID' => 'id',
            'Iso2' => ['order' => 'iso2', 'search' => 'iso2'],
            'Iso3' => ['order' => 'iso3', 'search' => 'iso3'],
            'Name En' => ['order' => 'name_en', 'search' => 'name_en'],
            'Name Native' => ['order' => 'name_native', 'search' => 'name_native'],
            'Capital' => ['order' => 'capital', 'search' => 'capital'],
            'Latitude' => ['order' => 'latitude', 'search' => 'latitude'],
            'Longitude' => ['order' => 'longitude', 'search' => 'longitude'],
            'actions' => null,
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @param array $data
     * @return array
     * @throws BasicException
     * @throws PhpfastcacheSimpleCacheException
     */
    protected function getTableElements(array $data): array
    {
        return array_map(
            function ($country) {
                return [
                    'ID' => $country->id,
                    'iso2' => $country->iso2,
                    'iso3' => $country->iso3,
                    'Name En' => $country->name_en,
                    'Name Native' => $country->name_native,
                    'Capital' => $country->capital,
                    'Latitude' => $country->latitude,
                    'Longitude' => $country->longitude,
                    'actions' => implode(
                        " ",
                        [
                            $this->getEditButton($country->id),
                            $this->getDeleteButton($country->id),
                        ]
                    ),
                ];
            },
            $data
        );
    }
}
