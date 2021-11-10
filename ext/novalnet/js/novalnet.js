
 if (window.addEventListener) { // For all major browsers, except IE 8 and earlier
		window.addEventListener("load", novalnet_load);
	} else if (window.attachEvent) { // For IE 8 and earlier versions
		window.attachEvent("onload", novalnet_load);
	}

function novalnet_load(){
	var number = getUrlVars()["module"];
	var value = number.toUpperCase();
if(jQuery('#gx_configurations').val() == '1'){
   //Restricts the tags in Notification for the buyer field  
   jQuery('input[name="configuration[configuration/MODULE_PAYMENT_'+ value +'_ENDCUSTOMER_INFO]"]').keyup(function(){
	   this.value = this.value.replace(/<(.|\n)*?>/g,'');
    });
   //allows numeric only in Set a limit for on-hold transaction field in backend
   jQuery('input[name="configuration[configuration/MODULE_PAYMENT_'+ value +'_MANUAL_CHECK_LIMIT]"]').keyup(function(){
       this.value = this.value.replace(/[^0-9]/g,'');
    });
   //allows numeric only in Minimum value of goods field in backend
   jQuery('input[name="configuration[configuration/MODULE_PAYMENT_'+ value +'_VISIBILITY_BY_AMOUNT]"]').keyup(function(){
       this.value = this.value.replace(/[^0-9]/g,'');
    });
   //allows gurantee only above 9,99 amount
   jQuery('input[name="configuration[configuration/MODULE_PAYMENT_'+ value +'_GUARANTEE_MINIMUM_ORDER_AMOUNT]"]').blur(function(){
	  if ($(this).val()  < 999){
		  $(this).val('');
      }
    });
}else{
	 //Restricts the tags in Notification for the buyer field  
   jQuery('input[name="configuration[MODULE_PAYMENT_'+ value +'_ENDCUSTOMER_INFO]"]').keyup(function(){
	   this.value = this.value.replace(/<(.|\n)*?>/g,'');
    });
   //allows numeric only in Set a limit for on-hold transaction field in backend
   jQuery('input[name="configuration[MODULE_PAYMENT_'+ value +'_MANUAL_CHECK_LIMIT]"]').keyup(function(){
       this.value = this.value.replace(/[^0-9]/g,'');
    });
   //allows numeric only in Minimum value of goods field in backend
   jQuery('input[name="configuration[MODULE_PAYMENT_'+ value +'_VISIBILITY_BY_AMOUNT]"]').keyup(function(){
       this.value = this.value.replace(/[^0-9]/g,'');
    });
    //allows gurantee only above 9,99 amount
   jQuery('input[name="configuration[MODULE_PAYMENT_'+ value +'_GUARANTEE_MINIMUM_ORDER_AMOUNT]"]').blur(function(){
	  if ($(this).val()  < 999){
		  $(this).val('');
      }
    });
}
    
    if(jQuery('#gx_configurations').val() == '1'){
	var cc_check      = $('input[name="configuration[configuration/MODULE_PAYMENT_NOVALNET_CC_MANUAL_CHECK_LIMIT]');
	var invoice_check = $('input[name="configuration[configuration/MODULE_PAYMENT_NOVALNET_INVOICE_MANUAL_CHECK_LIMIT]');
	var sepa_check    = $('input[name="configuration[configuration/MODULE_PAYMENT_NOVALNET_SEPA_MANUAL_CHECK_LIMIT]'); 
	var paypal_check  = $('input[name="configuration[configuration/MODULE_PAYMENT_NOVALNET_PAYPAL_MANUAL_CHECK_LIMIT]');
	
	jQuery('#cc_auth').val()      == 'false' ? cc_check.hide() : '' ;
	jQuery('#invoice_auth').val() == 'false' ? invoice_check.hide() : '' ;
	jQuery('#sepa_auth').val()    == 'false' ? sepa_check.hide() : '' ;
	jQuery('#paypal_auth').val()  == 'false' ? paypal_check.hide() : '' ;
	
   }else{
    var invoice_check = $('input[name="configuration[MODULE_PAYMENT_NOVALNET_INVOICE_MANUAL_CHECK_LIMIT]');
	var invoice_auth  = $("input[name='configuration[MODULE_PAYMENT_NOVALNET_INVOICE_AUTHENTICATE]']:checked");
	var sepa_check    = $('input[name="configuration[MODULE_PAYMENT_NOVALNET_SEPA_MANUAL_CHECK_LIMIT]'); 
	var sepa_auth     = $("input[name='configuration[MODULE_PAYMENT_NOVALNET_SEPA_AUTHENTICATE]']:checked");
	var cc_check      = $('input[name="configuration[MODULE_PAYMENT_NOVALNET_CC_MANUAL_CHECK_LIMIT]');
	var cc_auth       = $("input[name='configuration[MODULE_PAYMENT_NOVALNET_CC_AUTHENTICATE]']:checked");
	var paypal_check  = $('input[name="configuration[MODULE_PAYMENT_NOVALNET_PAYPAL_MANUAL_CHECK_LIMIT]');
	var paypal_auth   = $("input[name='configuration[MODULE_PAYMENT_NOVALNET_PAYPAL_AUTHENTICATE]']:checked");

		 ( invoice_auth.val()  != 'authorize' ) ? invoice_check.hide() : ''; 
		 ( sepa_auth.val()     != 'authorize' ) ? sepa_check.hide()    : '' ;
		 ( cc_auth.val()       != 'authorize' ) ? cc_check.hide()      : '';
		 ( paypal_auth.val()   != 'authorize' )  ? paypal_check.hide()  : '';
	 }
		 
$(document).ready(function(){
	
	if(jQuery('#gx_configurations').val() == '1'){
		document.onload = setTimeout(function () {
      $('div[title="MODULE_PAYMENT_'+ value +'_AUTHENTICATE_TITLE"]').click(function(){
		var value_auth       = $('div[title="MODULE_PAYMENT_'+value+'_AUTHENTICATE_TITLE"]').attr('class');
		var value_check      = $('input[name="configuration[configuration/MODULE_PAYMENT_'+value+'_MANUAL_CHECK_LIMIT]');
	    value_auth   == 'switcher checked'    ?  value_check.hide()  :    value_check.show()  ;
	
});
  //for handling onclick and zero amount field
   $('div[title="MODULE_PAYMENT_'+ value +'_SHOP_TYPE_CLICK_TITLE"]').click(function(){
	   var click       = $('div[title="MODULE_PAYMENT_'+value+'_SHOP_TYPE_CLICK_TITLE"]').attr('class');
	   var zero        = $('div[title="MODULE_PAYMENT_'+ value +'_ZERO_AMOUNT_TITLE"]').attr('class');
	   if(click != 'switcher checked'){
		   $('div[title="MODULE_PAYMENT_'+ value +'_ZERO_AMOUNT_TITLE"]').removeClass( "checked" );
		   $('input[name="configuration[configuration/MODULE_PAYMENT_'+ value +'_ZERO_AMOUNT]"]').val( "false" );
		 
	   }
   });
    $('div[title="MODULE_PAYMENT_'+ value +'_ZERO_AMOUNT_TITLE"]').click(function(){
	   var click       = $('div[title="MODULE_PAYMENT_'+ value +'_SHOP_TYPE_CLICK_TITLE"]').attr('class');
	   var zero        = $('div[title="MODULE_PAYMENT_'+ value +'_ZERO_AMOUNT_TITLE"]').attr('class');
	   if(zero != 'switcher checked'){
		   $('div[title="MODULE_PAYMENT_'+ value +'_SHOP_TYPE_CLICK_TITLE"]').removeClass( "checked" );
		   $('input[name="configuration[configuration/MODULE_PAYMENT_'+ value +'_SHOP_TYPE_CLICK]"]').val('false');
		  
	   }
   });
   //fraud module 
    $('div[title="MODULE_PAYMENT_'+ value +'_FRAUDMODULE_CALLBACK_TITLE"]').click(function(){
	   var call       = $('div[title="MODULE_PAYMENT_'+ value +'_FRAUDMODULE_CALLBACK_TITLE"]').attr('class');
	   var sms        = $('div[title="MODULE_PAYMENT_'+ value +'_FRAUDMODULE_SMS_TITLE"]').attr('class');
	   if(call != 'switcher checked'){
		   $('div[title="MODULE_PAYMENT_'+ value +'_FRAUDMODULE_SMS_TITLE"]').removeClass( "checked" );
		   $('input[name="configuration[configuration/MODULE_PAYMENT_'+ value +'_FRAUDMODULE_SMS]"]').val('false');
	   }
   });
   $('div[title="MODULE_PAYMENT_'+ value +'_FRAUDMODULE_SMS_TITLE"]').click(function(){
	   var call       = $('div[title="MODULE_PAYMENT_'+ value +'_FRAUDMODULE_CALLBACK_TITLE"]').attr('class');
	   var sms        = $('div[title="MODULE_PAYMENT_'+ value +'_FRAUDMODULE_SMS_TITLE"]').attr('class');
	   if(sms != 'switcher checked'){
		   $('div[title="MODULE_PAYMENT_'+ value +'_FRAUDMODULE_CALLBACK_TITLE"]').removeClass( "checked" );
		   $('input[name="configuration[configuration/MODULE_PAYMENT_'+ value +'_FRAUDMODULE_CALLBACK]"]').val('false');
	   }
   });
   }, 500);
}
     $("input[type='radio']").click(function(){
    var invoice_check = $('input[name="configuration[MODULE_PAYMENT_NOVALNET_INVOICE_MANUAL_CHECK_LIMIT]');
	var invoice_auth  = $("input[name='configuration[MODULE_PAYMENT_NOVALNET_INVOICE_AUTHENTICATE]']:checked");
	var sepa_check    = $('input[name="configuration[MODULE_PAYMENT_NOVALNET_SEPA_MANUAL_CHECK_LIMIT]'); 
	var sepa_auth     = $("input[name='configuration[MODULE_PAYMENT_NOVALNET_SEPA_AUTHENTICATE]']:checked");
	var cc_check      = $('input[name="configuration[MODULE_PAYMENT_NOVALNET_CC_MANUAL_CHECK_LIMIT]');
	var cc_auth       = $("input[name='configuration[MODULE_PAYMENT_NOVALNET_CC_AUTHENTICATE]']:checked");
	var paypal_check  = $('input[name="configuration[MODULE_PAYMENT_NOVALNET_PAYPAL_MANUAL_CHECK_LIMIT]');
	var paypal_auth   = $("input[name='configuration[MODULE_PAYMENT_NOVALNET_PAYPAL_AUTHENTICATE]']:checked");
    
		invoice_auth.val() ==  'authorize'  ?  invoice_check.show()  : (invoice_auth.val() == 'capture'  ?   invoice_check.hide() : '') ;
		 sepa_auth.val()   ==  'authorize'  ?  sepa_check.show() :  (sepa_auth.val()   == 'capture'  ?   sepa_check.hide() : '') ; 
		 cc_auth.val()     ==  'authorize'  ?  cc_check.show() : (cc_auth.val()     == 'capture'  ?   cc_check.hide() : '') ;
      paypal_auth.val()    ==  'authorize'  ?  paypal_check.show() : (paypal_auth.val()    == 'capture'  ?   paypal_check.hide() : '') ;
		
        });
        
    });
    
}

function getUrlVars() {
    var vars = {};
    var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
        vars[key] = value;
    });
    return vars;
}
 









