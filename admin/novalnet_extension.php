<?php
/**
 * Novalnet payment module related file
 * This module is used for real time processing of
 * Novalnet transaction of customers.
 *
 * Copyright (c) Novalnet
 *
 * Released under the GNU General Public License
 * This free contribution made by request.
 * If you have found this script useful a small
 * recommendation as well as a comment on merchant form
 * would be greatly appreciated.
 *
 * Script : novalnet_extension.php
 *
 */
require_once(DIR_FS_CATALOG . 'includes/external/novalnet/NovalnetHelper.class.php');
include_once(DIR_FS_LANGUAGES . $_SESSION['language']."/modules/payment/novalnet.php");
/**
 * To append novalnet extension features in admin
 *
 * @param integer $order_id
 */
function appendNovalnetOrderProcess($order_id) {
$request = $_REQUEST;
$message = NovalnetHelper::getServerResponse($request);
$class_message = ($request['status'] == 100 ) ? "success" : "danger " ; 
		if(!empty($request['status_desc']) || !empty($request['status_text'])) {?>
		<div class ="message_stack_container hidden breakpoint-large">
			<div class = "alert alert-<?php echo $class_message ?>">
			<?php echo $message; // To display error message
			?><button type="button" class="close" data-dismiss="alert">×</button>
			</div>
		</div>
<?php
	}
	$datas = NovalnetHelper::getNovalnetTransDetails($order_id); // To get transaction details from database
	$callback_info = xtc_db_fetch_array(xtc_db_query("SELECT sum(callback_amount) AS callback_total_amount, order_amount FROM novalnet_callback_history WHERE original_tid = '" . xtc_db_input($datas['tid']) . "'ORDER BY id DESC LIMIT 1"));
	$order_total = xtc_db_fetch_array(xtc_db_query("SELECT value FROM " . TABLE_ORDERS_TOTAL . " where class = 'ot_total' AND orders_id = " . xtc_db_input($order_id)));
	if (isset($datas) && $datas['amount'] == 0 && in_array($datas['payment_id'],array(6,37,34))  && $datas['gateway_status'] != 103) { // Zero amount booking transaction process
?>
<!-- zero amount booking block -->
<div class="content article-table grid">
	<div class="span12 remove-padding">
		<div class ="frame-wrapper">
			<div class="frame-head">
					<label class="title" ><?php echo MODULE_PAYMENT_NOVALNET_BOOK_TITLE; ?></label>
			</div>
				<?php echo xtc_draw_form('novalnet_book_amount', 'novalnet_extension_helper.php');  ?>
				<label style="margin:0% 0% 0% 1%"><?php echo MODULE_PAYMENT_NOVALNET_TRANSACTION_ID . $datas['tid']; ?></label>
				<?php echo xtc_draw_hidden_field('oID', $request['oID']);?>
				<table style="width=31%">
					<tr>
						<td>
							<?php
								$amount = $order_total['value']*100;
								echo MODULE_PAYMENT_NOVALNET_BOOK_AMT_TITLE; 
								echo xtc_draw_input_field('book_amount',$amount,'id="book_amount" onkeypress="return is_numeric_check(event)" autocomplete="off" style="margin:0% 0% 0% 2%"'); echo MODULE_PAYMENT_NOVALNET_AMOUNT_EX;
							?>
						</td>
					</tr><br><span id="nn_zero_amount_error" style="color:red"></span>
					<tr>
						<td>
							<?php 							
							echo xtc_draw_input_field('nn_book_confirm',html_entity_decode(MODULE_PAYMENT_NOVALNET_CONFIRM_TEXT), 'onclick="return zero_amount_validationt();" style="float:left"',false,'submit');
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

	if (in_array($datas['gateway_status'], array(98,99,91,85)) && in_array($datas['payment_id'], array('6','27','34','37','40','41')))  { // To process on-hold transaction
?>
<!--  Transaction management block  -->
<div class="content article-table grid">
	<div class="span12 remove-padding">
		<div class ="frame-wrapper">
			<div class="frame-head">
					<label class="title"><?php echo MODULE_PAYMENT_NOVALNET_TRANS_CONFIRM_TITLE; ?></label>
			</div>
				<?php echo xtc_draw_form('novalnet_status_change', 'novalnet_extension_helper.php');  ?>
					<br><label style='margin:0% 0% 0% 1%'> <?php echo MODULE_PAYMENT_NOVALNET_TRANSACTION_ID . $datas['tid']; ?></label>
				<?php echo xtc_draw_hidden_field('oID', $request['oID']);
				echo xtc_draw_hidden_field('nn_capture_update', MODULE_PAYMENT_NOVALNET_PAYMENT_CAPTURE_CONFIRM);
				echo xtc_draw_hidden_field('nn_void_update', MODULE_PAYMENT_NOVALNET_PAYMENT_VOID_CONFIRM);?>
				<table style="width:33%">
					<tr>
						<td>
							<?php echo MODULE_PAYMENT_NOVALNET_SELECT_STATUS_TEXT; ?>
						</td>
						<td>
							<select name="trans_status" id='trans_status' onclick="return remove_void_capture_error_message()">
								<option value=''><?php echo MODULE_PAYMENT_NOVALNET_SELECT_STATUS_OPTION; ?></option>
								<option value='100'><?php echo MODULE_PAYMENT_NOVALNET_CONFIRM_TEXT; ?></option>
								<option value='103'><?php echo MODULE_PAYMENT_NOVALNET_CANCEL_TEXT; ?></option>
							</select>
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
	if (isset($datas) && $datas['amount'] != 0 && $datas['gateway_status'] == 100) {
		global $order;
?>
<!-- Refund block -->
<div class="content article-table grid">
	<div class="span12 remove-padding">
		<div class ="frame-wrapper">
			<div class="frame-head">
				<label class="title" ><?php echo MODULE_PAYMENT_NOVALNET_REFUND_TITLE; ?></label>
			</div>
				<?php echo xtc_draw_form('novalnet_trans_refund', 'novalnet_extension_helper.php');  ?>
				<label style='margin:0% 0% 0% 1%'> <?php echo MODULE_PAYMENT_NOVALNET_TRANSACTION_ID . $datas['tid']; ?></label>
				<?php echo xtc_draw_hidden_field('oID', $request['oID']);
				echo xtc_draw_hidden_field('nn_refund_amount', MODULE_PAYMENT_NOVALNET_PAYMENT_REFUND_CONFIRM);?><br><span id="nn_refund_error" style="color:red"></span>
				<table style="width:100%" >
					<?php
						$order_date = strtotime(date("Y-m-d",strtotime($datas['novalnet_order_date'])));
						if ( strtotime(date('Y-m-d')) > $order_date ) { // date comparasion ?>
					<tr>
						<td>
							<?php echo MODULE_PAYMENT_NOVALNET_REFUND_REFERENCE_TEXT; ?>
							<?php echo xtc_draw_input_field('refund_ref','','id="refund_ref" autocomplete="off" style="width:174px;margin:0 0 0 2%;"');?>
						</td>
					</tr>
					<?php } ?>
				</table>
				<table style="width=31%">
					<tr>
						<td>
							<?php echo MODULE_PAYMENT_NOVALNET_REFUND_AMT_TITLE;?>
							<?php echo xtc_draw_input_field('refund_trans_amount',$datas['amount'],'id="refund_trans_amount" onkeypress="return is_numeric_check(event)" style="width:100px;margin:0 0 0 2%" autocomplete="off"'); echo MODULE_PAYMENT_NOVALNET_AMOUNT_EX;?>
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
	// To process subscription block
	if(isset($datas) && $datas['subs_id'] != 0) {
		$subscription_info = xtc_db_fetch_array(xtc_db_query("SELECT subs_id, tid, signup_date, termination_reason, termination_at FROM novalnet_subscription_detail WHERE order_no='". xtc_db_input($order_id) ."' "));
		if(isset($subscription_info) && $subscription_info['termination_reason'] == '' && $datas['gateway_status'] != 103) {  // To allow subscription cancel process for admin
		?>
<!-- subscription block -->
<div class="content article-table grid">
	<div class="span12 remove-padding">
		<div class ="frame-wrapper">
			<div class="frame-head">
				<label class="title" ><?php echo MODULE_PAYMENT_NOVALNET_SUBS_CANCEL_TITLE; ?></label>
			</div>
				<?php echo xtc_draw_form('novalnet_unsubscribe', 'novalnet_extension_helper.php');  ?>
				<label style='margin:0% 0% 0% 1%'><?php echo MODULE_PAYMENT_NOVALNET_TRANSACTION_ID . $datas['tid']; ?></label>
				<?php echo xtc_draw_hidden_field('oID', $request['oID']);
					  echo xtc_draw_hidden_field('nn_subscription_cancel', MODULE_PAYMENT_NOVALNET_PAYMENT_CANCEL_SUBSCRIPTION);?>
				<table style="width:50%">
					<tr>
						<td>
							<?php echo MODULE_PAYMENT_NOVALNET_SUBS_SELECT_REASON; ?>
						</td>
						<td>
							<?php $subs_termination_reason = array(MODULE_PAYMENT_NOVALNET_SUBS_REASON_1, MODULE_PAYMENT_NOVALNET_SUBS_REASON_2 ,MODULE_PAYMENT_NOVALNET_SUBS_REASON_3, MODULE_PAYMENT_NOVALNET_SUBS_REASON_4, MODULE_PAYMENT_NOVALNET_SUBS_REASON_5, MODULE_PAYMENT_NOVALNET_SUBS_REASON_6, MODULE_PAYMENT_NOVALNET_SUBS_REASON_7, MODULE_PAYMENT_NOVALNET_SUBS_REASON_8, MODULE_PAYMENT_NOVALNET_SUBS_REASON_9, MODULE_PAYMENT_NOVALNET_SUBS_REASON_10, MODULE_PAYMENT_NOVALNET_SUBS_REASON_11); ?>
							<select name="subscribe_termination_reason" id ="subscribe_termination_reason" style="margin:0% 0% 0% -10%" onclick="return remove_subs_error_message()">
								<option value=''><?php echo MODULE_PAYMENT_NOVALNET_SELECT_STATUS_OPTION; ?></option>
								<?php
									foreach($subs_termination_reason as $val) {
									?>
								<option value='<?php echo $val; ?>'><?php echo $val; ?></option>
								<?php
									}
									?>
							</select>
						</td><br><span id="nn_subs_cancel_error" style="color:red"></span>
					</tr>
					<tr>
					<td colspan='2'>
						<?php 							
							echo xtc_draw_input_field('nn_subs_confirm',html_entity_decode(MODULE_PAYMENT_NOVALNET_CONFIRM_TEXT), 'onclick="return validate_subscription_form();" style="float:left"',false,'submit');
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
	}
	if(($datas['payment_id'] == 37 && $datas['gateway_status'] == 99) || (in_array($datas['payment_id'],array(27,59)) && $datas['gateway_status'] == 100 && $callback_info['callback_total_amount'] <  $callback_info['order_amount'])) {
		$orderInfo = unserialize($datas['payment_details']);
?>
	<!-- Amount update block -->
<div class="content article-table grid">
	<div class="span12 remove-padding">
		<div class ="frame-wrapper">
			<div class="frame-head">
				<label class="title" ><?php 
				echo ($datas['payment_id'] == 37) ? MODULE_PAYMENT_NOVALNET_AMOUNT_CHANGE_TITLE : (($datas['payment_id'] == 27) ? MODULE_PAYMENT_NOVALNET_AMOUNT_CHANGE_DUE_DATE_TITLE : MODULE_PAYMENT_NOVALNET_SLIP_DATE_CHANGE_TITLE); ?></label>
			</div>
				<?php echo xtc_draw_form('novalnet_amount_change', 'novalnet_extension_helper.php');
				echo xtc_draw_hidden_field('nn_root_amount_update', DIR_WS_CATALOG);
				echo xtc_draw_hidden_field('novalnet_amount_update_value', 1);
				echo xtc_draw_hidden_field('oID', $request['oID']);				
				?>
				<table>
					<label style='margin:0% 0% 0% 1%'><?php echo MODULE_PAYMENT_NOVALNET_TRANSACTION_ID . $datas['tid']; ?></label>
					<tr>
						<td>
							<?php echo MODULE_PAYMENT_NOVALNET_TRANS_AMOUNT_TITLE; ?>
						</td>
						<td>
							<?php echo xtc_draw_input_field('new_amount',$datas['amount'],'id="new_amount" onkeypress="return is_numeric_check(event)" style="margin:0 0 0 -9%" autocomplete="off"'); echo MODULE_PAYMENT_NOVALNET_AMOUNT_EX;?>
						</td>
					</tr>
					<?php
						$invoice_prepayment_payment = 0;
						if (in_array($datas['payment_id'],array(27,59))) {
							$input_day = $input_month = $input_year = '';
							if ($orderInfo['due_date'] != '0000-00-00') {
								$input_date = strtotime($orderInfo['due_date']);
								$input_day = date('d',$input_date);
								$input_month = date('m',$input_date);
								$input_year = date('Y',$input_date);
							}
							$invoice_prepayment_payment = 1;
						?>
					<tr>
						<td>
							<?php echo $datas['payment_id'] == 59 ? MODULE_PAYMENT_NOVALNET_TRANS_SLIP_EXPIRY_DATE :MODULE_PAYMENT_NOVALNET_TRANS_DUE_DATE_TITLE; ?>
						</td>
						<td>
							<select style='margin:0% 0% 0% -9%' name='amount_change_day' id='amount_change_day'>
								<?php for ($i = 1; $i <= 31; $i++) { ?>
									<option <?php echo (($input_day == $i)?'selected':''); ?> value="<?php echo (($i < 10)?'0'.$i:$i); ?>"><?php echo (($i < 10)?'0'.$i:$i); ?></option>
								<?php } ?>
							</select>
							<select name='amount_change_month' id='amount_change_month'>
								<?php for($i = 1; $i <= 12; $i++) { ?>
									<option <?php echo (($input_month == $i)?'selected':''); ?> value="<?php echo (($i < 10)?'0'.$i:$i); ?>"><?php echo (($i < 10)?'0'.$i:$i); ?></option>
								<?php } ?>
							</select>
							<select name='amount_change_year' id='amount_change_year'>
								<?php $year_val = date('Y');
									for($i = $year_val; $i <= ($year_val+1); $i++) { ?>
								<option <?php echo (($input_year == $i)?'selected':''); ?> value="<?php echo $i; ?>"><?php echo $i; ?></option>
								<?php } ?>
							</select>
						</td>
						</tr>
						<?php } ?><br><span id="nn_amount_update_error" style="color:red"></span>
						<?php $date_change = $datas['payment_id'] == 59 ? MODULE_PAYMENT_NOVALNET_ORDER_AMT_SLIP_DATE_UPDATE_TEXT : MODULE_PAYMENT_NOVALNET_ORDER_AMT_DATE_UPDATE_TEXT ;
						 echo xtc_draw_hidden_field('invoice_payment_due_date', $orderInfo['due_date']);
							  echo xtc_draw_hidden_field('invoice_payment', $invoice_prepayment_payment);
							  echo xtc_draw_hidden_field('nn_duedate_update', $date_change);
						      echo xtc_draw_hidden_field('nn_order_amount_update', MODULE_PAYMENT_NOVALNET_ORDER_AMT_UPDATE_TEXT);?>
						<tr>
							<td>
							<?php
								echo xtc_draw_input_field('nn_amount_update_confirm',html_entity_decode(MODULE_PAYMENT_NOVALNET_UPDATE_TEXT), 'onclick="return validate_amount_update();" style="float:left"',false,'submit');
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
}
	echo '<script type="text/javascript" src="' . DIR_WS_ADMIN . 'html/assets/javascript/modules/novalnet/novalnet_extension.js"></script>';
	echo xtc_draw_hidden_field('nn_valid_account', MODULE_PAYMENT_NOVALNET_VALID_ACCOUNT_CREDENTIALS_ERROR);
	echo xtc_draw_hidden_field('nn_amount_error', MODULE_PAYMENT_NOVALNET_AMOUNT_ERROR_MESSAGE);
	echo xtc_draw_hidden_field('nn_duedate_feature_error', MODULE_PAYMENT_NOVALNET_VALID_DUEDATE_MESSAGE);
	echo xtc_draw_hidden_field('nn_duedate_error', MODULE_PAYMENT_INVOICE_DUE_DATE_INVAILD);
	echo xtc_draw_hidden_field('nn_refund_amount_confirm', MODULE_PAYMENT_NOVALNET_PAYMENT_REFUND_CONFIRM);	
	echo xtc_draw_hidden_field('nn_zero_amount_book_confirm', MODULE_PAYMENT_NOVALNET_PAYMENT_ZERO_AMOUNT_BOOK_CONFIRM);	
	echo xtc_draw_hidden_field('nn_select_status', MODULE_PAYMENT_NOVALNET_SELECT_STATUS_TEXT);
	echo xtc_draw_hidden_field('nn_subs_cancel', MODULE_PAYMENT_NOVALNET_SUBS_CANCEL_REASON_TITLE);
?>
