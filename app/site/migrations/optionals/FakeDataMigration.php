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

namespace App\Site\Migrations;

use App\App;
use App\Base\Abstracts\Migrations\BaseMigration;
use App\Base\Models\Block;
use App\Base\Models\Configuration;
use App\Site\Models\Contact;
use App\Site\Models\LinkExchange;
use App\Site\Models\MediaElement;
use App\Site\Models\MediaElementRewrite;
use App\Base\Models\Menu;
use App\Site\Models\News as NewsModel;
use App\Site\Models\Event as EventModel;
use App\Site\Models\Page;
use App\Base\Models\Rewrite;
use App\Site\Models\Taxonomy;
use App\Base\Models\User;
use App\Base\Models\Website;
use DateInterval;
use DateTime;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use Imagine\Image\Box;
use Imagine\Image\Palette\RGB;
use Imagine\Image\Point;
use HaydenPierce\ClassFinder\ClassFinder;

/**
 * fake data migration
 */
class FakeDataMigration extends BaseMigration
{
    /**
     * @var string lorem ipsum paragraph
     */
    protected string $lorem_ipsum_p = '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed viverra sapien nunc, id suscipit sem fermentum eget. Maecenas euismod mauris nibh, id interdum est ultricies nec. Integer euismod justo non ullamcorper facilisis. Fusce varius quam et enim tristique rutrum. Morbi feugiat pretium ultrices. Aenean eu sem ac massa commodo accumsan. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aenean ullamcorper pharetra dictum. Fusce rutrum auctor sapien. Aenean vel lectus ex. Duis dictum nisl quis mauris ullamcorper ullamcorper. </p>';

    /**
     * @var array locales
     */
    protected array $locales = ['en', 'fr', 'it', 'ro'];

    /**
     * @var array menu names
     */
    protected array $menu_names = [];

    /**
     * @var string site mail address
     */
    protected string $site_email = 'admin@localhost';

    /**
     * @var array links
     */
    protected array $link_exchange_urls = ["https://www.google.com", "https://www.wikipedia.org", "https://stackoverflow.com", "https://linux.org/", "https://www.php.net"];

    /**
     * @var int website id
     */
    protected int $website_id = 1;

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName(): string
    {
        return '200_' . parent::getName();
    }

