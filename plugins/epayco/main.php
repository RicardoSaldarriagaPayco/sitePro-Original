<?php
$txnId = sprintf("%08x", crc32(microtime().'|'.$pluginData->pkey));
?>
<input type="hidden" name="surl" value="<?php echo getBaseUrl().'store-return/Epayco'; ?>" />
<input type="hidden" name="totalAmount" value="<?php echo $pluginData->amount; ?>" />
<input type="hidden" name="subTotalPrice" value="<?php echo $pluginData->amount; ?>" />
<input type="hidden" name="taxPrice" value="0" />
<input type="hidden" name="txnid" value="<?php echo $txnId; ?>" />
<input type="hidden" name="countryCode" value="" />
<input type="hidden" name="invoiceNumber" value="" />
<input type="hidden" name="extra1" value="<?php echo $txnId; ?>" />
<input type="hidden" name="customer_name" value="" />
<input type="hidden" name="customer_email" value="" />
<input type="hidden" name="address1" value="" />