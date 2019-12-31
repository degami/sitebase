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

use \App\Base\Abstracts\Migration;
use \Psr\Container\ContainerInterface;
use \Degami\SqlSchema\Index;

/**
 * fake data migration
 */
class FakeDataMigration extends Migration
{
    /**
     * @var string lorem ipsum paragraph
     */
    protected $lipsum_p = '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed viverra sapien nunc, id suscipit sem fermentum eget. Maecenas euismod mauris nibh, id interdum est ultricies nec. Integer euismod justo non ullamcorper facilisis. Fusce varius quam et enim tristique rutrum. Morbi feugiat pretium ultrices. Aenean eu sem ac massa commodo accumsan. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aenean ullamcorper pharetra dictum. Fusce rutrum auctor sapien. Aenean vel lectus ex. Duis dictum nisl quis mauris ullamcorper ullamcorper. </p>';

    /**
     * @var array locales
     */
    protected $locales = ['en', 'fr', 'it', 'ro'];

    /**
     * @var array menu names
     */
    protected $menu_names = [];

    /**
     * @var string site mail address
     */
    protected $site_email = 'admin@localhost';

    /**
     * @var array links
     */
    protected $linkexchange_urls = ["https://www.google.com","https://www.wikipedia.org","https://stackoverflow.com","https://linux.org/","https://www.php.net"];

    /**
     * @var integer website id
     */
    protected $website_id = 1;

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    public function getName()
    {
        return '200_'.parent::getName();
    }

    /**
     * {@inheritdocs}
     */
    public function up()
    {
        $adminUser = $this->getContainer()->call([\App\Site\Models\User::class, 'load'], ['id' => 1]);

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
            $this->menu_names[$locale] = 'primary-menu_'.$locale;
        }

        for ($i=1; $i<=3; $i++) {
            foreach ($this->locales as $locale) {
                $terms[$locale][] = $this->addTerm(
                    "Term".$i,
                    str_repeat($this->lipsum_p, rand(1, 3)),
                    $locale,
                    $adminUser
                );
            }
        }

        for ($i=1; $i<=10; $i++) {
            $images[] = $this->createImage();
        }

        for ($i=1; $i<=5; $i++) {
            foreach ($this->locales as $locale) {
                $pages[$locale][] = $this->addPage(
                    'Page '.$i,
                    str_repeat($this->lipsum_p, rand(2, 6)),
                    $locale,
                    array_intersect_key($terms[$locale], array_flip(array_rand($terms[$locale], rand(2, count($terms[$locale])-1)))),
                    $adminUser,
                    array_intersect_key($images, array_flip(array_rand($images, rand(2, count($images)-1))))
                );
            }
        }

        for ($i=1; $i<=15; $i++) {
            $now = new \DateTime();

            $interval_spec = 'P';
            foreach ([
                'y' => rand(1, 3),
                'm' => rand(1, 12),
                'd' => rand(1, 31),
            ] as $key => $value) {
                $interval_spec.= $value.strtoupper($key);
            }

            $date = $now->add(new \DateInterval($interval_spec));
            foreach ($this->locales as $locale) {
                $news[$locale][] = $this->addNews(
                    'News '.$i,
                    str_repeat($this->lipsum_p, rand(2, 6)),
                    $date,
                    $locale,
                    $adminUser
                );
            }
        }


        foreach ($this->linkexchange_urls as $link_exchange) {
            foreach ($this->locales as $locale) {
                $links[$locale][] = $this->addLinkExchange(
                    $link_exchange,
                    $this->site_email,
                    $locale,
                    array_intersect_key($terms[$locale], array_flip(array_rand($terms[$locale], rand(2, count($terms[$locale])-1)))),
                    $adminUser
                );
            }
        }

