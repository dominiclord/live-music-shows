<?php

namespace Slim\Views;

use Psr\Http\Message\ResponseInterface;

/**
 * Mustache view
 *
 * The Mustache view is a custom View class that renders templates using the Mustache
 * template language (https://github.com/bobthecow/mustache.php).
 *
 * Two fields that you, the developer, will need to change are:
 * - parserDirectory
 * - parserOptions
 */
class Mustache
{
    /**
     * @var string The path to the Mustache code directory WITHOUT the trailing slash
     */
    public $parserDirectory = null;

    /**
     * @var array The options for the Mustache engine, see
     * https://github.com/bobthecow/mustache.php/wiki
     */
    public $parserOptions = array();

    /**
     * @var Mustache_Engine The Mustache engine for rendering templates.
     */
    private $parserInstance = null;

    /**
     * Default view variables
     *
     * @var array
     */
    protected $defaultVariables = [];

    /**
     * Create new Mustache view
     *
     * @param string $path     Path to templates directory
     * @param array  $settings Mustache environment settings
     */
    public function __construct($path, $settings = [])
    {
        $this->templatesDirectory = $path;
        $this->parserOptions = $settings;
    }

    /**
     * Fetch rendered template
     *
     * @param  string $template Template pathname relative to templates directory
     * @param  array  $data     Associative array of template variables
     *
     * @return string
     */
    public function fetch($template, $data = [])
    {
        $data = array_merge($this->defaultVariables, $data);

        $env = $this->getInstance();
        $parser = $env->loadTemplate($template);

        return $parser->render($data);

        //return $this->environment->loadTemplate($template)->render($data);
    }
    /**
     * Output rendered template
     *
     * @param ResponseInterface $response
     * @param  string $template Template pathname relative to templates directory
     * @param  array $data Associative array of template variables
     * @return ResponseInterface
     */
    public function render(ResponseInterface $response, $template, $data = [])
    {
        $response->getBody()->write($this->fetch($template, $data));
        return $response;
    }

    /**
     * Creates new Mustache_Engine if it doesn't already exist, and returns it.
     *
     * @return \Mustache_Engine
     */
    public function getInstance()
    {
        if (!$this->parserInstance) {
            /**
             * Check if Mustache_Autoloader class exists
             * otherwise include and register it.
             */
            if (!class_exists('\Mustache_Autoloader')) {
                require_once $this->parserDirectory . '/Autoloader.php';
                \Mustache_Autoloader::register();
            }

            $parserOptions = array(
                'loader' => new \Mustache_Loader_FilesystemLoader($this->templatesDirectory),
            );

            // Check if the partials directory exists, otherwise Mustache will throw a exception
            if (is_dir($this->templatesDirectory.'/partials')) {
                $parserOptions['partials_loader'] = new \Mustache_Loader_FilesystemLoader($this->templatesDirectory.'/partials');
            }

            $parserOptions = array_merge((array)$parserOptions, (array)$this->parserOptions);

            $this->parserInstance = new \Mustache_Engine($parserOptions);
        }

        return $this->parserInstance;
    }
}
