



```php

public function exportInsert(Request $request, Response $response, array $args): Response
{
    $this->authorization = $request->getAttribute(AUTHORIZATION_NAME);
    if( !Auth::checkRole($this->authorization, "N") ){
        return self::showMessage($response, "沒有權限");
    }

    $routeContext = RouteContext::fromRequest($request);
    $routeParser = $routeContext->getRouteParser();
    $funcName = $this->getFunctionName($request);

    // Get all POST parameters
    $params = (array)$request->getParsedBody();

    // 區域不符合規格
    $area = array('無選擇' ,'全區', '總公司', '北區', '中區', '南區', '北摩', '中摩', '南摩');
    if( !in_array($params['MF_Area'], $area) ){
        return self::showMessage($response, "匯出失敗: 區域輸入錯誤");
    }

    // 發布地區 字串轉代號 全區不存
    $releaseAreaArry = array(
        '全區' => '0',
        '總公司' => '1',
        '北區' => '2',
        '中區' => '3',
        '南區' => '4',
        '北摩' => '5',
        '中摩' => '6',
        '南摩' => '7'
    );

    // 取 地區 保修廠編號 保修廠名稱 做成陣列
    $query = array();
    foreach($params as $k => $v){
        if( $k !== 'csrf_name' && $k !== 'csrf_value'){
            // 地區字串轉代號
            if($k === 'MF_Area'){
                // 無選擇跳出
                if($v === '無選擇'){ continue; }
                if($v === '全區'){
                    // 將區域陣列全跑一次
                    // 因為下面query用OR 所以 選全區寫0也沒關係
                    foreach($releaseAreaArry as $key => $val){
                        $query[] = "{$k} = '{$val}'";
                    }
                }
                $v = $releaseAreaArry[$v];
            }
            elseif($k == 'MF_Num'){
                $k = 'm.MF_Num';
            }
            $query[] = "{$k} = '{$v}'";
        }
    }

    // 陣列 用 OR 串成字串
    $queryStr = implode(" OR ", $query);
    $model = $this->getCmsModel($request);
    $exportData = $model->requirementGetRowData($queryStr);

    if( count($exportData) === 0 ){
        return self::showMessage($response, "無保修廠符合匯出條件");
    }

    foreach($exportData as $key => $val){
        $row = $model->getEventRecords($exportData[$key]['MF_Num']);
        $exportData[$key]['eventRecordsForEventCode'] = ( count($row) > 0 )? $row[0]['counts'] : '0';
    }

    // 匯出時設定前期合約資料之以下欄位資料為空
    $arrTag = array();
    foreach($exportData as $key => $val){

        foreach($val as $item => $t){ 
            $t = ( is_null($t) )? '' : $t;
            if($item === 'UsablePoints' || $item === 'Usable_DF' || $item === 'EventCode' || $item === 'eventRECode' || $item === 'eventRecordsForEventCode'){
                if(in_array($val['MF_Num'], $arrTag)){
                    $t = '';
                }
            }
            $exportData[$key][$item] = $t;
        }
        array_push($arrTag, $val['MF_Num']);
    }

    // 取 組成csv要的陣列的欄位
    $needsArray = array(
        'MF_Num',
        'MF_Pwd',
        'ContactP',
        'MF_Name',
        'GUI_Num',
        'MF_Cel',
        'MF_Tel',
        'MF_Fax',
        'MF_Address',
        'MF_Class',
        'MF_Area',
        'SalesP',
        'ContractClass',
        'StartTime',
        'EndTime',
        'OilAmount',
        'OilReach',
        'OilComp',
        'UnOilAmount',
        'UnOilReach',
        'UnOilComp',
        'ContractReach',
        'TotalAmount',
        'Comp',
        'ContractStatu',
        'UsablePoints',
        'Usable_DF',
        'EventCode',
        'eventRecordsForEventCode',
        'eventRECode'
    );

    // csv filename
    $csvFileName = date('YmdHis') ."".(int)(microtime(true)*1000) . ".csv";

    // response 的表頭
    $response = $response->withHeader('Content-Type', 'text/x-csv');
    $response = $response->withHeader('Content-Disposition', 'attachment; filename="'.$csvFileName.'"')->withStatus(200);
    $content = "客戶編號,保修廠密碼,聯絡人,公司名稱,統一編號,手機號碼,市內電話,傳真號碼,地址,客戶類別,區域,負責業務,合約類別,";
    $content .= "合約月份(起),合約月份(訖),LM油品目前金額,LM油品達標金額,LM油品完成度,LM非油品目前金額,LM非油品達標金額,LM非油品完成度,";
    $content .= "合約達標金額,目前總金額,完成度,合約狀態,可使用點數,可抽獎次數,活動代碼,最新一檔抽獎次數,累積抽獎次數". PHP_EOL;
    // $content = mb_convert_encoding($content , 'BIG5', 'UTF-8');

    foreach($exportData as $k => $v){
        foreach($needsArray as $need){
            $str =  '';
            $str =  $v[$need];
            if($str !== ''){
                if($need === 'GUI_Num' || $need === 'MF_Cel' || $need === 'MF_Tel' || $need === 'MF_Fax'){

                    $str = '="' .$str. '"';
                }
                if($need === 'MF_Area'){
                    // 使用 value值 找到對應第一個結果的 key值
                    $str = array_search($str, $releaseAreaArry);
                }
                if($need === 'OilComp' || $need === 'UnOilComp' || $need === 'Comp'){
                    $str = $str . '%';
                }
                if($need === 'MF_Pwd'){
                    $str = '';
                }
            }
            // $content .= mb_convert_encoding($str , 'BIG5', 'UTF-8');
            $content .= trim($str);
            $content .= ',';
        }
        $content = mb_substr($content, 0 ,-1);

        $content .= PHP_EOL; //用引文逗號分開 
    }

    $content = "\xEF\xBB\xBF" . $content;
    $response->getBody()->write($content);
    return $response;
}


```



