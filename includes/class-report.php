<?php

/**
 * The reporting class
 *
 * @author Tareq Hasan
 */
class WeDevs_bbPress_Reporting {

    public static function date_filter( $start_date, $end_date, $tab ) {
        ?>
        <div class="filter-area clearfix">
            <form action="<?php echo admin_url( 'edit.php' ); ?>" method="get">
                <input type="hidden" name="post_type" value="forum">
                <input type="hidden" name="page" value="bbp-reports">
                <input type="hidden" name="tab" value="<?php echo $tab; ?>">

                <input type="text" name="bbp_report_start" id="bbp-report-start" class="bbp-datepicker" value="<?php echo esc_attr( $start_date ); ?>" readonly>
                <input type="text" name="bbp_report_end" id="bbp-report-end" class="bbp-datepicker" value="<?php echo esc_attr( $end_date ); ?>" readonly>

                <?php submit_button( __( 'Filter', 'bbp-reports' ), 'secondary', 'filter', '' ); ?>
            </form>
        </div>
        <?php
    }

    public static function conversation() {
        global $wpdb;

        $timestamp  = current_time( 'timestamp' );
        $cur_year   = date( 'Y', $timestamp );
        $cur_month  = date( 'm', $timestamp );
        $no_of_days = cal_days_in_month( CAL_GREGORIAN, $cur_month, $cur_year );
        $start_date = isset( $_GET['bbp_report_start'] ) ? sanitize_text_field( $_GET['bbp_report_start'] ) : date( 'Y-m-01', $timestamp );
        $end_date   = isset( $_GET['bbp_report_end'] ) ? sanitize_text_field( $_GET['bbp_report_end'] ) : date( 'Y-m-' . $no_of_days, $timestamp );
        $date_diff  = ( strtotime( $end_date ) - strtotime( $start_date ) ) / DAY_IN_SECONDS;
        $table_name = $wpdb->prefix . 'bbp_reports';

        $topic_created_query = "SELECT count(p.ID) as num
            FROM $wpdb->posts AS p
            WHERE p.post_type = 'topic' AND
                (p.post_date >= '$start_date' AND p.post_date <= '$end_date') ";

        $active_conversation_query = "SELECT count(p.ID) as num
            FROM $wpdb->posts AS p
            LEFT JOIN $wpdb->postmeta AS m1 ON m1.post_id = p.ID
            WHERE p.post_type = 'topic' AND m1.meta_key = '_bbp_last_active_time' AND
                ( (p.post_date >= '$start_date' AND p.post_date <= '$end_date') OR ( m1.meta_value >= '$start_date' AND m1.meta_value <= '$end_date' ) )
            ORDER BY m1.meta_value ASC";

        $topic_replies_query = "SELECT post_date as date, post_author
            FROM $wpdb->posts
            WHERE
                post_type IN ('topic', 'reply') AND
                ( post_date >= '$start_date' AND post_date <= '$end_date' ) AND
                post_status IN ( 'publish', 'closed' )
            -- GROUP BY day
            ORDER BY post_date DESC";

        $topic_created       = (int) $wpdb->get_var( $topic_created_query );
        $active_conversation = (int) $wpdb->get_var( $active_conversation_query );
        $topic_replies       = $wpdb->get_results( $topic_replies_query );
        // var_dump($topic_replies);

        $user_count = array_unique( wp_list_pluck( $topic_replies, 'post_author' ) );
        $fills = array(
            '12-3am' => 0,
            '3-6am'  => 0,
            '6-9am'  => 0,
            '9-12pm' => 0,
            '12-3pm' => 0,
            '3-6pm'  => 0,
            '6-9pm'  => 0,
            '9-12am' => 0
        );
        $replies_formatted = array(
            'Sunday'    => $fills,
            'Monday'    => $fills,
            'Tuesday'   => $fills,
            'Wednesday' => $fills,
            'Thursday'  => $fills,
            'Friday'    => $fills,
            'Saturday'  => $fills
        );
        $day_count = array(
            'Sunday'    => 0,
            'Monday'    => 0,
            'Tuesday'   => 0,
            'Wednesday' => 0,
            'Thursday'  => 0,
            'Friday'    => 0,
            'Saturday'  => 0
        );

        foreach ($topic_replies as $reply) {
            $timestamp = strtotime( $reply->date );
            $key       = date( 'l', $timestamp );
            $hour      = date('G', $timestamp);

            $day_count[$key] += 1;

            if ( $hour >= 0 && $hour <= 3 ) {
                $replies_formatted[ $key ]['12-3am'] += 1;
                $fills['12-3am'] += 1;
            } elseif ( $hour > 3 && $hour <= 6 ) {
                $replies_formatted[ $key ]['3-6am'] += 1;
                $fills['3-6am'] += 1;
            } elseif ( $hour > 6 && $hour <= 9 ) {
                $replies_formatted[ $key ]['6-9am'] += 1;
                $fills['6-9am'] += 1;
            } elseif ( $hour > 9 && $hour <= 12 ) {
                $replies_formatted[ $key ]['9-12pm'] += 1;
                $fills['9-12pm'] += 1;
            } elseif ( $hour > 12 && $hour <= 15 ) {
                $replies_formatted[ $key ]['12-3pm'] += 1;
                $fills['12-3pm'] += 1;
            } elseif ( $hour > 15 && $hour <= 18 ) {
                $replies_formatted[ $key ]['3-6pm'] += 1;
                $fills['3-6pm'] += 1;
            } elseif ( $hour > 18 && $hour <= 21 ) {
                $replies_formatted[ $key ]['6-9pm'] += 1;
                $fills['6-9pm'] += 1;
            } elseif ( $hour > 21 && $hour <= 24 ) {
                $replies_formatted[ $key ]['9-12am'] += 1;
                $fills['9-12am'] += 1;
            }
        }

        // var_dump($replies_formatted);
        arsort( $day_count );
        arsort( $fills );
        $day_count_flip = array_flip( $day_count );
        $fills_flip = array_flip( $fills );
        ?>

        <div class="chart-container clearfix">

            <?php self::date_filter( $start_date, $end_date, 'conversation' ); ?>

            <div class="chart-sidebar">
                <ul class="chart-legend clearfix">
                    <li>
                        <strong><?php echo $active_conversation; ?></strong>
                        <?php _e( 'Active Topics', 'bbp-reports' ); ?>
                    </li>
                    <li>
                        <strong><?php echo $topic_created; ?></strong>
                        <?php _e( 'Topics Created', 'bbp-reports' ); ?>
                    </li>
                    <li>
                        <strong><?php echo count( $user_count ); ?></strong>
                        <?php _e( 'User Participation', 'bbp-reports' ); ?>
                    </li>
                    <li>
                        <strong><?php echo ceil( $active_conversation / $date_diff ); ?></strong>
                        <?php _e( 'Avg. Topics/Day', 'bbp-reports' ); ?>
                    </li>
                    <li>
                        <strong><?php echo reset( $day_count_flip ); ?></strong>
                        <?php _e( 'Busiest Day', 'bbp-reports' ); ?>
                    </li>
                    <li>
                        <strong><?php echo reset( $fills_flip ); ?></strong>
                        <?php _e( 'Busiest Time', 'bbp-reports' ); ?>
                    </li>
                </ul>
            </div><!-- .chart-sidebar -->
            <div class="chart-main">

                <div class="postbox leads-actions">
                    <h3 class="hndle"><?php _e( 'Busiest Time of day', 'bbp-reports' ); ?> <span><?php _e( 'topic and replies', 'bbp-reports' ); ?></span></h3>
                    <div class="inside">

                        <table class="widefat">
                            <thead>
                                <tr>
                                    <th>&nbsp;</th>
                                    <th><?php _e( '12-3am', 'bbp-reports' ); ?></th>
                                    <th><?php _e( '3-6am', 'bbp-reports' ); ?></th>
                                    <th><?php _e( '6-9am', 'bbp-reports' ); ?></th>
                                    <th><?php _e( '9-12pm', 'bbp-reports' ); ?></th>
                                    <th><?php _e( '12-3pm', 'bbp-reports' ); ?></th>
                                    <th><?php _e( '3-6pm', 'bbp-reports' ); ?></th>
                                    <th><?php _e( '6-9pm', 'bbp-reports' ); ?></th>
                                    <th><?php _e( '9-12am', 'bbp-reports' ); ?></th>
                                    <th><?php _e( 'Total', 'bbp-reports' ); ?></th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php $row = 0; ?>
                                <?php foreach ($replies_formatted as $day => $values) { ?>
                                    <tr<?php echo ( $row % 2 ) == 0 ? ' class="alternate"' : ''; ?>>
                                        <th><?php echo substr( $day, 0, 3 ); ?></th>

                                        <?php foreach ($values as $range => $count) { ?>
                                            <td><?php echo $count; ?></td>
                                        <?php } ?>

                                        <td><?php echo $day_count[$day]; ?></td>
                                    </tr>
                                    <?php $row += 1; ?>
                                <?php } ?>

                                <tr>
                                    <th>&nbsp;</th>
                                    <td><?php echo $fills['12-3am']; ?></td>
                                    <td><?php echo $fills['3-6am']; ?></td>
                                    <td><?php echo $fills['6-9am']; ?></td>
                                    <td><?php echo $fills['9-12pm']; ?></td>
                                    <td><?php echo $fills['12-3pm']; ?></td>
                                    <td><?php echo $fills['3-6pm']; ?></td>
                                    <td><?php echo $fills['6-9pm']; ?></td>
                                    <td><?php echo $fills['9-12am']; ?></td>
                                    <td><?php echo array_sum( $fills ); ?></td>
                                </tr>
                            </tbody>

                        </table>
                    </div>
                </div><!-- .postbox -->

            </div><!-- .chart-main -->
        </div><!-- .chart-container -->
        <?php
    }

