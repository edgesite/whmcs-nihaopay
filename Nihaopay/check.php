<?php
require_once(__DIR__ . '/../../../init.php');
use Illuminate\Database\Capsule\Manager as DB;

if (isset($_SESSION['uid'])) {
  $uid = $_SESSION['uid'];
  $invoice = DB::table('tblinvoices')
    ->select('tblinvoices.status')
    ->where('tblinvoices.userid', '=', $uid)
    ->where('tblinvoices.id', '=', $_GET['id'])
    ->first();
  if (isset($invoice)) {
    $status = 'unpaid';
    if ($invoice && $invoice->status == 'Paid') {
      $status = 'paid';
    }
    header('content-type: application/json');
    echo json_encode(['status' => $status]);
    die;
  }
}
http_response_code(404);