        foreach ($this->locales as $locale) {
            $contacts[$locale][] = $this->addContactForm(
                'Contact Us',
                str_repeat($this->lipsum_p, rand(2, 6)),
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
            $rewrite_model = $this->getContainer()->make(\App\Site\Models\Rewrite::class);
            $rewrite_model->url = '/'.$locale.'/links.html';
            $rewrite_model->route = '/links';
            $rewrite_model->locale = $locale;
            $rewrite_model->website_id = $this->website_id;

            $rewrite_model->persist();
            $links_exchange_rewrites[$locale] = $rewrite_model;
        }

        foreach ($this->locales as $locale) {
            $rewrite_model = $this->getContainer()->make(\App\Site\Models\Rewrite::class);
            $rewrite_model->url = '/'.$locale.'/news.html';
            $rewrite_model->route = '/news';
            $rewrite_model->locale = $locale;
            $rewrite_model->website_id = $this->website_id;

            $rewrite_model->persist();
            $news_list_rewrites[$locale] = $rewrite_model;
        }

        foreach ($this->locales as $locale) {
            foreach (array_diff($this->locales, [$locale]) as $other_locale) {
                foreach (['pages', 'terms','contacts'] as $arrayname) {
                    foreach (${$arrayname}[$locale] as $index => $element_from) {
                        $element_to = ${$arrayname}[$other_locale][$index];

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
                $element_from = $links_exchange_rewrites[$locale];
                $element_to = $links_exchange_rewrites[$other_locale];
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

        foreach ($this->locales as $locale) {
            foreach (['pages', 'terms','contacts'] as $arrayname) {
                foreach (${$arrayname}[$locale] as $index => $element) {
                    $lastMenuItem = $this->addMenuItem(
                        $element->getTitle(),
                        $this->menu_names[$locale],
                        $element->getRewrite(),
                        $element->getLocale(),
                        ($index %3 == 2) ? $lastMenuItem : null
                    );
                }
            }
            $this->addMenuItem(
                'News',
                $this->menu_names[$locale],
                $news_list_rewrites[$locale],
                $locale,
                null
            );
            $this->addMenuItem(
                'Links Exchange',
                $this->menu_names[$locale],
                $links_exchange_rewrites[$locale],
                $locale,
                null
            );
        }

        foreach ($this->locales as $locale) {
            // menus
            $config = $this->getDb()->table('configuration')->where(['website_id' => $this->website_id, 'locale' => $locale, 'path'=> 'app/frontend/main_menu'])->fetch();
            if (!$config) {
                $config = $this->getDb()->createRow('configuration');
            }

            $config->update(
                [
                'website_id' => $this->website_id,
                'locale' => $locale,
                'path' => 'app/frontend/main_menu',
                'value' => $this->menu_names[$locale],
                'is_system' => 1,
                ]
            );

            // ses
            $config = $this->getDb()->table('configuration')->where(['website_id' => $this->website_id, 'locale' => $locale, 'path'=> 'app/mail/ses_sender'])->fetch();
            if (!$config) {
                $config = $this->getDb()->createRow('configuration');
            }

            $config->update(
                [
                'website_id' => $this->website_id,
                'locale' => $locale,
                'path' => 'app/mail/ses_sender',
                'value' => '',
                'is_system' => 1,
                ]
            );
        }

        // email
        $config = $this->getDb()->table('configuration')->where(['website_id' => $this->website_id, 'path'=> 'app/global/site_mail_address'])->fetch();
        $config->update(
            [
            'website_id' => $this->website_id,
            'locale' => null,
            'path' => 'app/global/site_mail_address',
            'value' => $this->site_email,
            'is_system' => 1,
            ]
        );

        // languages
        $config = $this->getDb()->table('configuration')->where(['website_id' => $this->website_id, 'path'=> 'app/frontend/langs'])->fetch();
        $config->update(
            [
            'website_id' => $this->website_id,
            'locale' => null,
            'path' => 'app/frontend/langs',
            'value' => implode(',', $this->locales),
            'is_system' => 1,
            ]
        );


        $home_page = $this->getContainer()->call([\App\Site\Models\Page::class, 'load'], ['id' => 1]);
        foreach ($this->locales as $locale) {
            $rewrites = [];
            $rewrites[] = $home_page->getRewrite();
            $rewrites[] = $contacts[$locale][0]->getRewrite();

            foreach (array_rand($pages[$locale], rand(2, count($pages[$locale])-1)) as $k) {
                $rewrites[] = $pages[$locale][$k]->getRewrite();
            }

            $this->addBlock('block_pre_footer', $this->lipsum_p, 'pre_footer', $locale, $rewrites);
        }

        $rewrites = [];
        foreach ($this->locales as $locale) {
            foreach (['terms', 'pages', 'contacts', 'links', 'news'] as $array) {
                foreach (${$array}[$locale] as $key => $object) {
                    $rewrites[] = $object->getRewrite();
                }
            }
        }

        for ($i=1; $i<=10; $i++) {
            $backgrounds[] = $this->createImage(1920, 350);
        }

        foreach (array_rand($rewrites, rand(ceil(count($rewrites)/2), count($rewrites)-1)) as $r) {
            foreach (array_rand($backgrounds, rand(ceil(count($backgrounds)/2), count($backgrounds)-1)) as $i) {
                $rewrite = $rewrites[$r];
                $media = $backgrounds[$i];

                if ($media->id && $rewrite->id) {
                    $media_rewrite = $this->getContainer()->make(\App\Site\Models\MediaElementRewrite::class);
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
     * @param string    $title
     * @param string    $content
     * @param \DateTime $date
     * @param string    $locale
     * @param User      $owner_model
     */
    private function addNews($title, $content, $date, $locale = 'en', $owner_model = null)
    {
        $news_model = $this->getContainer()->make(\App\Site\Models\News::class);

        $news_model->website_id =  $this->website_id;
        $news_model->url = $this->getUtils()->slugify($title);
        $news_model->title = $title;
        $news_model->locale = $locale;
        $news_model->content = $content;
        $news_model->date = $date;
        $news_model->user_id = $owner_model ? $owner_model->id : 0;

        $news_model->persist();
        return $news_model;
    }

    /**
     * adds a page model
     *
     * @param string $title
     * @param string $content
     * @param string $locale
     * @param array  $terms
     * @param User   $owner_model
     * @param array  $images
     */
    private function addPage($title, $content, $locale = 'en', $terms = [], $owner_model = null, $images = [])
    {
        $page_model = $this->getContainer()->make(\App\Site\Models\Page::class);

        $page_model->website_id =  $this->website_id;
        $page_model->url = $this->getUtils()->slugify($title);
        $page_model->title = $title;
        $page_model->locale = $locale;
        $page_model->content = $content;
        $page_model->user_id = $owner_model ? $owner_model->id : 0;

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
     * @param User   $owner_model
     */
    private function addTerm($title, $content, $locale = 'en', $owner_model = null)
    {
        $term_model = $this->getContainer()->make(\App\Site\Models\Taxonomy::class);

        $term_model->website_id =  $this->website_id;
        $term_model->url = $this->getUtils()->slugify($title);
        $term_model->title = $title;
        $term_model->locale = $locale;
        $term_model->content = $content;
        $term_model->user_id = $owner_model ? $owner_model->id : 0;

        $term_model->persist();

        return $term_model;
    }

    /**
     * adds a link model
     *
     * @param string $url
     * @param string $email
     * @param string $locale
     * @param array  $terms
     * @param User   $owner_model
     */
    private function addLinkExchange($url, $email, $locale = 'en', $terms = [], $owner_model = null)
    {
        $link_exchange_model = $this->getContainer()->make(\App\Site\Models\LinkExchange::class);

        $link_exchange_model->website_id =  $this->website_id;
        $link_exchange_model->url = $url;
        $link_exchange_model->title = $url;
        $link_exchange_model->description = $url . ' description<br />'.$this->lipsum_p;
        $link_exchange_model->email = $email;
        $link_exchange_model->locale = $locale;
        $link_exchange_model->active = 1;
        $link_exchange_model->user_id = $owner_model ? $owner_model->id : 0;

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
     * @param array  $fields
     * @param User   $owner_model
     */
    private function addContactForm($title, $content, $locale = 'en', $fields = [], $owner_model = null)
    {
        $contact_model = $this->getContainer()->make(\App\Site\Models\Contact::class);

        $contact_model->website_id =  $this->website_id;
        $contact_model->url = $this->getUtils()->slugify($title);
        $contact_model->title = $title;
        $contact_model->locale = $locale;
        $contact_model->content = $content;
        $contact_model->user_id = $owner_model ? $owner_model->id : 0;
        $contact_model->submit_to = $this->site_email;

        $contact_model->persist();

        foreach ($fields as $key => $field) {
            $this->getDb()->createRow('contact_definition')->update(
                [
                'contact_id' => $contact_model->id,
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
     * @param string  $title
     * @param string  $menu_name
     * @param Rewrite $rewrite
     * @param string  $locale
     * @param Menu    $parent
     */
    private function addMenuItem($title, $menu_name, $rewrite, $locale = 'en', $parent = null)
    {
        $menu_item_model = $this->getContainer()->make(\App\Site\Models\Menu::class);

        $menu_item_model->menu_name = $menu_name;
        $menu_item_model->website_id =  $this->website_id;
        $menu_item_model->title = $title;
        $menu_item_model->locale = $locale;
        $menu_item_model->rewrite_id = $rewrite->getId();

        if ($parent != null) {
            $menu_item_model->parent_id = $parent->getId();
        }

        $menu_item_model->breadcrumb = $menu_item_model->getParentIds();
        $menu_item_model->persist();
        return $menu_item_model;
    }

    /**
     * adds a block model
     *
     * @param string $title
     * @param string $content
     * @param string $region
     * @param string $locale
     * @param array  $rewrites
     */
    private function addBlock($title, $content, $region, $locale = 'en', $rewrites = [])
    {
        $block_model = $this->getContainer()->make(\App\Site\Models\Block::class);

        $block_model->website_id =  $this->website_id;
        $block_model->title = $title;
        $block_model->locale = $locale;
        $block_model->instance_class = \App\Site\Models\Block::class;
        $block_model->content = $content;
        $block_model->region = $region;

        $block_model->persist();

        if (!empty($rewrites)) {
            foreach ($rewrites as $rewrite) {
                $this->getDb()->createRow(
                    'block_rewrite',
                    [
                    'block_id' => $block_model->getId(),
                    'rewrite_id' => $rewrite->getId(),
                    ]
                )
                    ->save();
            }
        }

        return $block_model;
    }

    /**
     * generates an image
     *
     * @param  integer $w
     * @param  integer $h
     * @return MediaElement
     */
    private function createImage($w = 400, $h = 400)
    {
        $palette = new \Imagine\Image\Palette\RGB();
        $size  = new \Imagine\Image\Box($w, $h);

        $white = $palette->color('#ffffff', 100);
        $black = $palette->color('#000000', 100);

        $color1 = $palette->color([rand(0, 255), rand(0, 255), rand(0, 255)], 100);
        $color2 = $palette->color([rand(0, 255), rand(0, 255), rand(0, 255)], 100);
        $color3 = $palette->color([rand(0, 255), rand(0, 255), rand(0, 255)], 100);
        $color4 = $palette->color([rand(0, 255), rand(0, 255), rand(0, 255)], 100);

        $image = $this->getImagine()->create($size, $white);
        $image
            ->draw()
            ->rectangle(new \Imagine\Image\Point(1, 1), new \Imagine\Image\Point(($w/2)-1, ($h/2)-1), $color1, true)
            ->rectangle(new \Imagine\Image\Point(($w/2)+1, 1), new \Imagine\Image\Point($w-1, ($h/4)-1), $color2, true)
            ->rectangle(new \Imagine\Image\Point(1, ($h/2)+1), new \Imagine\Image\Point(($w/4)-1, $h-1), $color3, true)
            ->rectangle(new \Imagine\Image\Point(($w/2)+1, ($h/2)+1), new \Imagine\Image\Point($w-1, $h-1), $color4, true)
            ->circle(new \Imagine\Image\Point($w/2, $h/2), $h/2, $black, false, 1);


        $filename = \App\App::getDir(\App\App::MEDIA).DS.'image-'.rand().'-'.date("YmdHis").'.png';
        $image->save($filename);

        $media = $this->getContainer()->make(\App\Site\Models\MediaElement::class);
        $media->path = $filename;
        $media->filename = basename($filename);

        $finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
        $media->mimetype = finfo_file($finfo, $filename);
        finfo_close($finfo);

        $media->filesize = filesize($filename);

        $media->persist();
        return $media;
    }

    /**
     * {@inheritdocs}
     *
     * @return void
     */
    public function down()
    {
    }
}
