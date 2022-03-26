<?php
header('Content-Type: application/json');

defined( 'ABSPATH' ) || exit;

if (!wp_verify_nonce($_REQUEST['nonce'], "cdp_user_create_shift_nonce")) {
  exit("Not authorized to create shift");
}

$day_offset = $_REQUEST["day_id"];
$date_string = $_REQUEST["date"];

$result['type'] = "success";
$result['date'] = $date_string;

if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
  $result = json_encode($result);
  echo $result;
} else {
  header("Location: " . $_SERVER["HTTP_REFERER"]);
}

die();

?>
