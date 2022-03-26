<?php
header('Content-Type: application/json');

if (!wp_verify_nonce($_REQUEST['nonce'], "cdp_user_join_shift_nonce")) {
  exit("Not authorized to join shift");
}

$shift_id = $_REQUEST["shift_id"];

$result['type'] = "success";
$result['shift_id'] = $shift_id;

if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
  $result = json_encode($result);
  echo $result;
} else {
  header("Location: " . $_SERVER["HTTP_REFERER"]);
}

die();

?>
