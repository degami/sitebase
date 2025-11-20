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

namespace App\Base\Blocks;

use App\App;
use App\Base\Abstracts\Blocks\BaseCodeBlock;
use App\Base\Abstracts\Controllers\BasePage;
use Degami\Basics\Html\TagElement;
use Exception;

/**
 * AI Chat Block
 */
class AIChatBlock extends BaseCodeBlock
{

    public function isCachable() : bool
    {
        return false;
    }

    protected function chatBotCss(): TagElement
    {
        return $this->containerMake(TagElement::class, ['options' => [
            'tag' => 'style',
            'text' => '
.ai-chat-launcher {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: #007bff;
    color: #fff;
    padding: 12px 18px;
    border-radius: 30px;
    cursor: pointer;
    font-size: 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    z-index: 9999;
}

.ai-chat-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    z-index: 10000;
}

.ai-chat-backdrop {
    position: absolute;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.45);
}

.ai-chat-window {
    position: absolute;
    bottom: 80px;
    right: 20px;
    width: 350px;
    height: 480px;
    background: #fff;
    border-radius: 14px;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    box-shadow: 0 5px 20px rgba(0,0,0,0.25);
}

.ai-chat-header {
    background: #007bff;
    color: #fff;
    padding: 12px;
    font-size: 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.ai-chat-header button {
    background: transparent;
    border: none;
    color: #fff;
    font-size: 22px;
    cursor: pointer;
}

.ai-chat-messages {
    flex: 1;
    padding: 10px;
    overflow-y: auto;
}

.ai-chat-msg {
    margin: 5px 0;
    max-width: 85%;
}

.ai-chat-msg.user { margin-left: auto; text-align: right; }
.ai-chat-msg.user span {
    background: #007bff;
    color: white;
}

.ai-chat-msg.ai span {
    background: #f1f1f1;
}

.ai-chat-msg span {
    display: inline-block;
    padding: 8px 12px;
    border-radius: 8px;
}

.ai-chat-input {
    display: flex;
    border-top: 1px solid #eee;
}

.ai-chat-input input {
    flex: 1;
    padding: 10px;
    border: none;
    outline: none;
}

.ai-chat-input button {
    width: 80px;
    background: #007bff;
    color: #fff;
    border: none;
    cursor: pointer;
}

.ai-chat-loading {
    padding: 8px;
    text-align: center;
    font-size: 13px;
    color: #555;
}
    
.ai-chat-intro {
    background: #f9f9f9;
    border-bottom: 1px solid #eee;
    text-align: center;
    font-size: 11px;
}
            '
        ]]);
    }

    protected function chatBotJs(): TagElement
    {
        return $this->containerMake(TagElement::class, ['options' => [
            'tag' => 'script',
            'text' => '
(function($){

    function appendMessage(role, text) {
        const msg = $("<div class=\"ai-chat-msg "+role+"\"><span>"+text+"</span></div>");
        $(\'#ai-chat-messages\').append(msg);
        $(\'#ai-chat-messages\').scrollTop(999999);
    }

    function sendMessage() {
        const text = $(\'#ai-chat-text\').val().trim();
        if (!text) return;

        $(\'#ai-chat-text\').val(\'\');
        appendMessage("user", text);

        $(\'#ai-chat-loading\').show();

        $.ajax({
            method: "POST",
            url: "/commerce/chatbot/chat",
            contentType: "application/json",
            data: JSON.stringify({ prompt: text }),
            success: function(res) {
                appendMessage("ai", res.assistantText || "('.App::getInstance()->getUtils()->translate('I got no responses to send').')");
            },
            error: function() {
                appendMessage("ai", "'.App::getInstance()->getUtils()->translate('Can\'t reply now. please try again later').'.");
            },
            complete: function() {
                $(\'#ai-chat-loading\').hide();
            }
        });
    }

    // Eventi UI
    $(document).on(\'click\', \'#ai-chat-launcher\', function(){
        $(\'#ai-chat-modal\').show();
    });

    $(document).on(\'click\', \'#ai-chat-close, .ai-chat-backdrop\', function(){
        $(\'#ai-chat-modal\').hide();
    });

    $(document).on(\'click\', \'#ai-chat-send\', sendMessage);

    $(document).on(\'keydown\', \'#ai-chat-text\', function(e){
        if (e.key === "Enter") sendMessage();
    });

})(jQuery);
'
        ]]);
    }

    protected function chatBotDialog(?BasePage $current_page = null): string
    {
        $title = App::getInstance()->getUtils()->translate('Ecommerce AI Companion', locale: $current_page?->getCurrentLocale());
        $intro = App::getInstance()->getUtils()->translate('Ask me to find products, add them to your cart, and assist you with your shopping needs!', locale: $current_page?->getCurrentLocale());

        return <<<HTML
<div id="ai-chat-modal" class="ai-chat-modal" style="display:none;">
    <div class="ai-chat-backdrop"></div>
    <div class="ai-chat-window">
        <div class="ai-chat-header">
            <span>$title</span>
            <button id="ai-chat-close">×</button>
        </div>
        <div class="ai-chat-intro">$intro</div>
        <div class="ai-chat-messages" id="ai-chat-messages"></div>
        <div class="ai-chat-loading" id="ai-chat-loading" style="display:none;">Sto pensando...</div>
        <div class="ai-chat-input">
            <input id="ai-chat-text" type="text" placeholder="Scrivi un messaggio..." />
            <button id="ai-chat-send">Invia</button>
        </div>
    </div>
</div>
HTML;
    }

    protected function chatBotLaucher() : TagElement
    {
        return $this->containerMake(TagElement::class, ['options' => [
            'tag' => 'div',
            'id' => 'ai-chat-launcher',
            'attributes' => [
                'class' => 'ai-chat-launcher',
            ],
            'text' => '💬 Chat'
        ]]);
    }

    public function renderHTML(?BasePage $current_page = null, array $data = []): string
    {
        if (App::getInstance()->getEnvironment()->getVariable('ENABLE_COMMERCE', false) === false) {
            return "";
        }

        if (!$current_page->hasLoggedUser()) {
            return "";
        }

        try {
            return $this->chatBotCss() .
                   $this->chatBotJs() .
                   $this->chatBotLaucher() .
                   $this->chatBotDialog($current_page);
        } catch (Exception $e) {
            return "";
        }
    }
}
