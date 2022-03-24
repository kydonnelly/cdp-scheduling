<?php
/**
 * Plugin Name: CDP Validation
 * Plugin URI: https://www.cooperative4thecommunity.com/cdp-validation
 * Description: A plugin to view and submit shifts to the CDP Signature Gathering Campaign
 * Version: 1.0
 * Author: Kyle Donnelly
 * Author URI: https://www.cooperative4thecommunity.com
 */

defined( 'ABSPATH' ) || exit;

# database column names, must match cdp_echo_results_html()
define("SCHEDULING_COLUMNS", ['shift_id', 'gatherer', 'location', 'start_time', 'end_time', 'is_bottomliner', 'capacity']);
define("SHIFT_REPORT_COLUMNS", ['shift_id', 'gatherer', 'location', 'start_time', 'end_time', 'is_bottomliner', 'capacity', 'raw_signatures', 'validated_signatures', 'notes']);

function cdp_scheduling_code() {
  $today = cdp_nowtostr();
  $schedule = cdp_get_query_results(SCHEDULING_COLUMNS, "WHERE end_time > '$today' ORDER BY start_time ASC");
  cdp_echo_schedule_html($today, $schedule);
}

function cdp_shift_reports_code() {
  $today = cdp_nowtostr();
  $schedule = cdp_get_query_results(SCHEDULING_COLUMNS, "WHERE $end_time <= '$today' ORDER BY end_time DESC");
  cdp_echo_schedule_html($today, $schedule);
}

function cdp_echo_schedule_html($today, $schedule) {
  $seconds_in_day = 60 * 60 * 24;
  $current_day = cdp_strtotime($today);
  $daily_shifts = array_fill(0, 14, array());

  // partition all the shift results into days
  foreach ($schedule as $shift_result) {
    $start_time = cdp_strtotime($shift_result->start_time);
    $interval = date_diff($start_time, $current_day);
    $days_apart = $interval->format('%R%a');
    if ($days_apart >= 0 && $days_apart < 14) {
      $daily_shifts[$days_apart] []= $shift_result;
    }
  }
  
  echo '<table id="signup_table" style="width:100%" cellspacing="2" cellpadding="4">';
  echo '<tbody>';
  foreach ($daily_shifts as $day_offset => $daily_shift) {
    $period_offset = 'P' . $day_offset . 'D';
    $shift_day = date_add($current_day, new DateInterval($period_offset));
    $display_day = date_format($shift_day, 'l m/d/Y');
    echo '<tr>';

    // Date header
    echo '<th id="row_' . $period_offset . '" class="row_header" data-row-index="' . $day_offset . '" scope="row">' . $display_day . '</th>';

    // New shift cell
    echo '<td class="create-shift" data-col-index="0" data-row-index="' . $day_offset . '">';
    echo '</td>';
    // echo '<td><button class="blue-btn" name="validate" onclick="validateId(this)" id="' . $result->voter_id . '">Mark</button></td>';
    // echo '<td>' . $result->first_name . ' ' . $middle . $result->last_name . '</td>';
    // echo '<td>' . $result->street_num . ' ' . $result->street . ' ' . $result->type . $apt . '</td>'; 
    // echo '<td>' . $result->zip . '</td>';
    // echo '<td>' . $result->birth_date . '</td>';
    // echo '</tr>';
  }
  echo '</tbody>';
  echo '</table>';
  echo '<button name="invalid" onclick="invalidateSearch(this)">Mark Invalid</button>';
}

function cdp_create_shift_code() {
  // Shows the input form, keeping any values from previous submission
  echo '<form action="" id="voter_form" method="post">';
  echo '<p>First Name: <input autocapitalize="off" spellcheck="false" autocorrect="off" type="text" name="firstname" id="first" value="' . $_POST['firstname'] . '" placeholder="Jane"><br />';
  echo 'Last Name: <input autocapitalize="off" spellcheck="false" autocorrect="off" type="text" name="lastname" id="last" value="' . $_POST['lastname'] . '" placeholder="Doe"><br />';
  echo 'Street Number: <input autocapitalize="off" spellcheck="false" autocorrect="off" type="text" name="street_num" id="snum" value="' . $_POST['street_num'] . '" placeholder="1428"><br />';
  echo 'Street Name: <input autocapitalize="off" spellcheck="false" autocorrect="off" type="text" name="street_name" id="street" value="' . $_POST['street_name'] . '" placeholder="Franklin"><br />';
  echo 'Apartment Number: <input type="text" name="apt_num" id="anum" value="' . $_POST['apt_num'] . '" placeholder="420"><br />';
  echo 'Zip Code: <input type="text" name="zip" id="zip" value="' . $_POST['zip'] . '" placeholder="94612"></p>';
  echo '<p><input type="submit" name="submit" id="submitButton" value="Submit">';
  echo '<input style="background-color:#c7c7c7" type="reset" name="clear" id="clearInput" value="Clear" onclick="return resetForm(this.form);"></p>';
  echo '</form>';
}

function cdp_join_shift_code() {
  // Shows the input form, keeping any values from previous submission
  echo '<form action="" id="voter_form" method="post">';
  echo '<p>First Name: <input autocapitalize="off" spellcheck="false" autocorrect="off" type="text" name="firstname" id="first" value="' . $_POST['firstname'] . '" placeholder="Jane"><br />';
  echo 'Last Name: <input autocapitalize="off" spellcheck="false" autocorrect="off" type="text" name="lastname" id="last" value="' . $_POST['lastname'] . '" placeholder="Doe"><br />';
  echo 'Street Number: <input autocapitalize="off" spellcheck="false" autocorrect="off" type="text" name="street_num" id="snum" value="' . $_POST['street_num'] . '" placeholder="1428"><br />';
  echo 'Street Name: <input autocapitalize="off" spellcheck="false" autocorrect="off" type="text" name="street_name" id="street" value="' . $_POST['street_name'] . '" placeholder="Franklin"><br />';
  echo 'Apartment Number: <input type="text" name="apt_num" id="anum" value="' . $_POST['apt_num'] . '" placeholder="420"><br />';
  echo 'Zip Code: <input type="text" name="zip" id="zip" value="' . $_POST['zip'] . '" placeholder="94612"></p>';
  echo '<p><input type="submit" name="submit" id="submitButton" value="Submit">';
  echo '<input style="background-color:#c7c7c7" type="reset" name="clear" id="clearInput" value="Clear" onclick="return resetForm(this.form);"></p>';
  echo '</form>';
}

function cdp_get_query_results($columns, $query) {
  global $wpdb;
  $table_name = $wpdb->prefix . "shifts_2022";
  $query = "SELECT " . implode(', ', $columns) . " from $table_name $query;";
  return $wpdb->get_results($query);
}

function cdp_nowtostr() {
  $timezone = new DateTimeZone('America/Los_Angeles');
  $today = new DateTime("now", $timezone);
  $timestring = $today->format('Y-m-d');
  return $timestring;
}

function cdp_strtotime($timestring) {
  $timezone = new DateTimeZone('America/Los_Angeles');
  $date = new DateTime($timestring, $timezone);
  return $date;
}

function sc_cdp_scheduling() {
  // wordpress entry point
  ob_start();
  cdp_scheduling_code();
  return ob_get_clean();
}

function sc_cdp_shift_reports() {
  // wordpress entry point
  ob_start();
  cdp_shift_reports_code();
  return ob_get_clean();
}

add_shortcode( 'cdp_scheduling', 'sc_cdp_scheduling' );
add_shortcode( 'cdp_shift_reports', 'sc_cdp_shift_reports' );

?>
