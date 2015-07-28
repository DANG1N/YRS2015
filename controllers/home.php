<?php

class Home
{
    public function index()
    {
        $this->view->load()
        ->enableBaking()
        ->withConstant('scripts', $this->getScripts())
        ->render();
    }

    private function getScripts()
    {
        return array(
            array('src' => 'js/_libs/melonJS-2.1.1.js'),
            array('src' => 'js/_libs/plugins/debug/debugPanel.js'),
            array('src' => 'js/game.js'),
            array('src' => 'js/_shared/resources.js'),
            array('src' => 'js/_shared/entities/entities.js'),
            array('src' => 'js/_shared/entities/HUD.js'),
            array('src' => 'js/_shared/screens/title.js'),
            array('src' => 'js/_shared/screens/play.js')
        );
    }
}