    /**
     * {@inheritdoc}
     * @throws BasicException
     * @throws Exception
     */
    public function up() : void
    {
        $adminUser = $this->containerCall([User::class, 'load'], ['id' => 1]);

        $terms = [];
        $pages = [];
        $news = [];
        $events = [];
        $contacts = [];
        $links = [];
        $images = [];
        $backgrounds = [];
        $links_exchange_rewrites = [];
        $news_list_rewrites = [];
        $events_list_rewrites = [];


        /** @var Website $website */
        $website = $this->containerCall([Website::class, 'load'], ['id' => $this->website_id]);

        /** @var Page $home_page */
        $home_page = $this->containerCall([Page::class, 'load'], ['id' => 1]);
        $homePages[$home_page->getLocale()] = $home_page;
        foreach ($this->locales as $locale) {
            if ($locale == $website->getDefaultLocale()) {
                continue;
            }

            $homePages[$locale] = InitialDataMigration::addHomePage($home_page->getWebsite(), $locale, $home_page->getOwner());
        }

        foreach ($this->locales as $locale) {
            $this->menu_names[$locale] = 'primary-menu_' . $locale;
        }

        for ($i = 1; $i <= 3; $i++) {
            foreach ($this->locales as $locale) {
                $terms[$locale][] = $this->addTerm(
                    $this->getUtils()->translate("Term", locale: $locale). ' ' . $i,
                    strtoupper($locale) . ' - ' . str_repeat($this->lorem_ipsum_p, rand(1, 3)),
                    $locale,
                    $adminUser
                );
            }
        }

        for ($i = 1; $i <= 10; $i++) {
            $images[] = $this->createImage();
        }

        for ($i = 1; $i <= 5; $i++) {
            foreach ($this->locales as $locale) {
                $pages[$locale][] = $this->addPage(
                    $this->getUtils()->translate('Page', locale: $locale) . ' ' . $i,
                    strtoupper($locale) . ' - ' . str_repeat($this->lorem_ipsum_p, rand(2, 6)),
                    $locale,
                    array_intersect_key($terms[$locale], array_flip(array_rand($terms[$locale], rand(2, count($terms[$locale]) - 1)))),
                    $adminUser,
                    array_intersect_key($images, array_flip(array_rand($images, rand(2, count($images) - 1))))
                );
            }
        }

        $cities = [
            ['name' => 'New York', 'latitude' => 40.7128, 'longitude' => -74.0060],
            ['name' => 'London', 'latitude' => 51.5074, 'longitude' => -0.1278],
            ['name' => 'Tokyo', 'latitude' => 35.6895, 'longitude' => 139.6917],
            ['name' => 'Sydney', 'latitude' => -33.8688, 'longitude' => 151.2093],
            ['name' => 'Paris', 'latitude' => 48.8566, 'longitude' => 2.3522],
            ['name' => 'Berlin', 'latitude' => 52.5200, 'longitude' => 13.4050],
            ['name' => 'Moscow', 'latitude' => 55.7558, 'longitude' => 37.6173],
            ['name' => 'Rio de Janeiro', 'latitude' => -22.9068, 'longitude' => -43.1729],
            ['name' => 'Beijing', 'latitude' => 39.9042, 'longitude' => 116.4074],
            ['name' => 'Cairo', 'latitude' => 30.0444, 'longitude' => 31.2357],
            ['name' => 'Mumbai', 'latitude' => 19.0760, 'longitude' => 72.8777],
            ['name' => 'Buenos Aires', 'latitude' => -34.6037, 'longitude' => -58.3816],
            ['name' => 'Cape Town', 'latitude' => -33.9249, 'longitude' => 18.4241],
            ['name' => 'Bangkok', 'latitude' => 13.7563, 'longitude' => 100.5018],
            ['name' => 'Lagos', 'latitude' => 6.5244, 'longitude' => 3.3792],
            ['name' => 'Jakarta', 'latitude' => -6.2088, 'longitude' => 106.8456],
            ['name' => 'Istanbul', 'latitude' => 41.0082, 'longitude' => 28.9784],
            ['name' => 'Seoul', 'latitude' => 37.5665, 'longitude' => 126.9780],
            ['name' => 'Mexico City', 'latitude' => 19.4326, 'longitude' => -99.1332],
            ['name' => 'Nairobi', 'latitude' => -1.2921, 'longitude' => 36.8219],
            ['name' => 'Singapore', 'latitude' => 1.3521, 'longitude' => 103.8198],
            ['name' => 'Hong Kong', 'latitude' => 22.3193, 'longitude' => 114.1694],
            ['name' => 'Madrid', 'latitude' => 40.4168, 'longitude' => -3.7038],
            ['name' => 'Toronto', 'latitude' => 43.6511, 'longitude' => -79.3835],
            ['name' => 'San Francisco', 'latitude' => 37.7749, 'longitude' => -122.4194],
            ['name' => 'Rome', 'latitude' => 41.9028, 'longitude' => 12.4964],
            ['name' => 'Athens', 'latitude' => 37.9838, 'longitude' => 23.7275],
            ['name' => 'Lisbon', 'latitude' => 38.7223, 'longitude' => -9.1393],
            ['name' => 'Kuala Lumpur', 'latitude' => 3.1390, 'longitude' => 101.6869],
            ['name' => 'Dubai', 'latitude' => 25.276987, 'longitude' => 55.296249]
        ];
        
        foreach ($this->locales as $locale) {
            $rewrite_model = $this->containerMake(Rewrite::class);
            $rewrite_model->url = '/' . $locale . '/links.html';
            $rewrite_model->route = '/links';
            $rewrite_model->locale = $locale;
            $rewrite_model->website_id = $this->website_id;

            $rewrite_model->persist();
            $links_exchange_rewrites[$locale] = $rewrite_model;
        }

        foreach ($this->locales as $locale) {
            $rewrite_model = $this->containerMake(Rewrite::class);
            $rewrite_model->url = '/' . $locale . '/news.html';
            $rewrite_model->route = '/news';
            $rewrite_model->locale = $locale;
            $rewrite_model->website_id = $this->website_id;

            $rewrite_model->persist();
            $news_list_rewrites[$locale] = $rewrite_model;
        }

        foreach ($this->locales as $locale) {
            $rewrite_model = $this->containerMake(Rewrite::class);
            $rewrite_model->url = '/' . $locale . '/events.html';
            $rewrite_model->route = '/events';
            $rewrite_model->locale = $locale;
            $rewrite_model->website_id = $this->website_id;

            $rewrite_model->persist();
            $events_list_rewrites[$locale] = $rewrite_model;
        }

        for ($i = 1; $i <= 15; $i++) {
            $now = new DateTime();

            $interval_spec = 'P';
            $date_arr = [
                'y' => rand(1, 3),
                'm' => rand(1, 12),
                'd' => rand(1, 31),
            ];
            foreach ($date_arr as $key => $value) {
                $interval_spec .= $value . strtoupper($key);
            }

            $date = $now->add(new DateInterval($interval_spec));
            foreach ($this->locales as $locale) {
                $news[$locale][] = $this->addNews(
                    $this->getUtils()->translate('News', locale: $locale) . ' ' . $i,
                    strtoupper($locale) . ' - ' . str_repeat($this->lorem_ipsum_p, rand(2, 6)),
                    $date,
                    $locale,
                    $adminUser,
                    $news_list_rewrites[$locale] ?? null
                );
            }

            $location = $cities[array_rand($cities)];
            $date = $now->add(new DateInterval($interval_spec));
            foreach ($this->locales as $locale) {
                $events[$locale][] = $this->addEvent(
                    $this->getUtils()->translate('Event', locale: $locale) . ' ' . $i,
                    strtoupper($locale) . ' - ' . str_repeat($this->lorem_ipsum_p, rand(2, 6)),
                    $date,
                    $location['latitude'],
                    $location['longitude'],
                    $locale,
                    $adminUser,
                    $events_list_rewrites[$locale] ?? null
                );
            }
        }


        foreach ($this->link_exchange_urls as $link_exchange) {
            foreach ($this->locales as $locale) {
                $links[$locale][] = $this->addLinkExchange(
                    $link_exchange,
                    $this->site_email,
                    $locale,
                    array_intersect_key($terms[$locale], array_flip(array_rand($terms[$locale], rand(2, count($terms[$locale]) - 1)))),
                    $adminUser
                );
            }
        }

        foreach ($this->locales as $locale) {
            $contacts[$locale][] = $this->addContactForm(
                $this->getUtils()->translate('Contact Us', locale: $locale),
                strtoupper($locale) . ' - ' . str_repeat($this->lorem_ipsum_p, rand(2, 6)),
                $locale,
                [
                    [
                        'label' => 'name',
                        'type' => 'textfield',
                        'required' => true,
                        'data' => json_encode(['title' => "Your Name"])
                    ],
                    [
                        'label' => 'email',
                        'type' => 'email',
                        'data' => json_encode(['title' => "Your Email"])
                    ],
                    [
                        'label' => 'message',
                        'type' => 'textarea',
                        'required' => true,
                        'data' => json_encode(['title' => "Your Message"])
                    ],
                    [
                        'label' => 'captcha',
                        'type' => 'math_captcha',
                        'required' => true,
                        'data' => json_encode(['title' => "Do the maths"])
                    ],
                ],
                $adminUser
            );
        }

        foreach ($this->locales as $locale) {
            // home pages
            foreach (array_diff($this->locales, [$locale]) as $other_locale) {
                foreach (['homePages'] as $array_name) {
                    $element_from = ${$array_name}[$locale];
                    $element_to = ${$array_name}[$other_locale];
                    $rewrite_translation_row = $this->getDb()->table('rewrite_translation')->createRow();
                    $rewrite_translation_row->update(
                        [
                            'source' => $element_from->getId(),
                            'source_locale' => $element_from->getLocale(),
                            'destination' => $element_to->getId(),
                            'destination_locale' => $element_to->getLocale()
                        ]
                    );
                }
            }
        }

        foreach ($this->locales as $locale) {
            foreach (array_diff($this->locales, [$locale]) as $other_locale) {
                foreach (['pages', 'terms', 'contacts', 'news', 'events'] as $array_name) {
                    foreach (${$array_name}[$locale] as $index => $element_from) {
                        $element_to = ${$array_name}[$other_locale][$index];

                        if ($element_from->getRewrite() == null) {
                            $element_from = $this->containerCall([get_class($element_from), 'load'], ['id' => $element_from->getId()]);
                        }
                        if ($element_to->getRewrite() == null) {
                            $element_to = $this->containerCall([get_class($element_to), 'load'], ['id' => $element_to->getId()]);
                        }

                        $rewrite_translation_row = $this->getDb()->table('rewrite_translation')->createRow();
                        $rewrite_translation_row->update(
                            [
                                'source' => $element_from->getRewrite()->getId(),
                                'source_locale' => $element_from->getRewrite()->getLocale(),
                                'destination' => $element_to->getRewrite()->getId(),
                                'destination_locale' => $element_to->getRewrite()->getLocale()
                            ]
                        );
                    }
                }
            }

            // links exchange
            foreach (array_diff($this->locales, [$locale]) as $other_locale) {
                foreach (['links_exchange_rewrites', 'news_list_rewrites', 'events_list_rewrites'] as $array_name) {
                    $element_from = ${$array_name}[$locale];
                    $element_to = ${$array_name}[$other_locale];
                    $rewrite_translation_row = $this->getDb()->table('rewrite_translation')->createRow();
                    $rewrite_translation_row->update(
                        [
                            'source' => $element_from->getId(),
                            'source_locale' => $element_from->getLocale(),
                            'destination' => $element_to->getId(),
                            'destination_locale' => $element_to->getLocale()
                        ]
                    );
                }
            }
        }

        $lastMenuItem = null;
        foreach ($this->locales as $locale) {
            foreach (['pages', 'terms', 'contacts'] as $array_name) {
                foreach (${$array_name}[$locale] as $index => $element) {
                    $lastMenuItem = $this->addMenuItem(
                        $element->getTitle(),
                        $this->menu_names[$locale],
                        $element->getRewrite(),
                        $element->getLocale(),
                        ($index % 3 == 2) ? $lastMenuItem : null
                    );
                }
            }
            $this->addMenuItem(
                $this->getUtils()->translate('News', locale: $locale),
                $this->menu_names[$locale],
                $news_list_rewrites[$locale],
                $locale
            );
            $this->addMenuItem(
                $this->getUtils()->translate('Events', locale: $locale),
                $this->menu_names[$locale],
                $events_list_rewrites[$locale],
                $locale
            );
            $this->addMenuItem(
                $this->getUtils()->translate('Links Exchange', locale: $locale),
                $this->menu_names[$locale],
                $links_exchange_rewrites[$locale],
                $locale
            );
        }

        // locale dependent configuration
        foreach ($this->locales as $locale) {
            $configurations = [
                'app/frontend/main_menu' => $this->menu_names[$locale],
                'app/mail/ses_sender' => '',
            ];
            foreach ($configurations as $path => $value) {
                /** @var Configuration $config */
                $config = null;
                try {
                    $config = $this->containerCall([Configuration::class, 'loadByCondition'], ['condition' => ['website_id' => $this->website_id, 'locale' => $locale, 'path' => $path]]);
                } catch (Exception $e) {
                    $config = $this->containerCall([Configuration::class, 'new'], ['initial_data' => [
                        'website_id' => $this->website_id,
                        'locale' => $locale,
                        'path' => $path,
                    ]]);
                }

                $config
                    ->setValue($value)
                    ->setIsSystem(1)
                    ->persist();
            }

            if ($locale != $website->getDefaultLocale()) {
                $path = 'app/frontend/homepage';
                try {
                    $config = $this->containerCall([Configuration::class, 'loadByCondition'], ['condition' => ['website_id' => $this->website_id, 'locale' => $locale, 'path' => $path]]);
                } catch (Exception $e) {
                    $config = $this->containerCall([Configuration::class, 'new'], ['initial_data' => [
                        'website_id' => $this->website_id,
                        'locale' => $locale,
                        'path' => $path,
                    ]]);
                }

                $config
                    ->setValue($homePages[$locale]->getId())
                    ->persist();
            }
        }

        // locale independent configuration
        $configurations = [
            'app/global/site_mail_address' => $this->site_email,
            'app/frontend/langs' => implode(',', $this->locales),
        ];
        foreach ($configurations as $path => $value) {
            /** @var Configuration $config */
            $config = null;
            try {
                $config = $this->containerCall([Configuration::class, 'loadByCondition'], ['condition' => ['website_id' => $this->website_id, 'path' => $path]]);
            } catch (Exception $e) {
                $config = $this->containerCall([Configuration::class, 'new'], ['initial_data' => [
                    'website_id' => $this->website_id,
                    'path' => $path,
                ]]);
            }

            $config
                ->setValue($value)
                ->setLocale(null)
                ->setIsSystem(1)
                ->persist();
        }

        foreach ($this->locales as $locale) {
            $rewrites = [];
            $rewrites[] = $home_page->getRewrite();
            $rewrites[] = $contacts[$locale][0]->getRewrite();

            foreach (array_rand($pages[$locale], rand(2, count($pages[$locale]) - 1)) as $k) {
                $rewrites[] = $pages[$locale][$k]->getRewrite();
            }

            $this->addBlock('block_pre_footer', $this->lorem_ipsum_p, 'pre_footer', $locale, $rewrites);
        }

        $rewrites = [];
        foreach ($this->locales as $locale) {
            foreach (['terms', 'pages', 'contacts', 'links', 'news', 'events'] as $array) {
                foreach (${$array}[$locale] as $key => $object) {
                    $rewrites[] = $object->getRewrite();
                }
            }
        }

        for ($i = 1; $i <= 10; $i++) {
            $backgrounds[] = $this->createImage(1920, 350);
        }

        foreach (array_rand($rewrites, rand(ceil(count($rewrites) / 2), count($rewrites) - 1)) as $r) {
            foreach (array_rand($backgrounds, rand(ceil(count($backgrounds) / 2), count($backgrounds) - 1)) as $i) {
                $rewrite = $rewrites[$r];
                $media = $backgrounds[$i];

                if ($media && $rewrite && $media->id && $rewrite->id) {
                    $media_rewrite = $this->containerMake(MediaElementRewrite::class);
                    $media_rewrite->media_element_id = $media->id;
                    $media_rewrite->rewrite_id = $rewrite->id;

                    $media_rewrite->persist();
                }
            }
        }


        // add all blocks
        $blockClasses = array_merge(
            ClassFinder::getClassesInNamespace(App::BASE_BLOCKS_NAMESPACE), 
            ClassFinder::getClassesInNamespace(App::BLOCKS_NAMESPACE)
        );

        foreach ($blockClasses as $blockClass) {
            $existing_blocks = Block::getCollection()->where(['instance_class' => $blockClass])->getItems();
            if (count($existing_blocks) > 0) {
                continue;
            }

            /** @var Block $new_block */
            $new_block = $this->containerMake(Block::class);
            $new_block->setRegion(null);
            $new_block->setTitle(str_replace("App\\Site\\Blocks\\", "", $blockClass));
            $new_block->setLocale(null);
            $new_block->setInstanceClass($blockClass);
            $new_block->setContent(null);

            $new_block->persist();
        }

        // update blocks positions
        $this->getDb()->query('UPDATE `block` SET `region` = \'pre_content\', `config` = \'{"add-current":"0"}\', `order` = 1 WHERE instance_class = \'App\\\\Site\\\\Blocks\\\\BreadCrumbs\' LIMIT 1;');
        $this->getDb()->query('UPDATE `block` SET `region` = \'pre_footer\', `config` = \'{"show-language":"none","show-flags":"1"}\', `order` = 1  WHERE instance_class = \'App\\\\Base\\\\Blocks\\\\ChangeLanguage\' LIMIT 1;');
        $this->getDb()->query('UPDATE `block` SET `region` = \'before_body_close\', `config` = \'{"rewrite_en":"1","rewrite_fr":"1","rewrite_it":"1","rewrite_ro":"1","background-color":"#CECECE","color":"#000000","sticky":"bottom"}\', `order` = 0 WHERE instance_class = \'App\\\\Base\\\\Blocks\\\\CookieNotice\' LIMIT 1;');
        $this->getDb()->query('UPDATE `block` SET `region` = \'pre_content\', `config` = \'{"fx":"","speed":"","timeout":""}\', `order` = 0 WHERE instance_class = \'App\\\\Site\\\\Blocks\\\\RewriteMedia\' LIMIT 1;');
        $this->getDb()->query('UPDATE `block` SET `region` = \'post_menu\', `order` = 0 WHERE instance_class = \'App\\\\Base\\\\Blocks\\\\Search\' LIMIT 1;'); 
        $this->getDb()->query('UPDATE `block` SET `region` = \'post_footer\', `order` = 0 WHERE instance_class = \'App\\\\Base\\\\Blocks\\\\YearCopy\' LIMIT 1;'); 
        $this->getDb()->query('UPDATE `block` SET `region` = \'post_menu\', `order` = 1 WHERE instance_class = \'App\\\\Base\\\\Blocks\\\\LinkToUserArea\' LIMIT 1;'); 
        $this->getDb()->query('UPDATE `block` SET `region` = \'post_content\', `order` = 0 WHERE instance_class = \'App\\\\Base\\\\Blocks\\\\AuthorInfo\' LIMIT 1;'); 
        $this->getDb()->query('UPDATE `block` SET `region` = \'post_header\', `config` = \'{"add-current":"1"}\', `order` = 0 WHERE instance_class = \'App\\\\Base\\\\Blocks\\\\BreadCrumbs\' LIMIT 1;'); 
    }

