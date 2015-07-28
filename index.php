<?php

set_error_handler('error_handler');
function error_handler($severity, $message, $file, $line, $args)
{
    if (strpos($message, 'Calling MySQL->') === false)
        throw new ErrorException($message, 1, $severity, $file, $line);
}

require "vendor/autoload.php";

$url = isset($_GET['url']) ? $_GET['url'] : 'home/index';

$parts = explode('/', $url);

if (!isset($parts[0]) || $parts[0] == null)
    $parts[0] = 'home';

$options = array();
if (strpos($parts[0], '=') !== false) {
    foreach (explode(',', array_shift($parts)) as $option) {
        list($key, $value) = explode('=', $option);
        $options[$key] = $value;
    }
}

if (!isset($parts[1]) || $parts[1] == null)
    $parts[1] = 'index';

class MapDef
{
    private $minParams;
    private $verbs = 'all';
    private $actions;
    private $curr_type;
    private $sub_controller = false;

    public function __construct($incomming_action)
    {
        $this->curr_type = 'default';
        $this->actions = array($this->curr_type => $incomming_action);
        $this->minParams = array($this->curr_type => 0);
    }

    public function onto($action)
    {
        $this->actions[$this->curr_type] = $action;
        $this->curr_type = 'default';
        return $this;
    }

    public function when($verb)
    {
        $this->curr_type = strtoupper(str_replace('ing', '', $verb));
        return $this;
    }

    public function getAction($verb = 'GET')
    {
        if (isset($this->actions[$verb]))
            return $this->actions[$verb];
        return $this->actions['default'];
    }

    public function setMinParams($min)
    {
        $this->minParams[$this->curr_type] = $min;
        return $this;
    }

    public function getMinParams($verb = 'GET')
    {
        if (isset($this->minParams[$verb]))
            return $this->minParams[$verb];
        return $this->minParams['default'];
    }

    public function setHTTPVerbs()
    {
        $this->verbs = func_get_args();
        return $this;
    }

    public function verbIsAllowed($verb)
    {
        return $this->verbs === 'all' || in_array($verb, $this->verbs);
    }

    public function remove()
    {
        $this->actions = array('default' => null);
    }

    public function useSubController()
    {
        $this->sub_controller = true;
        return $this;
    }

    public function hasSubController()
    {
        return $this->sub_controller;
    }

    public function setProxyMethod($method)
    {
        $this->actions['proxyMethod'] = $method;
    }

    public function isProxied()
    {
        return isset($this->actions['proxyMethod']);
    }
}

class ReMapper
{
    private $mappings;
    private $map_all;
    private $map_404;

    public function __construct()
    {
        $this->mappings = array();
        $this->map_all = false;
        $this->map_404 = false;
    }

    public function map($action)
    {
        $action = strtolower($action);
        if (!isset($this->mappings[$action])) {
            $this->mappings[$action] = new MapDef($action);
        }
        return $this->mappings[$action];
    }

    public function mapAll($action)
    {
        $this->map_all = new MapDef($action);
        $this->map_all->onto($action);
    }

    public function set404($action)
    {
        $this->map_404 = new MapDef($action);
        $this->map_404->onto($action);
    }

    public function getMappingFor($action)
    {
        if ($this->map_all) {
            return $this->map_all;
        }
        return $this->map($action);
    }

    public function get404()
    {
        if ($this->map_404) {
            return $this->map_404->getAction();
        }
        return null;
    }

    public function proxyActions()
    {
        $definitions = array();
        foreach (func_get_args() as $action) {
            $definitions[] = $this->map($action);
        }
        return new MapProxy($definitions);
    }
}

class MapProxy
{
    public function __construct(array $definitions)
    {
        $this->defs = $definitions;
    }

    public function to($proxyMethod)
    {
        foreach ($this->defs as $mapDef) {
            $mapDef->setProxyMethod($proxyMethod);
        }
    }
}

class ProxiedAction
{
    public function __construct($controller, $action, array $args)
    {
        $this->controller = $controller;
        $this->action = $action;
        $this->args = $args;
    }

    public function run()
    {
        call_user_func_array(array($this->controller, $this->action), $this->args);
    }
}

class ProviderException extends Exception
{
    public function __construct($className, $message, Exception $prev = null)
    {
        parent::__construct("Unable to provide the class {$className}, {$message}", 1, $prev);
    }
}

class Provider
{
    private $dir;
    private $type;

    public function __construct($dir, $type)
    {
        $this->dir = $dir;
        $this->type = $type;
    }

    public function load($name)
    {
        $args = func_get_args();
        array_shift($args);
        try {
            include $this->dir . '/' . strtolower($name) . '.php';
        } catch (ErrorException $e) {
            if ($e->getCode() === E_ERROR) {
                throw new ProviderException($name, 'php file does not exist', $e);
            }
            throw $e;
        }
        if ($this->type === 'model' && (!isset($args[0]) || $args[0] != false)) {
            array_splice($args, 0, 1, array(MySQL::getInstance()));
        }
        try {
            $class = new ReflectionClass($name);
            return $class->newInstanceArgs($args);
        } catch (ReflectionException $e) {
            switch ($e->getCode()) {
                case -1:
                    throw new ProviderException($name, 'class not found (But the file was)', $e);
                case 0:
                    throw new ProviderException($name, 'Bad class constructor', $e);
                default:
                    throw $e;
            }
        }
    }
}

