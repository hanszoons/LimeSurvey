<?php
/* @var $this UserManagementController */
/* @var $dataProvider CActiveDataProvider */
/* @var $model User */
/* @var string $massiveAction */

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
    <div class="container-fluid">
        <?php
        $this->widget('application.extensions.admin.grid.CLSGridView', [
            'id' => 'usermanagement--identity-gridPanel',
            'dataProvider' => $model->search(),
            'columns' => $model->getManagementColums(),
            'massiveActionTemplate' => $massiveAction,
            'afterAjaxUpdate' => 'LS.UserManagement.bindButtons',
            'pager' => [
                'class' => 'application.extensions.admin.grid.CLSYiiPager',
            ],
            'filter'          => $model,
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
