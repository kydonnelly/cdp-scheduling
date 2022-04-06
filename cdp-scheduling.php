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
define("LOCATION_COLUMNS", ['location_id', 'name', 'quality', 'capacity']);
define("SCHEDULING_COLUMNS", ['shift_id', 'gatherer', 'location_id', 'start_time', 'end_time', 'capacity', 'cancelled', 'notes']);
define("SHIFT_REPORT_COLUMNS", ['shift_id', 'gatherer', 'location_id', 'start_time', 'end_time', 'capacity', 'raw_signatures', 'validated_signatures', 'notes']);

define("JOIN_SHIFT_COLUMNS", ['gatherer', 'location_id', 'start_time', 'end_time', 'capacity']);

function cdp_scheduling_code() {
  $days_to_show = 14;
  $future_period = new DateInterval('P' . $days_to_show . 'D');

  $today = cdp_nowtostr();
  $future = date_format(date_add(cdp_strtotime($today), $future_period), 'Y-m-d');
  $schedule = cdp_get_query_results(SCHEDULING_COLUMNS, "WHERE parent_id IS NULL AND end_time > '$today' AND end_time < '$future' ORDER BY start_time ASC");

  $daily_schedule = cdp_partition_daily_schedule($today, $schedule, $days_to_show);

  echo '<div id="contact_info">
  <p><label for="contact_phone"><b>*Required:</b> name (public) and phone number (private): </label><br />
  <input id="contact_name" class="name_field" size="48" maxlength="127" required="required" autocomplete="on" placeholder="Name" type="text" name="name_field" />    
  <input id="contact_phone" class="phone_field" size="24" maxlength="15" required="required" autocomplete="on" placeholder="510-555-9160" type="tel" name="phone_field" /></p>
  </div>';

  cdp_echo_schedule_html($today, $daily_schedule, true);
}

function cdp_shift_reports_code() {
  $today = cdp_nowtostr();
  $schedule = cdp_get_query_results(SCHEDULING_COLUMNS, "WHERE cancelled = 0 AND end_time <= '$today' ORDER BY end_time DESC");
  $daily_schedule = cdp_partition_daily_schedule($today, $schedule);

  echo '<h1>Past Shift Reports</h1>';

  cdp_echo_schedule_html($today, $daily_schedule, false);
}

