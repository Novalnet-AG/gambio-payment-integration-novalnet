<?php
/**
 * Novalnet payment module
 * This script is used for extension process
 *
 * @author     Novalnet AG
 * @copyright  Copyright (c) Novalnet
 * @license    https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 * @link       https://www.novalnet.de
 *
 * Script : novalnet_extension.php
 *
 */
require_once(DIR_FS_CATALOG . 'ext/novalnet/NovalnetHelper.class.php');
include_once(DIR_FS_LANGUAGES . $_SESSION['language']."/modules/payment/novalnet.php");
/**
 * To append Novalnet extension features in order details page
 *
 * @param integer $order_id
 */
function appendNovalnetOrderProcess($order_id) {
	$request = $_REQUEST;
	$transaction_details = NovalnetHelper::getNovalnetTransDetails($order_id);
	if(empty($transaction_details)){
		return false;
	}
	if($transaction_details['status'] == 'ON_HOLD' || in_array($transaction_details['status'], NovalnetHelper::$statuses['ON_HOLD'])) { // To process on-hold transaction
	?>
	<!--  Transaction management block  -->
	<div class="content article-table grid">
		<div class="span12 remove-padding">
			<div class ="frame-wrapper">
				<div class="frame-head">
					<label class="title"><?php echo MODULE_PAYMENT_NOVALNET_TRANS_CONFIRM_TITLE; ?></label>
				</div>
				<?php
				echo xtc_draw_form('novalnet_status_change', 'novalnet_extension_helper.php', 'oID='.$_GET['oID'].'&action=edit');
				?>
					<br><label style='margin:0% 0% 0% 1%'> <?php echo MODULE_PAYMENT_NOVALNET_TRANSACTION_ID . $transaction_details['tid']; ?></label>
					<?php
						echo xtc_draw_hidden_field('oID', $request['oID']);
						echo xtc_draw_hidden_field('nn_capture_update', MODULE_PAYMENT_NOVALNET_PAYMENT_CAPTURE_CONFIRM);
						echo xtc_draw_hidden_field('nn_void_update', MODULE_PAYMENT_NOVALNET_PAYMENT_VOID_CONFIRM);
					?>
					<table style="width:33%">
						<tr>
							<td>
								<?php
									echo MODULE_PAYMENT_NOVALNET_SELECT_STATUS_TEXT;
								?>
							</td>
							<td>
								<?php
									$options = array (
										array('id'=>'', 'text' => MODULE_PAYMENT_NOVALNET_SELECT_STATUS_OPTION),
										array('id'=>'CONFIRM', 'text' => MODULE_PAYMENT_NOVALNET_CONFIRM_TEXT),
										array('id'=>'CANCEL', 'text' => MODULE_PAYMENT_NOVALNET_CANCEL_TEXT),
									);
									echo xtc_draw_pull_down_menu('trans_status', $options, '', 'onclick="return remove_void_capture_error_message()"');
								?>
							</td><br><span id="nn_void_capture_error" style="color:red"></span>
						</tr>
						<tr>
							<td>
								<?php
								echo xtc_draw_input_field('nn_manage_confirm',html_entity_decode(MODULE_PAYMENT_NOVALNET_CONFIRM_TEXT), 'onclick="return void_capture_status();" style="float:left"',false,'submit');
								echo "<a class='button' style='float:left' href='" . xtc_href_link('orders.php') . "'>" . MODULE_PAYMENT_NOVALNET_BACK_TEXT . "</a>";
								?>
							</td>
						</tr>
					</table>
				</form>
			</div>
		</div>
	</div>
<?php	
	}
	if (($transaction_details['amount'] > 0)
	&& (($transaction_details['status'] == 'CONFIRMED' || in_array($transaction_details['status'], NovalnetHelper::$statuses['CONFIRMED'])) 
		|| ($transaction_details['status']=='PENDING' && in_array($transaction_details['payment_type'],array('INVOICE','PREPAYMENT','CASHPAYMENT')))) 
	&& !in_array($transaction_details['payment_type'],array('MULTIBANCO','INSTALMENT_INVOICE','INSTALMENT_DIRECT_DEBIT_SEPA','novalnet_instalment_invoice', 'novalnet_instalment_sepa'))
	&& ($transaction_details['refund_amount'] < $transaction_details['amount'])
	)
	{
?>
<!-- Refund block -->
	<div class="content article-table grid">
		<div class="span12 remove-padding">
			<div class ="frame-wrapper">
				<div class="frame-head">
					<label class="title" ><?php echo MODULE_PAYMENT_NOVALNET_REFUND_TITLE; ?></label>
				</div>
					<?php echo xtc_draw_form('novalnet_trans_refund', 'novalnet_extension_helper.php', 'oID='.$_GET['oID'].'&action=refund');  ?>
						<label style='margin:0% 0% 0% 1%'> <?php echo MODULE_PAYMENT_NOVALNET_TRANSACTION_ID . $transaction_details['tid']; ?></label>
					<?php
						echo xtc_draw_hidden_field('oID', $request['oID']);
						echo xtc_draw_hidden_field('nn_refund_amount', MODULE_PAYMENT_NOVALNET_PAYMENT_REFUND_CONFIRM);
					?>
					<br><span id="nn_refund_error" style="color:red"></span>
					<table>
						<tr>
							<td>
								<?php echo MODULE_PAYMENT_NOVALNET_REFUND_AMT_TITLE;?>
								<?php
								$avail_refund = (!empty($transaction_details['callback_amount'])) ? (int)$transaction_details['callback_amount'] : (int)$transaction_details['amount'];
								$refund_value = (!empty($transaction_details['refund_amount'])) ? ((int)$avail_refund - (int)$transaction_details['refund_amount']) : $avail_refund;
								echo xtc_draw_input_field('refund_trans_amount',$refund_value,'id="refund_trans_amount"  style="width:100px;margin:0 0 0 2%" autocomplete="off"'); echo MODULE_PAYMENT_NOVALNET_AMOUNT_EX;?>
							</td>
						</tr>
						<tr>
							<td>
								<?php echo MODULE_PAYMENT_NOVALNET_REFUND_REASON_TITLE;?>
								<?php
								echo xtc_draw_input_field('refund_reason' ,'' ,'id="refund_reason" style="margin:0 0 0 2%;" autocomplete="off"');?>
							</td>
						</tr>
						<tr>
							<td>
								<?php
								echo xtc_draw_input_field('nn_refund_confirm',html_entity_decode(MODULE_PAYMENT_NOVALNET_CONFIRM_TEXT), 'onclick="return refund_amount_validation();" style="float:left"',false,'submit');
								echo "<a class='button' style='float:left' href='" . xtc_href_link('orders.php') . "'>" . MODULE_PAYMENT_NOVALNET_BACK_TEXT . "</a>";
								?>
							</td>
						</tr>
					</table>
				</form>
			</div>
		</div>
	</div>
<?php
	}
	if (in_array($transaction_details['payment_type'],array('INSTALMENT_INVOICE','INSTALMENT_DIRECT_DEBIT_SEPA', 'novalnet_instalment_invoice', 'novalnet_instalment_sepa')) && ($transaction_details['status'] == 'CONFIRMED' || in_array($transaction_details['status'], NovalnetHelper::$statuses['CONFIRMED'])))
	{
		$instalment_details = (!empty($transaction_details['instalment_cycle_details'])) ? json_decode($transaction_details['instalment_cycle_details'], true) : unserialize($transaction_details['payment_details']);
		if(!empty($instalment_details)) {
		?>
		<div class="content article-table grid">
			<div class="span12 remove-padding">
				<script>
				function myFunction(cycle) {
				  var refund_id = document.getElementById("instalment_refund_"+cycle);
				  if (refund_id.style.display === "none") {
					refund_id.style.display = "block";
				  } else {
					refund_id.style.display = "none";
				  }
				}
				</script>
				<div class ="frame-wrapper">
					<div class="frame-head">
						<label class="title" ><?php echo MODULE_PAYMENT_NOVALNET_INSTALMENT_SUMMARY_BACKEND; ?></label>
					</div>
					<!-- instalment cancel button -->
					<br><div align="right">
						<?php echo xtc_draw_form('nn_instalment_cancel', 'novalnet_extension_helper.php'); 
						      echo xtc_draw_hidden_field('oID', $request['oID']); 
							  echo xtc_draw_input_field('nn_instalment_cancel',html_entity_decode(MODULE_PAYMENT_NOVALNET_INSTALMENT_CANCEL_ADMIN_TEXT), 'onclick="return refund_amount_validation();" style="float:left"',false,'submit');							
						?>
						</form>
					</div>
				<?php
					echo "<table><tr><td>S.No</td><td>".MODULE_PAYMENT_NOVALNET_INSTALMENT_AMOUNT_BACKEND."</td><td>".MODULE_PAYMENT_NOVALNET_INSTALMENT_PAID_DATE_BACKEND."</td><td>".MODULE_PAYMENT_NOVALNET_INSTALMENT_NEXT_DATE_BACKEND."</td><td>".MODULE_PAYMENT_NOVALNET_INSTALMENT_STATUS_BACKEND."</td><td>".MODULE_PAYMENT_NOVALNET_INSTALMENT_REFERENCE_BACKEND."</td></tr>";
					$sno = 1;
					foreach ($instalment_details as $key => $instalment_details_data) {
						$instalment_amount = (strpos((string)$instalment_details_data['instalment_cycle_amount'], '.')) ? $instalment_details_data['instalment_cycle_amount']*100 : $instalment_details_data['instalment_cycle_amount'];
						if(!empty($instalment_details_data['status'])) {
							$status = $instalment_details_data['status'];
						} else {
							$status = (empty($instalment_details_data['reference_tid'])) ? 'Pending' : (($instalment_amount > 0) ? 'Paid' : 'Refunded');
						}
						$status = constant('MODULE_PAYMENT_NOVALNET_INSTALMENT_STATUS_' .  strtoupper($status));
						$href = ($instalment_details_data['reference_tid'] != '' && $instalment_amount != '0') ? "<button id='nn_refund1'  onclick='myFunction($key)'>" . MODULE_PAYMENT_NOVALNET_REFUND_TEXT . "</button>" : '';
						$instalment_amount_formatted = !empty($instalment_amount) ? xtc_format_price_order($instalment_amount/100, 1, $transaction_details['currency']) : '-';
						echo "<tr><td>".$sno++."</td><td>".$instalment_amount_formatted.' '.$href."</td>
						<td>".$instalment_details_data['paid_date']."</td><td>".$instalment_details_data['next_instalment_date']."</td><td>$status</td><td>".$instalment_details_data['reference_tid']."</td><td>".    
						xtc_draw_form('nn_refund_confirm', 'novalnet_extension_helper.php');  
						echo '<div id= instalment_refund_'.$key.' style="display: none;">' ;
						echo xtc_draw_hidden_field('oID', $request['oID']); 
						echo xtc_draw_hidden_field('refund_tid', $instalment_details_data['reference_tid']); 
						echo xtc_draw_hidden_field('instalment_cycle', ''.$key.''); 
						echo xtc_draw_input_field('refund_trans_amount',$instalment_amount,'id="refund_trans_amount"  style="width:100px;margin:0 0 0 2%" autocomplete="off"');
						echo xtc_draw_input_field('nn_refund_confirm',html_entity_decode(MODULE_PAYMENT_NOVALNET_CONFIRM_TEXT), 'onclick="return refund_amount_validation();" style="float:left"',false,'submit');
						echo "<a class='button' style='float:left' href='" . xtc_href_link('orders.php?oID='.$request['oID'].'&action=edit') . "'>" . MODULE_PAYMENT_NOVALNET_INSTALMENT_CANCEL_TEXT . "</a>"; ?>
						</div></form></td></tr><?php 
					}
					echo "</table>"; 
				?>
				</div>
			</div>
		</div>
	<?php
		}
	}
}
echo '<script type="text/javascript" src="' . DIR_WS_CATALOG . 'ext/novalnet/js/novalnet_extension.js"></script>';
echo '<link rel="stylesheet" type="text/css" href="' . DIR_WS_CATALOG . 'ext/novalnet/css/novalnet.css">';
echo xtc_draw_hidden_field('nn_refund_amount_confirm', MODULE_PAYMENT_NOVALNET_PAYMENT_REFUND_CONFIRM);
echo xtc_draw_hidden_field('nn_select_status', MODULE_PAYMENT_NOVALNET_SELECT_STATUS_TEXT);
?>

