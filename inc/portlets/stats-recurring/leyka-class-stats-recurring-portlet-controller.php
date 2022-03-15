<?php if( !defined('WPINC') ) die;
/**
 * Leyka Portlets Controller class.
 **/

class Leyka_Recurring_Stats_Portlet_Controller extends Leyka_Portlet_Controller {

    protected static $_instance;

    public function get_template_data(array $params = []) {

        $interval_dates = leyka_count_interval_dates($params['interval']);

        global $wpdb;

        // Prev. interval recurring donations:
        $query = leyka_get_donations_storage_type() === 'post' ?
            // Post-based donations storage:
            "SELECT {$wpdb->prefix}posts.ID, {$wpdb->prefix}posts.post_parent
                FROM {$wpdb->prefix}posts 
                    JOIN {$wpdb->prefix}postmeta ON {$wpdb->prefix}posts.ID = {$wpdb->prefix}postmeta.post_id
                WHERE {$wpdb->prefix}posts.post_type='".Leyka_Donation_Management::$post_type."'
                AND {$wpdb->prefix}posts.post_status='funded'
                AND {$wpdb->prefix}posts.post_date BETWEEN '".$interval_dates["prev_interval_begin_date"]."' AND '".$interval_dates["curr_interval_begin_date"]."'
                AND {$wpdb->prefix}postmeta.meta_key='leyka_payment_type'
                AND {$wpdb->prefix}postmeta.meta_value='rebill'" :
            // Separate donations storage:
            "SELECT ID
                FROM {$wpdb->prefix}leyka_donations
                WHERE status='funded'
                AND date_created BETWEEN '".$interval_dates["prev_interval_begin_date"]."' AND '".$interval_dates["curr_interval_begin_date"]."'
                AND payment_type='rebill'";

        $prev_recurring_donations = $wpdb->get_results($query, 'ARRAY_A');
        $prev_subscriptions = [];
        $prev_recurring_donations_ids = [];

        foreach ($prev_recurring_donations as $prev_recurring_donation) {

            $prev_recurring_donations_ids[] = $prev_recurring_donation['ID'];

            if($prev_recurring_donation['post_parent'] === '0') {
                $prev_subscriptions[] = $prev_recurring_donation['ID'];
            }

        }

        $prev_recurring_amount = 0;
        if($prev_recurring_donations) {

            $query = leyka_get_donations_storage_type() === 'post' ?
                // Post-based donations storage:
                "SELECT SUM(meta_value)
                    FROM {$wpdb->prefix}postmeta
                    WHERE post_id IN (".implode(',', $prev_recurring_donations_ids).")
                    AND meta_key='leyka_donation_amount'" :
                // Separate donations storage:
                "SELECT SUM(amount)
                    FROM {$wpdb->prefix}leyka_donations
                    WHERE ID IN (".implode(',', $prev_recurring_donations_ids).')';

            $prev_recurring_amount = $wpdb->get_var($query);

        }

        // Curr. interval recurring donations:
        $query = leyka_get_donations_storage_type() === 'post' ?
            // Post-based donations storage:
            "SELECT {$wpdb->prefix}posts.ID, {$wpdb->prefix}posts.post_parent
                FROM {$wpdb->prefix}posts 
                    JOIN {$wpdb->prefix}postmeta ON {$wpdb->prefix}posts.ID = {$wpdb->prefix}postmeta.post_id
                WHERE {$wpdb->prefix}posts.post_type='".Leyka_Donation_Management::$post_type."'
                AND {$wpdb->prefix}posts.post_status='funded'
                AND {$wpdb->prefix}posts.post_date >= '".$interval_dates["curr_interval_begin_date"]."'
                AND {$wpdb->prefix}postmeta.meta_key='leyka_payment_type'
                AND {$wpdb->prefix}postmeta.meta_value='rebill'" :
            // Separate donations storage:
            "SELECT ID
                FROM {$wpdb->prefix}leyka_donations
                WHERE status='funded'
                AND date_created >= '".$interval_dates["curr_interval_begin_date"]."'
                AND payment_type='rebill'";

        $curr_recurring_donations = $wpdb->get_results($query, 'ARRAY_A');
        $curr_subscriptions = [];
        $curr_recurring_donations_ids = [];

        foreach ($curr_recurring_donations as $curr_recurring_donation) {

            $curr_recurring_donations_ids[] = $curr_recurring_donation['ID'];

            if($curr_recurring_donation['post_parent'] === '0') {
                $curr_subscriptions[] = $curr_recurring_donation['ID'];
            }

        }

        $curr_recurring_amount = 0;
        if($curr_recurring_donations) {

            $query = leyka_get_donations_storage_type() === 'post' ?
                // Post-based donations storage:
                "SELECT SUM(meta_value)
                    FROM {$wpdb->prefix}postmeta
                    WHERE post_id IN (".implode(',', $curr_recurring_donations_ids).")
                    AND meta_key='leyka_donation_amount'" :
                // Separate donations storage:
                "SELECT SUM(amount)
                    FROM {$wpdb->prefix}leyka_donations
                    WHERE ID IN (".implode(',', $curr_recurring_donations_ids).')';

            $curr_recurring_amount = $wpdb->get_var($query);

        }

        $recurring_amount_delta = leyka_get_delta_percent($prev_recurring_amount, $curr_recurring_amount);

        // Donations avg amount:
        $prev_amount_avg = $prev_recurring_amount ? round($prev_recurring_amount/count($prev_recurring_donations_ids), 2) : 0;
        $curr_amount_avg = $curr_recurring_amount ? round($curr_recurring_amount/count($curr_recurring_donations_ids), 2) : 0;
        $donations_amount_avg_delta = leyka_get_delta_percent($prev_amount_avg, $curr_amount_avg);

        // Subscriptions count:
        $prev_subscriptions_count = $prev_subscriptions ? count($prev_subscriptions) : 0;
        $curr_subscriptions_count = $curr_subscriptions ? count($curr_subscriptions) : 0;
        $subscriptions_count_delta = leyka_get_delta_percent($prev_subscriptions_count, $curr_subscriptions_count);

        return [
            'recurring_donations_amount' => $curr_recurring_amount,
            'recurring_donations_amount_delta_percent' => $recurring_amount_delta === NULL ?
                '—' : ($recurring_amount_delta < 0 ? '' : '+').$recurring_amount_delta.'%',
            'donations_amount_avg' => $curr_amount_avg,
            'donations_amount_avg_delta_percent' => $donations_amount_avg_delta === NULL ?
                '—' : ($donations_amount_avg_delta < 0 ? '' : '+').$donations_amount_avg_delta.'%',
            'subscriptions_count' => $curr_subscriptions_count,
            'subscriptions_count_delta_percent' => $subscriptions_count_delta === NULL ?
                '—' : ($subscriptions_count_delta < 0 ? '' : '+').$subscriptions_count_delta.'%',
        ];

    }

}