function cdp_partition_daily_schedule($today, $schedule, $min_rows = 0) {
  $current_day = cdp_strtotime($today);
  $daily_schedule = array();

  // Pad out the minimum number of days
  for ($i = 0; $i < $min_rows; $i++) {
    if (!array_key_exists($i, $daily_schedule)) {
      $daily_schedule[$i] = array();
    }
  }

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
  $join_nonce = wp_create_nonce("cdp_join_shift_nonce");
  $create_nonce = wp_create_nonce("cdp_create_shift_nonce");
  $cancel_nonce = wp_create_nonce("cdp_cancel_shift_nonce");

  $current_day = cdp_strtotime($today);
  $locations = cdp_get_locations(LOCATION_COLUMNS, "ORDER BY name");
  $location_map = array_combine(array_map(function($l) { return $l->location_id; }, $locations), $locations);

  $table_id = $is_future ? 'schedule_table' : 'reports_table';
  echo '<div style="overflow-x: scroll;">';
  echo '<table id="' . $table_id . '"  style="table-layout: fixed;" cellspacing="2" cellpadding="4">';
  echo '<tbody>';
  foreach ($daily_schedule as $day_offset => $daily_shifts) {
    $period_offset = 'P' . $day_offset . 'D';
    $date_offset = new DateInterval($period_offset);
    $shift_day = date_add($current_day, $date_offset);
    $display_day = date_format($shift_day, 'l m/d/Y');
    $db_date_string = date_format($shift_day, 'Y-m-d');
    date_sub($current_day, $date_offset); // need to undo the above line

    echo '<tr>';

    // Date header
    echo '<th id="row_' . $period_offset . '" class="row_header" data-row-index="' . $day_offset . '" scope="row">' . $display_day . '</th>';

    // New shift cell
    if ($is_future) {
      $create_shift_link = admin_url('admin-ajax.php?action=cdp_create_shift&day_id=' . $day_offset . '&date=' . $db_date_string . '&nonce=' . $create_nonce);

      echo '<td class="create-shift" data-col-index="0" data-row-index="' . $day_offset . '">';
      echo '<ul class="shift-create">';
      echo '<li>
      <label for="start_time_' . $day_offset . '">Time: </label>
      <input type="time" id="start_time_' . $day_offset . '" name="start_time" step="900" required> - <input type="time" id="end_time_' . $day_offset . '" name="end_time" step="900" required>
      </li>';
      echo '<li>
      <label for="create_location_' . $day_offset . '">Location: </label>
      <select id="create_location_' . $day_offset . '" class="location_field" style="width:67%" required="required" name="location_field">
      <option value="none">Choose...</option>';
      foreach ($locations as $location) {
        echo '<option value="' . $location->location_id . '">' . ' ' . $location->name . ' ' . cdp_location_quality_emoji($location) . '</option>';
      }
      echo '</select>
      </li>';
      echo '<li>
      <label for="capacity_' . $day_offset . '">Capacity: </label>
      <input id="capacity_' . $day_offset . '" class="capacity_field" size="8" maxlength="7" required="required" min="0" max="4" autocomplete="off" placeholder="0" type="number" name="capacity_field" />
      </li>';
      echo '<li>
      <label for="notes_' . $day_offset . '">Notes: </label>
      <input id="notes_' . $day_offset . '" class="notes_field" style="width:75%" maxlength="255" autocomplete="off" placeholder="optional" type="text" name="notes_field" />
      </li>';
      echo '<li class="create-button" id="create_' . $day_offset . '"><a class="create" id="' . $day_offset . '" data-nonce="' . $create_nonce . '" data-date="' . $db_date_string . '" data-day_id="' . $day_offset . '" href="' . $create_shift_link . '">Create</a></li>';
      echo '<li class="loading-button" id="creating_' . $day_offset . '" hidden><a class="loading">Creating...</a></li>';
      echo '</ul>';
      echo '</td>';

      // Create shift placeholder
      echo '<td class="upcoming-shift" id="placeholder_create_' . $day_offset . '" data-col-index="0" data-row-index="' . $day_offset . '" style="background-color: #FEFEFE" hidden>
      <ul class="shift-info">
      <li class="shift-gatherer"><span class="name" id="placeholder_bottomliner_' . $day_offset . '">NAME</span></li>
      <li class="shift-location"><span class="name" id="placeholder_location_' . $day_offset . '">LOCATION</span></li>
      <li class="shift-timestamp"><span class="name" id="placeholder_time_' . $day_offset . '">START - END</span></li>
      <li class="shift-notes"><span class="name" id="placeholder_notes_' . $day_offset . '">NOTES</span></li>
      <li><span class="name">Saved! Thank you for singing up &#x2705 Refresh the page to dismiss this message.</span></li>
      </ul></td>';
    }

    // Existing shifts
    foreach ($daily_shifts as $shift_index => $daily_shift) {
      $start_time = date_format(date_create($daily_shift->start_time), 'h:i A');
      $end_time = date_format(date_create($daily_shift->end_time), 'h:i A');
      $location = $location_map[$daily_shift->location_id];

      $joiners = array();
      if ($is_future) {
        $joiners = cdp_get_query_results(['shift_id', 'gatherer', 'cancelled'], "WHERE parent_id = $daily_shift->shift_id ORDER BY shift_id ASC");
      }

      $bottomliner_count = $daily_shift->cancelled ? 0 : 1;
      $joiners_count = count( array_filter($joiners, function ($j) { return $j->cancelled != 1; }) );
      $can_join = $daily_shift->capacity == 0 || $joiners_count + $bottomliner_count < $daily_shift->capacity;

      echo '<td id="' . $daily_shift->start_time . '" class="upcoming-shift" data-col-index="' . ($shift_index + 1) . '" data-row-index="' . $day_offset . '" style="background-color: ' . cdp_location_quality_hex($location) . ';">
      <ul class="shift-info">';

      // Location + Time
      echo '<li class="shift-location"><span class="name"><b>' . $location->name . '</b></span></li>';
      echo '<li class="shift-timestamp"><span class="name">' . $start_time . ' - ' . $end_time . '</span></li>';

      // Gatherers
      if ($daily_shift->cancelled) {
        // strikethrough
        echo '<li class="shift-gatherer"><span class="name" id="gatherer_' . $daily_shift->shift_id . '"><s><b>Leader: </b>' . $daily_shift->gatherer . '</s></span></li>';
      } else {
        // with cancel button
        $cancel_shift_link = admin_url('admin-ajax.php?action=cdp_cancel_shift&shift_id=' . $daily_shift->shift_id . '&nonce=' . $cancel_nonce);
        echo '<li class="shift-gatherer"><span class="name" id="gatherer_' . $daily_shift->shift_id . '"><b>Leader: </b>' . $daily_shift->gatherer . '</span><a class="cancel-shift" id="cancel_"' . $daily_shift->shift_id . ' href="' . $cancel_shift_link . '" data-nonce="' . $cancel_nonce . '" data-shift_id="' . $daily_shift->shift_id . '">x</a></li>';
      }
      foreach ($joiners as $joiner) {
        if ($joiner->cancelled) {
          // strikethrough
          echo '<li class="shift-gatherer"><span class="name" id="gatherer_' . $joiner->shift_id . '"><s>joined by ' . $joiner->gatherer . '</s></span></li>';
        } else {
          // with cancel button
          echo '<li class="shift-gatherer"><span class="name" id="gatherer_' . $joiner->shift_id . '">joined by ' . $joiner->gatherer . '</span><a class="cancel-shift" id="cancel_"' . $joiner->shift_id . ' href="' . $cancel_shift_link . '" data-nonce="' . $cancel_nonce . '" data-shift_id="' . $joiner->shift_id . '">x</a></li>';
        }
      }
      if ($is_future && $can_join) {
        echo '<li class="shift-gatherer" id="shift_joiner_' . $daily_shift->shift_id . '" hidden><span class="name" id="joiner_' . $daily_shift->shift_id . '">PLACEHOLDER</span></li>';
      }

      // Notes
      if (strlen($daily_shift->notes) > 0) {
        echo '<li class="shift-notes"><span class="notes"><i>' . $daily_shift->notes . '</i></span></li>';
      }
      echo '</ul>';

      // Join status
      if ($is_future) {
        $join_shift_link = admin_url('admin-ajax.php?action=cdp_join_shift&shift_id=' . $daily_shift->shift_id . '&nonce=' . $join_nonce);
        echo '<ul class="shift-join" id="join_' . $daily_shift->shift_id . '" ' . ($can_join ? '' : 'hidden') . '>
        <li class="join-button"><a class="join" id="' . $daily_shift->shift_id . '" data-nonce="' . $join_nonce . '" data-shift_id="' . $daily_shift->shift_id . '" href="' . $join_shift_link . '">Join</a></li>
        </ul>';
        echo '<ul class="shift-joining" id="joining_' . $daily_shift->shift_id . '" hidden>
        <li class="loading-button"><a class="loading">Joining...</a></li>
        </ul>';
        echo '<ul class="shift-join" id="full_' . $daily_shift->shift_id . '" ' . ($can_join ? 'hidden' : '') . '>
        <li class="shift-filled"><span class="full" id="' . $daily_shift->shift_id . '">&#x2728 Full &#x2728</span></li>
        </ul>';
      }
      echo '</td>';
    }
    echo '</tr>';
  }
  echo '</tbody>';
  echo '</table>';
  echo '</div>';
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

// DATABASE

function cdp_get_query_results($columns, $query) {
  global $wpdb;
  $table_name = $wpdb->prefix . "shifts_2022";
  $query = "SELECT " . implode(', ', $columns) . " from $table_name $query;";
  return $wpdb->get_results($query);
}

function cdp_get_locations($columns, $query) {
  global $wpdb;
  $table_name = $wpdb->prefix . "gathering_locations";
  $query = "SELECT " . implode(', ', $columns) . " from $table_name $query;";
  return $wpdb->get_results($query);
}

function cdp_submit_join_shift($shift_id, $name, $phone) {
  $parent_shifts = cdp_get_query_results(JOIN_SHIFT_COLUMNS, "WHERE shift_id = $shift_id");
  if (count($parent_shifts) != 1) {
    return false;
  }
  $parent_shift = $parent_shifts[0];

  $insertions = array('parent_id' => $shift_id,
    'gatherer' => $name,
    'contact' => $phone,
    'location_id' => $parent_shift->location_id,
    'start_time' => $parent_shift->start_time,
    'end_time' => $parent_shift->end_time,
    'capacity' => $parent_shift->capacity,
    'cancelled' => 0);

  global $wpdb;
  $table_name = $wpdb->prefix . "shifts_2022";
  $success = $wpdb->insert($table_name, $insertions);
  return $success;
}

function cdp_submit_create_shift($shift_id, $params) {
  $insertions = array(
    'gatherer' => $params['name'],
    'contact' => $params['phone'],
    'location_id' => intval($params['location']),
    'start_time' => $params['start_time'],
    'end_time' => $params['end_time'],
    'capacity' => intval($params['capacity']),
    'cancelled' => 0
  );
  if (isset($params['notes']) && strlen($params['notes']) > 0) {
    $insertions['notes'] = $params['notes'];
  }

  global $wpdb;
  $table_name = $wpdb->prefix . "shifts_2022";
  $success = $wpdb->insert($table_name, $insertions);
  return $success;
}

function cdp_submit_cancel_shift($shift_id) {
  $where = array('shift_id' => intval($shift_id));
  $update = array('cancelled' => true);
  global $wpdb;
  $table_name = $wpdb->prefix . "shifts_2022";
  $result = $wpdb->update($table_name, $update, $where);
  return $result == 1;
}

function cdp_location_quality_emoji($location) {
  switch (intval($location->quality)) {
    case 0: return '&#x26AA';  // gray circle
    case 1: return '&#x1F534'; // red circle
    case 2: return '&#x1F7E0'; // orange circle
    case 3: return '&#x1F7E1'; // yellow circle
    case 4: return '&#x1F7E2'; // green circle
  }
}

function cdp_location_quality_hex($location) {
  switch (intval($location->quality)) {
    case 0: return '#FEFEFE';  // gray
    case 1: return '#F27D8B'; // faded red
    case 2: return '#F48C66'; // orange salmon
    case 3: return '#F6D78D'; // pale yellow
    case 4: return '#AEC2B1'; // soft green
  }
}

// TIME

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

// AJAX

function cdp_setup_ajax() {
  // https://www.smashingmagazine.com/2011/10/how-to-use-ajax-in-wordpress/
  wp_register_script( 'cdp_scheduling', WP_PLUGIN_URL . '/cdp-scheduling/cdp-scheduling.js', array('jquery') );
  wp_localize_script( 'cdp_scheduling', 'cdpAjax', array( 'ajaxURL' => admin_url( 'admin-ajax.php' )));     

  wp_enqueue_script( 'jquery' );
  wp_enqueue_script( 'cdp_scheduling' );
}

function cdp_join_shift() {
  if (!wp_verify_nonce($_REQUEST['nonce'], "cdp_join_shift_nonce")) {
    exit("Not authorized to join shift");
  }

  $result = array();

  if (!isset($_REQUEST["shift_id"]) || !isset($_REQUEST["name"]) || !isset($_REQUEST["phone"])) {
    $result['type'] = "error";
    $result['error_reason'] = "Missing required fields.";
    echo json_encode($result);
    die();
  }

  $shift_id = $_REQUEST["shift_id"];
  $name = $_REQUEST["name"];
  $phone = $_REQUEST["phone"];

  if (strlen($name) <= 0 || strlen($phone) <= 0) {
    $result['type'] = "error";
    $result['error_reason'] = "Empty required fields.";
    echo json_encode($result);
    die();
  }

  $success = cdp_submit_join_shift($shift_id, $name, $phone);
  if (!$success) {
    $result['type'] = "error";
    $result['error_reason'] = "Could not update database.";
    echo json_encode($result);
    die();
  }

  $result['type'] = "success";
  if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    echo json_encode($result);
  } else {
    header("Location: " . $_SERVER["HTTP_REFERER"]);
  }

  die();
}

