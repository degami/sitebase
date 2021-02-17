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

namespace App\Site\Migrations;

use App\App;
use App\Base\Abstracts\Migrations\BaseMigration;
use App\Site\Models\Block;
use App\Site\Models\Configuration;
use App\Site\Models\Contact;
use App\Site\Models\LinkExchange;
use App\Site\Models\MediaElement;
use App\Site\Models\MediaElementRewrite;
use App\Site\Models\Menu;
use App\Site\Models\News as NewsModel;
use App\Site\Models\Page;
use App\Site\Models\Rewrite;
use App\Site\Models\Taxonomy;
use App\Site\Models\User;
use Cassandra\Date;
use DateInterval;
use DateTime;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use Imagine\Image\Box;
use Imagine\Image\Palette\RGB;
use Imagine\Image\Point;

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
     * {@inheritdocs}
     *
     * @return string
     */
    public function getName(): string
    {
        return '200_' . parent::getName();
    }

    /**
     * {@inheritdocs}
     * @throws BasicException
     * @throws Exception
     */
    public function up() : void
    {
        $adminUser = $this->getContainer()->call([User::class, 'load'], ['id' => 1]);

        $terms = [];
        $pages = [];
        $news = [];
        $contacts = [];
        $links = [];
        $images = [];
        $backgrounds = [];
        $links_exchange_rewrites = [];
        $news_list_rewrites = [];

        foreach ($this->locales as $locale) {
            $this->menu_names[$locale] = 'primary-menu_' . $locale;
        }

        for ($i = 1; $i <= 3; $i++) {
            foreach ($this->locales as $locale) {
                $terms[$locale][] = $this->addTerm(
                    "Term" . $i,
                    str_repeat($this->lorem_ipsum_p, rand(1, 3)),
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
                    'Page ' . $i,
                    str_repeat($this->lorem_ipsum_p, rand(2, 6)),
                    $locale,
                    array_intersect_key($terms[$locale], array_flip(array_rand($terms[$locale], rand(2, count($terms[$locale]) - 1)))),
                    $adminUser,
                    array_intersect_key($images, array_flip(array_rand($images, rand(2, count($images) - 1))))
                );
            }
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
                    'News ' . $i,
                    str_repeat($this->lorem_ipsum_p, rand(2, 6)),
                    $date,
                    $locale,
                    $adminUser
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
                'Contact Us',
                str_repeat($this->lorem_ipsum_p, rand(2, 6)),
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
            $rewrite_model = $this->getContainer()->make(Rewrite::class);
            $rewrite_model->url = '/' . $locale . '/links.html';
            $rewrite_model->route = '/links';
            $rewrite_model->locale = $locale;
            $rewrite_model->website_id = $this->website_id;

            $rewrite_model->persist();
            $links_exchange_rewrites[$locale] = $rewrite_model;
        }

        foreach ($this->locales as $locale) {
            $rewrite_model = $this->getContainer()->make(Rewrite::class);
            $rewrite_model->url = '/' . $locale . '/news.html';
            $rewrite_model->route = '/news';
            $rewrite_model->locale = $locale;
            $rewrite_model->website_id = $this->website_id;

            $rewrite_model->persist();
            $news_list_rewrites[$locale] = $rewrite_model;
        }

        foreach ($this->locales as $locale) {
            foreach (array_diff($this->locales, [$locale]) as $other_locale) {
                foreach (['pages', 'terms', 'contacts'] as $array_name) {
                    foreach (${$array_name}[$locale] as $index => $element_from) {
                        $element_to = ${$array_name}[$other_locale][$index];

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
                foreach (['links_exchange_rewrites', 'news_list_rewrites'] as $array_name) {
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
                'News',
                $this->menu_names[$locale],
                $news_list_rewrites[$locale],
                $locale
            );
            $this->addMenuItem(
                'Links Exchange',
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
                    $config = $this->getContainer()->call([Configuration::class, 'loadByCondition'], ['condition' => ['website_id' => $this->website_id, 'locale' => $locale, 'path' => $path]]);
                } catch (Exception $e) {
                    $config = $this->getContainer()->call([Configuration::class, 'new'], ['initial_data' => [
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
                $config = $this->getContainer()->call([Configuration::class, 'loadByCondition'], ['condition' => ['website_id' => $this->website_id, 'path' => $path]]);
            } catch (Exception $e) {
                $config = $this->getContainer()->call([Configuration::class, 'new'], ['initial_data' => [
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

        $home_page = $this->getContainer()->call([Page::class, 'load'], ['id' => 1]);
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
            foreach (['terms', 'pages', 'contacts', 'links', 'news'] as $array) {
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

                if ($media->id && $rewrite->id) {
                    $media_rewrite = $this->getContainer()->make(MediaElementRewrite::class);
                    $media_rewrite->media_element_id = $media->id;
                    $media_rewrite->rewrite_id = $rewrite->id;

                    $media_rewrite->persist();
                }
            }
        }
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
    private function addNews(string $title, string $content, DateTime $date, $locale = 'en', ?User $owner_model = null): NewsModel
    {
        /** @var NewsModel $news_model */
        $news_model = $this->getContainer()->call([NewsModel::class, 'new'], ['initial_data' => [
            'website_id' => $this->website_id,
            'url' => $this->getUtils()->slugify($title, false),
            'title' => $title,
            'locale' => $locale,
            'content' => $content,
            'date' => $date,
            'user_id' => $owner_model ? $owner_model->getId() : 0,
        ]]);
        $news_model->persist();

        return $news_model;
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
    private function addPage(string $title, string $content, $locale = 'en', array $terms = [], ?User $owner_model = null, $images = []): Page
    {
        /** @var Page $page_model */
        $page_model = $this->getContainer()->call([Page::class, 'new'], ['initial_data' => [
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
        $term_model = $this->getContainer()->call([Taxonomy::class, 'new'], ['initial_data' => [
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
    private function addLinkExchange(string $url, string $email, $locale = 'en', $terms = [], ?User $owner_model = null): LinkExchange
    {
        /** @var LinkExchange $link_exchange_model */
        $link_exchange_model = $this->getContainer()->call([LinkExchange::class, 'new'], ['initial_data' => [
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
    private function addContactForm(string $title, string $content, $locale = 'en', $fields = [], ?User $owner_model = null): Contact
    {
        /** @var Contact $contact_model */
        $contact_model = $this->getContainer()->call([Contact::class, 'new'], ['initial_data' => [
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
                    'field_required' => intval($field['required']),
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
    private function addMenuItem(string $title, string $menu_name, Rewrite $rewrite, $locale = 'en', ?Menu $parent = null): Menu
    {
        /** @var Menu $menu_item_model */
        $menu_item_model = $this->getContainer()->call([Menu::class, 'new'], ['initial_data' => [
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
    private function addBlock(string $title, string $content, string $region, $locale = 'en', $rewrites = []): Block
    {
        /** @var Block $block_model */
        $block_model = $this->getContainer()->call([Block::class, 'new'], ['initial_data' => [
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

        $media = $this->getContainer()->make(MediaElement::class);
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
     * {@inheritdocs}
     *
     * @return void
     */
    public function down() : void
    {
    }
}
