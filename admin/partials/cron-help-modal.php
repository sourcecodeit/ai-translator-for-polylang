<?php if (!defined('ABSPATH'))
	exit; ?>
<div class="polylai-modal-bg" style="display: none;" id="polylai-modal-cron">
	<div class="polylai-modal-dialog">
		<div class="polylai-modal-header">
			<h4>How to configure Cron in your hosting</h4>	
			<span class="dashicons dashicons-no" data-polyalai-close></span>
		</div>
		<div class="polylai-modal-body">
			The WP PolylAI translation procedure needs to be run <strong>every minute</strong>.<br />
			<br />
			If you know how to manually configure crontab then use the string:<br />
			<br />
			<pre class="polylai-cron-panel">* * * * * php "<?php echo esc_html(ABSPATH) ?>index.php" -- polylai_cron</pre>
			<br />
			Otherwise, read the following guides based on your current hosting provider:<br />
			<br />
			<ul>
				<li>
					<span class="dashicons dashicons-arrow-right-alt2"></span> <a href="https://cpanel.net/blog/tips-and-tricks/how-to-configure-a-cron-job/" target="_blank">cPanel</a>
				</li>
				<li>
					<span class="dashicons dashicons-arrow-right-alt2"></span> <a href="https://support.cloudways.com/en/articles/5124057-how-to-create-cron-jobs-on-cloudways" target="_blank">Cloudways</a>
				</li>
				<li>
					<span class="dashicons dashicons-arrow-right-alt2"></span> <a href="https://help.dreamhost.com/hc/en-us/articles/215088668-Create-a-cron-job" target="_blank">Dreamhost</a>
				</li>
				<li>
					<span class="dashicons dashicons-arrow-right-alt2"></span> <a href="https://www.siteground.com/kb/manage-cron-jobs/" target="_blank">SiteGround</a>
				</li>
				<li>
					<span class="dashicons dashicons-arrow-right-alt2"></span> <a href="https://www.godaddy.com/help/create-cron-jobs-16086" target="_blank">GoDaddy</a>
				</li>
				<li>
					<span class="dashicons dashicons-arrow-right-alt2"></span> <a href="https://support.plesk.com/hc/en-us/articles/12377347218711-How-to-add-a-scheduled-task-in-Plesk-UI-using-crontab-syntax" target="_blank">Plesk</a>
				</li>
				<li>
					<span class="dashicons dashicons-arrow-right-alt2"></span> <a href="https://kinsta.com/docs/wordpress-hosting/site-management/cron-jobs/" target="_blank">Kinsta</a>
				</li>
			</ul>
			If you can't find your provider or need help, write in the forum. If you have a premium license you can contact our 1:1 support<br />			
			<br />
			<strong>After you have set up the cron, wait one minute and you should see the activity 
				in the plugin settings.</strong>

		</div>
		<div class="polylai-modal-footer">
			<a class="button" target="_blank" href="https://wordpress.org/support/plugin/polyai-translator">PolylAISupport forum</a>
			
			<?php if (polylai_fs()->can_use_premium_code()): ?>
			<a class="button" href="options-general.php?page=polylai-translator-options-contact">Premium support</a>
			<?php else: ?>
				<a class="button" href="options-general.php?page=polylai-translator-options-pricing">Premium support</a>
			<?php endif ?>
		</div>
	</div>
</div>