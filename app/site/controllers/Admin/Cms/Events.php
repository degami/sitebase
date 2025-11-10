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

namespace App\Site\Controllers\Admin\Cms;

use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Base\Routing\RouteInfo;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use App\Base\Abstracts\Controllers\AdminManageFrontendModelsPage;
use Degami\PHPFormsApi as FAPI;
use App\Site\Models\Event as EventModel;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;

/**
 * "Events" Admin Page
 */
class Events extends AdminManageFrontendModelsPage
{
    public function __construct(
        protected ContainerInterface $container, 
        protected ?Request $request = null, 
        protected ?RouteInfo $route_info = null,
        bool $asGrid = false,
    ) {
        parent::__construct($container, $request, $route_info, $asGrid);

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
        return 'administer_events';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getObjectClass(): string
    {
        return EventModel::class;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getObjectIdQueryParam(): string
    {
        return 'event_id';
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
            'icon' => 'calendar',
            'text' => 'Events',
            'section' => 'cms',
            'order' => 20,
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
     * @throws PhpfastcacheSimpleCacheException
     */
    public function getFormDefinition(FAPI\Form $form, array &$form_state): FAPI\Form
    {
        $type = $this->getRequest()->query->get('action') ?? 'list';
        $event = $this->getObject();

        $form->addField('action', [
            'type' => 'value',
            'value' => $type,
        ]);

        switch ($type) {
            case 'edit':
            case 'new':

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

                $event_title = $event_content = $event_date = ''; $event_location = ['latitude' => null, 'longitude' => null];
                if ($event->isLoaded()) {
                    $event_title = $event->title;
                    $event_content = $event->content;
                    $event_date = $event->date;
                    $event_location = $event->getLocation();
                }
                $form->addField('title', [
                    'type' => 'textfield',
                    'title' => 'Title',
                    'default_value' => $event_title,
                    'validate' => ['required'],
                ])
                ->addField('location', [
                    'type' => $locationFieldType,
                    'title' => 'Location',
                    'default_value' => $event_location,
                    'validate' => ['required'],
                ] + $mapOptions)
                ->addField('date', [
                    'type' => 'datepicker',
                    'title' => 'Date',
                    'default_value' => $event_date,
                    'validate' => ['required'],
                ])
                ->addField('content', [
                    'type' => 'tinymce',
                    'title' => 'Content',
                    'tinymce_options' => DEFAULT_TINYMCE_OPTIONS,
                    'default_value' => $event_content,
                    'rows' => 20,
                ]);

                $this->addFrontendFormElements($form, $form_state);
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
        // @todo : check if page language is in page website languages?
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
         * @var EventModel $news
         */
        $event = $this->getObject();

        $values = $form->values();

        switch ($values['action']) {
            case 'new':
                $event->setUserId($this->getCurrentUser()->getId());
            // intentional fall trough
            // no break
            case 'edit':
                $event->setUrl($values['frontend']['url']);
                $event->setTitle($values['title']);
                $event->setLocale($values['frontend']['locale']);
                $event->setContent($values['content']);
                $event->setWebsiteId($values['frontend']['website_id']);
                $event->setDate($values['date']);
                $event->setLatitude($values['location']['latitude']);
                $event->setLongitude($values['location']['longitude']);

                $this->setAdminActionLogData($event->getChangedData());

                $event->persist();

                $this->addSuccessFlashMessage($this->getUtils()->translate("Event Saved."));
                break;
            case 'delete':
                $event->delete();

                $this->setAdminActionLogData('Deleted event ' . $event->getId());

                $this->addInfoFlashMessage($this->getUtils()->translate("Event Deleted."));

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
            'Website' => ['order' => 'website_id', 'foreign' => 'website_id', 'table' => $this->getModelTableName(), 'view' => 'site_name'],
            'URL' => ['order' => 'url', 'search' => 'url'],
            'Locale' => ['order' => 'locale', 'search' => 'locale'],
            'Title' => ['order' => 'title', 'search' => 'title'],
            'Date' => ['order' => 'date', 'search' => 'date'],
            'actions' => null,
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @param array $data
     * @param array $options
     * @return array
     * @throws BasicException
     * @throws Exception
     */
    protected function getTableElements(array $data, array $options = []): array
    {
        return array_map(
            function ($event) {
                return [
                    'ID' => $event->id,
                    'Website' => $event->getWebsiteId() == null ? 'All websites' : $event->getWebsite()->domain,
                    'URL' => $event->url,
                    'Locale' => $event->locale,
                    'Title' => $event->title,
                    'Date' => $event->date,
                    'actions' => $this->getModelRowButtons($event),
                ];
            },
            $data
        );
    }
}
