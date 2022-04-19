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

        if (!Permission::model()->hasSurveyPermission($surveyId, 'surveycontent', 'update')) {
            return;
        }

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
                [
                    'sa' => 'sidebody',
                    'plugin' => get_class($this),
                    'method' => 'actionSave',
                    'surveyId' => $surveyId
                ]
            );
            $moveUpUrl = Yii::app()->createUrl(
                'admin/pluginhelper',
                [
                    'sa' => 'sidebody',
                    'plugin' => get_class($this),
                    'method' => 'actionMoveUp',
                    'surveyId' => $surveyId
                ]
            );
            $tokenName = Yii::app()->request->csrfTokenName;
            $csrfToken = Yii::app()->request->csrfToken;
            $lang = Yii::app()->session['survey_' . $survey->sid]['s_lang'];

            if (empty($lang)) {
                throw new Exception('Found no lang for survey id ' . $survey->sid);
            }

            App()->getClientScript()->registerScript(
                "EditInPlaceBaseGlobalData",
                <<<JAVASCRIPT
var editInPlaceGlobalData = {
    editInPlaceBaseUrl: "$saveUrl",
    editInPlaceMoveUpUrl: "$moveUpUrl",
    csrfTokenName: "$tokenName",
    csrfToken: "$csrfToken",
    lang: "$lang",
    surveyId: "$surveyId"
};
JAVASCRIPT
,
                CClientScript::POS_BEGIN
            );

            $jsUrl = Yii::app()->assetManager->publish(dirname(__FILE__) . '/js/editinplace.js');
            $cssUrl = Yii::app()->assetManager->publish(dirname(__FILE__) . '/css/editinplace.css');
            App()->getClientScript()->registerScriptFile($jsUrl, null, ['type' => 'text/babel']);
            App()->getClientScript()->registerCssFile($cssUrl);
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
        $request    = Yii::app()->request;
        $surveyId   = (int) $request->getParam('surveyId');
        $questionId = (int) $request->getParam('questionId');
        $text       = $request->getParam('text');
        $code       = $request->getParam('code');
        $help       = $request->getParam('help');
        $lang       = $request->getParam('lang');

        if (!Permission::model()->hasSurveyPermission($surveyId, 'surveycontent', 'update')) {
            http_response_code(403);
            echo json_encode('No permission');
            Yii::app()->end();
        }

        /** @var ?Question */
        $question = Question::model()->findByAttributes(['qid' => $questionId, 'sid' => $surveyId]);
        if (empty($question)) {
            http_response_code(400);
            echo json_encode('Found no question with id ' . $questionId);
            Yii::app()->end();
        }
        $question->title = $code;
        if (!$question->save()) {
            http_response_code(400);
            echo json_encode("Could not save question code");
            Yii::app()->end();
        }

        /** @var ?QuestionL10n */
        $l10n = QuestionL10n::model()->findByAttributes(['qid' => $questionId, 'language' => $lang]);
        if (empty($l10n)) {
            http_response_code(400);
            echo json_encode("Found no l10n with question id " . $questionId);
            Yii::app()->end();
        }

        // TODO: script field
        $l10n->question = $text;;
        $l10n->help = $help;;
        if (!$l10n->save()) {
            http_response_code(400);
            echo json_encode("Could not save question text or help");
            Yii::app()->end();
        }

        // Reset session data
        killSurveySession($surveyId);

        echo json_encode("Saved");
        http_response_code(200);
        Yii::app()->end();
    }

    public function actionMoveUp()
    {
        header('Content-Type: application/json');

        $request    = Yii::app()->request;
        $surveyId   = (int) $request->getParam('surveyId');
        $questionId = (int) $request->getParam('questionId');
        error_log($questionId);

        if (!Permission::model()->hasSurveyPermission($surveyId, 'surveycontent', 'update')) {
            http_response_code(403);
            echo json_encode('No permission');
            Yii::app()->end();
        }

        /** @var ?Question */
        $question = Question::model()->findByAttributes(['qid' => $questionId, 'sid' => $surveyId]);
        if (empty($question)) {
            http_response_code(400);
            echo json_encode('Found no question with id ' . $questionId);
            Yii::app()->end();
        }

        $previousOrder = $question->question_order;
        $question->question_order = $previousOrder - 1;
        if ($question->question_order < 1) {
            $question->question_order = 1;
        }
        if (!$question->save()) {
            http_response_code(400);
            echo json_encode("Could not save question");
            Yii::app()->end();
        }
        error_log($question->question_order . ' ' . $question->qid);

        /** @var Question[] */
        $allQuestionsInGroup = Question::model()->byQuestionOrder()->findAllByAttributes(['gid' => $question->gid, 'sid' => $surveyId]);

        foreach ($allQuestionsInGroup as $i => $question) {
            if ($question->qid == $questionId) {
                // Ignore the question we just changed.
            } else {
                $question->question_order = $i + 1;
                error_log($question->question_order . ' ' . $question->qid);
                if (!$question->save()) {
                    http_response_code(400);
                    echo json_encode("Could not save question order");
                    Yii::app()->end();
                }
            }
        }

        // Reset session data
        killSurveySession($surveyId);

        echo json_encode("Saved");
        http_response_code(200);
        Yii::app()->end();
    }
}
