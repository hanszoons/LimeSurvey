<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
* LimeSurvey
* Copyright (C) 2007-2015 The LimeSurvey Project Team / Carsten Schmitz
* All rights reserved.
* License: GNU/GPL License v2 or later, see LICENSE.php
* LimeSurvey is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/

/*
 * This is the model class for table "{{template_configuration}}".
 *
 * NOTE: if you only need to access to the table, you don't need to call prepareTemplateRendering
 *
 * The followings are the available columns in table '{{template_configuration}}':
 * @property integer $id Primary key
 * @property string $template_name
 * @property integer $sid Survey ID
 * @property integer $gsid
 * @property integer $uid user ID
 * @property string $files_css
 * @property string $files_js
 * @property string $files_print_css
 * @property string $options
 * @property string $cssframework_name
 * @property string $cssframework_css
 * @property string $cssframework_js
 * @property string $packages_to_load
 * @property string $packages_ltr
 * @property string $packages_rtl
 * @property string $packages_rtl
 *
 * @package       LimeSurvey
 * @subpackage    Backend
 */
class TemplateConfiguration extends TemplateConfig
{

    /**
     * @var TemplateConfiguration $oParentTemplate The parent template name
     * A template configuration, in the database, can inherit from another one.
     * This used to manage the different configuration levels for a very same template: global, survey group, survey
     * This is not related to motherTemplate (inheritance between two different templates)
     */
    public $oParentTemplate;

    /**@var boolean Should the magic getters automatically retreives the parent value when field is set to inherit. Only turn to on for template rendering  */
    public $bUseMagicInherit = false;

    // Caches

    /** @var string $sPreviewImgTag the template preview image tag for the template list*/
    public $sPreviewImgTag;

    /** @var boolean $bTemplateCheckResult is the template valid?*/
    private $bTemplateCheckResult;

    /** @var string $sTypeIcon the type of template for icon (core vs user)*/
    private $sTypeIcon;

    /** @var array $aFilesToLoad cache for the method getFilesToLoad()*/
    private $aFilesToLoad;

    /** @var array $aFrameworkAssetsToReplace cache for the method getFrameworkAssetsToReplace()*/
    private $aFrameworkAssetsToReplace;

