<?php if (count($fields) < 20) : ?>

	<?php foreach ($fields as $field): ?>
		<a href="#" class="button button--auto button--secondary-alt" data-field-name="<?=$field->getShortName()?>">
			<img src="<?=$field->getIcon()?>" width="24" height="24" /><br />
			<?=lang('add')?> <?=$field->getItem('field_label')?>
		</a>
	<?php endforeach; ?>

<?php else: ?>

	<a href="javascript:void(0)" class="js-dropdown-toggle button button--auto button--secondary-alt"><i class="fa-2x icon--add"></i><br /> <?=lang('add_field')?></a>
	<div class="dropdown">
		<?php foreach ($fields as $field): ?>
			<a href="#" class="dropdown__link" data-field-name="<?=$field->getShortName()?>"><img src="<?=$field->getIcon()?>" width="12" height="12" /> <?=$field->getItem('field_label')?></a>
		<?php endforeach; ?>
	</div>

<?php endif; ?>