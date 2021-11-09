<?php
set_time_limit(0);
require '../../setting/setup_plug.php';
require '../../setting/setupdb_plug.php';
require "../../login/logincheck.php";
require '../../setting/arraytxt.php';

// http 或是 https判斷
$protocol = ( (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443 ) ? "https://" : "http://";
// ip位置
$host = $_SERVER['HTTP_HOST'];
// 串網址(ex http://192.168.1.10)
$url = $protocol.$host.$setup_upload_path. 'load/';

$id = GetRequest("id");

// $content = "=HYPERLINK("{$link}")";
// var_dump($content);
// $content = "=HYPERLINK(\"".$link."\")";
// $content = str_replace("\"", "\"\"", $content);
// $content = "\"" . $content . "\"";


if($id !== ''){
  // 開啟資料庫
  DB_Connection();
  $sql = "SELECT * FROM activitys WHERE id = ".$id." AND del = 0";
  $stmt = SQLselect($sql);
  $row = sqlsrv_fetch_array($stmt);
  $checkBoxSetting = json_decode($row['checkBoxSetting'], true);
  $content = '';
  $titleArr = array();
  foreach($checkBoxList as $k => $v){
    if($checkBoxSetting[$v] === 1){
        if($k !== '保護勾選'){
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

  // 讀取名單資料表
  $sql = "SELECT * FROM lotterysign WHERE activityid = " .$id. " AND del = 0 ORDER BY id ";
  $stmt = SQLselect($sql);

  while ($row = SQLdataArray($stmt)) {
    $row['uname'] = Regreplace($row['uname']);
    $row['fbook'] = Regreplace($row['fbook']);
    $row['line']  = Regreplace($row['line']);
    $row['ig']    = Regreplace($row['ig']);
    $row['award'] = Regreplace($row['award']);
    $regTime = '' . $row['createTime']->format('Y-m-d H:i:s');

    foreach($row as $k => $v){

      if( in_array($k, $titleArr, true) ){
        $str = ($v === '' || is_null($v) )? '':$v;
        if($k === 'cel' || $k === 'tel' || $k === 'orders' || $k === 'invoice' || $k === 'vip'){
          $str = "\t{$str}";
        }
        elseif($k === 'uploadImg'){
          $link = $url.$str;
          $cell = "=HYPERLINK(\"".$link."\", \"".$str."\")";
          $cell = str_replace("\"", "\"\"", $cell);
          $cell = "\"" . $cell . "\"";
          $str = $cell;

          // $str = "<a href=\"{$link}\">{$link}</a>"; //確認不行
          // $str = "=HYPERLINK(".$link.")";
          // $str = "=HYPERLINK(\"" .$url . $str ."\")";
          //"=HYPERLINK(""http://www.google.com"")"
        }
        $content .= "{$str},";
      }
    }
    $content .= "{$regTime}\r\n";
  }

  // 將資料轉成 Excel 的 BIG5 編碼
  // $content = mb_convert_encoding($content, 'BIG5', 'UTF-8');
  echo "\xEF\xBB\xBF";

  // 釋放 Rs
  SQLdataNothing($stmt);

  // 輸出 CSV
  echo $content;

  // 關閉資料庫
  DB_Close();

}


?>