    /** @var array $aReplacements cache for the method getFrameworkAssetsReplacement */
    private $aReplacements;


    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{template_configuration}}';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('template_name', 'required'),
            array('id, sid, gsid', 'numerical', 'integerOnly'=>true),
            array('template_name', 'length', 'max'=>150),
            array('cssframework_name', 'length', 'max'=>45),
            array('files_css, files_js, files_print_css, options, cssframework_css, cssframework_js, packages_to_load', 'safe'),
            // The following rule is used by search().
            array('id, template_name, sid, gsid, files_css, files_js, files_print_css, options, cssframework_name, cssframework_css, cssframework_js, packages_to_load', 'safe', 'on'=>'search'),
        );
    }

    /**
     * @return array relational rules.
     */
    public function relations()
    {
        return array(
            'template' => array(self::HAS_ONE, 'Template', array('name' => 'template_name'), 'together' => true),
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return array(
            'id' => 'ID',
            'template_name' => 'Templates Name',
            'sid' => 'Sid',
            'gsid' => 'Gsid',
            'files_css' => 'Files Css',
            'files_js' => 'Files Js',
            'files_print_css' => 'Files Print Css',
            'options' => 'Options',
            'cssframework_name' => 'Cssframework Name',
            'cssframework_css' => 'Cssframework Css',
            'cssframework_js' => 'Cssframework Js',
            'packages_to_load' => 'Packages To Load',
        );
    }

    /**
     * Gets an instance of a templateconfiguration by name
     *
     * @return TemplateConfiguration
     */
    public static function getInstanceFromConfigurationId($iTemplateConfigId){
        $oTemplateConfiguration = self::model()->findByPk($iTemplateConfigId);
        $oTemplateConfiguration->setThisTemplate();
        return $oTemplateConfiguration;
    }

    /**
     * Gets an instance of a templateconfiguration by name
     *
     * @param [String] $sTemplateName
     * @return TemplateConfiguration
     */
    public static function getInstanceFromTemplateName($sTemplateName){
        return  self::model()->find(
            'template_name=:template_name AND sid IS NULL AND gsid IS NULL',
            array(':template_name'=>$sTemplateName)
        );
    }

    /**
     * Returns a TemplateConfiguration Object based on a surveygroup ID
     * If no instance is existing, it will create one.
     *
     * @param [Integer] $iSurveyGroupId
     * @param [String] $sTemplateName
     * @return TemplateConfiguration
     */
    public static function getInstanceFromSurveyGroup($iSurveyGroupId, $sTemplateName=null){

        //if a template name is given also check against that
        $sTemplateName = $sTemplateName != null ? $sTemplateName : SurveysGroups::model()->findByPk($iSurveyGroupId)->template;

        $criteria = new CDbCriteria();
        $criteria->addCondition('gsid=:gsid');
        $criteria->addCondition('template_name=:template_name');
        $criteria->params = array('gsid' => $iSurveyGroupId, 'template_name' => $sTemplateName);
        $oTemplateConfigurationModel = TemplateConfiguration::model()->find($criteria);

        // No specific template configuration for this surveygroup => create one
        // TODO: Move to SurveyGroup creation, right now the 'lazy loading' approach is ok.
        if (!is_a($oTemplateConfigurationModel, 'TemplateConfiguration') && $sTemplateName != null){
            $oTemplateConfigurationModel = TemplateConfiguration::getInstanceFromTemplateName($sTemplateName);
            $oTemplateConfigurationModel->id = null;
            $oTemplateConfigurationModel->isNewRecord = true;
            $oTemplateConfigurationModel->gsid = $iSurveyGroupId;
            $oTemplateConfigurationModel->setToInherit();
            $oTemplateConfigurationModel->save();
        }

        return $oTemplateConfigurationModel;

    }

    /**
     * Returns a TemplateConfiguration Object based on a surveyID
     * If no instance is existing, it will create one.
     *
     * @param [Integer] $iSurveyId
     * @param [String] $sTemplateName
     * @return TemplateConfiguration
     */
    public static function getInstanceFromSurveyId($iSurveyId, $sTemplateName=null){

        //if a template name is given also check against that
        $sTemplateName = $sTemplateName!=null ? $sTemplateName : Survey::model()->findByPk($iSurveyId)->template;

        $criteria = new CDbCriteria();
        $criteria->addCondition('sid=:sid');
        $criteria->addCondition('template_name=:template_name');
        $criteria->params = array('sid' => $iSurveyId, 'template_name' => $sTemplateName);

        $oTemplateConfigurationModel = TemplateConfiguration::model()->find($criteria);


        // No specific template configuration for this surveygroup => create one
        // TODO: Move to SurveyGroup creation, right now the 'lazy loading' approach is ok.
        if (!is_a($oTemplateConfigurationModel, 'TemplateConfiguration')  && $sTemplateName != null){
            $oTemplateConfigurationModel = TemplateConfiguration::getInstanceFromTemplateName($sTemplateName);
            $oTemplateConfigurationModel->id = null;
            $oTemplateConfigurationModel->isNewRecord = true;
            $oTemplateConfigurationModel->sid = $iSurveyId;
            $oTemplateConfigurationModel->setToInherit();
            $oTemplateConfigurationModel->save();
        }

        return $oTemplateConfigurationModel;
    }

    /**
     * Get an instance of a fitting TemplateConfiguration
     *
     * @param [String] $sTemplateName
     * @param [Integer] $iSurveyGroupId
     * @param integer $iSurveyId
     * @return TemplateConfiguration
     */
    public static function getInstance($sTemplateName=null, $iSurveyGroupId=null, $iSurveyId=null){

        $oTemplateConfigurationModel = new TemplateConfiguration();

        if ($sTemplateName!=null){
            $oTemplateConfigurationModel = TemplateConfiguration::getInstanceFromTemplateName($sTemplateName);
        }

        if($iSurveyGroupId!=null && $iSurveyId==null) {
            $oTemplateConfigurationModel = TemplateConfiguration::getInstanceFromSurveyGroup($iSurveyGroupId, $sTemplateName);
        }

        if($iSurveyId!=null) {
            $oTemplateConfigurationModel = TemplateConfiguration::getInstanceFromSurveyId($iSurveyId, $sTemplateName);
        }

        return $oTemplateConfigurationModel;

    }


    /**
     * Retrieves a list of models based on the current search/filter conditions.
     *
     * Typical usecase:
     * - Initialize the model fields with values from filter form.
     * - Execute this method to get CActiveDataProvider instance which will filter
     * models according to data in model fields.
     * - Pass data provider to CGridView, CListView or any similar widget.
     *
     * @return CActiveDataProvider the data provider that can return the models
     * based on the search/filter conditions.
     */
    public function search()
    {
        // @todo Please modify the following code to remove attributes that should not be searched.

        $criteria=new CDbCriteria;

        $criteria->join = 'INNER JOIN {{templates}} AS template ON t.template_name = template.name';
        //Don't show surveyspecifi settings on the overview
        $criteria->addCondition('t.sid IS NULL');
        $criteria->addCondition('t.gsid IS NULL');
        $criteria->addCondition('template.name IS NOT NULL');

        $criteria->compare('id',$this->id);
        $criteria->compare('template_name',$this->template_name,true);
        $criteria->compare('files_css',$this->files_css,true);
        $criteria->compare('files_js',$this->files_js,true);
        $criteria->compare('files_print_css',$this->files_print_css,true);
        $criteria->compare('options',$this->options,true);
        $criteria->compare('cssframework_name',$this->cssframework_name,true);
        $criteria->compare('cssframework_css',$this->cssframework_css,true);
        $criteria->compare('cssframework_js',$this->cssframework_js,true);
        $criteria->compare('packages_to_load',$this->packages_to_load,true);

        return new CActiveDataProvider($this, array(
            'criteria'=>$criteria,
        ));
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return TemplateConfiguration the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var self $model */
        $model =parent::model($className);
        return $model;
    }


    /**
     * Create a new entry in {{templates}} and {{template_configuration}} table using the template manifest
     * @param string $sTemplateName the name of the template to import
     * @return boolean true on success | exception
     * @throws Exception
     */
    public static function importManifest($sTemplateName, $aDatas=array() )
    {
        if ( !empty($aDatas['extends'])  ){

            $oMotherTemplate = self::getInstanceFromTemplateName($aDatas['extends']);
            if (is_a($oMotherTemplate, 'TemplateConfiguration')) {
                $aDatas['api_version']     = $oMotherTemplate->template->api_version;
                $aDatas['view_folder']     = $oMotherTemplate->template->view_folder;
                $aDatas['author_email']    = $oMotherTemplate->template->author_email;
                $aDatas['author_url']      = $oMotherTemplate->template->author_url;
                $aDatas['copyright']       = $oMotherTemplate->template->copyright;
                $aDatas['version']         = $oMotherTemplate->template->version;
                $aDatas['license']         = $oMotherTemplate->template->license;
                $aDatas['files_folder']    = $oMotherTemplate->template->files_folder;
                $aDatas['aOptions']        =  json_decode($oMotherTemplate->options);
            }
        }

        return parent::importManifest($sTemplateName, $aDatas );
    }

    public function setToInherit(){
        $this->files_css         = 'inherit';
        $this->files_js          = 'inherit';
        $this->files_print_css   = 'inherit';
        $this->options           = 'inherit';
        $this->cssframework_name = 'inherit';
        $this->cssframework_css  = 'inherit';
        $this->cssframework_js   = 'inherit';
        $this->packages_to_load  = 'inherit';
    }

    public function checkTemplate()
    {
        if (empty($this->bTemplateCheckResult)){
            $this->bTemplateCheckResult = true;
            if (is_object($this->template) && !is_dir(Yii::app()->getConfig("standardthemerootdir").DIRECTORY_SEPARATOR.$this->template->folder)&& !is_dir(Yii::app()->getConfig("userthemerootdir").DIRECTORY_SEPARATOR.$this->template->folder)){
                $this->bTemplateCheckResult = false;
            }
        }
        return $this->bTemplateCheckResult;
    }

    /**
     * Prepare all the needed datas to render the temple
     * If any problem (like template doesn't exist), it will load the default template configuration
     * NOTE 1: This function will create/update all the packages needed to render the template, which imply to do the same for all mother templates
     * NOTE 2: So if you just want to access the TemplateConfiguration AR Object, you don't need to call it. Call it only before rendering anything related to the template.
     *
     * @param  string $sTemplateName the name of the template to load. The string comes from the template selector in survey settings
     * @param  string $iSurveyId the id of the survey. If
     * @param bool $bUseMagicInherit
     * @return $this
     */
    public function prepareTemplateRendering($sTemplateName='', $iSurveyId='', $bUseMagicInherit=true)
    {
        $this->bUseMagicInherit = $bUseMagicInherit;
        $this->setBasics($sTemplateName, $iSurveyId);
        $this->setMotherTemplates();                                            // Recursive mother templates configuration
        $this->setThisTemplate();                                               // Set the main config values of this template
        $this->createTemplatePackage($this);                                    // Create an asset package ready to be loaded
        return $this;
    }

    public function setBasics($sTemplateName='', $iSurveyId='')
    {
        $this->sTemplateName = $this->template->name;
        $this->setIsStandard();                                                 // Check if  it is a CORE template
        $this->path = ($this->isStandard)
            ? Yii::app()->getConfig("standardthemerootdir").DIRECTORY_SEPARATOR.$this->template->folder.DIRECTORY_SEPARATOR
            : Yii::app()->getConfig("userthemerootdir").DIRECTORY_SEPARATOR.$this->template->folder.DIRECTORY_SEPARATOR;
    }

    /**
     * Add a file replacement in the field `file_{css|js|print_css}` in table {{template_configuration}},
     * eg: {"replace": [ {original files to replace here...}, "css/template.css",]}
     * In general, should be called from TemplateManifest, after adding a file replacement inside the manifest.
     *
     * @param string $sFile the file to replace
     * @param string $sType css|js
     * @return bool|void
     * @throws Exception
     */
    public function addFileReplacement($sFile, $sType)
    {
        $sField = 'files_'.$sType;
        $oFiles = (array) json_decode($this->$sField);

        $oFiles['replace'][] = $sFile;

        $this->$sField = json_encode($oFiles);

        if ($this->save()){
            return true;
        }else{
            throw new Exception("could not add $sFile to  $sField replacements! ".$this->getErrors());
        }
    }

    public function getTypeIcon()
    {
        if (empty($this->sTypeIcon)){
            $this->sTypeIcon = (Template::isStandardTemplate($this->template->name))?gT("Core template"):gT("User template");
        }
        return $this->sTypeIcon;
    }


    public function getButtons()
    {
        $sEditorUrl    = Yii::app()->getController()->createUrl('admin/templates/sa/view', array("templatename"=>$this->template_name));
        if (App()->getController()->action->id == "surveysgroups"){
            $gisd = Yii::app()->request->getQuery('id', null);
            $sOptionUrl    = Yii::app()->getController()->createUrl('admin/themeoptions/sa/updatesurveygroup', array("id"=>$this->id, "gsid"=>$gisd));
        }else{
            $sOptionUrl    = Yii::app()->getController()->createUrl('admin/themeoptions/sa/update', array("id"=>$this->id));
        }

        $sUninstallUrl = Yii::app()->getController()->createUrl('admin/themeoptions/sa/uninstall/', array("templatename"=>$this->template_name));

        $sEditorLink = "<a
            id='template_editor_link_".$this->template_name."'
            href='".$sEditorUrl."'
            class='btn btn-default'>
                <span class='icon-templates'></span>
                ".gT('Template editor')."
            </a>";

            //

        $OptionLink = '';

        if ($this->hasOptionPage){
            $OptionLink .=  "<a
                id='template_options_link_".$this->template_name."'
                href='".$sOptionUrl."'
                class='btn btn-default'>
                    <span class='fa fa-tachometer'></span>
                    ".gT('Template options')."
                </a>";
        }


        $sUninstallLink = '<a
            id="remove_fromdb_link_'.$this->template_name.'"
            data-href="'.$sUninstallUrl.'"
            data-target="#confirmation-modal"
            data-toggle="modal"
            data-message="'.gT('This will delete all the specific configurations of this template').'<br>'.gT('Do you want to continue?').'"
            data-tooltip="true"
            title="'.gT('uninstall this template').'"
            class="btn btn-danger">
                <span class="icon-trash"></span>
                '.gT('Uninstall').'
            </a>';


        if (App()->getController()->action->id == "surveysgroups"){
            $sButtons = $OptionLink;
        }else{
            $sButtons = $sEditorLink.'<br><br>'.$OptionLink;

            if ($this->template_name != 'default'){
                $sButtons .= '<br><br>'.$sUninstallLink;
            }
        }





        return $sButtons;
    }

    public function getHasOptionPage()
    {
        $this->prepareTemplateRendering();
        $oRTemplate = $this;
        $sOptionFile = 'options'.DIRECTORY_SEPARATOR.'options.twig';
        while (!file_exists($oRTemplate->path.$sOptionFile)){

            $oMotherTemplate = $oRTemplate->oMotherTemplate;
            if(!($oMotherTemplate instanceof TemplateConfiguration)){
                return false;
                break;
            }
            $oMotherTemplate->prepareTemplateRendering();
            $oRTemplate = $oMotherTemplate;
        }
        return true;
    }

    private function _filterImages($file){
        $checkImage = getimagesize($this->filesPath.$file['name']);
        if (!($checkImage === false ||  !in_array($checkImage[2], [IMAGETYPE_JPEG,IMAGETYPE_PNG,IMAGETYPE_GIF])))
            return ['filepath' => './files/'.$file['name'], 'filename'=>$file['name']];
    }

    protected function getOptionPageAttributes(){
        $aData = $this->attributes;
        $fileList = Template::getOtherFiles($this->filesPath);
        $aData['brandlogoFileList'] = [];
        foreach($fileList as $file){
            $isImage = $this->_filterImages($file);

            if($isImage)
                $aData['brandlogoFileList'][] = $isImage;
        };
        return $aData;
    }

    public function getOptionPage()
    {
        $this->prepareTemplateRendering();
        return Yii::app()->twigRenderer->renderOptionPage($this, array('templateConfiguration' => $this->getOptionPageAttributes()));
    }

    /**
     * From a list of json files in db it will generate a PHP array ready to use by removeFileFromPackage()
     *
     * @param TemplateConfiguration $oTemplate
     * @param string $sType
     * @return array
     * @internal param string $jFiles json
     */
    protected function getFilesToLoad($oTemplate, $sType)
    {
        if (empty($this->aFilesToLoad)){
            $this->aFilesToLoad = array();
        }

        $sField = 'files_'.$sType;
        $jFiles = $oTemplate->$sField;
        $this->aFilesToLoad[$sType] = array();


        if(!empty($jFiles)){
            $oFiles = json_decode($jFiles, true);
            if ($oFiles === null) {
                Yii::app()->setFlashMessage(
                    sprintf(
                        gT('Error: Malformed JSON: Field %s must be either a JSON array or the string "inherit". Found "%s".'),
                        $sField,
                        $jFiles
                    ),
                    'error'
                );
            } else {
                foreach($oFiles as $action => $aFileList){

                    if ( is_array( $aFileList ) ){
                        if ($action == "add" || $action == "replace"){

                            // Specific inheritance of one of the value of the json array
                            if ($aFileList[0] == 'inherit'){
                                $aParentjFiles = (array) json_decode($oTemplate->getParentConfiguration->$sField);
                                $aFileList = $aParentjFiles[$action];
                            }

                            $this->aFilesToLoad[$sType] = array_merge($this->aFilesToLoad[$sType], $aFileList);
                        }
                    }
                }
            }

        }


        return $this->aFilesToLoad[$sType];
    }

    /**
     * Change the mother template configuration depending on template settings
     * @var $sType     string   the type of settings to change (css or js)
     * @var $aSettings array    array of local setting
     * @return array
     */
    protected function changeMotherConfiguration( $sType, $aSettings )
    {
        if (is_a($this->oMotherTemplate, 'TemplateConfiguration')){


            // Check if each file exist in this template path
            // If the file exists in local template, we can remove it from mother template package.
            // Else, we must remove it from current package, and if it doesn't exist in mother template definition, we must add it.
            // (and leave it in moter template definition if it already exists.)
            foreach ($aSettings as $key => $sFileName){
                if (file_exists($this->path.$sFileName)){
                    Yii::app()->clientScript->removeFileFromPackage($this->oMotherTemplate->sPackageName, $sType, $sFileName );

                }else{
                    $oTemplate = $this->getTemplateForFile($sFileName, $this);
                    if (!Yii::app()->clientScript->IsFileInPackage($oTemplate->sPackageName, $sType, $sFileName)){
                        Yii::app()->clientScript->addFileToPackage($oTemplate->sPackageName, $sType, $sFileName);
                        unset($aSettings[$key]);
                    }
                }
            }
        }

        return $aSettings;
    }

    /**
     * Proxy for Yii::app()->clientScript->removeFileFromPackage()
     *
     * @param string $sPackageName     string   name of the package to edit
     * @param string $sType            string   the type of settings to change (css or js)
     * @param $aSettings        array    array of local setting
     * @return array
     */
    protected function removeFileFromPackage( $sPackageName, $sType, $aSettings )
    {
        foreach( $aSettings as $sFile){
            Yii::app()->clientScript->removeFileFromPackage($sPackageName, $sType, $sFile );
        }
    }

    /**
     * Configure the mother template (and its mother templates)
     * This is an object recursive call to TemplateConfiguration::prepareTemplateRendering()
     */
    protected function setMotherTemplates()
    {
        if(!empty($this->template->extends)){
            $sMotherTemplateName   = $this->template->extends;
            $this->oMotherTemplate = TemplateConfiguration::getInstanceFromTemplateName($sMotherTemplateName);
            $this->oMotherTemplate->prepareTemplateRendering($sMotherTemplateName, null);
            if ($this->oMotherTemplate->checkTemplate()){
                $this->oMotherTemplate->prepareTemplateRendering($sMotherTemplateName, null); // Object Recursion
            }else{
                // Throw exception? Set to default template?
            }
        }
    }

    /**
     * @param TemplateConfiguration $oRTemplate
     * @param string $sPath
     */
    protected function getTemplateForPath($oRTemplate, $sPath )
    {
        while (empty($oRTemplate->template->$sPath)){
            $oMotherTemplate = $oRTemplate->oMotherTemplate;
            if(!($oMotherTemplate instanceof TemplateConfiguration)){
                //throw new Exception("can't find a template for template '{$oRTemplate->template_name}' for path '$sPath'.");
                TemplateConfiguration::uninstall($this->template_name);
                Yii::app()->setFlashMessage(sprintf(gT("Templates '%s' has been uninstalled because it's not compatible with this LimeSurvey version."), $this->template_name), 'error');
                Yii::app()->getController()->redirect(array("admin/themeoptions"));
                break;
            }
            $oRTemplate = $oMotherTemplate;
        }
        return $oRTemplate;
    }

    /**
     * Set the default configuration values for the template, and use the motherTemplate value if needed
     */
    protected function setThisTemplate()
    {

        $this->apiVersion  = (!empty($this->template->api_version))? $this->template->api_version : null; // Mandtory setting in config XML
        $this->viewPath    =  $this->path.$this->getTemplateForPath($this, 'view_folder')->template->view_folder.DIRECTORY_SEPARATOR;
        $this->filesPath   = $this->path.$this->getTemplateForPath($this, 'files_folder')->template->files_folder.DIRECTORY_SEPARATOR ;

        // Options are optional
        $this->setOptions();

        // Not mandatory (use package dependances)
        $this->setCssFramework();
        $this->packages = $this->getDependsPackages($this);
        if (!empty($this->packages_to_load)){
            $templateToLoadPackages = json_decode($this->packages_to_load);
            if(is_array($templateToLoadPackages))
                $this->packages =  array_merge( $templateToLoadPackages, $this->getDependsPackages($this));
        }

        // Add depend package according to packages
        $this->depends   =  array_merge($this->depends, $this->packages);
    }

    private function setCssFramework()
    {
        if (!empty($this->cssframework_name)){
            $this->cssFramework = new \stdClass();
            $this->cssFramework->name = $this->cssframework_name;
            $this->cssFramework->css  = json_decode($this->cssframework_css);
            $this->cssFramework->js   = json_decode($this->cssframework_js);

        }else{
            $this->cssFramework = '';
        }
    }

    protected function setOptions()
    {
        $this->oOptions = array();
        if (!empty($this->options)){
            $this->oOptions = json_decode($this->options);
        }

        $this->setOptionInheritance();
    }

    protected function setOptionInheritance()
    {
        $oOptions = $this->oOptions;

        if (!empty($oOptions)){
            foreach($oOptions as $sKey => $sOption){
                    $oOptions->$sKey = $this->getOptionKey($sKey);
            }
        }
    }

    /**
     * @param string $key
     * @return mixed
     */
    protected function getOptionKey($key)
    {
        $aOptions = (array) json_decode($this->options);
        if (isset($aOptions[$key])) {
            $value = $aOptions[$key];
            if ($value === 'inherit') {
                return $this->getParentConfiguration()->getOptionKey($key);
            }
            return  $value;
        } else {
            return null;
        }
    }

    protected function addMotherTemplatePackage($packages)
    {
        if (!empty($this->template->extends)){
            $sMotherTemplateName = (string) $this->template->extends;
            $packages[]          = 'survey-template-'.$sMotherTemplateName;
        }
        return $packages;
    }

    /**
     * Get the list of file replacement from Engine Framework
     * @param string  $sType            css|js the type of file
     * @param boolean $bInlcudeRemove   also get the files to remove
     * @return array
     */
    protected function getFrameworkAssetsToReplace( $sType, $bInlcudeRemove = false)
    {
        if (empty($this->aFrameworkAssetsToReplace)){
            $this->aFrameworkAssetsToReplace = array();
        }

        $this->aFrameworkAssetsToReplace[$sType] = array();

        $sFieldName  = 'cssframework_'.$sType;
        $aFieldValue = (array) json_decode($this->$sFieldName);

        if (!empty( $aFieldValue ) && !empty($aFieldValue['replace'])){
            $this->aFrameworkAssetsToReplace[$sType] = (array) $aFieldValue['replace'] ;

            // Inner field inheritance
            foreach ($this->aFrameworkAssetsToReplace[$sType] as $key => $aFiles){
                foreach($aFiles as $sReplacement){
                    if ( $sReplacement == "inherit"){
                        $aParentReplacement = $this->getParentConfiguration()->getFrameworkAssetsToReplace($sType);
                        $this->aFrameworkAssetsToReplace[$sType][$key][1] = $aParentReplacement[$key][1];
                    }
                }
            }

            if($bInlcudeRemove && isset($aFieldValue['remove'])){
                $this->aFrameworkAssetsToReplace[$sType] = array_merge($this->aFrameworkAssetsToReplace, (array) $aFieldValue['remove'] );
            }
        }


        return $this->aFrameworkAssetsToReplace[$sType];
    }

    /**
     * Get the list of file replacement from Engine Framework
     * @param string  $sType            css|js the type of file
     * @return array
     */
    protected function getFrameworkAssetsReplacement( $sType )
    {
        if (empty($this->aReplacements)){
            $this->aReplacements = array();
        }
        $this->aReplacements[$sType] = array();

        $aFrameworkAssetsToReplace = $this->getFrameworkAssetsToReplace($sType);

        foreach($aFrameworkAssetsToReplace as $key => $aAsset ){
            $aReplace = $aAsset[1];
            $this->aReplacements[$sType][] = $aReplace;
        }


        return $this->aReplacements[$sType];
    }


    public function getParentConfiguration(){
        if (empty($this->oParentTemplate)){

            //check for surveygroup id if a survey is given
            if($this->sid != null ){
                $oSurvey = Survey::model()->findByPk($this->sid);
                $oParentTemplate = Template::getTemplateConfiguration($this->sTemplateName,null,$oSurvey->gsid);
                if (is_a($oParentTemplate, 'TemplateConfiguration')){
                    $this->oParentTemplate = $oParentTemplate;
                    $this->oParentTemplate->bUseMagicInherit = $this->bUseMagicInherit;
                    return $this->oParentTemplate;
                }
            }

            //check for surveygroup id if a surveygroup is given
            if($this->sid == null && $this->gsid != null ){
                $oSurveyGroup = SurveysGroups::model()->findByPk($this->gsid);
                //Switch if the surveygroup inherits from a parent surveygroup
                if($oSurveyGroup != null && $oSurveyGroup->parent_id != 0) {
                    $oParentTemplate = Template::getTemplateConfiguration($this->sTemplateName,null,$oSurveyGroup->parent_id);
                    if (is_a($oParentTemplate, 'TemplateConfiguration')){
                        $this->oParentTemplate = $oParentTemplate;
                        $this->oParentTemplate->bUseMagicInherit = $this->bUseMagicInherit;
                        return $this->oParentTemplate;
                    }

                }
            }

            //in the endcheck for general global template
            $this->oParentTemplate = Template::getTemplateConfiguration($this->template_name, null, null);
            $this->oParentTemplate->bUseMagicInherit = $this->bUseMagicInherit;
            return $this->oParentTemplate;
        }
        return $this->oParentTemplate;
    }


    /**
     * Change the template name inside the configuration entries (called from template editor)
     * NOTE: all tests (like template exist, etc) are done from template controller.
     *
     * @param string $sOldName The old name of the template
     * @param string $sNewName The newname of the template
     */
    public static function rename($sOldName,$sNewName)
    {
        self::model()->updateAll(array( 'template_name' => $sNewName  ), "template_name = :oldname", array(':oldname'=>$sOldName));
    }


    /**
     * Proxy for the AR method to manage the inheritance
     * If one of the field that can be inherited is set to "inherit", then it will return the value of its parent
     * NOTE: this is recursive, if the parent field itself is set to inherit, then it will the value of the parent of the parent, etc
     *
     * @param string $name the name of the attribute
     * @return mixed
     */
    public function __get($name)
    {
        $aAttributesThatCanBeInherited = array('files_css', 'files_js', 'options', 'cssframework_name', 'cssframework_css', 'cssframework_js', 'packages_to_load');

        if (in_array($name, $aAttributesThatCanBeInherited) && $this->bUseMagicInherit){
            // Full inheritance of the whole field
            $sAttribute = parent::__get($name);
            if($sAttribute === 'inherit'){
                // NOTE: this is object recursive (if parent configuration field is set to inherit, then it will lead to this method again.)
                $sAttribute = $this->getParentConfiguration()->$name;
            }
        }else{
            $sAttribute = parent::__get($name);
        }

        return $sAttribute;
    }

}
