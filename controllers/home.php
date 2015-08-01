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
            array('src' => 'js/ajax.js'),
            array('src' => 'js/gameManager.js'),
            array('src' => 'js/game.js'),
            array('src' => 'js/scratchLoader.js'),
            array('src' => 'js/googleMapsLoader.js')
        );
    }
}
