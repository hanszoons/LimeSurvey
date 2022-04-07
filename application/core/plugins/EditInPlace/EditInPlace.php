<?php

use LimeSurvey\PluginManager\PluginBase;

/**
 */
class EditInPlace extends PluginBase
{
    protected $storage = 'DbStorage';
    protected static $description = 'Edit-in-place in survey preview';
    protected static $name = 'EditInPlace';

    public function init()
    {
        $this->subscribe('beforeSurveyPage');
    }

    public function beforeSurveyPage()
    {
        $event = $this->getEvent();
        $event->set('template', __DIR__ . '/moo.html');
    }
}