    /**
     * adds a news model
     *
     * @param string $title
     * @param string $content
     * @param DateTime $date
     * @param string $locale
     * @param User|null $owner_model
     * @return NewsModel
     * @throws BasicException
     */
    private function addNews(string $title, string $content, DateTime $date, string $locale = 'en', ?User $owner_model = null, ?Rewrite $parentRewrite = null): NewsModel
    {
        /** @var NewsModel $news_model */
        $news_model = $this->containerCall([NewsModel::class, 'new'], ['initial_data' => [
            'website_id' => $this->website_id,
            'url' => $this->getUtils()->slugify($title, false),
            'title' => $title,
            'locale' => $locale,
            'content' => $content,
            'date' => $date,
            'user_id' => $owner_model ? $owner_model->getId() : 0,
        ]]);
        $news_model->persist();

        if ($parentRewrite) {
            // update parent rewrite for created element
            $news_model->getRewrite()?->setParentId($parentRewrite->getId())->persist();
        }

        return $news_model;
    }

    /**
     * adds a event model
     *
     * @param string $title
     * @param string $content
     * @param DateTime $date
     * @param float $latitude
     * @param float $longitude
     * @param string $locale
     * @param User|null $owner_model
     * @return EventModel
     * @throws BasicException
     */
    private function addEvent(string $title, string $content, DateTime $date, float $latitude, float $longitude, string $locale = 'en', ?User $owner_model = null, ?Rewrite $parentRewrite = null): EventModel
    {
        /** @var EventModel $event_model */
        $event_model = $this->containerCall([EventModel::class, 'new'], ['initial_data' => [
            'website_id' => $this->website_id,
            'url' => $this->getUtils()->slugify($title, false),
            'title' => $title,
            'locale' => $locale,
            'content' => $content,
            'date' => $date,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'user_id' => $owner_model ? $owner_model->getId() : 0,
        ]]);
        $event_model->persist();

        if ($parentRewrite) {
            // update parent rewrite for created element
            $event_model->getRewrite()?->setParentId($parentRewrite->getId())->persist();
        }

        return $event_model;
    }
    /**
     * adds a page model
     *
     * @param string $title
     * @param string $content
     * @param string $locale
     * @param array $terms
     * @param User|null $owner_model
     * @param array $images
     * @return Page
     * @throws BasicException
     */
    private function addPage(string $title, string $content, $locale = 'en', array $terms = [], ?User $owner_model = null, array $images = []): Page
    {
        /** @var Page $page_model */
        $page_model = $this->containerCall([Page::class, 'new'], ['initial_data' => [
            'website_id' => $this->website_id,
            'url' => $this->getUtils()->slugify($title, false),
            'title' => $title,
            'locale' => $locale,
            'content' => $content,
            'user_id' => $owner_model ? $owner_model->getId() : 0,
        ]]);
        $page_model->persist();

        foreach ($terms as $key => $term) {
            $page_model->addTerm($term);
        }

        foreach ($images as $key => $image) {
            $page_model->addMedia($image);
        }

        return $page_model;
    }

