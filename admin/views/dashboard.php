<?php
/**
 * Dashboard admin view — stat cards, heatmap, and charts.
 *
 * @package IpQuery
 */

defined( 'ABSPATH' ) || exit; ?>

<div class="wrap ipquery-wrap">
	<h1 class="wp-heading-inline">
		<span class="dashicons dashicons-location-alt"></span>
		<?php esc_html_e( 'IpQuery — Dashboard', 'ipquery' ); ?>
	</h1>
	<hr class="wp-header-end">

	<?php
	$ipquery_total_visits  = IpQuery_DB::get_total_visits();
	$ipquery_unique_ips    = IpQuery_DB::get_unique_ips();
	$ipquery_risk_counts   = IpQuery_DB::get_risk_counts();
	$ipquery_top_countries = IpQuery_DB::get_top_countries( 10 );
	$ipquery_top_cities    = IpQuery_DB::get_top_cities( 10 );
	?>

	<!-- Stat cards -->
	<div class="ipquery-cards">
		<div class="ipquery-card ipquery-card--blue">
			<div class="ipquery-card__icon dashicons dashicons-admin-users"></div>
			<div class="ipquery-card__body">
				<span class="ipquery-card__value"><?php echo esc_html( number_format_i18n( $ipquery_total_visits ) ); ?></span>
				<span class="ipquery-card__label"><?php esc_html_e( 'Total Visits', 'ipquery' ); ?></span>
			</div>
		</div>
		<div class="ipquery-card ipquery-card--green">
			<div class="ipquery-card__icon dashicons dashicons-networking"></div>
			<div class="ipquery-card__body">
				<span class="ipquery-card__value"><?php echo esc_html( number_format_i18n( $ipquery_unique_ips ) ); ?></span>
				<span class="ipquery-card__label"><?php esc_html_e( 'Unique IPs', 'ipquery' ); ?></span>
			</div>
		</div>
		<div class="ipquery-card ipquery-card--orange">
			<div class="ipquery-card__icon dashicons dashicons-shield-alt"></div>
			<div class="ipquery-card__body">
				<span class="ipquery-card__value"><?php echo esc_html( number_format_i18n( $ipquery_risk_counts['vpn'] + $ipquery_risk_counts['proxy'] + $ipquery_risk_counts['tor'] ) ); ?></span>
				<span class="ipquery-card__label"><?php esc_html_e( 'VPN / Proxy / Tor', 'ipquery' ); ?></span>
			</div>
		</div>
		<div class="ipquery-card ipquery-card--red">
			<div class="ipquery-card__icon dashicons dashicons-warning"></div>
			<div class="ipquery-card__body">
				<?php
				$ipquery_risky_pct = $ipquery_unique_ips > 0
					? round( ( ( $ipquery_risk_counts['vpn'] + $ipquery_risk_counts['proxy'] + $ipquery_risk_counts['tor'] ) / $ipquery_unique_ips ) * 100, 1 )
					: 0;
				?>
				<span class="ipquery-card__value"><?php echo esc_html( $ipquery_risky_pct ); ?>%</span>
				<span class="ipquery-card__label"><?php esc_html_e( 'Risk Rate', 'ipquery' ); ?></span>
			</div>
		</div>
	</div>

	<!-- Map + charts row -->
	<div class="ipquery-row">

		<!-- World heatmap -->
		<div class="ipquery-panel ipquery-panel--wide">
			<h2><?php esc_html_e( 'Visitor Heatmap', 'ipquery' ); ?></h2>
			<div id="ipquery-map" style="height:420px;"></div>
		</div>

		<!-- Country chart -->
		<div class="ipquery-panel ipquery-panel--narrow">
			<h2><?php esc_html_e( 'Top Countries', 'ipquery' ); ?></h2>
			<div class="ipquery-chart-wrap" style="position:relative;height:390px;overflow:hidden;">
				<canvas id="ipquery-country-chart"></canvas>
			</div>
		</div>

	</div>

	<!-- Risk breakdown row -->
	<div class="ipquery-row">

		<div class="ipquery-panel ipquery-panel--half">
			<h2><?php esc_html_e( 'Risk Breakdown', 'ipquery' ); ?></h2>
			<div class="ipquery-chart-wrap" style="position:relative;height:260px;overflow:hidden;">
				<canvas id="ipquery-risk-chart"></canvas>
			</div>
		</div>

		<!-- Country table -->
		<div class="ipquery-panel ipquery-panel--half">
			<h2><?php esc_html_e( 'Top Countries', 'ipquery' ); ?></h2>
			<table class="widefat striped ipquery-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Country', 'ipquery' ); ?></th>
						<th><?php esc_html_e( 'Visits', 'ipquery' ); ?></th>
						<th><?php esc_html_e( '%', 'ipquery' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php if ( empty( $ipquery_top_countries ) ) : ?>
					<tr><td colspan="3"><?php esc_html_e( 'No data yet.', 'ipquery' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $ipquery_top_countries as $ipquery_row ) : ?>
					<tr>
						<td>
							<?php if ( $ipquery_row['country_code'] ) : ?>
								<img src="https://flagcdn.com/16x12/<?php echo esc_attr( strtolower( $ipquery_row['country_code'] ) ); ?>.png"
									width="16" height="12"
									alt="<?php echo esc_attr( $ipquery_row['country_code'] ); ?>"
									style="vertical-align:middle;margin-right:4px;">
							<?php endif; ?>
							<?php echo esc_html( $ipquery_row['country'] ?? $ipquery_row['country_code'] ?? '—' ); ?>
						</td>
						<td><?php echo esc_html( number_format_i18n( (int) $ipquery_row['visits'] ) ); ?></td>
						<td><?php echo $ipquery_total_visits > 0 ? esc_html( round( ( $ipquery_row['visits'] / $ipquery_total_visits ) * 100, 1 ) ) . '%' : '—'; ?></td>
					</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
		</div>

	</div>

	<!-- Cities table -->
	<div class="ipquery-row">
		<div class="ipquery-panel ipquery-panel--half">
			<h2><?php esc_html_e( 'Top Cities', 'ipquery' ); ?></h2>
			<table class="widefat striped ipquery-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'City', 'ipquery' ); ?></th>
						<th><?php esc_html_e( 'Country', 'ipquery' ); ?></th>
						<th><?php esc_html_e( 'Visits', 'ipquery' ); ?></th>
						<th><?php esc_html_e( '%', 'ipquery' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php if ( empty( $ipquery_top_cities ) ) : ?>
					<tr><td colspan="4"><?php esc_html_e( 'No data yet.', 'ipquery' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $ipquery_top_cities as $ipquery_row ) : ?>
					<tr>
						<td><?php echo esc_html( $ipquery_row['city'] ); ?></td>
						<td>
							<?php if ( $ipquery_row['country_code'] ) : ?>
								<img src="https://flagcdn.com/16x12/<?php echo esc_attr( strtolower( $ipquery_row['country_code'] ) ); ?>.png"
									width="16" height="12"
									alt="<?php echo esc_attr( $ipquery_row['country_code'] ); ?>"
									style="vertical-align:middle;margin-right:4px;">
							<?php endif; ?>
							<?php echo esc_html( $ipquery_row['country'] ?? $ipquery_row['country_code'] ?? '—' ); ?>
						</td>
						<td><?php echo esc_html( number_format_i18n( (int) $ipquery_row['visits'] ) ); ?></td>
						<td><?php echo $ipquery_total_visits > 0 ? esc_html( round( ( $ipquery_row['visits'] / $ipquery_total_visits ) * 100, 1 ) ) . '%' : '—'; ?></td>
					</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>

	<!-- Risk detail cards -->
	<div class="ipquery-row">
		<div class="ipquery-panel">
			<h2><?php esc_html_e( 'Risk Details', 'ipquery' ); ?></h2>
			<div class="ipquery-risk-grid">
				<?php
				$ipquery_risk_items = array(
					array(
						'label' => __( 'VPN', 'ipquery' ),
						'count' => $ipquery_risk_counts['vpn'],
						'icon'  => 'lock',
						'class' => 'orange',
					),
					array(
						'label' => __( 'Proxy', 'ipquery' ),
						'count' => $ipquery_risk_counts['proxy'],
						'icon'  => 'update',
						'class' => 'red',
					),
					array(
						'label' => __( 'Tor', 'ipquery' ),
						'count' => $ipquery_risk_counts['tor'],
						'icon'  => 'hidden',
						'class' => 'red',
					),
					array(
						'label' => __( 'Datacenter', 'ipquery' ),
						'count' => $ipquery_risk_counts['datacenter'],
						'icon'  => 'cloud',
						'class' => 'blue',
					),
					array(
						'label' => __( 'Mobile', 'ipquery' ),
						'count' => $ipquery_risk_counts['mobile'],
						'icon'  => 'smartphone',
						'class' => 'green',
					),
				);
				foreach ( $ipquery_risk_items as $ipquery_item ) :
					?>
			<div class="ipquery-risk-item ipquery-risk-item--<?php echo esc_attr( $ipquery_item['class'] ); ?>">
				<span class="dashicons dashicons-<?php echo esc_attr( $ipquery_item['icon'] ); ?>"></span>
				<strong><?php echo esc_html( number_format_i18n( $ipquery_item['count'] ) ); ?></strong>
				<small><?php echo esc_html( $ipquery_item['label'] ); ?></small>
					<?php if ( $ipquery_unique_ips > 0 ) : ?>
				<span class="ipquery-pct"><?php echo esc_html( round( ( $ipquery_item['count'] / $ipquery_unique_ips ) * 100, 1 ) ); ?>%</span>
				<?php endif; ?>
			</div>
			<?php endforeach; ?>
			</div>
		</div>
	</div>

</div>
