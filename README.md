# php-lambda-runtime-api-symfony-handler

Symfony handler for the topic-advisor/php-lambda-runtime-api library

# Installation

Use composer: `composer require topic-advisor/php-lambda-runtime-api-symfony-handler`

# Usage

```php
#!/opt/bin/php
<?php

require __DIR__ . '/vendor/autoload.php';

$env = getenv('APP_ENV') ?: 'dev';
$debug = getenv('APP_DEBUG') !== false ? ((bool) getenv('APP_DEBUG')) : false;

if ($debug) {
    umask(0000);
    Symfony\Component\Debug\Debug::enable();
}

$kernel = new AppKernel($env, $debug);
$kernel->boot();

$loop = new TopicAdvisor\Lambda\RuntimeApi\RuntimeApiLoop();
$loop
    ->setHandlers([
        new TopicAdvisor\Lambda\Symfony\Handler\Http\RequestHandler($kernel),
    ])
    ->run();
```