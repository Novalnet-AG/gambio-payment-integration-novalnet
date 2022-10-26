<?php
include ('includes/application_top.php');
if($_REQUEST['action'] == 'delete_token'){
	$data = [];
	xtc_db_query("UPDATE novalnet_transaction_detail SET payment_details = NULL WHERE (status = 'CONFIRMED' or status = 'ON_HOLD' or status = 'PENDING') and id='" . $_REQUEST['id'] . "'");
	unset($_SESSION['saved_card_details']);
	$json_data = json_encode($data);
	echo $json_data;
	exit;
}
?>
