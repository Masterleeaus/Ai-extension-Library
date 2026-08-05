<?php

declare(strict_types=1);


namespace App\Extensions\Chatbot\System\Services\Builder;

class MobilekitComponentAdapter
{
    protected const COMPONENT_LIBRARY = [
        'appHeader' => [
            'name' => 'Application Header',
            'category' => 'layout',
            'html' => '<div class="appHeader bg-primary text-light"><div class="left"></div><div class="pageTitle">Header</div><div class="right"></div></div>',
            'css' => ['appHeader', 'bg-primary', 'text-light'],
            'props' => [
                'title' => ['type' => 'string', 'default' => 'Header'],
                'background' => ['type' => 'color', 'default' => '#667eea'],
                'showBack' => ['type' => 'boolean', 'default' => false],
            ],
        ],
        'appCapsule' => [
            'name' => 'App Content Container',
            'category' => 'layout',
            'html' => '<div id="appCapsule"><div class="section"><div class="wide-block">Content goes here</div></div></div>',
            'css' => ['appCapsule', 'section', 'wide-block'],
            'props' => [
                'padding' => ['type' => 'string', 'default' => 'md'],
            ],
        ],
        'bottomMenu' => [
            'name' => 'Bottom Navigation Menu',
            'category' => 'navigation',
            'html' => <<<HTML
<div class="bottomMenu">
    <a href="#" class="menu-item">
        <ion-icon name="home-outline"></ion-icon>
        <span class="menu-label">Home</span>
    </a>
    <a href="#" class="menu-item">
        <ion-icon name="chatbubbles-outline"></ion-icon>
        <span class="menu-label">Chat</span>
    </a>
    <a href="#" class="menu-item">
        <ion-icon name="help-circle-outline"></ion-icon>
        <span class="menu-label">Help</span>
    </a>
</div>
HTML,
            'css' => ['bottomMenu', 'menu-item'],
            'props' => [
                'items' => ['type' => 'array', 'default' => ['Home', 'Chat', 'Help']],
            ],
        ],
        'button' => [
            'name' => 'Button Component',
            'category' => 'input',
            'html' => '<button class="btn btn-primary">Click me</button>',
            'css' => ['btn', 'btn-primary'],
            'props' => [
                'text' => ['type' => 'string', 'default' => 'Click me'],
                'variant' => ['type' => 'select', 'default' => 'primary', 'options' => ['primary', 'secondary', 'success', 'danger', 'warning', 'info']],
                'size' => ['type' => 'select', 'default' => 'md', 'options' => ['sm', 'md', 'lg']],
                'disabled' => ['type' => 'boolean', 'default' => false],
            ],
        ],
        'card' => [
            'name' => 'Card Component',
            'category' => 'content',
            'html' => <<<HTML
<div class="card">
    <div class="card-header">Card Title</div>
    <div class="card-body">Card content goes here</div>
    <div class="card-footer">Footer</div>
</div>
HTML,
            'css' => ['card', 'card-header', 'card-body', 'card-footer'],
            'props' => [
                'title' => ['type' => 'string', 'default' => 'Card Title'],
                'shadow' => ['type' => 'boolean', 'default' => true],
            ],
        ],
        'form' => [
            'name' => 'Form Group',
            'category' => 'input',
            'html' => <<<HTML
<div class="form-group">
    <label>Label</label>
    <input type="text" class="form-control" placeholder="Enter text">
</div>
HTML,
            'css' => ['form-group', 'form-control'],
            'props' => [
                'label' => ['type' => 'string', 'default' => 'Label'],
                'placeholder' => ['type' => 'string', 'default' => 'Enter text'],
                'type' => ['type' => 'select', 'default' => 'text', 'options' => ['text', 'email', 'password', 'number', 'tel']],
            ],
        ],
        'badge' => [
            'name' => 'Badge Component',
            'category' => 'content',
            'html' => '<span class="badge bg-primary">Badge</span>',
            'css' => ['badge', 'bg-primary'],
            'props' => [
                'text' => ['type' => 'string', 'default' => 'Badge'],
                'color' => ['type' => 'select', 'default' => 'primary', 'options' => ['primary', 'secondary', 'success', 'danger', 'warning', 'info']],
            ],
        ],
        'alert' => [
            'name' => 'Alert Component',
            'category' => 'content',
            'html' => '<div class="alert alert-primary">Alert message</div>',
            'css' => ['alert', 'alert-primary'],
            'props' => [
                'message' => ['type' => 'string', 'default' => 'Alert message'],
                'type' => ['type' => 'select', 'default' => 'primary', 'options' => ['primary', 'secondary', 'success', 'danger', 'warning', 'info']],
                'dismissible' => ['type' => 'boolean', 'default' => false],
            ],
        ],
        'toast' => [
            'name' => 'Toast Notification',
            'category' => 'feedback',
            'html' => '<div class="toast"><div class="toast-body">Toast message</div></div>',
            'css' => ['toast', 'toast-body'],
            'props' => [
                'message' => ['type' => 'string', 'default' => 'Toast message'],
                'position' => ['type' => 'select', 'default' => 'bottom', 'options' => ['top', 'bottom', 'center']],
                'duration' => ['type' => 'number', 'default' => 3000],
            ],
        ],
        'tabs' => [
            'name' => 'Tabbed Content',
            'category' => 'navigation',
            'html' => <<<HTML
<ul class="nav nav-tabs">
    <li class="nav-item"><a class="nav-link active">Tab 1</a></li>
    <li class="nav-item"><a class="nav-link">Tab 2</a></li>
</ul>
HTML,
            'css' => ['nav', 'nav-tabs', 'nav-item', 'nav-link'],
            'props' => [
                'tabs' => ['type' => 'array', 'default' => ['Tab 1', 'Tab 2']],
            ],
        ],
        'modal' => [
            'name' => 'Modal Dialog',
            'category' => 'overlay',
            'html' => <<<HTML
<div class="modal" id="modalExample">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">Modal Title</div>
            <div class="modal-body">Modal content</div>
            <div class="modal-footer"><button class="btn btn-primary">Close</button></div>
        </div>
    </div>
</div>
HTML,
            'css' => ['modal', 'modal-dialog', 'modal-content'],
            'props' => [
                'title' => ['type' => 'string', 'default' => 'Modal Title'],
                'size' => ['type' => 'select', 'default' => 'md', 'options' => ['sm', 'md', 'lg', 'xl']],
            ],
        ],
        'listview' => [
            'name' => 'List View',
            'category' => 'content',
            'html' => <<<HTML
<ul class="listview">
    <li class="item-inner">
        <div class="in">List Item 1</div>
    </li>
    <li class="item-inner">
        <div class="in">List Item 2</div>
    </li>
</ul>
HTML,
            'css' => ['listview', 'item-inner'],
            'props' => [
                'items' => ['type' => 'array', 'default' => ['Item 1', 'Item 2']],
                'clickable' => ['type' => 'boolean', 'default' => false],
            ],
        ],
        'chatBubble' => [
            'name' => 'Chat Message Bubble',
            'category' => 'content',
            'html' => <<<HTML
<div class="chat-message">
    <div class="message-bubble user">
        <div class="message-avatar"></div>
        <div class="message-text">Hello! How can I help?</div>
        <div class="message-time">10:30 AM</div>
    </div>
</div>
HTML,
            'css' => ['chat-message', 'message-bubble', 'message-text'],
            'props' => [
                'message' => ['type' => 'string', 'default' => 'Hello! How can I help?'],
                'sender' => ['type' => 'select', 'default' => 'bot', 'options' => ['bot', 'user']],
                'timestamp' => ['type' => 'string', 'default' => now()],
            ],
        ],
    ];

