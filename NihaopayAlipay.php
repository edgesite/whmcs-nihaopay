<?php
require_once realpath(dirname(__FILE__)) . '/Nihaopay/Nihaopay.php';

function NihaopayAlipay_MetaData()
{
  return NihaopayGateway::getMetaData('Alipay');
}

function NihaopayAlipay_config()
{
  return NihaopayGateway::getConfig('Alipay');
}

function NihaopayAlipay_link($params)
{
  return NihaopayGateway::genLink('alipay', false, $params);
}
