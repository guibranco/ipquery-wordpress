<?php
/**
 * Visitors admin view — searchable, sortable IP log with lookup and purge tools.
 *
 * @package SVA
 */

defined( 'ABSPATH' ) || exit; ?>

<div class="wrap sva-wrap">
	<h1 class="wp-heading-inline">
		<span class="dashicons dashicons-admin-users"></span>
		<?php esc_html_e( 'Visitor Analytics — Visitors', 'stracini-visitor-analytics' ); ?>
	</h1>
	<hr class="wp-header-end">

	<?php
	// Admin notices — these are redirect params set by the action handlers, not raw user input.
	if ( isset( $_GET['deleted'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'IP deleted.', 'stracini-visitor-analytics' ) . '</p></div>';
	elseif ( isset( $_GET['purged'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		// translators: %d is the number of old records that were removed.
		printf( '<div class="notice notice-success is-dismissible"><p>' . esc_html__( '%d old records removed.', 'stracini-visitor-analytics' ) . '</p></div>', (int) $_GET['purged'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	elseif ( isset( $_GET['lookup_ok'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'IP looked up and stored.', 'stracini-visitor-analytics' ) . '</p></div>';
	elseif ( isset( $_GET['lookup_error'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		// translators: %s is the error message returned by the lookup.
		echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( sprintf( __( 'Lookup error: %s', 'stracini-visitor-analytics' ), urldecode( sanitize_text_field( wp_unslash( $_GET['lookup_error'] ) ) ) ) ) . '</p></div>'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	elseif ( isset( $_GET['country_deleted'] ) && 'false' !== $_GET['country_deleted'] ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$sva_deleted_count   = (int) $_GET['country_deleted']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$sva_deleted_country = esc_html( strtoupper( sanitize_text_field( wp_unslash( $_GET['country_code'] ?? '' ) ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		printf(
			'<div class="notice notice-success is-dismissible"><p>' . esc_html(
				sprintf(
					// translators: %1$d is the number of records deleted, %2$s is the country code.
					_n(
						'%1$d record deleted for country: %2$s.',
						'%1$d records deleted for country: %2$s.',
						$sva_deleted_count,
						'stracini-visitor-analytics'
					),
					$sva_deleted_count,
					$sva_deleted_country
				)
			) . '</p></div>'
		);
	elseif ( isset( $_GET['country_delete_error'] ) || ( isset( $_GET['country_deleted'] ) && 'false' === $_GET['country_deleted'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Failed to delete records. Please try again.', 'stracini-visitor-analytics' ) . '</p></div>';
	endif;
	?>

	<!-- Toolbar: search + filters + manual lookup -->
	<div class="sva-toolbar">
		<form method="get" action="" class="sva-filter-form">
			<input type="hidden" name="page" value="sva-visitors">

			<input type="search"
					name="s"
					value="<?php echo esc_attr( sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>"
					placeholder="<?php esc_attr_e( 'Search IP, city, country, ISP…', 'stracini-visitor-analytics' ); ?>"
					class="regular-text">

			<?php
			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			$sva_risk_filter = sanitize_text_field( wp_unslash( $_GET['risk_filter'] ?? '' ) );
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
			?>
			<select name="risk_filter">
				<option value=""><?php esc_html_e( 'All types', 'stracini-visitor-analytics' ); ?></option>
				<option value="is_vpn"        <?php selected( $sva_risk_filter, 'is_vpn' ); ?>><?php esc_html_e( 'VPN', 'stracini-visitor-analytics' ); ?></option>
				<option value="is_proxy"      <?php selected( $sva_risk_filter, 'is_proxy' ); ?>><?php esc_html_e( 'Proxy', 'stracini-visitor-analytics' ); ?></option>
				<option value="is_tor"        <?php selected( $sva_risk_filter, 'is_tor' ); ?>><?php esc_html_e( 'Tor', 'stracini-visitor-analytics' ); ?></option>
				<option value="is_datacenter" <?php selected( $sva_risk_filter, 'is_datacenter' ); ?>><?php esc_html_e( 'Datacenter', 'stracini-visitor-analytics' ); ?></option>
				<option value="is_mobile"     <?php selected( $sva_risk_filter, 'is_mobile' ); ?>><?php esc_html_e( 'Mobile', 'stracini-visitor-analytics' ); ?></option>
			</select>

			<?php submit_button( __( 'Filter', 'stracini-visitor-analytics' ), 'secondary', 'filter', false ); ?>
			<?php
			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			if ( ! empty( $_GET['s'] ) || ! empty( $_GET['risk_filter'] ) ) :
				// phpcs:enable WordPress.Security.NonceVerification.Recommended
				?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=sva-visitors' ) ); ?>" class="button"><?php esc_html_e( 'Reset', 'stracini-visitor-analytics' ); ?></a>
			<?php endif; ?>
		</form>

		<!-- Manual IP lookup -->
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sva-lookup-form">
			<?php wp_nonce_field( 'sva_manual_lookup' ); ?>
			<input type="hidden" name="action" value="sva_lookup">
			<input type="text" name="ip" placeholder="<?php esc_attr_e( 'Lookup IP…', 'stracini-visitor-analytics' ); ?>" class="regular-text" pattern="^(\d{1,3}\.){3}\d{1,3}$|^[0-9a-fA-F:]+$">
			<?php submit_button( __( 'Lookup', 'stracini-visitor-analytics' ), 'secondary', 'lookup_btn', false ); ?>
		</form>

		<!-- Export CSV (carries active filters into the POST body) -->
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sva-export-form">
			<?php wp_nonce_field( 'sva_export_csv' ); ?>
			<input type="hidden" name="action" value="sva_export_csv">
			<input type="hidden" name="s" value="<?php echo esc_attr( sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>">
			<input type="hidden" name="risk_filter" value="<?php echo esc_attr( $sva_risk_filter ); ?>">
			<?php submit_button( __( 'Export CSV', 'stracini-visitor-analytics' ), 'secondary', 'export_csv_btn', false ); ?>
		</form>
	</div>

	<?php
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$sva_per_page     = 25;
	$sva_current_page = max( 1, (int) sanitize_text_field( wp_unslash( $_GET['paged'] ?? '1' ) ) );
	$sva_orderby      = sanitize_text_field( wp_unslash( $_GET['orderby'] ?? 'last_seen' ) );
	$sva_order        = 'ASC' === strtoupper( sanitize_text_field( wp_unslash( $_GET['order'] ?? 'DESC' ) ) ) ? 'ASC' : 'DESC';
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	$sva_result = SVA_DB::get_visitors(
		array(
			'per_page'    => $sva_per_page,
			'page'        => $sva_current_page,
			'orderby'     => $sva_orderby,
			'order'       => $sva_order,
			'search'      => sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'risk_filter' => $sva_risk_filter,
		)
	);

	$sva_rows        = $sva_result['rows'];
	$sva_total       = $sva_result['total'];
	$sva_total_pages = ceil( $sva_total / $sva_per_page );

	/**
	 * Returns a sortable column header link.
	 *
	 * @param string $col             Column key.
	 * @param string $label           Display label.
	 * @param string $current_orderby Currently active sort column.
	 * @param string $current_order   Currently active sort direction.
	 * @return string Safe HTML anchor.
	 */
	function sva_sortable_col( string $col, string $label, string $current_orderby, string $current_order ): string {
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

	<p class="sva-count">
		<?php
		printf(
			// translators: %s is the formatted record count.
			esc_html( _n( '%s record found.', '%s records found.', $sva_total, 'stracini-visitor-analytics' ) ),
			'<strong>' . esc_html( number_format_i18n( $sva_total ) ) . '</strong>'
		);
		?>
	</p>

	<table class="widefat striped sva-visitors-table">
		<thead>
			<tr>
				<th><?php echo wp_kses_post( sva_sortable_col( 'ip', __( 'IP Address', 'stracini-visitor-analytics' ), $sva_orderby, $sva_order ) ); ?></th>
				<th><?php echo wp_kses_post( sva_sortable_col( 'country', __( 'Location', 'stracini-visitor-analytics' ), $sva_orderby, $sva_order ) ); ?></th>
				<th><?php esc_html_e( 'ISP', 'stracini-visitor-analytics' ); ?></th>
				<th><?php esc_html_e( 'Risk Flags', 'stracini-visitor-analytics' ); ?></th>
				<th><?php echo wp_kses_post( sva_sortable_col( 'risk_score', __( 'Score', 'stracini-visitor-analytics' ), $sva_orderby, $sva_order ) ); ?></th>
				<th><?php echo wp_kses_post( sva_sortable_col( 'visit_count', __( 'Visits', 'stracini-visitor-analytics' ), $sva_orderby, $sva_order ) ); ?></th>
				<th><?php echo wp_kses_post( sva_sortable_col( 'first_seen', __( 'First Seen', 'stracini-visitor-analytics' ), $sva_orderby, $sva_order ) ); ?></th>
				<th><?php echo wp_kses_post( sva_sortable_col( 'last_seen', __( 'Last Seen', 'stracini-visitor-analytics' ), $sva_orderby, $sva_order ) ); ?></th>
				<th><?php esc_html_e( 'Actions', 'stracini-visitor-analytics' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php if ( empty( $sva_rows ) ) : ?>
			<tr><td colspan="9"><?php esc_html_e( 'No records found.', 'stracini-visitor-analytics' ); ?></td></tr>
		<?php else : ?>
			<?php
			$sva_allowed_badge_html = array(
				'span' => array( 'class' => true ),
			);
			foreach ( $sva_rows as $sva_row ) :
				$sva_flags = array();
				if ( $sva_row['is_vpn'] ) {
					$sva_flags[] = '<span class="sva-badge sva-badge--orange">VPN</span>';
				}
				if ( $sva_row['is_proxy'] ) {
					$sva_flags[] = '<span class="sva-badge sva-badge--red">Proxy</span>';
				}
				if ( $sva_row['is_tor'] ) {
					$sva_flags[] = '<span class="sva-badge sva-badge--red">Tor</span>';
				}
				if ( $sva_row['is_datacenter'] ) {
					$sva_flags[] = '<span class="sva-badge sva-badge--blue">DC</span>';
				}
				if ( $sva_row['is_mobile'] ) {
					$sva_flags[] = '<span class="sva-badge sva-badge--green">Mobile</span>';
				}

				$sva_score     = (int) $sva_row['risk_score'];
				$sva_score_cls = $sva_score >= 80 ? 'red' : ( $sva_score >= 40 ? 'orange' : 'green' );
				$sva_location  = array_filter( array( $sva_row['city'], $sva_row['state'], $sva_row['country'] ) );
				?>
			<tr>
				<td><code><?php echo esc_html( $sva_row['ip'] ); ?></code></td>
				<td>
					<?php if ( $sva_row['country_code'] ) : ?>
						<img src="https://flagcdn.com/16x12/<?php echo esc_attr( strtolower( $sva_row['country_code'] ) ); ?>.png"
							width="16" height="12"
							alt="<?php echo esc_attr( $sva_row['country_code'] ); ?>"
							style="vertical-align:middle;margin-right:4px;">
					<?php endif; ?>
					<?php echo esc_html( implode( ', ', $sva_location ) !== '' ? implode( ', ', $sva_location ) : '—' ); ?>
				</td>
				<td><?php echo esc_html( $sva_row['isp'] ?? '—' ); ?></td>
				<td>
					<?php
					if ( $sva_flags ) {
						echo wp_kses( implode( ' ', $sva_flags ), $sva_allowed_badge_html );
					} else {
						echo '<span class="sva-badge sva-badge--green">Clean</span>';
					}
					?>
				</td>
				<td><span class="sva-score sva-score--<?php echo esc_attr( $sva_score_cls ); ?>"><?php echo esc_html( $sva_score ); ?></span></td>
				<td><?php echo esc_html( number_format_i18n( (int) $sva_row['visit_count'] ) ); ?></td>
				<td><?php echo esc_html( wp_date( get_option( 'date_format' ), strtotime( $sva_row['first_seen'] ) ) ); ?></td>
				<td><?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $sva_row['last_seen'] ) ) ); ?></td>
				<td>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
						<?php wp_nonce_field( 'sva_delete_ip' ); ?>
						<input type="hidden" name="action" value="sva_delete_ip">
						<input type="hidden" name="ip" value="<?php echo esc_attr( $sva_row['ip'] ); ?>">
						<button type="submit" class="button button-small button-link-delete"
								onclick="return confirm('<?php echo esc_js( __( 'Delete this IP record?', 'stracini-visitor-analytics' ) ); ?>')">
							<?php esc_html_e( 'Delete', 'stracini-visitor-analytics' ); ?>
						</button>
					</form>
				</td>
			</tr>
			<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
	</table>

	<?php if ( $sva_total_pages > 1 ) : ?>
	<div class="tablenav bottom">
		<div class="tablenav-pages">
			<?php
			echo wp_kses_post(
				paginate_links(
					array(
						'base'      => add_query_arg( 'paged', '%#%' ),
						'format'    => '',
						'current'   => $sva_current_page,
						'total'     => $sva_total_pages,
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
	<div class="sva-panel" style="margin-top:24px;">
		<h3><?php esc_html_e( 'Purge Old Records', 'stracini-visitor-analytics' ); ?></h3>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'sva_purge' ); ?>
			<input type="hidden" name="action" value="sva_purge">
			<label>
				<?php esc_html_e( 'Delete records older than', 'stracini-visitor-analytics' ); ?>
				<input type="number" name="days" value="90" min="1" max="3650" style="width:70px;"> <?php esc_html_e( 'days', 'stracini-visitor-analytics' ); ?>
			</label>
			<?php submit_button( __( 'Purge', 'stracini-visitor-analytics' ), 'secondary', 'purge_btn', false ); ?>
		</form>
	</div>

	<!-- Delete by Country -->
	<div class="sva-panel sva-panel--spaced">
		<h3><?php esc_html_e( 'Delete by Country', 'stracini-visitor-analytics' ); ?></h3>
		<p><?php esc_html_e( 'Permanently delete all visitor records from a specific country (GDPR right-to-erasure).', 'stracini-visitor-analytics' ); ?></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'sva_delete_by_country' ); ?>
			<input type="hidden" name="action" value="sva_delete_by_country">
			<label>
				<?php esc_html_e( 'Country:', 'stracini-visitor-analytics' ); ?>
				<select name="country_code" required>
					<option value=""><?php esc_html_e( '— Select a country —', 'stracini-visitor-analytics' ); ?></option>
					<?php
					// Fetch distinct countries that actually have records.
					$sva_countries = SVA_DB::get_distinct_countries();
					foreach ( $sva_countries as $sva_country ) :
						if ( empty( $sva_country['country_code'] ) ) {
							continue;
						}
						?>
					<option value="<?php echo esc_attr( $sva_country['country_code'] ); ?>">
						<?php echo esc_html( $sva_country['country'] . ' (' . $sva_country['country_code'] . ')' ); ?>
					</option>
					<?php endforeach; ?>
				</select>
			</label>
			<?php
			submit_button(
				__( 'Delete Records', 'stracini-visitor-analytics' ),
				'secondary',
				'delete_country_btn',
				false,
				array(
					'onclick' => "return confirm('" . esc_js( __( 'Are you sure? This will permanently delete ALL visitor records from the selected country. This cannot be undone.', 'stracini-visitor-analytics' ) ) . "')",
				)
			);
			?>
		</form>
	</div>


</div>
