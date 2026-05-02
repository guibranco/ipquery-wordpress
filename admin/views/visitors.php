<?php
/**
 * Visitors admin view — searchable, sortable IP log with lookup and purge tools.
 *
 * @package IpQuery
 */

defined( 'ABSPATH' ) || exit; ?>

<div class="wrap ipquery-wrap">
	<h1 class="wp-heading-inline">
		<span class="dashicons dashicons-admin-users"></span>
		<?php esc_html_e( 'IpQuery — Visitors', 'ipquery' ); ?>
	</h1>
	<hr class="wp-header-end">

	<?php
	// Admin notices — these are redirect params set by the action handlers, not raw user input.
	if ( isset( $_GET['deleted'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'IP deleted.', 'ipquery' ) . '</p></div>';
	elseif ( isset( $_GET['purged'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		// translators: %d is the number of old records that were removed.
		printf( '<div class="notice notice-success is-dismissible"><p>' . esc_html__( '%d old records removed.', 'ipquery' ) . '</p></div>', (int) $_GET['purged'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	elseif ( isset( $_GET['lookup_ok'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'IP looked up and stored.', 'ipquery' ) . '</p></div>';
	elseif ( isset( $_GET['lookup_error'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		// translators: %s is the error message returned by the lookup.
		echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( sprintf( __( 'Lookup error: %s', 'ipquery' ), urldecode( sanitize_text_field( wp_unslash( $_GET['lookup_error'] ) ) ) ) ) . '</p></div>'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	elseif ( isset( $_GET['country_deleted'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		printf(
			'<div class="notice notice-success is-dismissible"><p>' . esc_html__( '%1$d record(s) deleted for country: %2$s.', 'ipquery' ) . '</p></div>',
			(int) $_GET['country_deleted'], // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			esc_html( strtoupper( sanitize_text_field( wp_unslash( $_GET['country_code'] ?? '' ) ) ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		);
	endif;
	?>

	<!-- Toolbar: search + filters + manual lookup -->
	<div class="ipquery-toolbar">
		<form method="get" action="" class="ipquery-filter-form">
			<input type="hidden" name="page" value="ipquery-visitors">

			<input type="search"
					name="s"
					value="<?php echo esc_attr( sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>"
					placeholder="<?php esc_attr_e( 'Search IP, city, country, ISP…', 'ipquery' ); ?>"
					class="regular-text">

			<?php
			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			$ipquery_risk_filter = sanitize_text_field( wp_unslash( $_GET['risk_filter'] ?? '' ) );
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
			?>
			<select name="risk_filter">
				<option value=""><?php esc_html_e( 'All types', 'ipquery' ); ?></option>
				<option value="is_vpn"        <?php selected( $ipquery_risk_filter, 'is_vpn' ); ?>><?php esc_html_e( 'VPN', 'ipquery' ); ?></option>
				<option value="is_proxy"      <?php selected( $ipquery_risk_filter, 'is_proxy' ); ?>><?php esc_html_e( 'Proxy', 'ipquery' ); ?></option>
				<option value="is_tor"        <?php selected( $ipquery_risk_filter, 'is_tor' ); ?>><?php esc_html_e( 'Tor', 'ipquery' ); ?></option>
				<option value="is_datacenter" <?php selected( $ipquery_risk_filter, 'is_datacenter' ); ?>><?php esc_html_e( 'Datacenter', 'ipquery' ); ?></option>
				<option value="is_mobile"     <?php selected( $ipquery_risk_filter, 'is_mobile' ); ?>><?php esc_html_e( 'Mobile', 'ipquery' ); ?></option>
			</select>

			<?php submit_button( __( 'Filter', 'ipquery' ), 'secondary', 'filter', false ); ?>
			<?php
			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			if ( ! empty( $_GET['s'] ) || ! empty( $_GET['risk_filter'] ) ) :
				// phpcs:enable WordPress.Security.NonceVerification.Recommended
				?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=ipquery-visitors' ) ); ?>" class="button"><?php esc_html_e( 'Reset', 'ipquery' ); ?></a>
			<?php endif; ?>
		</form>

		<!-- Manual IP lookup -->
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ipquery-lookup-form">
			<?php wp_nonce_field( 'ipquery_manual_lookup' ); ?>
			<input type="hidden" name="action" value="ipquery_lookup">
			<input type="text" name="ip" placeholder="<?php esc_attr_e( 'Lookup IP…', 'ipquery' ); ?>" class="regular-text" pattern="^(\d{1,3}\.){3}\d{1,3}$|^[0-9a-fA-F:]+$">
			<?php submit_button( __( 'Lookup', 'ipquery' ), 'secondary', 'lookup_btn', false ); ?>
		</form>
	</div>

	<?php
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$ipquery_per_page     = 25;
	$ipquery_current_page = max( 1, (int) sanitize_text_field( wp_unslash( $_GET['paged'] ?? '1' ) ) );
	$ipquery_orderby      = sanitize_text_field( wp_unslash( $_GET['orderby'] ?? 'last_seen' ) );
	$ipquery_order        = 'ASC' === strtoupper( sanitize_text_field( wp_unslash( $_GET['order'] ?? 'DESC' ) ) ) ? 'ASC' : 'DESC';
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	$ipquery_result = IpQuery_DB::get_visitors(
		array(
			'per_page'    => $ipquery_per_page,
			'page'        => $ipquery_current_page,
			'orderby'     => $ipquery_orderby,
			'order'       => $ipquery_order,
			'search'      => sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'risk_filter' => $ipquery_risk_filter,
		)
	);

	$ipquery_rows        = $ipquery_result['rows'];
	$ipquery_total       = $ipquery_result['total'];
	$ipquery_total_pages = ceil( $ipquery_total / $ipquery_per_page );

	/**
	 * Returns a sortable column header link.
	 *
	 * @param string $col             Column key.
	 * @param string $label           Display label.
	 * @param string $current_orderby Currently active sort column.
	 * @param string $current_order   Currently active sort direction.
	 * @return string Safe HTML anchor.
	 */
	function ipquery_sortable_col( string $col, string $label, string $current_orderby, string $current_order ): string {
		$is_sorted  = $col === $current_orderby;
		$next_order = ( $is_sorted && 'ASC' === $current_order ) ? 'DESC' : 'ASC';
		$url        = add_query_arg(
			array(
				'orderby' => $col,
				'order'   => $next_order,
			)
		);
		$arrow      = $is_sorted ? ( 'ASC' === $current_order ? ' ▲' : ' ▼' ) : '';
		return '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . esc_html( $arrow ) . '</a>';
	}
	?>

	<p class="ipquery-count">
		<?php
		printf(
			// translators: %s is the formatted record count.
			esc_html( _n( '%s record found.', '%s records found.', $ipquery_total, 'ipquery' ) ),
			'<strong>' . esc_html( number_format_i18n( $ipquery_total ) ) . '</strong>'
		);
		?>
	</p>

	<table class="widefat striped ipquery-visitors-table">
		<thead>
			<tr>
				<th><?php echo wp_kses_post( ipquery_sortable_col( 'ip', __( 'IP Address', 'ipquery' ), $ipquery_orderby, $ipquery_order ) ); ?></th>
				<th><?php echo wp_kses_post( ipquery_sortable_col( 'country', __( 'Location', 'ipquery' ), $ipquery_orderby, $ipquery_order ) ); ?></th>
				<th><?php esc_html_e( 'ISP', 'ipquery' ); ?></th>
				<th><?php esc_html_e( 'Risk Flags', 'ipquery' ); ?></th>
				<th><?php echo wp_kses_post( ipquery_sortable_col( 'risk_score', __( 'Score', 'ipquery' ), $ipquery_orderby, $ipquery_order ) ); ?></th>
				<th><?php echo wp_kses_post( ipquery_sortable_col( 'visit_count', __( 'Visits', 'ipquery' ), $ipquery_orderby, $ipquery_order ) ); ?></th>
				<th><?php echo wp_kses_post( ipquery_sortable_col( 'first_seen', __( 'First Seen', 'ipquery' ), $ipquery_orderby, $ipquery_order ) ); ?></th>
				<th><?php echo wp_kses_post( ipquery_sortable_col( 'last_seen', __( 'Last Seen', 'ipquery' ), $ipquery_orderby, $ipquery_order ) ); ?></th>
				<th><?php esc_html_e( 'Actions', 'ipquery' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php if ( empty( $ipquery_rows ) ) : ?>
			<tr><td colspan="9"><?php esc_html_e( 'No records found.', 'ipquery' ); ?></td></tr>
		<?php else : ?>
			<?php
			$ipquery_allowed_badge_html = array(
				'span' => array( 'class' => true ),
			);
			foreach ( $ipquery_rows as $ipquery_row ) :
				$ipquery_flags = array();
				if ( $ipquery_row['is_vpn'] ) {
					$ipquery_flags[] = '<span class="ipquery-badge ipquery-badge--orange">VPN</span>';
				}
				if ( $ipquery_row['is_proxy'] ) {
					$ipquery_flags[] = '<span class="ipquery-badge ipquery-badge--red">Proxy</span>';
				}
				if ( $ipquery_row['is_tor'] ) {
					$ipquery_flags[] = '<span class="ipquery-badge ipquery-badge--red">Tor</span>';
				}
				if ( $ipquery_row['is_datacenter'] ) {
					$ipquery_flags[] = '<span class="ipquery-badge ipquery-badge--blue">DC</span>';
				}
				if ( $ipquery_row['is_mobile'] ) {
					$ipquery_flags[] = '<span class="ipquery-badge ipquery-badge--green">Mobile</span>';
				}

				$ipquery_score     = (int) $ipquery_row['risk_score'];
				$ipquery_score_cls = $ipquery_score >= 80 ? 'red' : ( $ipquery_score >= 40 ? 'orange' : 'green' );
				$ipquery_location  = array_filter( array( $ipquery_row['city'], $ipquery_row['state'], $ipquery_row['country'] ) );
				?>
			<tr>
				<td><code><?php echo esc_html( $ipquery_row['ip'] ); ?></code></td>
				<td>
					<?php if ( $ipquery_row['country_code'] ) : ?>
						<img src="https://flagcdn.com/16x12/<?php echo esc_attr( strtolower( $ipquery_row['country_code'] ) ); ?>.png"
							width="16" height="12"
							alt="<?php echo esc_attr( $ipquery_row['country_code'] ); ?>"
							style="vertical-align:middle;margin-right:4px;">
					<?php endif; ?>
					<?php echo esc_html( implode( ', ', $ipquery_location ) !== '' ? implode( ', ', $ipquery_location ) : '—' ); ?>
				</td>
				<td><?php echo esc_html( $ipquery_row['isp'] ?? '—' ); ?></td>
				<td>
					<?php
					if ( $ipquery_flags ) {
						echo wp_kses( implode( ' ', $ipquery_flags ), $ipquery_allowed_badge_html );
					} else {
						echo '<span class="ipquery-badge ipquery-badge--green">Clean</span>';
					}
					?>
				</td>
				<td><span class="ipquery-score ipquery-score--<?php echo esc_attr( $ipquery_score_cls ); ?>"><?php echo esc_html( $ipquery_score ); ?></span></td>
				<td><?php echo esc_html( number_format_i18n( (int) $ipquery_row['visit_count'] ) ); ?></td>
				<td><?php echo esc_html( wp_date( get_option( 'date_format' ), strtotime( $ipquery_row['first_seen'] ) ) ); ?></td>
				<td><?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $ipquery_row['last_seen'] ) ) ); ?></td>
				<td>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
						<?php wp_nonce_field( 'ipquery_delete_ip' ); ?>
						<input type="hidden" name="action" value="ipquery_delete_ip">
						<input type="hidden" name="ip" value="<?php echo esc_attr( $ipquery_row['ip'] ); ?>">
						<button type="submit" class="button button-small button-link-delete"
								onclick="return confirm('<?php echo esc_js( __( 'Delete this IP record?', 'ipquery' ) ); ?>')">
							<?php esc_html_e( 'Delete', 'ipquery' ); ?>
						</button>
					</form>
				</td>
			</tr>
			<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
	</table>

	<?php if ( $ipquery_total_pages > 1 ) : ?>
	<div class="tablenav bottom">
		<div class="tablenav-pages">
			<?php
			echo wp_kses_post(
				paginate_links(
					array(
						'base'      => add_query_arg( 'paged', '%#%' ),
						'format'    => '',
						'current'   => $ipquery_current_page,
						'total'     => $ipquery_total_pages,
						'prev_text' => '&laquo;',
						'next_text' => '&raquo;',
					)
				)
			);
			?>
		</div>
	</div>
	<?php endif; ?>

	<!-- Purge old records -->
	<div class="ipquery-panel" style="margin-top:24px;">
		<h3><?php esc_html_e( 'Purge Old Records', 'ipquery' ); ?></h3>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'ipquery_purge' ); ?>
			<input type="hidden" name="action" value="ipquery_purge">
			<label>
				<?php esc_html_e( 'Delete records older than', 'ipquery' ); ?>
				<input type="number" name="days" value="90" min="1" max="3650" style="width:70px;"> <?php esc_html_e( 'days', 'ipquery' ); ?>
			</label>
			<?php submit_button( __( 'Purge', 'ipquery' ), 'secondary', 'purge_btn', false ); ?>
		</form>
	</div>

	<!-- Delete by Country -->
	<div class="ipquery-panel" style="margin-top:24px;">
		<h3><?php esc_html_e( 'Delete by Country', 'ipquery' ); ?></h3>
		<p><?php esc_html_e( 'Permanently delete all visitor records from a specific country (GDPR right-to-erasure).', 'ipquery' ); ?></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'ipquery_delete_by_country' ); ?>
			<input type="hidden" name="action" value="ipquery_delete_by_country">
			<label>
				<?php esc_html_e( 'Country:', 'ipquery' ); ?>
				<select name="country_code" required>
					<option value=""><?php esc_html_e( '— Select a country —', 'ipquery' ); ?></option>
					<?php
					// Fetch distinct countries that actually have records.
					$ipquery_countries = IpQuery_DB::get_distinct_countries();
					foreach ( $ipquery_countries as $ipquery_country ) :
						if ( empty( $ipquery_country['country_code'] ) ) {
							continue;
						}
						?>
					<option value="<?php echo esc_attr( $ipquery_country['country_code'] ); ?>">
						<?php echo esc_html( $ipquery_country['country'] . ' (' . $ipquery_country['country_code'] . ')' ); ?>
					</option>
					<?php endforeach; ?>
				</select>
			</label>
			<?php
			submit_button(
				__( 'Delete Records', 'ipquery' ),
				'secondary',
				'delete_country_btn',
				false,
				array(
					'onclick' => "return confirm('" . esc_js( __( 'Are you sure? This will permanently delete ALL visitor records from the selected country. This cannot be undone.', 'ipquery' ) ) . "')",
				)
			);
			?>
		</form>
	</div>


</div>
