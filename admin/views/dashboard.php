<?php
/**
 * Dashboard admin view — stat cards, heatmap, and charts.
 *
 * @package SVA
 */

defined( 'ABSPATH' ) || exit; ?>

<div class="wrap sva-wrap">
	<h1 class="wp-heading-inline">
		<span class="dashicons dashicons-location-alt"></span>
		<?php esc_html_e( 'Visitor Analytics — Dashboard', 'stracini-visitor-analytics' ); ?>
	</h1>
	<hr class="wp-header-end">

	<?php
	$sva_total_visits  = SVA_DB::get_total_visits();
	$sva_unique_ips    = SVA_DB::get_unique_ips();
	$sva_risk_counts   = SVA_DB::get_risk_counts();
	$sva_top_countries = SVA_DB::get_top_countries( 10 );
	$sva_top_cities    = SVA_DB::get_top_cities( 10 );
	?>

	<!-- Stat cards -->
	<div class="sva-cards">
		<div class="sva-card sva-card--blue">
			<div class="sva-card__icon dashicons dashicons-admin-users"></div>
			<div class="sva-card__body">
				<span class="sva-card__value"><?php echo esc_html( number_format_i18n( $sva_total_visits ) ); ?></span>
				<span class="sva-card__label"><?php esc_html_e( 'Total Visits', 'stracini-visitor-analytics' ); ?></span>
			</div>
		</div>
		<div class="sva-card sva-card--green">
			<div class="sva-card__icon dashicons dashicons-networking"></div>
			<div class="sva-card__body">
				<span class="sva-card__value"><?php echo esc_html( number_format_i18n( $sva_unique_ips ) ); ?></span>
				<span class="sva-card__label"><?php esc_html_e( 'Unique IPs', 'stracini-visitor-analytics' ); ?></span>
			</div>
		</div>
		<div class="sva-card sva-card--orange">
			<div class="sva-card__icon dashicons dashicons-shield-alt"></div>
			<div class="sva-card__body">
				<span class="sva-card__value"><?php echo esc_html( number_format_i18n( $sva_risk_counts['vpn'] + $sva_risk_counts['proxy'] + $sva_risk_counts['tor'] ) ); ?></span>
				<span class="sva-card__label"><?php esc_html_e( 'VPN / Proxy / Tor', 'stracini-visitor-analytics' ); ?></span>
			</div>
		</div>
		<div class="sva-card sva-card--red">
			<div class="sva-card__icon dashicons dashicons-warning"></div>
			<div class="sva-card__body">
				<?php
				$sva_risky_pct = $sva_unique_ips > 0
					? round( ( ( $sva_risk_counts['vpn'] + $sva_risk_counts['proxy'] + $sva_risk_counts['tor'] ) / $sva_unique_ips ) * 100, 1 )
					: 0;
				?>
				<span class="sva-card__value"><?php echo esc_html( $sva_risky_pct ); ?>%</span>
				<span class="sva-card__label"><?php esc_html_e( 'Risk Rate', 'stracini-visitor-analytics' ); ?></span>
			</div>
		</div>
	</div>

	<!-- Map + charts row -->
	<div class="sva-row">

		<!-- World heatmap -->
		<div class="sva-panel sva-panel--wide">
			<h2><?php esc_html_e( 'Visitor Heatmap', 'stracini-visitor-analytics' ); ?></h2>
			<div id="sva-map" style="height:420px;"></div>
		</div>

		<!-- Country chart -->
		<div class="sva-panel sva-panel--narrow">
			<h2><?php esc_html_e( 'Top Countries', 'stracini-visitor-analytics' ); ?></h2>
			<div class="sva-chart-wrap" style="position:relative;height:390px;overflow:hidden;">
				<canvas id="sva-country-chart"></canvas>
			</div>
		</div>

	</div>

	<!-- Risk breakdown row -->
	<div class="sva-row">

		<div class="sva-panel sva-panel--half">
			<h2><?php esc_html_e( 'Risk Breakdown', 'stracini-visitor-analytics' ); ?></h2>
			<div class="sva-chart-wrap" style="position:relative;height:260px;overflow:hidden;">
				<canvas id="sva-risk-chart"></canvas>
			</div>
		</div>

		<!-- Country table -->
		<div class="sva-panel sva-panel--half">
			<h2><?php esc_html_e( 'Top Countries', 'stracini-visitor-analytics' ); ?></h2>
			<table class="widefat striped sva-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Country', 'stracini-visitor-analytics' ); ?></th>
						<th><?php esc_html_e( 'Visits', 'stracini-visitor-analytics' ); ?></th>
						<th><?php esc_html_e( '%', 'stracini-visitor-analytics' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php if ( empty( $sva_top_countries ) ) : ?>
					<tr><td colspan="3"><?php esc_html_e( 'No data yet.', 'stracini-visitor-analytics' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $sva_top_countries as $sva_row ) : ?>
					<tr>
						<td>
							<?php if ( $sva_row['country_code'] ) : ?>
								<img src="https://flagcdn.com/16x12/<?php echo esc_attr( strtolower( $sva_row['country_code'] ) ); ?>.png"
									width="16" height="12"
									alt="<?php echo esc_attr( $sva_row['country_code'] ); ?>"
									style="vertical-align:middle;margin-right:4px;">
							<?php endif; ?>
							<?php echo esc_html( $sva_row['country'] ?? $sva_row['country_code'] ?? '—' ); ?>
						</td>
						<td><?php echo esc_html( number_format_i18n( (int) $sva_row['visits'] ) ); ?></td>
						<td><?php echo $sva_total_visits > 0 ? esc_html( round( ( $sva_row['visits'] / $sva_total_visits ) * 100, 1 ) ) . '%' : '—'; ?></td>
					</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
		</div>

	</div>

	<!-- Cities table -->
	<div class="sva-row">
		<div class="sva-panel sva-panel--half">
			<h2><?php esc_html_e( 'Top Cities', 'stracini-visitor-analytics' ); ?></h2>
			<table class="widefat striped sva-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'City', 'stracini-visitor-analytics' ); ?></th>
						<th><?php esc_html_e( 'Country', 'stracini-visitor-analytics' ); ?></th>
						<th><?php esc_html_e( 'Visits', 'stracini-visitor-analytics' ); ?></th>
						<th><?php esc_html_e( '%', 'stracini-visitor-analytics' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php if ( empty( $sva_top_cities ) ) : ?>
					<tr><td colspan="4"><?php esc_html_e( 'No data yet.', 'stracini-visitor-analytics' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $sva_top_cities as $sva_row ) : ?>
					<tr>
						<td><?php echo esc_html( $sva_row['city'] ); ?></td>
						<td>
							<?php if ( $sva_row['country_code'] ) : ?>
								<img src="https://flagcdn.com/16x12/<?php echo esc_attr( strtolower( $sva_row['country_code'] ) ); ?>.png"
									width="16" height="12"
									alt="<?php echo esc_attr( $sva_row['country_code'] ); ?>"
									style="vertical-align:middle;margin-right:4px;">
							<?php endif; ?>
							<?php echo esc_html( $sva_row['country'] ?? $sva_row['country_code'] ?? '—' ); ?>
						</td>
						<td><?php echo esc_html( number_format_i18n( (int) $sva_row['visits'] ) ); ?></td>
						<td><?php echo $sva_total_visits > 0 ? esc_html( round( ( $sva_row['visits'] / $sva_total_visits ) * 100, 1 ) ) . '%' : '—'; ?></td>
					</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>

	<!-- Risk detail cards -->
	<div class="sva-row">
		<div class="sva-panel">
			<h2><?php esc_html_e( 'Risk Details', 'stracini-visitor-analytics' ); ?></h2>
			<div class="sva-risk-grid">
				<?php
				$sva_risk_items = array(
					array(
						'label' => __( 'VPN', 'stracini-visitor-analytics' ),
						'count' => $sva_risk_counts['vpn'],
						'icon'  => 'lock',
						'class' => 'orange',
					),
					array(
						'label' => __( 'Proxy', 'stracini-visitor-analytics' ),
						'count' => $sva_risk_counts['proxy'],
						'icon'  => 'update',
						'class' => 'red',
					),
					array(
						'label' => __( 'Tor', 'stracini-visitor-analytics' ),
						'count' => $sva_risk_counts['tor'],
						'icon'  => 'hidden',
						'class' => 'red',
					),
					array(
						'label' => __( 'Datacenter', 'stracini-visitor-analytics' ),
						'count' => $sva_risk_counts['datacenter'],
						'icon'  => 'cloud',
						'class' => 'blue',
					),
					array(
						'label' => __( 'Mobile', 'stracini-visitor-analytics' ),
						'count' => $sva_risk_counts['mobile'],
						'icon'  => 'smartphone',
						'class' => 'green',
					),
				);
				foreach ( $sva_risk_items as $sva_item ) :
					?>
			<div class="sva-risk-item sva-risk-item--<?php echo esc_attr( $sva_item['class'] ); ?>">
				<span class="dashicons dashicons-<?php echo esc_attr( $sva_item['icon'] ); ?>"></span>
				<strong><?php echo esc_html( number_format_i18n( $sva_item['count'] ) ); ?></strong>
				<small><?php echo esc_html( $sva_item['label'] ); ?></small>
					<?php if ( $sva_unique_ips > 0 ) : ?>
				<span class="sva-pct"><?php echo esc_html( round( ( $sva_item['count'] / $sva_unique_ips ) * 100, 1 ) ); ?>%</span>
				<?php endif; ?>
			</div>
			<?php endforeach; ?>
			</div>
		</div>
	</div>

</div>
