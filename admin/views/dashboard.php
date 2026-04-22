<?php defined( 'ABSPATH' ) || exit; ?>

<div class="wrap ipquery-wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-location-alt"></span>
        <?php esc_html_e( 'IpQuery — Dashboard', 'ipquery-wp' ); ?>
    </h1>
    <hr class="wp-header-end">

    <?php
    $total_visits  = IpQuery_DB::get_total_visits();
    $unique_ips    = IpQuery_DB::get_unique_ips();
    $risk_counts   = IpQuery_DB::get_risk_counts();
    $top_countries = IpQuery_DB::get_top_countries( 10 );
    $top_cities    = IpQuery_DB::get_top_cities( 10 );
    ?>

    <!-- Stat cards -->
    <div class="ipquery-cards">
        <div class="ipquery-card ipquery-card--blue">
            <div class="ipquery-card__icon dashicons dashicons-admin-users"></div>
            <div class="ipquery-card__body">
                <span class="ipquery-card__value"><?php echo esc_html( number_format_i18n( $total_visits ) ); ?></span>
                <span class="ipquery-card__label"><?php esc_html_e( 'Total Visits', 'ipquery-wp' ); ?></span>
            </div>
        </div>
        <div class="ipquery-card ipquery-card--green">
            <div class="ipquery-card__icon dashicons dashicons-networking"></div>
            <div class="ipquery-card__body">
                <span class="ipquery-card__value"><?php echo esc_html( number_format_i18n( $unique_ips ) ); ?></span>
                <span class="ipquery-card__label"><?php esc_html_e( 'Unique IPs', 'ipquery-wp' ); ?></span>
            </div>
        </div>
        <div class="ipquery-card ipquery-card--orange">
            <div class="ipquery-card__icon dashicons dashicons-shield-alt"></div>
            <div class="ipquery-card__body">
                <span class="ipquery-card__value"><?php echo esc_html( number_format_i18n( $risk_counts['vpn'] + $risk_counts['proxy'] + $risk_counts['tor'] ) ); ?></span>
                <span class="ipquery-card__label"><?php esc_html_e( 'VPN / Proxy / Tor', 'ipquery-wp' ); ?></span>
            </div>
        </div>
        <div class="ipquery-card ipquery-card--red">
            <div class="ipquery-card__icon dashicons dashicons-warning"></div>
            <div class="ipquery-card__body">
                <?php
                $risky_pct = $unique_ips > 0
                    ? round( ( ( $risk_counts['vpn'] + $risk_counts['proxy'] + $risk_counts['tor'] ) / $unique_ips ) * 100, 1 )
                    : 0;
                ?>
                <span class="ipquery-card__value"><?php echo esc_html( $risky_pct ); ?>%</span>
                <span class="ipquery-card__label"><?php esc_html_e( 'Risk Rate', 'ipquery-wp' ); ?></span>
            </div>
        </div>
    </div>

    <!-- Map + charts row -->
    <div class="ipquery-row">

        <!-- World heatmap -->
        <div class="ipquery-panel ipquery-panel--wide">
            <h2><?php esc_html_e( 'Visitor Heatmap', 'ipquery-wp' ); ?></h2>
            <div id="ipquery-map" style="height:420px;"></div>
        </div>

        <!-- Country chart -->
        <div class="ipquery-panel ipquery-panel--narrow">
            <h2><?php esc_html_e( 'Top Countries', 'ipquery-wp' ); ?></h2>
            <div class="ipquery-chart-wrap" style="position:relative;height:390px;overflow:hidden;">
                <canvas id="ipquery-country-chart"></canvas>
            </div>
        </div>

    </div>

    <!-- Risk breakdown row -->
    <div class="ipquery-row">

        <div class="ipquery-panel ipquery-panel--half">
            <h2><?php esc_html_e( 'Risk Breakdown', 'ipquery-wp' ); ?></h2>
            <div class="ipquery-chart-wrap" style="position:relative;height:260px;overflow:hidden;">
                <canvas id="ipquery-risk-chart"></canvas>
            </div>
        </div>

        <!-- Country table -->
        <div class="ipquery-panel ipquery-panel--half">
            <h2><?php esc_html_e( 'Top Countries', 'ipquery-wp' ); ?></h2>
            <table class="widefat striped ipquery-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Country', 'ipquery-wp' ); ?></th>
                        <th><?php esc_html_e( 'Visits', 'ipquery-wp' ); ?></th>
                        <th><?php esc_html_e( '%', 'ipquery-wp' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php if ( empty( $top_countries ) ) : ?>
                    <tr><td colspan="3"><?php esc_html_e( 'No data yet.', 'ipquery-wp' ); ?></td></tr>
                <?php else : ?>
                    <?php foreach ( $top_countries as $row ) : ?>
                    <tr>
                        <td>
                            <?php if ( $row['country_code'] ) : ?>
                                <img src="https://flagcdn.com/16x12/<?php echo esc_attr( strtolower( $row['country_code'] ) ); ?>.png"
                                     width="16" height="12"
                                     alt="<?php echo esc_attr( $row['country_code'] ); ?>"
                                     style="vertical-align:middle;margin-right:4px;">
                            <?php endif; ?>
                            <?php echo esc_html( $row['country'] ?? $row['country_code'] ?? '—' ); ?>
                        </td>
                        <td><?php echo esc_html( number_format_i18n( (int) $row['visits'] ) ); ?></td>
                        <td><?php echo $total_visits > 0 ? esc_html( round( ( $row['visits'] / $total_visits ) * 100, 1 ) ) . '%' : '—'; ?></td>
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
            <h2><?php esc_html_e( 'Top Cities', 'ipquery-wp' ); ?></h2>
            <table class="widefat striped ipquery-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'City', 'ipquery-wp' ); ?></th>
                        <th><?php esc_html_e( 'Country', 'ipquery-wp' ); ?></th>
                        <th><?php esc_html_e( 'Visits', 'ipquery-wp' ); ?></th>
                        <th><?php esc_html_e( '%', 'ipquery-wp' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php if ( empty( $top_cities ) ) : ?>
                    <tr><td colspan="4"><?php esc_html_e( 'No data yet.', 'ipquery-wp' ); ?></td></tr>
                <?php else : ?>
                    <?php foreach ( $top_cities as $row ) : ?>
                    <tr>
                        <td><?php echo esc_html( $row['city'] ); ?></td>
                        <td>
                            <?php if ( $row['country_code'] ) : ?>
                                <img src="https://flagcdn.com/16x12/<?php echo esc_attr( strtolower( $row['country_code'] ) ); ?>.png"
                                     width="16" height="12"
                                     alt="<?php echo esc_attr( $row['country_code'] ); ?>"
                                     style="vertical-align:middle;margin-right:4px;">
                            <?php endif; ?>
                            <?php echo esc_html( $row['country'] ?? $row['country_code'] ?? '—' ); ?>
                        </td>
                        <td><?php echo esc_html( number_format_i18n( (int) $row['visits'] ) ); ?></td>
                        <td><?php echo $total_visits > 0 ? esc_html( round( ( $row['visits'] / $total_visits ) * 100, 1 ) ) . '%' : '—'; ?></td>
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
            <h2><?php esc_html_e( 'Risk Details', 'ipquery-wp' ); ?></h2>
            <div class="ipquery-risk-grid">
                <?php
                $risk_items = [
                    [ 'label' => __( 'VPN',        'ipquery-wp' ), 'count' => $risk_counts['vpn'],        'icon' => 'lock',         'class' => 'orange' ],
                    [ 'label' => __( 'Proxy',      'ipquery-wp' ), 'count' => $risk_counts['proxy'],      'icon' => 'update',       'class' => 'red'    ],
                    [ 'label' => __( 'Tor',        'ipquery-wp' ), 'count' => $risk_counts['tor'],        'icon' => 'hidden',       'class' => 'red'    ],
                    [ 'label' => __( 'Datacenter', 'ipquery-wp' ), 'count' => $risk_counts['datacenter'], 'icon' => 'cloud',        'class' => 'blue'   ],
                    [ 'label' => __( 'Mobile',     'ipquery-wp' ), 'count' => $risk_counts['mobile'],     'icon' => 'smartphone',   'class' => 'green'  ],
                ];
                foreach ( $risk_items as $item ) : ?>
                <div class="ipquery-risk-item ipquery-risk-item--<?php echo esc_attr( $item['class'] ); ?>">
                    <span class="dashicons dashicons-<?php echo esc_attr( $item['icon'] ); ?>"></span>
                    <strong><?php echo esc_html( number_format_i18n( $item['count'] ) ); ?></strong>
                    <small><?php echo esc_html( $item['label'] ); ?></small>
                    <?php if ( $unique_ips > 0 ) : ?>
                    <span class="ipquery-pct"><?php echo esc_html( round( ( $item['count'] / $unique_ips ) * 100, 1 ) ); ?>%</span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

</div>
