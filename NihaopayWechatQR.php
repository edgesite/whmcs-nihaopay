<?php
require_once realpath(dirname(__FILE__)) . '/Nihaopay/Nihaopay.php';

function NihaopayWechatQR_MetaData()
{
  return NihaopayGateway::getMetaData('Wechat QR');
}

function NihaopayWechatQR_config()
{
  return NihaopayGateway::getConfig('wechat QR');
}

function NihaopayWechatQR_link($params)
{
  return NihaopayGateway::genLink('wechatpay', true, $params);
}