function cdp_create_shift() {
  if (!wp_verify_nonce($_REQUEST['nonce'], "cdp_create_shift_nonce")) {
    exit("Not authorized to create shift");
  }

  $keys = ["name", "phone", "start_time", "end_time", "location", "capacity", "notes"];
  foreach ($keys as $key) {
    if (!isset($_REQUEST[$key])) {
      $result['type'] = "error";
      $result['error_reason'] = "Missing required field $key.";
      echo json_encode($result);
      die();
    }
  }

  $params = array_combine($keys, array_map(function($k) { return $_REQUEST[$k]; }, $keys));
  $success = cdp_submit_create_shift($shift_id, $params);
  if (!$success) {
    $result['type'] = "error";
    $result['error_reason'] = "Could not update database.";
    echo json_encode($result);
    die();
  }

  $result['type'] = "success";
  if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    echo json_encode($result);
  } else {
    header("Location: " . $_SERVER["HTTP_REFERER"]);
  }

  die();
}

function cdp_cancel_shift() {
  if (!wp_verify_nonce($_REQUEST['nonce'], "cdp_cancel_shift_nonce")) {
    exit("Not authorized to cancel shift");
  }

  $result = array();

  if (!isset($_REQUEST["shift_id"])) {
    $result['type'] = "error";
    $result['error_reason'] = "Missing required fields.";
    echo json_encode($result);
    die();
  }

  $shift_id = $_REQUEST["shift_id"];

  $success = cdp_submit_cancel_shift($shift_id);
  if (!$success) {
    $result['type'] = "error";
    global $wpdb;
    $result['error_reason'] = "Could not cancel the shift.";
    echo json_encode($result);
    die();
  }

  $result['type'] = "success";
  if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    echo json_encode($result);
  } else {
    header("Location: " . $_SERVER["HTTP_REFERER"]);
  }

  die();
}

add_action( 'init', 'cdp_setup_ajax' );
add_action( 'wp_ajax_cdp_join_shift', "cdp_join_shift" );
add_action( 'wp_ajax_cdp_create_shift', "cdp_create_shift" );
add_action( 'wp_ajax_cdp_cancel_shift', "cdp_cancel_shift" );
add_action( 'wp_ajax_nopriv_cdp_join_shift', "cdp_join_shift" );
add_action( 'wp_ajax_nopriv_cdp_create_shift', "cdp_create_shift" );
add_action( 'wp_ajax_nopriv_cdp_cancel_shift', "cdp_cancel_shift" );

// SHORTCODE

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
