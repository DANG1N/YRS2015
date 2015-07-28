<?php

class TemplateBlock
{
    private $text;
    private $globals;
    private $modifiers = array("var", "if", "for", "insert");

    public function __construct($text, array $globals = array())
    {
        $this->text = $text;
        $this->globals = $globals;
    }

    public function parse()
    {
        $text = $this->text;
        foreach ($this->modifiers as $modifier) {
            if (strpos($text, "{%$modifier") === false)
                continue;
            $text = preg_replace_callback(
                '#\{%' . $modifier . '(\(\d+\)|)\s+(.*?)\s*?%end' . $modifier . '\1(?!\(\d+\))\}\s*?(?:[\r\n]+)?#s',
                array($this, "parse$modifier"), $text);
        }
        $text = preg_replace_callback("#\{%\{\s*([\w\.]+)\s*\}%\}#", array($this, 'getVarFromRegex'), $text);
        return $text;
    }

    private function getVarFromRegex($matches)
    {
        return $this->getVariable($matches[1]);
    }

    private function getVariable($varname)
    {
        $parts = explode('.', $varname);
        $last = array_pop($parts);
        $arr = $this->globals;
        foreach ($parts as $part) {
            if (isset($arr[$part])) {
                if (gettype($arr[$part]) === 'array') {
                    $arr = $arr[$part];
                } elseif (gettype($arr[$part]) === 'object') {
                    $arr = (array) $arr[$part];
                } else {
                    $arr = array();
                }
                continue;
            }
            return "";
        }
        return isset($arr[$last]) ? $arr[$last] : '';
    }

    private function parseVar($m)
    {
        $matches = array();
        preg_match("/(\w+)\s*=\s*(.*)/s", $m[2], $matches);
        $this->setGlobal($matches[1], new TemplateBlock($matches[2], $this->globals));
    }

    private function parseIf($m)
    {
        $matches = array();
        $i = '\?' . ($m[1] ? str_replace(array('(', ')'), array('', ''), $m[1]) : '');
        preg_match("/(not)?\s*([\w\.]+)\s*\{$i(.*?)$i\}(?:\s*else\s*\{$i(.*?)$i\})?/s", $m[2], $matches);
        $true = $matches[1] != "not";
        $variable = $this->getVariable($matches[2]);
        $isvar = !empty($variable);
        if (($isvar && $true) || (!$isvar && !$true)) {
            $block = new TemplateBlock($matches[3], $this->globals);
        } else {
            $block = new TemplateBlock(count($matches) >= 5 ? $matches[4] : "", $this->globals);
        }
        return $block->parse();
    }

    private function parseFor($m)
    {
        $matches = array();
        preg_match("/(\w+)(?:\s*,\s*(\w+))?\s+in\s+([\w\.]+)\s*\{[\r\n]*(.*)[\r\n]*\}/s", $m[2], $matches);
        $text = "";
        if (is_array($this->getVariable($matches[3]))) {
            foreach($this->getVariable($matches[3]) as $k => $v) {
                $loopvars = array($matches[1] => $v);
                if ($matches[2]) {
                    $loopvars[$matches[1]] = $k;
                    $loopvars[$matches[2]] = $v;
                }
                $block = new TemplateBlock($matches[4], array_merge($this->globals, $loopvars));
                $text .= $block->parse();
            }
        }
        return $text;
    }

    private function parseInsert($m)
    {
        try {
            $t = Template::getTemplate($m[2], $this->globals);
            return $t->parse();
        } catch (Exception $e) {
            return '';
        }
    }

    public function setGlobal($name, $data)
    {
        if ($data instanceof TemplateBlock) {
            $this->globals[$name] = $data->parse();
        } else {
            $this->globals[$name] = $data;
        }
    }

    public function setGlobals(array $globals)
    {
        foreach($globals as $name => $data)
          $this->setGlobal($name, $data);
    }
}

class Template
{
    private static $env = array();

    public function __construct($filename, array $vars = array())
    {
        if ($filename != null) {
            if ($filename{0} == ":") {
                $text = substr($filename, 1);
            } else {
                $file = fopen($filename, "r");
                $text = fread($file, filesize($filename));
                fclose($file);
            }
        } else {
            $text = '';
        }
        $vars['env'] = self::$env;
        $this->block = new TemplateBlock($text, $vars);
    }

    public function parse(array $variables = array())
    {
        $this->block->setGlobals($variables);
        return $this->block->parse();
    }

    public static function resolveName($name)
    {
        if (count(($parts = explode(":", $name))) > 1) {
            $ns = $parts[0];
            $file = $parts[1];
        } elseif (count(($parts = explode(".", $name))) > 1) {
            $ns = $parts[1];
            $file = $parts[0];
        } else {
            throw new Exception("Invalid template name '$name'");
        }
        $ext = '.html';
        if (strpos($file, '.') !== false) {
            $ext = '';
        }
        $path = 'views/' . $ns . '/' . $file . $ext;
        if (!file_exists($path)) {
            throw new Exception("No template called '$name'");
        }
        return $path;
    }

    public static function setEnvVariable($varName, $value)
    {
        self::$env[$varName] = $value;
    }

    public static function getTemplate($name, array $vars = array())
    {
        $path = self::resolveName($name);
        return new self($path, $vars);
    }
}
