<?php defined( 'ABSPATH' ) || exit; ?>

<div class="wrap ipquery-wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-admin-users"></span>
        <?php esc_html_e( 'IpQuery — Visitors', 'ipquery-wp' ); ?>
    </h1>
    <hr class="wp-header-end">

    <?php
    // Admin notices.
    if ( isset( $_GET['deleted'] ) ) :
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'IP deleted.', 'ipquery-wp' ) . '</p></div>';
    elseif ( isset( $_GET['purged'] ) ) :
        printf( '<div class="notice notice-success is-dismissible"><p>' . esc_html__( '%d old records removed.', 'ipquery-wp' ) . '</p></div>', (int) $_GET['purged'] );
    elseif ( isset( $_GET['lookup_ok'] ) ) :
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'IP looked up and stored.', 'ipquery-wp' ) . '</p></div>';
    elseif ( isset( $_GET['lookup_error'] ) ) :
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( sprintf( __( 'Lookup error: %s', 'ipquery-wp' ), urldecode( sanitize_text_field( $_GET['lookup_error'] ) ) ) ) . '</p></div>';
    endif;
    ?>

    <!-- Toolbar: search + filters + manual lookup -->
    <div class="ipquery-toolbar">
        <form method="get" action="" class="ipquery-filter-form">
            <input type="hidden" name="page" value="ipquery-visitors">

            <input type="search"
                   name="s"
                   value="<?php echo esc_attr( sanitize_text_field( $_GET['s'] ?? '' ) ); ?>"
                   placeholder="<?php esc_attr_e( 'Search IP, city, country, ISP…', 'ipquery-wp' ); ?>"
                   class="regular-text">

            <select name="risk_filter">
                <option value=""><?php esc_html_e( 'All types', 'ipquery-wp' ); ?></option>
                <option value="is_vpn"        <?php selected( $_GET['risk_filter'] ?? '', 'is_vpn' ); ?>><?php esc_html_e( 'VPN', 'ipquery-wp' ); ?></option>
                <option value="is_proxy"      <?php selected( $_GET['risk_filter'] ?? '', 'is_proxy' ); ?>><?php esc_html_e( 'Proxy', 'ipquery-wp' ); ?></option>
                <option value="is_tor"        <?php selected( $_GET['risk_filter'] ?? '', 'is_tor' ); ?>><?php esc_html_e( 'Tor', 'ipquery-wp' ); ?></option>
                <option value="is_datacenter" <?php selected( $_GET['risk_filter'] ?? '', 'is_datacenter' ); ?>><?php esc_html_e( 'Datacenter', 'ipquery-wp' ); ?></option>
                <option value="is_mobile"     <?php selected( $_GET['risk_filter'] ?? '', 'is_mobile' ); ?>><?php esc_html_e( 'Mobile', 'ipquery-wp' ); ?></option>
            </select>

            <?php submit_button( __( 'Filter', 'ipquery-wp' ), 'secondary', 'filter', false ); ?>
            <?php if ( ! empty( $_GET['s'] ) || ! empty( $_GET['risk_filter'] ) ) : ?>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=ipquery-visitors' ) ); ?>" class="button"><?php esc_html_e( 'Reset', 'ipquery-wp' ); ?></a>
            <?php endif; ?>
        </form>

        <!-- Manual IP lookup -->
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ipquery-lookup-form">
            <?php wp_nonce_field( 'ipquery_manual_lookup' ); ?>
            <input type="hidden" name="action" value="ipquery_lookup">
            <input type="text" name="ip" placeholder="<?php esc_attr_e( 'Lookup IP…', 'ipquery-wp' ); ?>" class="regular-text" pattern="^(\d{1,3}\.){3}\d{1,3}$|^[0-9a-fA-F:]+$">
            <?php submit_button( __( 'Lookup', 'ipquery-wp' ), 'secondary', 'lookup_btn', false ); ?>
        </form>
    </div>

    <?php
    $per_page    = 25;
    $current_page = max( 1, (int) ( $_GET['paged'] ?? 1 ) );
    $orderby     = sanitize_text_field( $_GET['orderby']     ?? 'last_seen' );
    $order       = strtoupper( sanitize_text_field( $_GET['order'] ?? 'DESC' ) ) === 'ASC' ? 'ASC' : 'DESC';

    $result = IpQuery_DB::get_visitors( [
        'per_page'     => $per_page,
        'page'         => $current_page,
        'orderby'      => $orderby,
        'order'        => $order,
        'search'       => sanitize_text_field( $_GET['s'] ?? '' ),
        'risk_filter'  => sanitize_text_field( $_GET['risk_filter'] ?? '' ),
    ] );

    $rows       = $result['rows'];
    $total      = $result['total'];
    $total_pages = ceil( $total / $per_page );

    function ipquery_sortable_col( string $col, string $label, string $current_orderby, string $current_order ): string {
        $is_sorted = $current_orderby === $col;
        $next_order = ( $is_sorted && $current_order === 'ASC' ) ? 'DESC' : 'ASC';
        $url = add_query_arg( [ 'orderby' => $col, 'order' => $next_order ] );
        $arrow = $is_sorted ? ( $current_order === 'ASC' ? ' ▲' : ' ▼' ) : '';
        return '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . $arrow . '</a>';
    }
    ?>

    <p class="ipquery-count">
        <?php printf(
            esc_html( _n( '%s record found.', '%s records found.', $total, 'ipquery-wp' ) ),
            '<strong>' . esc_html( number_format_i18n( $total ) ) . '</strong>'
        ); ?>
    </p>

    <table class="widefat striped ipquery-visitors-table">
        <thead>
            <tr>
                <th><?php echo ipquery_sortable_col( 'ip',          __( 'IP Address',  'ipquery-wp' ), $orderby, $order ); ?></th>
                <th><?php echo ipquery_sortable_col( 'country',     __( 'Location',    'ipquery-wp' ), $orderby, $order ); ?></th>
                <th><?php esc_html_e( 'ISP', 'ipquery-wp' ); ?></th>
                <th><?php esc_html_e( 'Risk Flags', 'ipquery-wp' ); ?></th>
                <th><?php echo ipquery_sortable_col( 'risk_score',  __( 'Score',       'ipquery-wp' ), $orderby, $order ); ?></th>
                <th><?php echo ipquery_sortable_col( 'visit_count', __( 'Visits',      'ipquery-wp' ), $orderby, $order ); ?></th>
                <th><?php echo ipquery_sortable_col( 'first_seen',  __( 'First Seen',  'ipquery-wp' ), $orderby, $order ); ?></th>
                <th><?php echo ipquery_sortable_col( 'last_seen',   __( 'Last Seen',   'ipquery-wp' ), $orderby, $order ); ?></th>
                <th><?php esc_html_e( 'Actions', 'ipquery-wp' ); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php if ( empty( $rows ) ) : ?>
            <tr><td colspan="9"><?php esc_html_e( 'No records found.', 'ipquery-wp' ); ?></td></tr>
        <?php else : ?>
            <?php foreach ( $rows as $row ) :
                $flags = [];
                if ( $row['is_vpn'] )        $flags[] = '<span class="ipquery-badge ipquery-badge--orange">VPN</span>';
                if ( $row['is_proxy'] )      $flags[] = '<span class="ipquery-badge ipquery-badge--red">Proxy</span>';
                if ( $row['is_tor'] )        $flags[] = '<span class="ipquery-badge ipquery-badge--red">Tor</span>';
                if ( $row['is_datacenter'] ) $flags[] = '<span class="ipquery-badge ipquery-badge--blue">DC</span>';
                if ( $row['is_mobile'] )     $flags[] = '<span class="ipquery-badge ipquery-badge--green">Mobile</span>';

                $score     = (int) $row['risk_score'];
                $score_cls = $score >= 80 ? 'red' : ( $score >= 40 ? 'orange' : 'green' );
                $location  = array_filter( [ $row['city'], $row['state'], $row['country'] ] );
            ?>
            <tr>
                <td><code><?php echo esc_html( $row['ip'] ); ?></code></td>
                <td>
                    <?php if ( $row['country_code'] ) : ?>
                        <img src="https://flagcdn.com/16x12/<?php echo esc_attr( strtolower( $row['country_code'] ) ); ?>.png"
                             width="16" height="12"
                             alt="<?php echo esc_attr( $row['country_code'] ); ?>"
                             style="vertical-align:middle;margin-right:4px;">
                    <?php endif; ?>
                    <?php echo esc_html( implode( ', ', $location ) ?: '—' ); ?>
                </td>
                <td><?php echo esc_html( $row['isp'] ?? '—' ); ?></td>
                <td><?php echo $flags ? implode( ' ', $flags ) : '<span class="ipquery-badge ipquery-badge--green">Clean</span>'; ?></td>
                <td><span class="ipquery-score ipquery-score--<?php echo esc_attr( $score_cls ); ?>"><?php echo esc_html( $score ); ?></span></td>
                <td><?php echo esc_html( number_format_i18n( (int) $row['visit_count'] ) ); ?></td>
                <td><?php echo esc_html( wp_date( get_option( 'date_format' ), strtotime( $row['first_seen'] ) ) ); ?></td>
                <td><?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $row['last_seen'] ) ) ); ?></td>
                <td>
                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
                        <?php wp_nonce_field( 'ipquery_delete_ip' ); ?>
                        <input type="hidden" name="action" value="ipquery_delete_ip">
                        <input type="hidden" name="ip" value="<?php echo esc_attr( $row['ip'] ); ?>">
                        <button type="submit" class="button button-small button-link-delete"
                                onclick="return confirm('<?php echo esc_js( __( 'Delete this IP record?', 'ipquery-wp' ) ); ?>')">
                            <?php esc_html_e( 'Delete', 'ipquery-wp' ); ?>
                        </button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>

    <?php if ( $total_pages > 1 ) : ?>
    <div class="tablenav bottom">
        <div class="tablenav-pages">
            <?php
            echo paginate_links( [
                'base'      => add_query_arg( 'paged', '%#%' ),
                'format'    => '',
                'current'   => $current_page,
                'total'     => $total_pages,
                'prev_text' => '&laquo;',
                'next_text' => '&raquo;',
            ] );
            ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Purge old records -->
    <div class="ipquery-panel" style="margin-top:24px;">
        <h3><?php esc_html_e( 'Purge Old Records', 'ipquery-wp' ); ?></h3>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <?php wp_nonce_field( 'ipquery_purge' ); ?>
            <input type="hidden" name="action" value="ipquery_purge">
            <label>
                <?php esc_html_e( 'Delete records older than', 'ipquery-wp' ); ?>
                <input type="number" name="days" value="90" min="1" max="3650" style="width:70px;"> <?php esc_html_e( 'days', 'ipquery-wp' ); ?>
            </label>
            <?php submit_button( __( 'Purge', 'ipquery-wp' ), 'secondary', 'purge_btn', false ); ?>
        </form>
    </div>

</div>
