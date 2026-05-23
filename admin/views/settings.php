<?php
/**
 * Settings admin view.
 *
 * @package SVA
 */

defined( 'ABSPATH' ) || exit; ?>

<div class="wrap sva-wrap">
	<h1 class="wp-heading-inline">
		<span class="dashicons dashicons-admin-settings"></span>
		<?php esc_html_e( 'Visitor Analytics — Settings', 'stracini-visitor-analytics' ); ?>
	</h1>
	<hr class="wp-header-end">

	<?php
	$sva_raw      = get_option( 'sva_settings', array() );
	$sva_defaults = array(
		'tracking_enabled'   => true,
		'track_logged_in'    => false,
		'track_admins'       => false,
		'excluded_ips'       => '',
		'retention_days'     => 90,
		'lookup_private_ips' => false,
	);
	$sva_settings = wp_parse_args( $sva_raw, $sva_defaults );
	?>

	<form method="post" action="">
		<?php wp_nonce_field( 'sva_save_settings', 'sva_settings_nonce' ); ?>

		<table class="form-table" role="presentation">

			<tr>
				<th scope="row"><?php esc_html_e( 'Enable Tracking', 'stracini-visitor-analytics' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="tracking_enabled" value="1" <?php checked( $sva_settings['tracking_enabled'] ); ?>>
						<?php esc_html_e( 'Track visitor IPs on every page load', 'stracini-visitor-analytics' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Disable to pause all tracking without deactivating the plugin.', 'stracini-visitor-analytics' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Track Logged-in Users', 'stracini-visitor-analytics' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="track_logged_in" value="1" <?php checked( $sva_settings['track_logged_in'] ); ?>>
						<?php esc_html_e( 'Also track visitors who are logged in to WordPress', 'stracini-visitor-analytics' ); ?>
					</label>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Track Administrators', 'stracini-visitor-analytics' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="track_admins" value="1" <?php checked( $sva_settings['track_admins'] ); ?>>
						<?php esc_html_e( 'Track users with the manage_options capability', 'stracini-visitor-analytics' ); ?>
					</label>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Look Up Private IPs', 'stracini-visitor-analytics' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="lookup_private_ips" value="1" <?php checked( $sva_settings['lookup_private_ips'] ); ?>>
						<?php esc_html_e( 'Send private/LAN IPs to the IpQuery API (useful for testing on localhost)', 'stracini-visitor-analytics' ); ?>
					</label>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="excluded_ips"><?php esc_html_e( 'Excluded IPs', 'stracini-visitor-analytics' ); ?></label>
				</th>
				<td>
					<textarea id="excluded_ips" name="excluded_ips" rows="6" class="large-text code"><?php echo esc_textarea( $sva_settings['excluded_ips'] ); ?></textarea>
					<p class="description"><?php esc_html_e( 'One IP per line. These IPs will never be tracked.', 'stracini-visitor-analytics' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="retention_days"><?php esc_html_e( 'Data Retention', 'stracini-visitor-analytics' ); ?></label>
				</th>
				<td>
					<input type="number" id="retention_days" name="retention_days"
							value="<?php echo esc_attr( (int) $sva_settings['retention_days'] ); ?>"
							min="1" max="3650" class="small-text">
					<?php esc_html_e( 'days', 'stracini-visitor-analytics' ); ?>
					<p class="description"><?php esc_html_e( 'Visitor records older than this are deleted by the daily cleanup cron.', 'stracini-visitor-analytics' ); ?></p>
				</td>
			</tr>

		</table>

		<?php submit_button( __( 'Save Settings', 'stracini-visitor-analytics' ) ); ?>
	</form>

	<!-- About / API info -->
	<hr>
	<div class="sva-panel" style="margin-top:16px;">
		<h3><?php esc_html_e( 'About IpQuery for WordPress', 'stracini-visitor-analytics' ); ?></h3>
		<p>
			<?php esc_html_e( 'This plugin uses the free IpQuery API (', 'stracini-visitor-analytics' ); ?>
			<a href="https://ipquery.io" target="_blank" rel="noopener">ipquery.io</a>
			<?php esc_html_e( ') to enrich visitor IP addresses with location, ISP, and risk data. No API key is required for basic usage.', 'stracini-visitor-analytics' ); ?>
		</p>
		<p>
			<?php esc_html_e( 'Lookups are cached for 1 hour per IP via WordPress transients to minimise API calls and avoid rate limits.', 'stracini-visitor-analytics' ); ?>
		</p>
		<p>
			<a href="https://guilherme.stracini.com.br/ipquery-wordpress/" target="_blank" rel="noopener" class="button button-secondary">
				<?php esc_html_e( 'Documentation', 'stracini-visitor-analytics' ); ?>
			</a>
			&nbsp;
			<a href="https://github.com/guibranco/ipquery-wordpress" target="_blank" rel="noopener" class="button button-secondary">
				<?php esc_html_e( 'GitHub', 'stracini-visitor-analytics' ); ?>
			</a>
		</p>
		<p>
			<strong><?php esc_html_e( 'PHP Library:', 'stracini-visitor-analytics' ); ?></strong>
			<a href="https://github.com/guibranco/ipquery-php" target="_blank" rel="noopener">guibranco/ipquery-php</a>
			&mdash;
			<?php esc_html_e( 'the underlying API client bundled inside this plugin.', 'stracini-visitor-analytics' ); ?>
		</p>

		<table class="widefat striped" style="max-width:500px;margin-top:12px;">
			<thead><tr><th><?php esc_html_e( 'Requirement', 'stracini-visitor-analytics' ); ?></th><th><?php esc_html_e( 'Status', 'stracini-visitor-analytics' ); ?></th></tr></thead>
			<tbody>
				<tr>
					<td><?php esc_html_e( 'PHP ≥ 8.2', 'stracini-visitor-analytics' ); ?></td>
					<td>
						<?php if ( version_compare( PHP_VERSION, '8.2', '>=' ) ) : ?>
							<span class="sva-badge sva-badge--green">✔ <?php echo esc_html( PHP_VERSION ); ?></span>
						<?php else : ?>
							<span class="sva-badge sva-badge--red">✘ <?php echo esc_html( PHP_VERSION ); ?></span>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'cURL extension', 'stracini-visitor-analytics' ); ?></td>
					<td>
						<?php if ( extension_loaded( 'curl' ) ) : ?>
							<span class="sva-badge sva-badge--green">✔ <?php esc_html_e( 'Enabled', 'stracini-visitor-analytics' ); ?></span>
						<?php else : ?>
							<span class="sva-badge sva-badge--red">✘ <?php esc_html_e( 'Missing', 'stracini-visitor-analytics' ); ?></span>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'WP Cron (cleanup)', 'stracini-visitor-analytics' ); ?></td>
					<td>
						<?php $sva_next = wp_next_scheduled( 'sva_daily_cleanup' ); ?>
						<?php if ( $sva_next ) : ?>
							<span class="sva-badge sva-badge--green">✔ <?php echo esc_html( wp_date( 'Y-m-d H:i', $sva_next ) ); ?></span>
						<?php else : ?>
							<span class="sva-badge sva-badge--orange"><?php esc_html_e( 'Not scheduled', 'stracini-visitor-analytics' ); ?></span>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'DB version', 'stracini-visitor-analytics' ); ?></td>
					<td><?php echo esc_html( get_option( 'sva_db_version', '—' ) ); ?></td>
				</tr>
			</tbody>
		</table>
	</div>

</div>
