CREATE TABLE IF NOT EXISTS novalnet_transaction_details (
	id INT(30) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	`date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,						
	order_no INT(10) NOT NULL,
	tid VARCHAR(30) NOT NULL,
	amount INT(10) NOT NULL,
	customer_id INT(10) NOT NULL,
	payment_type VARCHAR(50),
	test_mode VARCHAR(50),
	status text(50),
	payment_details text(100),
	refund_amount int(11),
	instalment_cycle_details text(100),
	callback_amount int(11) unsigned DEFAULT '0'
)COMMENT='Novalnet transaction history';

