<?php
/* @var $this UserManagementController */
/* @var $dataProvider CActiveDataProvider */
/* @var $model User */

// DO NOT REMOVE This is for automated testing to validate we see that page
echo viewHelper::getViewTestTag('usersIndex');

?>

<?php if (!Permission::model()->hasGlobalPermission('users', 'read')) :?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h2><?=gT("We are sorry but you don't have permissions to do this.")?></h2>
        </div>
    </div>
</div>
    <?php App()->end();?>
<?php endif; ?>

<?php $this->renderPartial('partial/_menubar'); ?>

<div class="row">
    <?php
    /* Example Datepicker ...
    $this->widget('zii.widgets.jui.CJuiDatePicker',array(
    'name'=>'publishDate',
    // additional javascript options for the date picker plugin
    'options'=>array(
    'showAnim'=>'fold',
    ),
    'htmlOptions'=>array(
    'style'=>'height:20px;'
    ),
    ));
    */
    ?>

    <?php
    /* Example used in survey participants gridview
    Yii::app()->getController()->widget('yiiwheels.widgets.datetimepicker.WhDateTimePicker', array(
        'name' => "no",
        'id'   => "no",
        'value' => '',

    ));
     */
    ?>
</div>
<div class="row">
    <div class="container-fluid">
        <?php /*
            $this->widget('yiistrap.widgets.TbGridView', array(
                'id'              => 'usermanagement--identity-gridPanel',
                'htmlOptions'     => ['class' => 'table-responsive grid-view-ls'],
                'dataProvider'    => $model->search(),
                'columns'         => $columnDefinition,
                'filter'          => $model,
                'afterAjaxUpdate' => 'LS.UserManagement.bindButtons',
                'template'        => "{items}\n<div id='userListPager'><div class=\"col-sm-4\" id=\"massive-action-container\">$massiveAction</div><div class=\"col-sm-4 pager-container ls-ba \">{pager}</div><div class=\"col-sm-4 summary-container\">{summary}</div></div>",
                'summaryText'     => gT('Displaying {start}-{end} of {count} result(s).') . ' '
                    . sprintf(
                        gT('%s rows per page'),
                        CHtml::dropDownList(
                            'pageSize',
                            $pageSize,
                            App()->params['pageSizeOptions'],
                            array('class' => 'changePageSize form-control', 'style' => 'display: inline; width: auto')
                        )
                    ),
            ));*/
            ?>
    </div>

    <div class="container-fluid">
        <?php
        $this->widget('zii.widgets.grid.CGridView', [
            'id' => 'my-grid',
            'dataProvider' => $model->search(),
            'selectableRows' => 5, // can be anything other than 1 or 0
            'columns' => $columnDefinition,
            'pager' => [
                'class' => 'application.extensions.admin.grid.CLSYiiPager',
            ],
            'filter'          => $model,
            'template'        => "{items}\n<div class=\"row\" id='userListPager'><div class=\"col-4\" id=\"massive-action-container\">$massiveAction</div><div class=\"col-4 \">{pager}</div><div class=\"col-4 summary-container\">{summary}</div></div>",
            'summaryText'     => gT('Displaying {start}-{end} of {count} result(s).') . ' '
                . sprintf(
                    gT('%s rows per page'),
                    CHtml::dropDownList(
                        'pageSize',
                        $pageSize,
                        App()->params['pageSizeOptions'],
                        array('class' => 'changePageSize form-control', 'style' => 'display: inline; width: auto')
                    )
                ),
        ]);
        ?>
    </div>

    <!-- To update rows per page via ajax -->
    <script type="text/javascript">
        jQuery(function ($) {
            jQuery(document).on("change", '#pageSize', function () {
                $.fn.yiiGridView.update('usermanagement--identity-gridPanel', {data: {pageSize: $(this).val()}});
            });
        });
        //show tooltip for gridview icons
        $('body').tooltip({selector: '[data-toggle="tooltip"]'});
    </script>
</div>
<div id='UserManagement-action-modal' class="modal fade UserManagement--selector--modal" tabindex="-1" role="dialog">
    <div id="usermanagement-modal-doalog" class="modal-dialog" role="document">
        <div class="modal-content">
        </div>
    </div>
</div>
