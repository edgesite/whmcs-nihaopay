<?php
require_once realpath(dirname(__FILE__)) . '/Nihaopay/Nihaopay.php';

function NihaopayUnionpay_MetaData()
{
  return NihaopayGateway::getMetaData('Unionpay');
}

function NihaopayUnionpay_config()
{
  return NihaopayGateway::getConfig('Unionpay');
}

function NihaopayUnionpay_link($params)
{
  return NihaopayGateway::genLink('unionpay', false, $params);
}