    public function getComponent(string $componentType): ?array
    {
        return self::COMPONENT_LIBRARY[$componentType] ?? null;
    }

    public function getAllComponents(): array
    {
        return self::COMPONENT_LIBRARY;
    }

    public function getComponentsByCategory(string $category): array
    {
        return array_filter(self::COMPONENT_LIBRARY, function ($component) use ($category) {
            return $component['category'] === $category;
        });
    }

    public function getCategories(): array
    {
        $categories = array_unique(array_map(fn($c) => $c['category'], self::COMPONENT_LIBRARY));
        return array_values($categories);
    }

    public function renderComponent(string $componentType, array $props = []): string
    {
        $component = $this->getComponent($componentType);

        if (!$component) {
            return '';
        }

        $html = $component['html'];

        // Replace simple prop placeholders
        foreach ($props as $key => $value) {
            $html = str_replace("{{$key}}", $value, $html);
        }

        return $html;
    }

    public function getComponentClasses(string $componentType): array
    {
        $component = $this->getComponent($componentType);
        return $component['css'] ?? [];
    }

    public function getComponentProps(string $componentType): array
    {
        $component = $this->getComponent($componentType);
        return $component['props'] ?? [];
    }

    public function buildThemeFromMobilekit(array $config): string
    {
        $primary = $config['colors']['primary'] ?? '#667eea';
        $secondary = $config['colors']['secondary'] ?? '#764ba2';
        $accent = $config['colors']['accent'] ?? '#4CAF50';
        $background = $config['header_background']['value'] ?? 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';

        return <<<CSS
:root {
    --bs-primary: $primary;
    --bs-secondary: $secondary;
    --bs-success: $accent;
    --chatbot-header-bg: $background;
}

/* Mobilekit Bootstrap overrides */
.appHeader {
    background: var(--chatbot-header-bg);
    padding: 15px;
}

.btn-primary {
    background-color: var(--bs-primary);
    border-color: var(--bs-primary);
}

.btn-primary:hover {
    background-color: var(--bs-secondary);
    border-color: var(--bs-secondary);
}

.badge {
    padding: 5px 10px;
    border-radius: 12px;
}

.card {
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.chat-message {
    padding: 10px;
    margin: 8px 0;
}

.message-bubble {
    padding: 12px 16px;
    border-radius: 18px;
    max-width: 80%;
}

.message-bubble.bot {
    background-color: #f0f0f0;
    color: #333;
}

.message-bubble.user {
    background-color: var(--bs-primary);
    color: white;
    margin-left: auto;
}

.bottomMenu {
    display: flex;
    justify-content: space-around;
    border-top: 1px solid #eee;
    padding: 10px 0;
}

.menu-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 5px;
    color: var(--bs-primary);
    text-decoration: none;
}
CSS;
    }
}
