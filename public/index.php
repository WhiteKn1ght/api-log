<?php
declare(strict_types = 1);

// Delegate static file requests back to the PHP built-in webserver
if (PHP_SAPI === 'cli-server' && $_SERVER['SCRIPT_FILENAME'] !== __FILE__) {
    return false;
}

chdir(dirname(__DIR__));
require 'vendor/autoload.php';

/**
 * Self-called anonymous function that creates its own scope and keep the global namespace clean.
 */
(function () {
    /** @var Array $config */
    $config = require 'config/config.php';
    try {
        list (,$class, $method, $arg1, $arg2) = explode("/", $_GET['_url']);
        if (empty($class) || empty($method)) {
            throw new \Exception('Wrong request params');
        }
        $className = '\\App\\'.$class.'\\Handler';
        if(!class_exists($className)) {
            throw new \Exception('Wrong class');
        }
        $app = new $className($config);
        $method = explode("-", $method);
        foreach ($method as $k => &$part) {
            if($k != 0) {
                $part = ucfirst($part);
            }
        }
        $method = implode('', $method);

        if(!method_exists($app, $method) || !is_callable(array($app, $method))) {
            throw new \Exception('Wrong method');
        }
        $app->$method();
    } catch (\Exception $e) {
        echo json_encode([
            'status' => 'fail',
            'response' => $e->getMessage()
        ]);
        die();
    }
})();
