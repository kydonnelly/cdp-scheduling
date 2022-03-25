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
  $days_to_show = 14;
  $future_period = new DateInterval('P' . $days_to_show . 'D');

  $today = cdp_nowtostr();
  $future = date_format(date_add(cdp_strtotime($today), $future_period), 'Y-m-d');
  $schedule = cdp_get_query_results(SCHEDULING_COLUMNS, "WHERE end_time > '$today' AND end_time < '$future' ORDER BY start_time ASC");

  $daily_schedule = cdp_partition_daily_schedule($today, $schedule);
  for ($i = 0; $i < $days_to_show; $i++) {
    if (!array_key_exists($i, $daily_schedule)) {
      $daily_schedule[$i] = array();
    }
  }

  cdp_echo_schedule_html($today, $daily_schedule, true);
}

function cdp_shift_reports_code() {
  $today = cdp_nowtostr();
  $schedule = cdp_get_query_results(SCHEDULING_COLUMNS, "WHERE $end_time <= '$today' ORDER BY end_time DESC");
  $daily_schedule = cdp_partition_daily_schedule($today, $schedule);

  cdp_echo_schedule_html($today, $daily_schedule, false);
}

function cdp_partition_daily_schedule($today, $schedule) {
  $current_day = cdp_strtotime($today);
  $daily_schedule = array();

  // partition all the shift results into days
  foreach ($schedule as $shift_result) {
    $start_time = cdp_strtotime($shift_result->start_time);
    $interval = date_diff($current_day, $start_time);
    $days_apart = intval($interval->format('%d'));

    if (array_key_exists($days_apart, $daily_schedule)) {
      $daily_schedule[$days_apart] []= $shift_result;
    } else {
      $daily_schedule[$days_apart] = [$shift_result];
    }
  }

  return $daily_schedule;
}

function cdp_echo_schedule_html($today, $daily_schedule, $is_future) {
  $current_day = cdp_strtotime($today);
  $table_id = $is_future ? 'schedule_table' : 'reports_table';

  echo '<table id="' . $table_id . '" style="width:100%" cellspacing="2" cellpadding="4">';
  echo '<tbody>';
  foreach ($daily_schedule as $day_offset => $daily_shifts) {
    $period_offset = 'P' . $day_offset . 'D';
    $date_offset = new DateInterval($period_offset);
    $shift_day = date_add($current_day, $date_offset);
    $display_day = date_format($shift_day, 'l m/d/Y');
    date_sub($current_day, $date_offset); // need to undo the above line

    echo '<tr>';

    // Date header
    echo '<th id="row_' . $period_offset . '" class="row_header" data-row-index="' . $day_offset . '" scope="row">' . $display_day . '</th>';

    // New shift cell
    if ($is_future) {
      echo '<td class="create-shift" data-col-index="0" data-row-index="' . $day_offset . '">';
      echo '<ul class="shift-create">';
      echo '<li class="create-button"><button class="create" name="create_' . $day_offset . '" onclick="createShift(this)">Create</button></li>';
      echo '</ul>';
      echo '</td>';
    }

    // Existing shifts
    foreach ($daily_shifts as $shift_index => $daily_shift) {
      $start_time = date_format(date_create($daily_shift->start_time), 'h:i A');
      $end_time = date_format(date_create($daily_shift->end_time), 'h:i A');

      echo '<td class="upcoming-shift" data-col-index="' . ($shift_index + 1) . '" data-row-index="' . $day_offset . '">';
      echo '<ul class="shift-info">';
      echo '<li class="shift-gatherer"><span class="name">' . $daily_shift->gatherer . '</span></li>';
      echo '<li class="shift-location"><span class="name">' . $daily_shift->location . '</span></li>';
      echo '<li class="shift-timestamp"><span class="name">' . $start_time . ' - ' . $end_time . '</span></li>';
      echo '</ul>';

      // Join button
      if (intval($daily_shift->capacity) > 1) {
        echo '<ul class="shift-join">';
        echo '<li class="join-button"><button class="join" name="join_' . $daily_shift->shift_id . '" onclick="joinShift(this)">Join</button></li>';
        echo '</ul>';
      }
      echo '</td>';
    }
    echo '</tr>';
  }
  echo '</tbody>';
  echo '</table>';
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