    public static function report_team() {
        global $wpdb;

        $timestamp  = current_time( 'timestamp' );
        $cur_year   = date( 'Y', $timestamp );
        $cur_month  = date( 'm', $timestamp );
        $no_of_days = cal_days_in_month( CAL_GREGORIAN, $cur_month, $cur_year );
        $start_date = isset( $_GET['bbp_report_start'] ) ? sanitize_text_field( $_GET['bbp_report_start'] ) : date( 'Y-m-01', $timestamp );
        $end_date   = isset( $_GET['bbp_report_end'] ) ? sanitize_text_field( $_GET['bbp_report_end'] ) : date( 'Y-m-' . $no_of_days, $timestamp );
        $table_name = $wpdb->prefix . 'bbp_reports';

        // $start_date = '2014-11-01';
        // $end_date = '2014-11-30';

        if ( isset( $_GET['user_id'] ) ) {
            $user_id = intval( $_GET['user_id'] );

            self::report_user( $user_id, $start_date, $end_date );
            return;
        }
        // get mdoerators
        $administrators = get_users( array( 'role' => 'administrator' ) );
        $keymaster      = get_users( array( 'role' => 'bbp_keymaster' ) );
        $moderators     = get_users( array( 'role' => 'bbp_moderator' ) );

        $all_users = array_merge( $administrators, $keymaster, $moderators );
        $all_users = array_unique( wp_list_pluck( $all_users, 'ID' ) );
        // var_dump( $all_users );

        $query = "SELECT COUNT(*) as count, u.ID, p.post_author, u.display_name
            FROM $wpdb->posts AS p
            INNER JOIN $wpdb->users AS u ON u.ID = p.post_author
            WHERE post_author IN (" . implode( ',', $all_users ) . ") AND post_type = 'reply' AND
            ( post_date >= '$start_date' AND post_date <= '$end_date' ) AND post_status in ('publish', 'closed' )
            GROUP BY p.post_author
            ORDER BY count DESC";

        $users = $wpdb->get_results( $query );
        ?>
        <?php self::date_filter( $start_date, $end_date, 'team' ); ?>

        <table class="widefat">
            <thead>
                <tr>
                    <th class="col-id"><?php _e( 'ID', 'bbp-reports' ); ?></th>
                    <th><?php _e( 'User', 'bbp-reports' ); ?></th>
                    <th><?php _e( 'Replies', 'bbp-reports' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ( $users ) {
                    foreach ($users as $key => $user) {
                        ?>
                        <tr<?php echo ( $key % 2 ) == 0 ? ' class="alternate"' : ''; ?>>
                            <td class="col-id">
                                <a href="<?php echo bbp_user_replies_created_url( $user->ID ); ?>">#<?php echo $user->ID; ?></a>
                            </td>
                            <td>
                                <a href="<?php echo add_query_arg( array( 'user_id' => $user->ID ) ); ?>"><?php echo $user->display_name; ?></a>
                            </td>
                            <td>
                                <?php echo $user->count; ?>
                            </td>
                        </tr>
                        <?php
                    }
                } else {
                    ?>
                    <tr>
                        <td colspan="3">
                            <?php _e( 'No replies found.', 'bbp-reports' ); ?>
                        </td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>

        <?php
    }

    public static function count_user_posts_by_type( $user_id, $post_type = 'post' ) {
        global $wpdb;

        $where = get_posts_by_author_sql( $post_type, true, $user_id );

        $count = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts $where" );

        return apply_filters( 'get_usernumposts', $count, $user_id );
    }

    public static function report_user( $user_id, $start_date, $end_date ) {
        global $wpdb;

        self::date_filter( $start_date, $end_date, 'team' );

        $user = get_user_by( 'id', $user_id );

        $sql = "SELECT ID, post_author, post_parent
            FROM $wpdb->posts AS p
            WHERE p.post_type = 'reply' AND p.post_author = $user_id AND
            ( p.post_date >= '$start_date' AND p.post_date <= '$end_date' )
        ";
        echo $sql;

        $replies = $wpdb->get_results( $sql );
        var_dump( $replies );
        ?>

        <?php echo get_avatar( $user_id, '80' ); ?>
        <?php echo $user->display_name; ?>
        <?php printf( __( 'Total replies: %d', 'bbp-reports' ), self::count_user_posts_by_type( $user_id, 'reply' ) ); ?>

        <?php
    }
}