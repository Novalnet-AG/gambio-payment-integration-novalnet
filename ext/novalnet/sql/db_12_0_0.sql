CREATE TABLE IF NOT EXISTS novalnet_transaction_detail (
  id int(10) unsigned AUTO_INCREMENT COMMENT 'Auto increment',
  `date` timestamp DEFAULT CURRENT_TIMESTAMP,
  order_no int(10) unsigned COMMENT 'Order no from shop',
  tid bigint(20) unsigned COMMENT 'Transaction id',
  amount int(10) unsigned COMMENT 'Amount',
  currency char(10) COMMENT 'Currency',
  customer_id int(10) unsigned COMMENT 'Customer no from shop',
  payment_id int(10) unsigned COMMENT 'Payment key',
  payment_type varchar(40) COMMENT 'Payment type',
  test_mode enum('0','1') COMMENT 'Test mode status',
  status varchar(60) COMMENT 'Transaction status',
  payment_details text COMMENT 'Masked account/card details of customer',
  instalment_cycle_details text NULL COMMENT 'Instalment information used in gateways',
  refund_amount int(11) COMMENT 'Refund amount',
  callback_amount int(11) COMMENT 'Callback amount',
  PRIMARY KEY (id),
  KEY tid (tid),
  KEY payment_type (payment_type),
  KEY order_no (order_no)
) COMMENT='Novalnet transaction history';

CREATE TABLE IF NOT EXISTS novalnet_version_detail (
  version varchar(10) COMMENT 'Novalnet payment module current version',
  KEY version (version)
) COMMENT='Novalnet version information';
INSERT INTO novalnet_version_detail VALUES ('12.3.0');

