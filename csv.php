
  public function export($DB, $Common, $twig, $param = '')
  {
    $LogData_arr = parent::loginCheck($DB, $Common, $twig);
    $menu_arr = parent::menu($DB, $Common, $twig, $LogData_arr);

    //驗證權限
    if(!$Common->CheckRole($menu_arr, 'I04', 'V')){
      $Common->ShowMsgReturn('無權限處理', '/admin/index');
    }

    //因為匯出需要 搜尋參數，所以必須由傳過來的參數陣列中獲得
    $param_arry = $Common->ResultArray($param);
    if(is_array($param_arry)){
      
      $page = $param_arry['page'];
      $PageSize = $param_arry['PageSize'];
      $field_sort = $param_arry['field_sort'];
      $sort_type = $param_arry['sort_type'];
      $SearchWhat = $param_arry['SearchWhat'];
      $SearchVal = $param_arry['SearchVal'];
      if($SearchVal != '') $SearchVal = urldecode(str_replace('*', '%', $SearchVal));
      
    }else{
      $page = 1;
      $PageSize = 10;
      $field_sort = 'CreateTime';
      $sort_type = 'asc';
      $SearchWhat = '';
      $SearchVal = '';
    }

    // 開啟資料庫
    $DB->DB_Connection();

    //查詢語法
    $SQL = "";
    $SQL = "SELECT aa.TryEatNum, aa.TryEatOrderNum, aa.TryEatName, aa.TryEatTel, aa.TryEatAddress, ";
    $SQL .= "aa.TryEatQA, aa.TryEatDepiction, aa.CreateTime,aa.Creator, ";
    $SQL .= "bb.DetailNum, bb.ProductName, bb.ProductIndex, bb.ProductFormat, bb.ProductDepiction ";
    $SQL .= "FROM [TryEat] aa ";
    $SQL .= "LEFT JOIN [TryEatDetail] bb ";
    $SQL .= "ON aa.TryEatNum = bb.TryEatNum ";
    if($SearchVal != '') $SQL .= " WHERE charindex('" . $SearchVal . "', aa." . $SearchWhat . ") > 0";
    $SQL .= "ORDER BY aa.TryEatOrderNum";

    //匯出在測一下。
    $stmt = $DB->SQLselect($SQL);
    $rs   = $DB->SQLdataArray($stmt);
    
    if(count($rs) != 0){

      //----- 取得協定和網域並串接 -----

      //取得協定http或https
      $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
      
      //網域
      $domain = $Common->WebUrl;

      //$server_protocol是協定，$domain就是網域
      $file_path = $http_type.$domain;
      //----- /取得協定和網域並串接 -----

      // 設定 CSV 表頭
      header('Content-type: text/x-csv');
      header('Content-Disposition: attachment; filename=滴魚精試吃申請表' . date('Y-m-d-H-i-s') . '.csv');

      //串接匯出內容，表格頭部
      $content = "編號,姓名,電話,地址,問券內容,申請備註,商品名稱,商品圖片,商品規格,商品備註,申請時間\n"; 
      
      foreach ($rs  as $key => $value) {

        $productIndex = $file_path.$value["ProductIndex"];

        // 要輸出的資料
        $content .= "=\"{$value["TryEatOrderNum"]}\",\"{$value['TryEatName']}\",=\"{$value['TryEatTel']}\",\"{$value['TryEatAddress']}\",\"{$value['TryEatQA']}\",\"{$value['TryEatDepiction']}\",";
        $content .= "\"{$value["ProductName"]}\",\"{$productIndex}\",\"{$value['ProductFormat']}\",\"{$value['ProductDepiction']}\",=\"{$value['CreateTime']}\"\n";

      }

      // 將資料轉成 Excel 的 BIG5 編碼
      $content = mb_convert_encoding($content, 'BIG5', 'UTF-8');
      
      // 關閉資料庫
      $DB->DB_Close();

      // 釋放 Rs
      $DB->SQLdataNothing($rs);

      // 輸出 CSV
      echo $content;

    }else{

      // 關閉資料庫
      $DB->DB_Close();
      $Common->ShowMsgReturn('無此資料', '/admin/tryeat/list');
      
    }

  }
