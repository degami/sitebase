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

namespace App\Base\Traits;

use \App\Base\Abstracts\Models\BaseModel;
use \App\Site\Models\User;
use Degami\Basics\Exceptions\BasicException;
use Exception;

/**
 * Frontend pages Trait
 */
trait FrontendTrait
{
    use PageTrait;

    /**
     * @var array template data
     */
    protected $template_data = [];


    /**
     * @var User current user model
     */
    protected $current_user_model;

    /**
     * gets route group
     *
     * @return string
     */
    public static function getRouteGroup(): ?string
    {
        return '';
    }

    /**
     * {@inheritdocs}
     *
     * @return array
     */
    protected function getTemplateData(): array
    {
        return $this->template_data;
    }

    /**
     * sets object to show
     *
     * @param BaseModel $object
     * @return self
     */
    protected function setObject(BaseModel $object)
    {
        $this->template_data['object'] = $object;
        return $this;
    }

    /**
     * gets object to show
     *
     * @return BaseModel|null
     */
    protected function getObject(): ?BaseModel
    {
        return $this->template_data['object'] ?? null;
    }


    /**
     * {@inheritdocs}
     *
     * @return array
     * @throws BasicException
     */
    public function getTranslations(): array
    {
        return array_map(
            function ($el) {
                return $this->getWebRouter()->getBaseUrl() . $el;
            },
            $this->getContainer()->call([$this->getObject(), 'getTranslations'])
        );
    }


    /**
     * {@inheritdocs}
     *
     * @return string
     * @throws BasicException
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public function getCurrentLocale(): string
    {
        if (isset($this->template_data['object']) && ($this->template_data['object'] instanceof BaseModel) && $this->template_data['object']->isLoaded()) {
            if ($this->template_data['object']->getLocale()) {
                return $this->getApp()->setCurrentLocale($this->template_data['object']->getLocale())->getCurrentLocale();
            }
        }

        return $this->getApp()->setCurrentLocale(parent::getCurrentLocale())->getCurrentLocale();
    }

    /**
     *
     * checks user credentials
     *
     * @return boolean
     * @throws BasicException
     */
    protected function checkCredentials(): bool
    {
        try {
            if ($this->getTokenData()) {
                $this->current_user_model = $this->getCurrentUser();
                return $this->current_user_model->checkPermission('view_logged_site');
            }
        } catch (Exception $e) {
            $this->getUtils()->logException($e);
        }

        return false;
    }
}
