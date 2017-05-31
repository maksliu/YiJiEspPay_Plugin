<?php
    # import application top
    require('includes/application_top.php');
    require('includes/modules/payment/yjfpayc/common_functions.php');

    # read history
    
    // file_put_contents('E:/log/hkzencart/getdata.log', 'getdata:'.json_encode($_GET,true) . 'order_id:' . $orderID);
    if($_GET['status'] == 'success' || $_GET['status'] == 'authorizing' || $_GET['status'] == 'processing') {
        echo "pay success,please wait.";
        return;
    } elseif ($_GET['status'] == 'success') {
        echo "Order pay success";
        return;
    }
    
    $orderID = intval($_GET['order_id']);
    // file_put_contents('E:/log/hkzencart/isdo.log', 'is do' . $orderID);
    
    $history = read_pay_history($orderID);

    if ($history->EOF) return;

    $options = json_decode(base64_decode($history->fields['add_data']), true);
    $orderNo = date('YmdHis') . rand(10000, 999999);
    $gateway = array(
        'service'   => 'cardAcquiringCashierPay',
        'partnerId' => MODULE_PAYMENT_YJFPAYC_PARTNER_ID,
        'orderNo'   => $orderNo,
        'returnUrl' => zen_href_link('yjfpayc.php', '', 'SSL', false, false, true),
        'notifyUrl' => zen_href_link('yjfpayc_handler.php', '', 'SSL', false, false, true)
    );

    # update order no
    update_pay_history_order_no($orderID, $orderNo);

    function read_pay_history($order_id) {
        $sql
            = <<<EOF
            SELECT
                order_id,order_no,status,
                pay_total,pay_date,pay_status,pay_message,
                refund_date,refund_total,refund_reason,
                auth_date,auth_accept,auth_reason,auth_message,
                add_date,add_data
            FROM
                yjfpayc_history
            WHERE
                order_id = :order_id AND status = 0
EOF;
        global $db;

        # bind var and return value
        $sql = $db->bindVars($sql, ':order_id', $order_id, 'integer');
        return $db->Execute($sql);
    }

    function update_pay_history_order_no($orderID, $orderNo) {
        global $db;

        $update_array = array(
            array('fieldName' => 'order_no', 'type' => 'string', 'value' => $orderNo)
        );

        $db->perform(TABLE_YJFPAYC_HISTORY, $update_array, 'update', 'order_id=' . intval($orderID));
    }

    # set options
    $allOptions = array_merge($options, $gateway);

    $allOptions['sign'] = yjfpayc_signature($allOptions);

    if (MODULE_PAYMENT_YJFPAYC_GATEWAY_URL == 'True') {
        // $gatewayURL = YJFPAYC_PRODUCT_URL;
        $gatewayURL = YJFPAYC_DEBUG_URL;
    } else {
        $gatewayURL = YJFPAYC_PRODUCT_URL;
        // $gatewayURL = YJFPAYC_DEBUG_URL;
    }
?>
<html>
<body>
<form id="submitPayForm" action="<?php echo $gatewayURL; ?>" method="POST">
    <?php foreach ($allOptions as $name => $value) { ?>
        <input type="hidden" name="<?php echo $name; ?>" value='<?php echo $value; ?>'/>
    <?php } ?>
</form>
<script type="text/javascript">
    var submitPayForm = document.getElementById('submitPayForm');
    submitPayForm.submit();
</script>
</body>
</html>