    /**
     * adds a term model
     *
     * @param string $title
     * @param string $content
     * @param string $locale
     * @param User|null $owner_model
     * @return Taxonomy
     * @throws BasicException
     */
    private function addTerm(string $title, string $content, string $locale = 'en', ?User $owner_model = null): Taxonomy
    {
        /** @var Taxonomy $term_model */
        $term_model = $this->containerCall([Taxonomy::class, 'new'], ['initial_data' => [
            'website_id' => $this->website_id,
            'url' => $this->getUtils()->slugify($title, false),
            'title' => $title,
            'locale' => $locale,
            'content' => $content,
            'user_id' => $owner_model ? $owner_model->getId() : 0,
        ]]);

        $term_model->persist();

        return $term_model;
    }

    /**
     * adds a link model
     *
     * @param string $url
     * @param string $email
     * @param string $locale
     * @param array $terms
     * @param User|null $owner_model
     * @return LinkExchange
     * @throws BasicException
     */
    private function addLinkExchange(string $url, string $email, $locale = 'en', array $terms = [], ?User $owner_model = null): LinkExchange
    {
        /** @var LinkExchange $link_exchange_model */
        $link_exchange_model = $this->containerCall([LinkExchange::class, 'new'], ['initial_data' => [
            'website_id' => $this->website_id,
            'url' => $url,
            'title' => $url,
            'description' => $url . ' description<br />' . $this->lorem_ipsum_p,
            'email' => $email,
            'locale' => $locale,
            'active' => 1,
            'user_id' => $owner_model ? $owner_model->getId() : 0,
        ]]);
        $link_exchange_model->persist();

        foreach ($terms as $key => $term) {
            $link_exchange_model->addTerm($term);
        }

        return $link_exchange_model;
    }

