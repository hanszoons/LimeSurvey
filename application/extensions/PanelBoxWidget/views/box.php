<div class="<?php echo $sizeClass; ?> ls-flex-column ls-panelboxes-panelbox" >
    <div class="card card-clickable ls-panelboxes-panelbox-inner selector__<?php echo CHtml::encode(str_replace(' ', '_', strtolower(strip_tags($title)))) ?>"
        id="card-<?php echo $position; ?>"
        data-url="<?php echo CHtml::encode($url); ?>"
        <?php if ($external): ?>
            data-target="_blank"
        <?php endif; ?>
    >
        <div class="card-header bg-primary p-3">
            <div class=""><?php echo viewHelper::filterScript(gT($title)); ?></div>
        </div>
        <div class="card-body d-flex align-items-center justify-content-center">
            <span class="sr-only"><?php echo viewHelper::filterScript(gT($title)); ?></span>
            <span class="<?php echo CHtml::encode($ico); ?>" style="font-size: 4em">
            </span>
        </div>
        <p class="card-body-link">
            <?php echo viewHelper::filterScript(gT($description)); ?>
            <?php if ($external): ?>
                &nbsp;<i class="fa fa-external-link"></i>
            <?php endif; ?>
        </p>
    </div>
</div>
