<?php
set_time_limit(0);
require '../../setting/setup_plug.php';
require '../../setting/setupdb_plug.php';
require "../../login/logincheck.php";
require '../../setting/arraytxt.php';

$id = GetRequest("id");

// var_dump($_COOKIE["checklogin"]);die();

//checkBox生成
// $checkBoxList = array(
//   '姓名'     => 'uname',
//   '生日'     => 'birthday',
//   '性別'     => 'sex',
//   '手機'     => 'cel',
//   '市話'     => 'tel',
//   '地址'     => 'address',
//   'Email'    => 'email',
//   '好康卡號'  => 'vip',
//   '訂單編號'  => 'orders',
//   '發票號碼'  => 'invoice',
//   '圖片上傳'  => 'uploadImg',
//   'FB名稱'    => 'fbook',
//   'LINE名稱'  => 'line',
//   'IG帳號'    => 'ig',
//   '中獎品項'  => 'award',
//   '保護勾選'  => 'protection'
// );

// $checkBoxLimitList = array(
//   '手機'     => 'cel',
//   '好康卡號' => 'vip'
// );

DB_Connection();


if($id !== ''){
  // 開啟資料庫
  $sql = "SELECT * FROM activitys WHERE id = ".$id." AND del = 0";
  $stmt = SQLselect($sql);
  $row = sqlsrv_fetch_array($stmt);
  $checkBoxSetting = json_decode($row['checkBoxSetting'], true);
  $content = '';
  $titleArr = array();
  foreach($checkBoxList as $k => $v){
    if($checkBoxSetting[$v] === 1){
        if($k !== '保護勾選' && $k !== '圖片上傳'){
          $content .= $k .',';
          $titleArr[] = $v;
        }
    }
  }

  $content .= "登錄時間\n";

  // 設定 CSV 表頭
  ob_clean();
  header('Content-type: text/x-csv');
  header('Content-Disposition: attachment; filename=Carrefour_PS5_' . date('Y-m-d-H-i-s') . '.csv');
  // $content = "姓名|生日|性別|手機|市話|區號|縣市|鄉鎮|地址|Email|好康卡號|訂單編號|發票號碼|FB名稱|LINE名稱|IG帳號|登錄時間\n";

  // 讀取名單資料表
  $sql = "SELECT * FROM lotterysign WHERE activityid = " .$id. " AND del = 0 ORDER BY id ";
  // $stmt = SQLselect($sql);
  // $row = sqlsrv_fetch_array($stmt);

  $stmt = SQLselect($sql);
  while ($row = SQLdataArray($stmt)) {
    $row['uname'] = Regreplace($row['uname']);
    $row['fbook'] = Regreplace($row['fbook']);
    $row['line']  = Regreplace($row['line']);
    $row['ig']    = Regreplace($row['ig']);
    $row['award'] = Regreplace($row['award']);
    $regTime = '' . $row['createTime']->format('Y-m-d H:i:s');
    
    if($row['uname'] !== ''){
      $content .= "{$row['uname']}|";
    }
    if($row['birthday'] !== ''){
      $content .= "{$row['birthday']}|";
    }
    if($row['sex'] !== ''){
      $content .= "{$row['sex']}|";
    }
    if($row['cel'] !== ''){
      $content .= "{$row['cel']}|";
    }
    if($row['tel'] !== ''){
      $content .= "{$row['tel']}|";
    }
    if($row['zip'] !== ''){
      $content .= "{$row['zip']}|";
    }
    if($row['city'] !== ''){
      $content .= "{$row['city']}|";
    }
    if($row['town'] !== ''){
      $content .= "{$row['town']}|";
    }
    if($row['address'] !== ''){
      $content .= "{$row['address']}|";
    }
    if($row['email'] !== ''){
      $content .= "{$row['email']}|";
    }
    if($row['vip'] !== ''){
      $content .= "{$row['vip']}|";
    }
    if($row['orders'] !== ''){
      $content .= "{$row['orders']}|";
    }
    if($row['invoice'] !== ''){
      $content .= "{$row['invoice']}|";
    }
    if($row['fbook'] !== ''){
      $content .= "{$row['fbook']}|";
    }
    if($row['line'] !== ''){
      $content .= "{$row['line']}|";
    }
    if($row['ig'] !== ''){
      $content .= "{$row['ig']}|";
    }
    if($row['award'] !== ''){
      $content .= "{$row['award']}|";
    }

    $content .= "{$regTime}\r\n";

  }
  // 將資料轉成 Excel 的 BIG5 編碼
  // $content = mb_convert_encoding($content, 'BIG5', 'UTF-8');
  // echo "\xEF\xBB\xBF";

  // 釋放 Rs
  SQLdataNothing($stmt);

  // 輸出 CSV
  echo $content;

  // 關閉資料庫
}
DB_Close();

?>
