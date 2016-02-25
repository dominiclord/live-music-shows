<?php

use \Shows\Scraper\AtomheartScraper;
use \Shows\Scraper\BstbScraper;
use \Shows\Scraper\CoronatheatreScraper;
use \Shows\Scraper\GreenlandproductionsScraper;
use \Shows\Scraper\TixzaScraper;

$autoloader = require_once '../vendor/autoload.php';
$autoloader->add('Shows\\', __DIR__.'/../src/');

require_once('../mustache-view.php');

$container_options = [
    'settings' => [
        'displayErrorDetails' => true
    ],
    'view' => function ($c) {
        $view = new \Slim\Views\Mustache('../templates');
        return $view;
    }
];
$container = new \Slim\Container($container_options);

$app = new \Slim\App($container);

$app->get('/atomheart', function ($request, $response, $args) {
    $scraper = new \Shows\Scraper\AtomHeartScraper();
    $args = [
        'nodes' => $scraper->events()
    ];
    return $this->view->render($response, 'index', $args);
});

$app->get('/bstb', function ($request, $response, $args) {
    $scraper = new \Shows\Scraper\BstbScraper();
    $args = [
        'nodes' => $scraper->events()
    ];
    return $this->view->render($response, 'index', $args);
});

$app->get('/corona', function ($request, $response, $args) {
    $scraper = new \Shows\Scraper\CoronatheatreScraper();
    $args = [
        'nodes' => $scraper->events()
    ];
    return $this->view->render($response, 'index', $args);
});

$app->get('/greenland', function ($request, $response, $args) {
    $scraper = new \Shows\Scraper\GreenlandproductionsScraper();
    $args = [
        'nodes' => $scraper->events()
    ];
    return $this->view->render($response, 'index', $args);
});

$app->get('/tixza', function ($request, $response, $args) {
    $scraper = new \Shows\Scraper\TixzaScraper();
    $args = [
        'nodes' => $scraper->events()
    ];
    return $this->view->render($response, 'index', $args);
});

$app->run();