<?php if (!defined('ABSPATH'))
	exit; ?>
<div class="polylai-modal-bg" style="display: none;" id="polylai-modal-logs">
	<div class="polylai-modal-dialog">
		<div class="polylai-modal-header">
			<h4>PolylAI Logs</h4>	
			<span class="dashicons dashicons-no" data-polyalai-close></span>
		</div>
		<div class="polylai-modal-body">
			<p>Most recent events are shown here.</p>
			<table class="wp-list-table widefat striped table-view-list" id="polylai-logs">
				<thead>
					<tr>
						<th>Date</th>
						<th>Type</th>
						<th>Operation</th>
						<th>Message</th>
						<th>Post ID</th>
						<th>Post title</th>
					</tr>
				</thead>
				<tbody>
					
				</tbody>	
			</table>
		</div>
		<div class="polylai-modal-footer">
			<a class="button" data-download-logs>Download</a>
		</div>
	</div>
</div>