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
			$ipq_risk_filter = sanitize_text_field( wp_unslash( $_GET['risk_filter'] ?? '' ) );
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
			?>
			<select name="risk_filter">
				<option value=""><?php esc_html_e( 'All types', 'ipquery' ); ?></option>
				<option value="is_vpn"        <?php selected( $ipq_risk_filter, 'is_vpn' ); ?>><?php esc_html_e( 'VPN', 'ipquery' ); ?></option>
				<option value="is_proxy"      <?php selected( $ipq_risk_filter, 'is_proxy' ); ?>><?php esc_html_e( 'Proxy', 'ipquery' ); ?></option>
				<option value="is_tor"        <?php selected( $ipq_risk_filter, 'is_tor' ); ?>><?php esc_html_e( 'Tor', 'ipquery' ); ?></option>
				<option value="is_datacenter" <?php selected( $ipq_risk_filter, 'is_datacenter' ); ?>><?php esc_html_e( 'Datacenter', 'ipquery' ); ?></option>
				<option value="is_mobile"     <?php selected( $ipq_risk_filter, 'is_mobile' ); ?>><?php esc_html_e( 'Mobile', 'ipquery' ); ?></option>
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
	$ipq_per_page     = 25;
	$ipq_current_page = max( 1, (int) sanitize_text_field( wp_unslash( $_GET['paged'] ?? '1' ) ) );
	$ipq_orderby      = sanitize_text_field( wp_unslash( $_GET['orderby'] ?? 'last_seen' ) );
	$ipq_order        = 'ASC' === strtoupper( sanitize_text_field( wp_unslash( $_GET['order'] ?? 'DESC' ) ) ) ? 'ASC' : 'DESC';
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	$ipq_result = IpQuery_DB::get_visitors(
		array(
			'per_page'    => $ipq_per_page,
			'page'        => $ipq_current_page,
			'orderby'     => $ipq_orderby,
			'order'       => $ipq_order,
			'search'      => sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'risk_filter' => $ipq_risk_filter,
		)
	);

	$ipq_rows        = $ipq_result['rows'];
	$ipq_total       = $ipq_result['total'];
	$ipq_total_pages = ceil( $ipq_total / $ipq_per_page );

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
		$arrow = $is_sorted ? ( 'ASC' === $current_order ? ' ▲' : ' ▼' ) : '';
		return '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . esc_html( $arrow ) . '</a>';
	}
	?>

	<p class="ipquery-count">
		<?php
		// translators: %s is the formatted record count.
		printf(
			esc_html( _n( '%s record found.', '%s records found.', $ipq_total, 'ipquery' ) ),
			'<strong>' . esc_html( number_format_i18n( $ipq_total ) ) . '</strong>'
		);
		?>
	</p>

	<table class="widefat striped ipquery-visitors-table">
		<thead>
			<tr>
				<th><?php echo wp_kses_post( ipquery_sortable_col( 'ip', __( 'IP Address', 'ipquery' ), $ipq_orderby, $ipq_order ) ); ?></th>
				<th><?php echo wp_kses_post( ipquery_sortable_col( 'country', __( 'Location', 'ipquery' ), $ipq_orderby, $ipq_order ) ); ?></th>
				<th><?php esc_html_e( 'ISP', 'ipquery' ); ?></th>
				<th><?php esc_html_e( 'Risk Flags', 'ipquery' ); ?></th>
				<th><?php echo wp_kses_post( ipquery_sortable_col( 'risk_score', __( 'Score', 'ipquery' ), $ipq_orderby, $ipq_order ) ); ?></th>
				<th><?php echo wp_kses_post( ipquery_sortable_col( 'visit_count', __( 'Visits', 'ipquery' ), $ipq_orderby, $ipq_order ) ); ?></th>
				<th><?php echo wp_kses_post( ipquery_sortable_col( 'first_seen', __( 'First Seen', 'ipquery' ), $ipq_orderby, $ipq_order ) ); ?></th>
				<th><?php echo wp_kses_post( ipquery_sortable_col( 'last_seen', __( 'Last Seen', 'ipquery' ), $ipq_orderby, $ipq_order ) ); ?></th>
				<th><?php esc_html_e( 'Actions', 'ipquery' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php if ( empty( $ipq_rows ) ) : ?>
			<tr><td colspan="9"><?php esc_html_e( 'No records found.', 'ipquery' ); ?></td></tr>
		<?php else : ?>
			<?php
			$ipq_allowed_badge_html = array(
				'span' => array( 'class' => true ),
			);
			foreach ( $ipq_rows as $ipq_row ) :
				$ipq_flags = array();
				if ( $ipq_row['is_vpn'] ) {
					$ipq_flags[] = '<span class="ipquery-badge ipquery-badge--orange">VPN</span>';
				}
				if ( $ipq_row['is_proxy'] ) {
					$ipq_flags[] = '<span class="ipquery-badge ipquery-badge--red">Proxy</span>';
				}
				if ( $ipq_row['is_tor'] ) {
					$ipq_flags[] = '<span class="ipquery-badge ipquery-badge--red">Tor</span>';
				}
				if ( $ipq_row['is_datacenter'] ) {
					$ipq_flags[] = '<span class="ipquery-badge ipquery-badge--blue">DC</span>';
				}
				if ( $ipq_row['is_mobile'] ) {
					$ipq_flags[] = '<span class="ipquery-badge ipquery-badge--green">Mobile</span>';
				}

				$ipq_score     = (int) $ipq_row['risk_score'];
				$ipq_score_cls = $ipq_score >= 80 ? 'red' : ( $ipq_score >= 40 ? 'orange' : 'green' );
				$ipq_location  = array_filter( array( $ipq_row['city'], $ipq_row['state'], $ipq_row['country'] ) );
				?>
			<tr>
				<td><code><?php echo esc_html( $ipq_row['ip'] ); ?></code></td>
				<td>
					<?php if ( $ipq_row['country_code'] ) : ?>
						<img src="https://flagcdn.com/16x12/<?php echo esc_attr( strtolower( $ipq_row['country_code'] ) ); ?>.png"
							width="16" height="12"
							alt="<?php echo esc_attr( $ipq_row['country_code'] ); ?>"
							style="vertical-align:middle;margin-right:4px;">
					<?php endif; ?>
					<?php echo esc_html( implode( ', ', $ipq_location ) !== '' ? implode( ', ', $ipq_location ) : '—' ); ?>
				</td>
				<td><?php echo esc_html( $ipq_row['isp'] ?? '—' ); ?></td>
				<td>
					<?php
					if ( $ipq_flags ) {
						echo wp_kses( implode( ' ', $ipq_flags ), $ipq_allowed_badge_html );
					} else {
						echo '<span class="ipquery-badge ipquery-badge--green">Clean</span>';
					}
					?>
				</td>
				<td><span class="ipquery-score ipquery-score--<?php echo esc_attr( $ipq_score_cls ); ?>"><?php echo esc_html( $ipq_score ); ?></span></td>
				<td><?php echo esc_html( number_format_i18n( (int) $ipq_row['visit_count'] ) ); ?></td>
				<td><?php echo esc_html( wp_date( get_option( 'date_format' ), strtotime( $ipq_row['first_seen'] ) ) ); ?></td>
				<td><?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $ipq_row['last_seen'] ) ) ); ?></td>
				<td>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
						<?php wp_nonce_field( 'ipquery_delete_ip' ); ?>
						<input type="hidden" name="action" value="ipquery_delete_ip">
						<input type="hidden" name="ip" value="<?php echo esc_attr( $ipq_row['ip'] ); ?>">
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

	<?php if ( $ipq_total_pages > 1 ) : ?>
	<div class="tablenav bottom">
		<div class="tablenav-pages">
			<?php
			echo wp_kses_post(
				paginate_links(
					array(
						'base'      => add_query_arg( 'paged', '%#%' ),
						'format'    => '',
						'current'   => $ipq_current_page,
						'total'     => $ipq_total_pages,
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

</div>