class ViewLoader
{
    private $controller;
    private $action;

    public function __construct($controller, $action)
    {
        $this->controller = $controller;
        $this->action = $action;
    }

    public function load($name = null)
    {
        if ($name === null) {
            $name = "{$this->controller}:{$this->action}";
        }
        return new BoundView(Template::getTemplate($name));
    }
}
class BoundView
{
    public function __construct(Template $template)
    {
        $this->template = $template;
        $this->args = array();
    }

    public function enableBaking()
    {
        return $this;
    }

    public function withConstant($name, $value)
    {
        $this->args[$name] = $value;
        return $this;
    }

    public function render()
    {
        echo $this->template->parse($this->args);
    }
}

function showError($code, $message, $fileType = 'html')
{
    http_response_code($code);
    $messages = array(400 => 'Bad Request', 403 => 'Forbidden', 404 => 'Not Found', 406 => 'Not Acceptable', 500 => 'Internal Server Error');
    $httpMessage = isset($messages[$code]) ? $messages[$code] : 'HTTP Error';
    if ($fileType == 'json') {
        App\Util\JSONOutput::exitJSONException(new Exception($message), $code);
    }
    try {
        die(Template::getTemplate("_errors:$code")->parse(array(
            'error_message' => $message,
            'http_message' => $httpMessage,
            'title' => $httpMessage
        )));
    } catch (Exception $e) {
        echo <<<HTML
<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html><head>
<title>{$code} {$httpMessage}</title>
</head><body>
<h1>{$httpMessage}</h1>
<p>{$message}</p>
<p>Additionally, a {$code} handler page does not exist.</p>
</body></html>

HTML;
        die;
    }
}

function load($controller_name, array $parts, array $options)
{
    // Do not differentiate between action/a/b/c and action/a/b/c/
    $endWithSlash = false;
    if ($parts[count($parts) - 1] === '') {
        array_pop($parts);
        $endWithSlash = true;
    }
    if (!file_exists("controllers/{$controller_name}.php")) {
        showError(404, "Unknown controller '{$controller_name}'");
    }

    require "controllers/{$controller_name}.php";
    if (!class_exists($controller_name)) {
        showError(500, "Missing controller class for '{$controller_name}'");
    }
    $mapper = new ReMapper;
    $controller = new $controller_name($mapper, $options);
    $controller->models = new Provider('models', 'model');
    $info = pathinfo(array_shift($parts));
    $in_action = $info['filename'];
    $controller->fileType = isset($info['extension']) ? $info['extension'] : 'html';
    $controller->endWithSlash = $endWithSlash;
    $controller->view = new ViewLoader($controller_name, $in_action);
    $verb = $_SERVER['REQUEST_METHOD'];
    $map = $mapper->getMappingFor($in_action);
    $action = $map->getAction($verb);
    if ($action === null) {
        showError(403, "Inaccessible URL {$controller_name}/{$in_action}", $controller->fileType);
    }
    if ($map->hasSubController()) {
        if (!isset($parts[0]) || $parts[0] == null)
            $parts[0] = 'index';
        return load("{$controller_name}_{$action}", $parts, $options);
    }
    $minargs = $map->getMinParams($verb);
    if (!method_exists($controller, $action)) {
        $notFoundAction = $mapper->get404();
        if ($notFoundAction === null || !$controller->$notFoundAction($action)) {
            showError(404, "Unknown URL '{$controller_name}/{$action}'");
        }
        die;
    }
    if (count($parts) < $minargs) {
        showError(400, "Not enough arguments given to {$action} (expecting {$minargs}, got " . count($parts) . ")", $controller->fileType);
    }
    if (!$map->verbIsAllowed($verb)) {
        showError(403, "The HTTP verb '{$verb}' is not allowed for {$controller_name}/{$action}", $controller->fileType);
    }
    try {
        if ($map->isProxied()) {
            call_user_func(array($controller, $map->getAction('proxyMethod')), new ProxiedAction($controller, $action, $parts));
        } else {
            call_user_func_array(array($controller, $action), $parts);
        }
    } catch (Exception $e) {
        $method = new ReflectionMethod($controller, $action);
        if (!$method->isPublic()) {
            showError(403, "Inaccessible URL {$controller_name}/{$in_action}", $controller->fileType);
        }
        http_response_code(500);
        throw new Exception('Relayed DEBUG exception', 0, $e);
        showError(500, "An uncaught exception was thrown (" . get_class($e) . ")<br>Exception Message: {$e->getMessage()}", $controller->fileType);
    }
}
$controller_name = array_shift($parts);
load($controller_name, $parts, $options);
?>
