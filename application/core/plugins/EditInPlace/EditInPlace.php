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
        $this->subscribe('newDirectRequest');
    }

    public function beforeSurveyPage()
    {
        $event = $this->getEvent();
        $surveyId = $event->get('surveyId');
        $survey = Survey::model()->findByPk($surveyId);
        // TODO: Check edit permission for survey
        if (!empty($survey) && $survey->active === 'N') {
            // Register React dev environment for edit-in-place in preview
            // @see https://reactjs.org/docs/add-react-to-a-website.html#quickly-try-jsx
            // @see https://raw.githubusercontent.com/reactjs/reactjs.org/main/static/html/single-file-example.html
            // @todo Not recommended for production use (but kind of OK since traffic will be low)
            App()->getClientScript()->registerScriptFile('https://unpkg.com/react@18/umd/react.development.js');
            App()->getClientScript()->registerScriptFile('https://unpkg.com/react-dom@18/umd/react-dom.development.js');
            App()->getClientScript()->registerScriptFile('https://unpkg.com/@babel/standalone/babel.min.js');
            $saveUrl = Yii::app()->createUrl(
                'admin/pluginhelper',
                array(
                    'sa' => 'sidebody',
                    'plugin' => get_class($this),
                    'method' => 'actionSave',
                    'surveyId' => $surveyId
                )
            );
            $tokenName = Yii::app()->request->csrfTokenName;
            $csrfToken = Yii::app()->request->csrfToken;

            App()->getClientScript()->registerScript(
                "EditInPlaceBaseGlobalData",
                <<<JS
var editInPlaceGlobalData = {
    editInPlaceBaseUrl: "$saveUrl",
    csrfTokenName: "$tokenName",
    csrfToken: "$csrfToken"
};
JS
,
                CClientScript::POS_BEGIN
            );

            $jsUrl = Yii::app()->assetManager->publish(dirname(__FILE__) . '/js/editinplace.js');
            App()->getClientScript()->registerScriptFile($jsUrl, null, ['type' => 'text/babel']);
        }
    }

    public function newDirectRequest()
    {
        if($this->event->get('target') != get_class($this)){
            return;
        }
    }

    public function actionSave()
    {
        header('Content-Type: application/json');
        echo '"saving"';
        Yii::app()->end();
    }
}
