
```php
public function importInsert(Request $request, Response $response, array $args): Response
{
    // function 名稱
    $routeContext = RouteContext::fromRequest($request);
    $routeParser = $routeContext->getRouteParser();
    $funcName = $this->getFunctionName($request);

    // get file
    $uploadedFiles = $request->getUploadedFiles();
    if(count($uploadedFiles) !== 1){
        return self::showMessage($response, '檔案上傳失敗: 檔案不存在', $routeParser->urlFor("M02"));
    }

    // 檔
    $file = $uploadedFiles['file'];

    // 附檔名 pathinfo+條件 將檔名轉成副檔名
    $extension = pathinfo($file->getClientFilename(), PATHINFO_EXTENSION);
    if(!in_array($extension, array('csv'))){
        return self::showMessage($response, '檔案上傳失敗: 檔案格式不正確請使用csv', $routeParser->urlFor("M02"));
    }

    // 檔案大小超過上限
    if($file->getError() === UPLOAD_ERR_INI_SIZE ){
        // 返回原畫面 不帶訊息
        return self::showMessage($response, '檔案上傳失敗: 檔案過大', $routeParser->urlFor("M02"));
    }

    // 檔案上傳失敗
    if($file->getError() !== UPLOAD_ERR_OK ){
        // 返回原畫面 不帶訊息
        return self::showMessage($response, '檔案上傳失敗: 返回上傳畫面', $routeParser->urlFor("M02"));
    }

    // 取model
    $model = $this->getCmsModel($request);

    // 取得上傳臨時目錄
    $tmp = $file->getFilePath();

    $dataline = array();
    if(file_exists($tmp)){
        $tmpFile = fopen($tmp, 'rb');
        // 跳過Bom
        // fseek($tmpFile, 3);
        $filecount = 0;
        while (!feof($tmpFile)){
            $data = trim(fgets($tmpFile));
            $data = iconv(mb_detect_encoding($data), 'UTF-8', $data);

            // 判斷第一行
            if($filecount === 0){

                /* 是否帶UTF8 with BOM
                if (substr($data, 0, 3) != pack('CCC', 0xef, 0xbb, 0xbf)) {
                    return self::showMessage($response, '檔案上傳失敗: 格式不符 CSV檔請用UTF8 with BOM做編碼');
                    die();
                }
                */

                // 是否被壓成一行
                if (mb_strlen($data) > 500){
                    return self::showMessage($response, '檔案上傳失敗: 請檢查CSV檔 內容是否被Excel壓縮');
                    die();
                }

                $data = explode(',', $data);

                // 計算第一行 欄數
                $num = (is_array($data))? count($data) : 0;
                if ($num !== 30 ){
                    return self::showMessage($response, '檔案上傳失敗: 請檢查CSV檔 欄位數需30行');
                    die();
                }

            }
            else{
                // 是否被壓成一行
                if (mb_strlen($data) > 500){
                    return self::showMessage($response, '檔案上傳失敗: 請檢查CSV檔 內容是否Excel被壓縮');
                    die();
                }
                // 壓成一行但又沒超過字數 會成為一個array(count=1)
                $data = explode(',', $data);
                if (is_array($data)){
                    // $dataline[] = mb_convert_encoding($data ,'UTF-8', 'BIG5');
                    $dataline[] = $data;
                }
            }
            $filecount++;
        } 
    }
    fclose($tmpFile);

    // 匯入資料陣列
    $importArray = [];
    // 回傳字串
    $returnStr = '';
    foreach($dataline as $k => $v){

        if($v[0] === ''){
            continue;
        }
        if($v[0] === '' && $v[1] === '' && $v[2] === '' && $v[3] === '' && $v[4] === ''){
            continue;
        }
        $reg = array( ",", "-", " ", "%", "\"", "=", "\t", "\r", "\n", "\t", "\v");
        // $reg2 = array(",", " ", "%", "\t", "\r", "\n", "\t", "\v");

        $v[0] = ($v[0] === '')? '' : str_replace( $reg , '', $v[0]);
        $v[1] = ($v[1] === '')? '' : str_replace( $reg , '', $v[1]);
        $v[2] = ($v[2] === '')? '' : str_replace( $reg , '', $v[2]);
        $v[3] = ($v[3] === '')? '' : str_replace( $reg , '', $v[3]);

        $v[4] = ($v[4] === '')? '' : str_replace( $reg , '', $v[4]);
        $v[5] = ($v[5] === '')? '' : str_replace( $reg , '', $v[5]);
        $v[6] = ($v[6] === '')? '' : str_replace( $reg , '', $v[6]);
        $v[7] = ($v[7] === '')? '' : str_replace( $reg , '', $v[7]);

        $v[15] = str_replace($reg , '', $v[15]);
        $v[16] = str_replace($reg , '', $v[16]);
        $v[17] = str_replace($reg , '', $v[17]);

        $v[18] = str_replace($reg , '', $v[18]);
        $v[19] = str_replace($reg , '', $v[19]);
        $v[20] = str_replace($reg , '', $v[20]);

        $v[21] = str_replace($reg , '', $v[21]);
        $v[22] = str_replace($reg , '', $v[22]);
        $v[23] = str_replace($reg , '', $v[23]);

        $dataArray = array(
            "MF_Num"        => htmlspecialchars($v[0]),
            "MF_Pwd"        => $v[1],
            "ContactP"      => htmlspecialchars($v[2]),
            "MF_Name"       => htmlspecialchars($v[3]),
            "GUI_Num"       => htmlspecialchars($v[4]),
            "MF_Cel"        => htmlspecialchars($v[5]),
            "MF_Tel"        => htmlspecialchars($v[6]),
            "MF_Fax"        => htmlspecialchars($v[7]),
            "MF_Address"    => htmlspecialchars($v[8]),
            "MF_Class"      => htmlspecialchars($v[9]),
            "MF_Area"       => htmlspecialchars($v[10]),
            "SalesP"        => htmlspecialchars($v[11]),
            "ContractClass" => htmlspecialchars($v[12]),
            "StartTime"     => htmlspecialchars($v[13]),
            "EndTime"       => htmlspecialchars($v[14]),
            "OilAmount"     => htmlspecialchars($v[15]),
            "OilReach"      => htmlspecialchars($v[16]),
            "OilComp"       => htmlspecialchars($v[17]),
            "UnOilAmount"   => htmlspecialchars($v[18]),
            "UnOilReach"    => htmlspecialchars($v[19]),
            "UnOilComp"     => htmlspecialchars($v[20]),
            "ContractReach" => htmlspecialchars($v[21]),
            "TotalAmount"   => htmlspecialchars($v[22]),
            "Comp"          => htmlspecialchars($v[23]),
            "ContractStatu" => htmlspecialchars($v[24]),
            "UsablePoints"  => htmlspecialchars($v[25]),
            "Usable_DF"     => htmlspecialchars($v[26]),
            "EventCode"     => htmlspecialchars($v[27]),
        );
        array_push($importArray, $dataArray);
    }

    // 檢查是否新增更新 記數
    $info = [];
    // 合約陣列
    $contractsList = [];

    $ip = get_client_ip();

    // 發布地區 地區轉代號
    $releaseAreaArry = array(
        '總公司' => '1',
        '北區' => '2',
        '中區' => '3',
        '南區' => '4',
        '北摩' => '5',
        '中摩' => '6',
        '南摩' => '7'
    );

    foreach ($importArray as $k => $v) {
        $numRow = $model->getMF_infosListByNum($v['MF_Num']);
        $sth = '';

        if( $v['ContractClass'] === '' ){ continue; }
        if( $v['ContractClass'] !== '無合約' && $v['ContractClass'] !== 'LIQUI MOLY專約' && $v['ContractClass'] !== '年度採購合約' ){ continue; }

        // 沒有合約類別
        if( $v['ContractClass'] !== '無合約' ){
            if( !strtotime( ($v['StartTime'].'01') ) || !strtotime( ($v['EndTime'].'01') ) ){
                $returnStr .= "匯入資料: 保修廠編號為{$v['MF_Num']}/此筆合約資料因，開始時間或無結束時間不符合格式故此合約資料不匯入 ";
                continue;
            }
        }

        if($v['MF_Pwd'] !== ''){
            if( strlen(trim($v['MF_Pwd'])) < 3){
                $v['MF_Pwd'] = '';
            }
        }

        // 會員資料
        $MF_data = array(
            "MF_Num"        => $v['MF_Num'],
            "MF_Pwd"        => ($v['MF_Pwd'] == '')? '': md5($v['MF_Pwd']),
            "ContactP"      => $v['ContactP'],
            "MF_Name"       => $v['MF_Name'],
            "GUI_Num"       => $v['GUI_Num'],
            "MF_Cel"        => $v['MF_Cel'],
            "MF_Tel"        => $v['MF_Tel'],
            "MF_Fax"        => $v['MF_Fax'],
            "MF_Address"    => $v['MF_Address'],
            "MF_Class"      => $v['MF_Class'],
            "MF_Area"       => $releaseAreaArry[$v['MF_Area']],
            "SalesP"        => $v['SalesP'],
            "ContractClass" => '',
            "Publish"       => 1,
            "EventCode"     => ($v['EventCode']    == '')? '':$v['EventCode'],
            "UsablePoints"  => ($v['UsablePoints'] == '')? '0':$v['UsablePoints'],
            "Usable_DF"     => ($v['Usable_DF']    == '')? '0':$v['Usable_DF'],
            "IP"            => $ip
        );

        // 合約資料
        $contracts_data = array(
            "CId"           => 'tmp',          // 判斷資料來自哪裡
            "StartTime"     => $v['StartTime'],
            "EndTime"       => $v['EndTime'],
            "OilAmount"     => ($v['OilAmount']     == '')? '0':$v['OilAmount'],
            "OilReach"      => ($v['OilReach']      == '')? '0':$v['OilReach'],
            "OilComp"       => ($v['OilComp']       == '')? '0':$v['OilComp'],
            "UnOilAmount"   => ($v['UnOilAmount']   == '')? '0':$v['UnOilAmount'],
            "UnOilReach"    => ($v['UnOilReach']    == '')? '0':$v['UnOilReach'],
            "UnOilComp"     => ($v['UnOilComp']     == '')? '0':$v['UnOilComp'],
            "ContractReach" => ($v['ContractReach'] == '')? '0':$v['ContractReach'],
            "TotalAmount"   => ($v['TotalAmount']   == '')? '0':$v['TotalAmount'],
            "Comp"          => ($v['Comp']          == '')? '0':$v['Comp'],
            "ContractStatu" => $v['ContractStatu'],
            "ContractClass" => $v['ContractClass'],
            "MF_Num"        => $v['MF_Num']
        );

        // 合約資料丟進新陣列中
        array_push($contractsList, $contracts_data);

        // 會員不存在(新增)
        if( count($numRow) === 0){

            // 沒有新增過(檢查陣列)
            if( !in_array($v['MF_Num'], $info) ){

                $sth = $model->addRowMF_infos($MF_data);
                // 新增後 會員編號存進陣列
                array_push($info, $v['MF_Num']);
            }
        }
        else{
            if( !in_array($v['MF_Num'], $info) ){           
                // 會員存在(修改) 取id
                $mfInfosRow = $numRow[0];

                // 取資料庫裡 同保修廠最大時間
                $lastDataTime = $model->checkLastStartTime($v['MF_Num'], $v['StartTime']);

                // 更新登入LastLogin
                if( $MF_data['MF_Pwd'] != ''){
                    $MF_data['LastLogin'] = null;
                }else{
                    $MF_data['MF_Pwd'] = $mfInfosRow['MF_Pwd'];
                }

                // 至少有一筆合約資料  取最大時間
                if( count($lastDataTime) > 0){           
                    if( $v['StartTime'] < $lastDataTime[0]['StartTime'] ){ continue; }
                }

                $sth = $model->setRowMF_infos($MF_data, (int)$mfInfosRow['MF_Id']);
                // 修改號後 會員編號存進陣列
                array_push($info, $v['MF_Num']);
            }
        }
    }

    // 比較匯入資料與資料庫中 同個保修廠編號的合約資料中是否有 開始時間一樣之資料
    foreach($contractsList as $k => $v){
        // 同保修改編號 及 同開始時間
        $deletearr = $model->checkMFStartTime($v['MF_Num'], $v['StartTime']);
        // 將資料庫中的資料刪除(多筆)
        if( count($deletearr) > 0){           
            foreach($deletearr as $key => $val){
                $model->delContracts($val['CId']);
            }
        }
    }

    // 取 資料庫所有合約資料
    $cList = $model->getCs();
    $st = "";
    if(count($cList) >0 ){
        // 合併 合約資料 資料庫合約資料
        $st = array_merge($contractsList, $cList);

        // 多維陣列 排序(MF_Num以小到大 StartTime 已大到小)
        foreach ($st as $key => $row) {
            $MF_Num[$key] = $row['MF_Num'];
            $StartTime[$key] = $row['StartTime'];
        }
        // 排序
        array_multisort($MF_Num, SORT_ASC, $StartTime, SORT_DESC, $st);
    }
    else{
        // 資料庫沒有合約資料 原本contractsList就依照時間大小排序
        $st = $contractsList;
    }

    // 清空合約表格
    if($model->clearContractsTable() == false ){
        $returnStr .= "table刪除失敗,";
    };

    $cont = [];     // 計數器
    $frequency = 0; // 計數器歸0
    foreach($st as $k => $v){
        // 計數器
        if( !in_array($v['MF_Num'], $cont) ){
            array_push($cont, $v['MF_Num']);
            $frequency = 0;
        }

        // 合約資料
        $data = array(
            "StartTime"     => $v['StartTime'],
            "EndTime"       => $v['EndTime'],
            "OilAmount"     => $v['OilAmount'],
            "OilReach"      => $v['OilReach'],
            "OilComp"       => $v['OilComp'],
            "UnOilAmount"   => $v['UnOilAmount'],
            "UnOilReach"    => $v['UnOilReach'],
            "UnOilComp"     => $v['UnOilComp'],
            "ContractReach" => $v['ContractReach'],
            "TotalAmount"   => $v['TotalAmount'],
            "Comp"          => $v['Comp'],
            "ContractStatu" => $v['ContractStatu'],
            "ContractClass" => $v['ContractClass'],
            "MF_Num"        => $v['MF_Num'],
            "IP"            => $ip
        );

        // 最大兩筆
        if($frequency < 2){
            // >新增<合約資料
            if($model->addRowContracts($data) == false){
                if( $v['CId'] === 'tmp' ){
                    $returnStr .= "匯入資料: 保修廠編號為{$v['MF_Num']}/合約資料 新增失敗， ";
                }
            }
        }
        $frequency++;
    }

    if($returnStr === ''){
        return self::showMessage($response, "匯入成功", $routeParser->urlFor("M02"));
    }
    else{
        return self::showMessage($response, "匯入失敗之資料為以下: {$returnStr}", $routeParser->urlFor("M02"));
    }
}



```






