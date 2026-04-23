<?php
/**
 * Settings admin view.
 *
 * @package IpQuery
 */

defined( 'ABSPATH' ) || exit; ?>

<div class="wrap ipquery-wrap">
	<h1 class="wp-heading-inline">
		<span class="dashicons dashicons-admin-settings"></span>
		<?php esc_html_e( 'IpQuery — Settings', 'ipquery' ); ?>
	</h1>
	<hr class="wp-header-end">

	<?php
	$ipquery_raw      = get_option( 'ipquery_settings', array() );
	$ipquery_defaults = array(
		'tracking_enabled'   => true,
		'track_logged_in'    => false,
		'track_admins'       => false,
		'excluded_ips'       => '',
		'retention_days'     => 90,
		'lookup_private_ips' => false,
	);
	$ipquery_settings = wp_parse_args( $ipquery_raw, $ipquery_defaults );
	?>

	<form method="post" action="">
		<?php wp_nonce_field( 'ipquery_save_settings', 'ipquery_settings_nonce' ); ?>

		<table class="form-table" role="presentation">

			<tr>
				<th scope="row"><?php esc_html_e( 'Enable Tracking', 'ipquery' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="tracking_enabled" value="1" <?php checked( $ipquery_settings['tracking_enabled'] ); ?>>
						<?php esc_html_e( 'Track visitor IPs on every page load', 'ipquery' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Disable to pause all tracking without deactivating the plugin.', 'ipquery' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Track Logged-in Users', 'ipquery' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="track_logged_in" value="1" <?php checked( $ipquery_settings['track_logged_in'] ); ?>>
						<?php esc_html_e( 'Also track visitors who are logged in to WordPress', 'ipquery' ); ?>
					</label>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Track Administrators', 'ipquery' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="track_admins" value="1" <?php checked( $ipquery_settings['track_admins'] ); ?>>
						<?php esc_html_e( 'Track users with the manage_options capability', 'ipquery' ); ?>
					</label>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Look Up Private IPs', 'ipquery' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="lookup_private_ips" value="1" <?php checked( $ipquery_settings['lookup_private_ips'] ); ?>>
						<?php esc_html_e( 'Send private/LAN IPs to the IpQuery API (useful for testing on localhost)', 'ipquery' ); ?>
					</label>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="excluded_ips"><?php esc_html_e( 'Excluded IPs', 'ipquery' ); ?></label>
				</th>
				<td>
					<textarea id="excluded_ips" name="excluded_ips" rows="6" class="large-text code"><?php echo esc_textarea( $ipquery_settings['excluded_ips'] ); ?></textarea>
					<p class="description"><?php esc_html_e( 'One IP per line. These IPs will never be tracked.', 'ipquery' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="retention_days"><?php esc_html_e( 'Data Retention', 'ipquery' ); ?></label>
				</th>
				<td>
					<input type="number" id="retention_days" name="retention_days"
							value="<?php echo esc_attr( (int) $ipquery_settings['retention_days'] ); ?>"
							min="1" max="3650" class="small-text">
					<?php esc_html_e( 'days', 'ipquery' ); ?>
					<p class="description"><?php esc_html_e( 'Visitor records older than this are deleted by the daily cleanup cron.', 'ipquery' ); ?></p>
				</td>
			</tr>

		</table>

		<?php submit_button( __( 'Save Settings', 'ipquery' ) ); ?>
	</form>

	<!-- About / API info -->
	<hr>
	<div class="ipquery-panel" style="margin-top:16px;">
		<h3><?php esc_html_e( 'About IpQuery for WordPress', 'ipquery' ); ?></h3>
		<p>
			<?php esc_html_e( 'This plugin uses the free IpQuery API (', 'ipquery' ); ?>
			<a href="https://ipquery.io" target="_blank" rel="noopener">ipquery.io</a>
			<?php esc_html_e( ') to enrich visitor IP addresses with location, ISP, and risk data. No API key is required for basic usage.', 'ipquery' ); ?>
		</p>
		<p>
			<?php esc_html_e( 'Lookups are cached for 1 hour per IP via WordPress transients to minimise API calls and avoid rate limits.', 'ipquery' ); ?>
		</p>
		<p>
			<a href="https://guilherme.stracini.com.br/ipquery-wordpress/" target="_blank" rel="noopener" class="button button-secondary">
				<?php esc_html_e( 'Documentation', 'ipquery' ); ?>
			</a>
			&nbsp;
			<a href="https://github.com/guibranco/ipquery-wordpress" target="_blank" rel="noopener" class="button button-secondary">
				<?php esc_html_e( 'GitHub', 'ipquery' ); ?>
			</a>
		</p>
		<p>
			<strong><?php esc_html_e( 'PHP Library:', 'ipquery' ); ?></strong>
			<a href="https://github.com/guibranco/ipquery-php" target="_blank" rel="noopener">guibranco/ipquery-php</a>
			&mdash;
			<?php esc_html_e( 'the underlying API client bundled inside this plugin.', 'ipquery' ); ?>
		</p>

		<table class="widefat striped" style="max-width:500px;margin-top:12px;">
			<thead><tr><th><?php esc_html_e( 'Requirement', 'ipquery' ); ?></th><th><?php esc_html_e( 'Status', 'ipquery' ); ?></th></tr></thead>
			<tbody>
				<tr>
					<td><?php esc_html_e( 'PHP ≥ 8.2', 'ipquery' ); ?></td>
					<td>
						<?php if ( version_compare( PHP_VERSION, '8.2', '>=' ) ) : ?>
							<span class="ipquery-badge ipquery-badge--green">✔ <?php echo esc_html( PHP_VERSION ); ?></span>
						<?php else : ?>
							<span class="ipquery-badge ipquery-badge--red">✘ <?php echo esc_html( PHP_VERSION ); ?></span>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'cURL extension', 'ipquery' ); ?></td>
					<td>
						<?php if ( extension_loaded( 'curl' ) ) : ?>
							<span class="ipquery-badge ipquery-badge--green">✔ <?php esc_html_e( 'Enabled', 'ipquery' ); ?></span>
						<?php else : ?>
							<span class="ipquery-badge ipquery-badge--red">✘ <?php esc_html_e( 'Missing', 'ipquery' ); ?></span>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'WP Cron (cleanup)', 'ipquery' ); ?></td>
					<td>
						<?php $ipquery_next = wp_next_scheduled( 'ipquery_daily_cleanup' ); ?>
						<?php if ( $ipquery_next ) : ?>
							<span class="ipquery-badge ipquery-badge--green">✔ <?php echo esc_html( wp_date( 'Y-m-d H:i', $ipquery_next ) ); ?></span>
						<?php else : ?>
							<span class="ipquery-badge ipquery-badge--orange"><?php esc_html_e( 'Not scheduled', 'ipquery' ); ?></span>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'DB version', 'ipquery' ); ?></td>
					<td><?php echo esc_html( get_option( 'ipquery_db_version', '—' ) ); ?></td>
				</tr>
			</tbody>
		</table>
	</div>

</div>
