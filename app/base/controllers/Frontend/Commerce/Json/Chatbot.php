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

namespace App\Base\Controllers\Frontend\Commerce\Json;

use App\App;
use App\Base\Abstracts\Controllers\BaseJsonPage;
use App\Base\AI\Actions\GraphQLExecutor;
use App\Base\AI\Actions\GraphQLSchemaProvider;
use App\Base\AI\Actions\Orchestrator;
use App\Base\AI\Flows\EcommerceFlow;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use FastVolt\Helper\Markdown;
use Throwable;

/**
 * Ecommerce Chatbot
 */
class Chatbot extends BaseJsonPage
{
    /*
    $.ajax({
    'method': 'POST',
    'url': '/commerce/chatbot/chat',
    'contentType': 'application/json',
    'data': JSON.stringify({'prompt': 'Trova il prodotto meno caro e aggiungilo al carrello'})
    }) 
    */

    /**
     * determines if route is available for router
     * 
     * @return bool
     */
    public static function isEnabled() : bool 
    {
        return App::getInstance()->getEnvironment()->getVariable('ENABLE_COMMERCE', false); // && App::getInstance()->getAuth()->getCurrentUser();
    }

    /**
     * return route path
     *
     * @return array
     */
    public static function getRoutePath(): array
    {
        return [
            'frontend.commerce.chatbot' => '/chatbot/chat', 
        ];
    }

    public static function getRouteGroup(): ?string
    {
        return '/commerce';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getAccessPermission(): string
    {
        return 'view_site';
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function getJsonData(): array
    {
        // start session for chatbot history
        session_start();

        $endpoint = rtrim($this->getUrl('frontend.root'), '/') . '/graphql';
        // internally we can use http to speed things up
        $endpoint = str_replace('https://', 'http://', $endpoint);

        $authToken = $this->getRequest()->cookies->get('Authorization', '');
        if (empty($authToken)) {
            throw new Exception("Missing auth token cookie");
        }

        $llm = $this->getAI()->getAIModel($this->getRequest()->query->get('ai', 'googlegemini'));
        $gql = new GraphQLExecutor($endpoint, 'Bearer '.$authToken);
        $flow = new EcommerceFlow(new GraphQLSchemaProvider($gql), $gql);

        $orchestrator = new Orchestrator($llm, $flow);

        if (!isset($_SESSION['ecommerce_chatbot_history'])) {
            $_SESSION['ecommerce_chatbot_history'] = [];
        }

        if ($this->getRequest()->query->get('reset_history', false)) {
            $_SESSION['ecommerce_chatbot_history'] = [];
        }

        $response = $orchestrator->runFlow($this->getPrompt($this->getRequest()), $_SESSION['ecommerce_chatbot_history']);

        try {
            $markdown = new Markdown(); // or Markdown::new()
            $markdown->setContent($response['assistantText']);
            $response['assistantText'] = $markdown->getHtml();
        } catch (Throwable $e) {}

        if ($this->getEnvironment()->canDebug()){
            return $response;
        }

        return [
            'assistantText' => $response['assistantText'] ?? '',
        ];
    }

    /**
     * @return string|null
     */
    protected function getPrompt(Request $request) : ?string
    {
        $content = json_decode($request->getContent(), true);
        if (is_array($content) && !empty($content['prompt'])) {
            return (string) $content['prompt'];
        }

        return null;
    }
}
