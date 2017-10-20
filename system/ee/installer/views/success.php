<div class="box <?=empty($update_notices) ? 'success' : 'warn'?>">
	<h1><?=lang('install_complete')?><span class="icon-success"></span></h1>
	<div class="updater-msg">
		<p><?=$success_note?></p>
		<p><?=lang('success_delete')?></p>
		<?php if ( ! empty($update_notices)): ?>
			<div class="alert-notice">
				<?php foreach ($update_notices as $notice): ?>
					<?php if ($notice->is_header): ?>
						<p><b><?=$notice->message?></b></p>
					<?php else: ?>
						<p><?=$notice->message?></p>
					<?php endif?>
				<?php endforeach;?>
			</div>
		<?php endif ?>
		<p class="msg-choices"><a href="<?=$cp_login_url?>"><?=lang('cp_login')?></a></p>
	</div>
</div>
