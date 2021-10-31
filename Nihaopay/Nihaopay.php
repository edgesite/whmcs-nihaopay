<?php
use Illuminate\Database\Capsule\Manager as DB;
require_once __DIR__.'/config.php';

function nihaopay_sign($params, $token)
{
  ksort($params);
  $str_to_sign = "";
  foreach ($params as $key => $val) {
    if ($key == 'verify_sign') continue;
    if ($val == null || $val == '' || $val == 'null') continue;
    $str_to_sign .= sprintf("%s=%s&", $key, $val);
  }
  return md5($str_to_sign . strtolower(md5($token)));
}

function httppost($url, $data, $headers) {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
  $response = curl_exec($ch);
  $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  if ($httpCode != 200) {
    return false;
  }
  return $response;
}

function isMobile() {
  return preg_match("/(alipay|MicroMessenger|TBS|NetType|android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $_SERVER["HTTP_USER_AGENT"]);
}

class NihaopayGateway
{
  static function getMetaData($gateway) {
    return array(
      'DisplayName' => ucfirst($gateway) . ' by Nihaopay',
      'APIVersion' => '1.1',
      'DisableLocalCredtCardInput' => true,
      'TokenisedStorage' => false,
    );
  }

  static function getConfig($gateway)
  {
    return array(
      'FriendlyName' => array(
        'Type' => 'System',
        'Value' => ucfirst($gateway),
      ),
      'testMode' => array(
        'FriendlyName' => 'Test Mode',
        'Type' => 'yesno',
        'Description' => 'Tick to enable test mode',
      ),
    );
  }

  static function genLink($gateway, $qrPay, $params)
  {
    if (strpos($_SERVER['REQUEST_URI'], 'viewinvoice.php') == -1) return 'Redirecting...';
    $url = 'https://api.nihaopay.com/v1.2/transactions/' . ($qrPay ? 'qrcode' : 'securepay');
    // support gateway change
    $reference = $params['invoiceid'].'-'.$gateway.($qrPay?'qr':'');
    $data = array(
      'vendor' => $gateway,
      'reference' => $reference,
      'callback_url' => $params['systemurl'].'modules/gateways/Nihaopay/callback.php?act=callback&id='.$params['invoiceid'],
      'ipn_url' => $params['systemurl'].'modules/gateways/Nihaopay/callback.php?act=ipn&id='.$params['invoiceid'],
    );
    if ($params['currency'] == 'CNY') {
      $data['rmb_amount'] = ceil($params['amount']*100);
      $data['currency'] = 'USD';
    } else {
      $data['amount'] = ceil($params['amount']*100);
      $data['currency'] = $params['currency'];
    }
    if (!$qrPay) {
      $data['terminal'] = isMobile() ? 'WAP' : 'ONLINE';
    }
    $resp = httppost($url, $data, array("Authorization: Bearer ".NIHAOPAY_TOKEN));
    if (!$resp) {
      return 'The gateway is currently unavailable to process the payment.';
    }

    if (!$qrPay) {
      $match = array();
      preg_match('/<form(.+)<\/form>/', $resp, $match);
      $gateway_namemap = [
        'alipay' => '支付宝',
        'unionpay' => '银联云闪付',
      ];
      return "{$match[0]}
<button type='button' class='btn btn-info btn-block' onclick='document.forms.pay_form.submit()'>使用{$gateway_namemap[$gateway]}支付</button>";
    }
    $resp = json_decode($resp, true);
    // inline qrcode.js to improve performance
    $qrcodejs = file_get_contents(realpath(__DIR__).'/static/qrcode.min.js');
    return "
<div style='width: 240px; height: 240px; border: 1px solid #aaa; padding: 10px; margin: 0 auto'>
  <div id='nihaopay_qr'></div>
</div>
<script>$qrcodejs;
(function(){
  var el = document.getElementById('nihaopay_qr');
  new QRCode(el, {
    text: '{$resp['code_url']}',
    width: 220,
    height: 220,
    colorDark : '#000000',
    colorLight : '#ffffff',
    correctLevel : QRCode.CorrectLevel.H,
  });
  setTimeout(function() {
    var int = setInterval(function() {
      var xhr = new XMLHttpRequest();
      xhr.open('GET', '{$params['systemurl']}modules/gateways/Nihaopay/check.php?id={$params['invoiceid']}');
      xhr.onload = function(){
        if (JSON.parse(xhr.responseText)['status'] === 'paid') {
          location.reload()
          clearInterval(int);
        }
      }
      xhr.withCredentials = true;
      xhr.send();
    }, 1000);
  }, 5000);
})();
</script>";
  }
}
