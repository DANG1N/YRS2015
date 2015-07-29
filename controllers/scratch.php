<?php

class Scratch
{
    public function project($projectId)
    {
        echo file_get_contents("http://scratch.mit.edu/projects/embed/{$projectId}/?autostart=true");
    }
}