    /**
     * adds a contact form model
     *
     * @param string $title
     * @param string $content
     * @param string $locale
     * @param array $fields
     * @param User|null $owner_model
     * @return Contact
     * @throws BasicException
     */
    private function addContactForm(string $title, string $content, $locale = 'en', array $fields = [], ?User $owner_model = null): Contact
    {
        /** @var Contact $contact_model */
        $contact_model = $this->containerCall([Contact::class, 'new'], ['initial_data' => [
            'website_id' => $this->website_id,
            'url' => $this->getUtils()->slugify($title, false),
            'title' => $title,
            'locale' => $locale,
            'content' => $content,
            'user_id' => $owner_model ? $owner_model->getId() : 0,
            'submit_to' => $this->site_email,
        ]]);
        $contact_model->persist();

        foreach ($fields as $key => $field) {
            $this->getDb()->createRow('contact_definition')->update(
                [
                    'contact_id' => $contact_model->getId(),
                    'field_label' => $field['label'],
                    'field_type' => $field['type'],
                    'field_required' => intval($field['required'] ?? 0),
                    'field_data' => $field['data'],
                ]
            );
        }

        return $contact_model;
    }

    /**
     * adds a menu item model
     *
     * @param string $title
     * @param string $menu_name
     * @param Rewrite $rewrite
     * @param string $locale
     * @param Menu|null $parent
     * @return Menu
     * @throws BasicException
     */
    private function addMenuItem(string $title, string $menu_name, Rewrite $rewrite, string $locale = 'en', ?Menu $parent = null): Menu
    {
        /** @var Menu $menu_item_model */
        $menu_item_model = $this->containerCall([Menu::class, 'new'], ['initial_data' => [
            'menu_name' => $menu_name,
            'website_id' => $this->website_id,
            'title' => $title,
            'locale' => $locale,
            'rewrite_id' => $rewrite->getId(),
            'parent_id' => $parent?->getId() ?? null,
            //'breadcrumb' => $menu_item_model->getParentIds(),
        ]]);
        $menu_item_model->persist();

        $menu_item_model
            ->setBreadcrumb($menu_item_model->getParentIds())
            ->persist();

        return $menu_item_model;
    }

