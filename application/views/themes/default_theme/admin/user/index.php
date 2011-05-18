<?php defined('SYSPATH') OR die('No direct access allowed.'); ?>

<div class="admin-holder questions-holder">

	<?php echo View::factory($theme_dir . 'admin/generic_nav')->render(); ?>

	<div class="cms-sub-navigation">
		<?php 
		foreach(Helper_View::get_cms_user_url_list() as $link)
			echo $link;
		?>
	</div>
	
	<br /><br />

	<div class="user-count">
		<?php echo $total_users, ' ', ucfirst(Inflector::plural('user', $total_users)); ?>
	</div>
	
	<br /><br />
	
	<div class="users-holder">
		
		<table>
		<tr class="table-row">
			<td><?php echo __('id'); ?></td>
			<td><?php echo __('twitter_id'); ?></td>
			<td><?php echo __('email'); ?></td>
			<td><?php echo __('username'); ?></td>
			<td><?php echo __('logins'); ?></td>
			<td><?php echo __('last_login'); ?></td>
			<td><?php echo __('website'); ?></td>
			<td><?php echo __('latest_activity'); ?></td>
			<td><?php echo __('last_ip'); ?></td>
			<td><?php echo __('reputation'); ?></td>
			<td><?php echo __('question_count'); ?></td>
			<td><?php echo __('answer_count'); ?></td>
			<td><?php echo __('account_status'); ?></td>
			<td>&nbsp;</td>
		</tr>
		
		<?php foreach ($users as $ind => $user) { ?>
		<tr class="table-row row-holder-<?php echo $user->id; ?>">
			
		<td><?php echo $user->id; ?></td>
		<td><?php echo Check::isNull($user->twitter_id) ? '-' : $user->twitter_id; ?></td>
		<td><?php echo Check::isNull($user->email) ? '-' : $user->email; ?></td>
		<td><?php echo Check::isNull($user->username) ? '-' : $user->username; ?></td>
		<td><?php echo $user->logins; ?></td>
		<td><?php echo Check::isNull($user->last_login) ? '-' : $user->get_user_last_login(); ?></td>
		<td><?php echo $user->website; ?></td>
		<td><?php echo $user->get_user_latest_activity(); ?></td>
		<td><?php echo $user->last_ip; ?></td>
		<td><?php echo $user->reputation; ?></td>
		<td><?php echo $user->question_count; ?></td>
		<td><?php echo $user->answer_count; ?></td>
		<td><?php echo $user->account_status; ?></td>
		
		<td>
		<form class="moderate_form" action="<?php echo URL::site(Route::get('admin_ajax')->uri(array('directory' => 'admin', 'action' => 'usermoderate'))) ?>" method="post">
		<input type="hidden" name="hdn_id" value="<?php echo $user->id ?>" />
		<input type="hidden" name="token" value="<?php echo (isset($token)) ? $token : ''; ?>" />
		<span class="result-<?php echo $user->id; ?>"></span>
		
		<select class="moderate-select-<?php echo $user->id; ?> user-moderate-select" id="<?php echo $user->id; ?>">
		<?php 
		 echo 
			'<option value="' . Helper_AccountStatus::APPROVED . '">' . Helper_AccountStatus::APPROVED . '</option>
			<option value="' . Helper_AccountStatus::DISAPPROVED . '">' . Helper_AccountStatus::DISAPPROVED . '</option>
			<option value="' . Helper_AccountStatus::DELETED . '">' . Helper_AccountStatus::DELETED . '</option>
			<option value="' . Helper_AccountStatus::IN_REVIEW . '">' . Helper_AccountStatus::IN_REVIEW . '</option>
			<option value="' . Helper_AccountStatus::NORMAL . '">' . Helper_AccountStatus::NORMAL . '</option>
			<option value="' . Helper_AccountStatus::SPAM . '">' . Helper_AccountStatus::SPAM . '</option>';
		?>
		</select>
		
		<input type="submit" value="<?php echo __("Moderate") ?>" />
		</form>
		
		<div class="spam-management-<?php echo $user->id ?>" style="display:none;">
		
			<form class="spam_moderate_form" action="<?php echo URL::site(Route::get('admin_ajax')->uri(array('directory' => 'admin', 'action' => 'spammoderate'))) ?>" method="post">
			<input type="hidden" name="hdn_id" value="<?php echo $user->id ?>" />
			<input type="hidden" name="token" value="<?php echo (isset($token)) ? $token : ''; ?>" />
			<span class="spam-result-<?php echo $user->id; ?>"></span>
			
			<br />
			<input type="checkbox" name="input_delete_all_posts" />
			<?php echo __('Delete all user posts'); ?>
			
			<br />
			<input type="checkbox" name="input_mark_anonymous_all_posts" />
			<?php echo __('Mark anonymous all user posts'); ?>
			
			<input type="submit" value="<?php echo __("Process") ?>" />
			</form>
		
		</div>
		</td>
			
		</tr>
		<?php } ?>
		
		</table>
	
	</div>
	
	<div class="pagination-holder"><?php echo $pagination; ?></div>
</div>