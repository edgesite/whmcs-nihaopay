<?php
require_once(__DIR__ . '/../../../init.php');
require_once(__DIR__ . '/../../../includes/gatewayfunctions.php');
require_once(__DIR__ . '/../../../includes/invoicefunctions.php');
require_once(__DIR__ . '/Nihaopay.php');

function sign($params, $token) {
  ksort($params);
  $sign_str = [];
  foreach ($params as $key => $val) {
    if ($key == 'act' || $key == 'verify_sign' || $val == null || $val == '' || $val == 'null') continue;
    array_push($sign_str, "$key=$val");
  }
  $sign_str = implode('&', $sign_str);
  return md5("$sign_str&" . md5($token));
}

if(!isset($_REQUEST['verify_sign']) || $_REQUEST['verify_sign'] != sign($_REQUEST, NIHAOPAY_TOKEN)) {
  http_response_code(400);
  die;
}

$success = $_REQUEST["status"] == 'success';
$transactionId = $_REQUEST["id"];
$amount = $_REQUEST["amount"];

$reference = $_REQUEST['reference'];
$gw = substr($reference, strpos($reference, '-')+1);
$invoiceId = substr($reference, 0, strpos($reference, '-'));

switch ($gw) {
  case 'alipay': $gw = 'NihaopayAlipay'; break;
  case 'wechatpay': $gw = 'NihaopayWechat'; break;
  case 'wechatpayqr': $gw = 'NihaopayWechatQR'; break;
  case 'unionpay': $gw = 'NihaopayUnionpay'; break;
  case 'unionpayqr': $gw = 'NihaopayUnionpayQR'; break;
  default:
    http_response_code(400);
    die;
}

$invoiceId = checkCbInvoiceID($invoiceId, $gw);
checkCbTransID($transactionId);
logTransaction($gw, $_REQUEST, $success ? 'Success' : 'Failure');

if ($success) {
  addInvoicePayment($invoiceId, $transactionId, 0, 0, $gw);
}

if ($_REQUEST['act'] == 'ipn') {
  echo 'ok';
} else {
  header('Location: /viewinvoice.php?id='.$invoiceId);
}