    /**
     * adds a block model
     *
     * @param string $title
     * @param string $content
     * @param string $region
     * @param string $locale
     * @param array $rewrites
     * @return Block
     * @throws BasicException
     */
    private function addBlock(string $title, string $content, string $region, string $locale = 'en', array $rewrites = []): Block
    {
        /** @var Block $block_model */
        $block_model = $this->containerCall([Block::class, 'new'], ['initial_data' => [
            'website_id' => $this->website_id,
            'title' => $title,
            'locale' => $locale,
            'instance_class' => Block::class,
            'content' => $content,
            'region' => $region,
        ]]);
        $block_model->persist();

        if (!empty($rewrites)) {
            foreach ($rewrites as $rewrite) {
                $this->getDb()->createRow(
                    'block_rewrite',
                    [
                        'block_id' => $block_model->getId(),
                        'rewrite_id' => $rewrite->getId(),
                    ]
                )->save();
            }
        }

        return $block_model;
    }

    /**
     * generates an image
     *
     * @param int $w
     * @param int $h
     * @return MediaElement
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    private function createImage(int $w = 400, int $h = 400): MediaElement
    {
        $palette = new RGB();
        $size = new Box($w, $h);

        $white = $palette->color('#ffffff', 100);
        $black = $palette->color('#000000', 100);

        $color1 = $palette->color([rand(0, 255), rand(0, 255), rand(0, 255)], 100);
        $color2 = $palette->color([rand(0, 255), rand(0, 255), rand(0, 255)], 100);
        $color3 = $palette->color([rand(0, 255), rand(0, 255), rand(0, 255)], 100);
        $color4 = $palette->color([rand(0, 255), rand(0, 255), rand(0, 255)], 100);

        $image = $this->getImagine()->create($size, $white);
        $image
            ->draw()
            ->rectangle(new Point(1, 1), new Point(($w / 2) - 1, ($h / 2) - 1), $color1, true)
            ->rectangle(new Point(($w / 2) + 1, 1), new Point($w - 1, ($h / 4) - 1), $color2, true)
            ->rectangle(new Point(1, ($h / 2) + 1), new Point(($w / 4) - 1, $h - 1), $color3, true)
            ->rectangle(new Point(($w / 2) + 1, ($h / 2) + 1), new Point($w - 1, $h - 1), $color4, true)
            ->circle(new Point($w / 2, $h / 2), $h / 2, $black, false, 1);


        $filename = App::getDir(App::MEDIA) . DS . 'image-' . rand() . '-' . date("YmdHis") . '.png';
        $image->save($filename);

        $media = $this->containerMake(MediaElement::class);
        $media->path = $filename;
        $media->filename = basename($filename);

        $file_info = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
        $media->mimetype = finfo_file($file_info, $filename);
        finfo_close($file_info);

        $media->filesize = filesize($filename);

        $media->persist();
        return $media;
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function down() : void
    {
    }
}
