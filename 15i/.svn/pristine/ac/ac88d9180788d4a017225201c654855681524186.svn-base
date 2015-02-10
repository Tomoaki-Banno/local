<?php

//     pear install OLE
//     pear install Spreadsheet_Excel_Writer

require_once (PEAR_DIR . 'Spreadsheet/Excel/Writer.php');

class Gen_Excel
{

    // SQL文を実行して、結果をExcel出力する。
    // 引数
    //  $sql                    SQL文
    //  $title                  タイトル（シート先頭に表示される）
    //  $colArray               列情報。2次元配列
    //                              type（data, numeric, date, datetime, textbox だけが表示対象。dataは文字貼り付けされる）
    //                              label, field, width, sameCellJoin, parentColumn, hide, visible, colorCondition
    //  $showArray              セル位置を指定して書き込む文字（配列）
    //                              array(array(x,y,text),・・・) という形になる
    //  $detailRow              sqlの結果を書き出すy位置を指定
    //  $rowColorCondition
    //  $subSumCriteria         小計基準
    //  $subSumCriteriaDateType 小計基準の日付タイプ
    //  $titleColumn            （複数シート用）シートタイトルとして使用するDB列。指定すれば$titleより優先される。省略可能
    //  $sheetKeyColumn         （複数シート用）シートブレイク列。このDB列の内容が変わったらシートが変わる
    //  $forceCellJoin          自社情報の設定にかかわらずセル結合する


    static function sqlToExcel($sql, $title, $colArray, $showArray, $detailRow, $rowColorCondition, $subSumCriteria = "", $subSumCriteriaDateType = "",
            $titleColumn = "", $sheetKeyColumn = "", $forceCellJoin = false)
    {
        global $gen_db;

        // ワークブックを作成
        $workbook = new Spreadsheet_Excel_Writer();

        $workbook->setVersion(8);   // BIFF8

        // ファイル名の文字コードは、IEの場合はSJIS、Firefoxの場合はダウンロード元ページと同じ（UTF-8）である必要がある。
        // Firefoxの場合、SJISでも一応大丈夫なのだがいわゆる5C文字（「構」「表」など）が文字化けしてしまう。
        $ua = $_SERVER['HTTP_USER_AGENT'];
        if (stripos($ua, 'MSIE') !== false || stripos($ua, 'Trident') !== false) {
            // IE
            $filename = mb_convert_encoding($title . ".xls", "SJIS", "UTF-8");
        } else {
            // FF, Webkit
            $filename = $title . ".xls";
        }

        // HTTP ヘッダを送信
        $workbook->send($filename);

        // SQLが指定されていない場合は、ここまでで終了
        if ($sql == "") {
            // シート作成、見出し表示
            self::_makeSheet($workbook, $title, $showArray, $colArray, $detailRow);

            // ファイルを送信
            $workbook->close();
            return;
        }

        // データを取得
        $arr = $gen_db->getArray($sql);

        // データがない場合
        if (!is_array($arr)) {
            // シート作成、見出し表示
            self::_makeSheet($workbook, $title, $showArray, $colArray, $detailRow);

            // ファイルを送信
            $workbook->close();
            return;
        }

        // 日付の出力形式の取得
        $query = "select excel_date_type from company_master";
        $excelDateType = $gen_db->queryOneValue($query);

        // セル結合と色付けの有無
        $query = "select excel_cell_join, excel_color from company_master";
        $obj = $gen_db->queryOneRowObject($query);
        $isJoinMode = ($forceCellJoin || $obj->excel_cell_join == 't');
        $isColorMode = ($obj->excel_color == 't');

        // セル色のリストアップ
        $colorArr = array();
        $colorArr['#ffffff'] = 8;    // 使用できるパレットインデックスは 8-63
        if ($isColorMode) {
            $newPalletIndex = 9;
            if (is_array($rowColorCondition)) {    // rowColor
                foreach ($rowColorCondition as $cellColor => $val) {
                    if (!isset($colorArr[$cellColor]))
                        $colorArr[$cellColor] = $newPalletIndex++;
                    if ($newPalletIndex > 63)
                        break 2;
                }
            }
            if ($newPalletIndex <= 62) {    // 63は小計行で使用する
                foreach ($colArray as $col) {        // cellColor
                    if (isset($col['colorCondition'])) {
                        foreach ($col['colorCondition'] as $cellColor => $val) {
                            if (!isset($colorArr[$cellColor]))
                                $colorArr[$cellColor] = $newPalletIndex++;
                            if ($newPalletIndex > 63)
                                break 2;
                        }
                    }
                }
            }
        }

        // 小計行の色
        $subsumColor = "#d5ebff";
        $colorArr[$subsumColor] = 63;

        // 書式の作成
        //    作成した書式の内容は上書きして使いまわすということができないので、セル色ごとに個別に作成しておく必要がある。
        //    ちなみにこの書式パターンの数が4,000を超えると、Excel2003で開けなくなる（http://support.microsoft.com/kb/213904/ja）Excel2007は 64,000。
        //        バーコードフォントを使用しない限り、ほとんど問題ないと思う。
        $format_data = array();
        $format_data_wrap = array();
        $format_data_num = array();
        $format_data_int = array();
        $format_data_date = array();
        foreach ($colorArr as $color => $palletIndex) {
            // 色の作成
            $cellColorRGB = self::html2rgb($color);
            $workbook->setCustomColor($palletIndex, $cellColorRGB[0], $cellColorRGB[1], $cellColorRGB[2]);

            // 文字書式
            $format_data[$palletIndex] = &$workbook->addFormat(array('border' => 1, 'fgColor' => $palletIndex));
            $format_data[$palletIndex]->setAlign('vcenter');
            $format_data_wrap[$palletIndex] = &$workbook->addFormat(array('border' => 1, 'fgColor' => $palletIndex));
            $format_data_wrap[$palletIndex]->setTextWrap();
            $format_data_wrap[$palletIndex]->setAlign('vcenter');

            // 日付書式
            $format_data_date[$palletIndex] = &$workbook->addFormat(array('border' => 1, 'NumFormat' => 'yyyy/m/d', 'fgColor' => $palletIndex));
            $format_data_date[$palletIndex]->setAlign('vcenter');

            // 数値書式
            if (GEN_DECIMAL_POINT_EXCEL < 0) {
                // 自然丸め
                $format_data_num[$palletIndex] = &$workbook->addFormat(array('border' => 1, 'NumFormat' => '#,##0.#########', 'fgColor' => $palletIndex));   // 自然丸め。カンマあり
                $format_data_num[$palletIndex]->setAlign('vcenter');
                $format_data_int[$palletIndex] = &$workbook->addFormat(array('border' => 1, 'NumFormat' => '#,##0', 'fgColor' => $palletIndex));             // 自然丸めの整数用。上の行の書式だと、小数点がない場合にドットが残ってしまうので。
                $format_data_int[$palletIndex]->setAlign('vcenter');
            } else if (GEN_DECIMAL_POINT_EXCEL == 0) {
                // 整数
                $format_data_num[$palletIndex] = &$workbook->addFormat(array('border' => 1, 'NumFormat' => '#,##0', 'fgColor' => $palletIndex));    // 数値の整数丸め。カンマあり
                $format_data_num[$palletIndex]->setAlign('vcenter');
            } else {
                // 小数点以下桁数固定
                $zero = str_repeat('0', GEN_DECIMAL_POINT_EXCEL <= 0 ? 1 : GEN_DECIMAL_POINT_EXCEL);   // 小数点以下の桁数分、ゼロをならべる。-1（自然丸め）のときは使用されない
                $format_data_num[$palletIndex] = &$workbook->addFormat(array('border' => 1, 'NumFormat' => '#,##0.' . $zero . '_ ', 'fgColor' => $palletIndex));    // 数値の桁数固定丸め。カンマあり
                $format_data_num[$palletIndex]->setAlign('vcenter');
            }
        }

        // ループの準備
        $y = $detailRow + 1;

        $upCellValue = array();
        $joinStartRow = array();
        $joinCount = 0;

        $breakKey = null;

        // キーブレイクがない場合のシート作成、見出し表示
        if ($sheetKeyColumn == "") {
            $worksheet = self::_makeSheet($workbook, $title, $showArray, $colArray, $detailRow);
        }

        // 小計行 (gen_data_list.tpl にある処理とだいたい同じ）
        $existSubSum = ($subSumCriteria != "");
        if ($existSubSum) {
            self::_initSubSum($arr, 0, $colArray, $subSumCriteria, $subSumCriteriaDateType, $subSumCriteriaDateTypeStr, $subSumCriteriaCache, $subSumArray);
        }

        // メインループ
        $recordCount = count($arr);
        for ($arrY = 0; $arrY < $recordCount; $arrY++) {
            // キーブレイク（シートチェンジ）
            if ($sheetKeyColumn != "") {
                if ($breakKey !== $arr[$arrY][$sheetKeyColumn]) {
                    $breakKey = $arr[$arrY][$sheetKeyColumn];
                    $title = ($titleColumn == "" ? $title : $arr[$arrY][$titleColumn]);

                    // 小計関連
                    if ($existSubSum && $arrY > 0) {
                        // 前シートの最後の小計表示
                        self::_showSubSum($worksheet, $y, $colArray, $subSumCriteria, $subSumCriteriaCache, $subSumArray, $colorArr,
                            $subsumColor, $format_data, $format_data_int, $format_data_num, $format_data_date, $format_data_wrap, $excelDateType);

                        // 小計のクリア
                        self::_initSubSum($arr, $arrY, $colArray, $subSumCriteria, $subSumCriteriaDateType, $subSumCriteriaDateTypeStr, $subSumCriteriaCache, $subSumArray);
                    }

                    // シート作成、見出し表示
                    $worksheet = self::_makeSheet($workbook, $title, $showArray, $colArray, $detailRow);
                    $y = $detailRow + 1;
                }
            }

            $row = $arr[$arrY];

            // 行の色
            unset($rowPalletIndex);
            if ($isColorMode && is_array($rowColorCondition)) {
                $rowColor = self::getRowBgColor($rowColorCondition, $row);
                if ($rowColor) {
                    $rowPalletIndex = $colorArr[$rowColor];
                }
            }

            // 小計行 (gen_data_list.tpl にある処理とだいたい同じ）
            $isInsertSubSumRow = false;
            if ($existSubSum) {
                // 「===」で比較していることに注意。  「==」や「!=」は文字を数値変換して比較するので、
                //  "00" === "000" のようなケースでもtrueになってしまう
                $subSumCriteriaValue = $row[$subSumCriteria];
                if ($subSumCriteriaDateTypeStr != "") {
                    if ($subSumCriteriaValue != "") {
                        $subSumCriteriaValue = date($subSumCriteriaDateTypeStr, strtotime($subSumCriteriaValue));
                    }
                }
                if ($subSumCriteriaValue !== $subSumCriteriaCache) {
                    // 小計行表示
                    self::_showSubSum($worksheet, $y, $colArray, $subSumCriteria, $subSumCriteriaCache, $subSumArray, $colorArr,
                        $subsumColor, $format_data, $format_data_int, $format_data_num, $format_data_date, $format_data_wrap, $excelDateType);
                    foreach ($subSumArray as $key => $val) {
                        $subSumArray[$key] = 0;
                    }
                    $subSumCriteriaCache = $subSumCriteriaValue;
                    $isInsertSubSumRow = true;
                    $y++;
                }
            }

            // 1行分の処理
            $x = 0;
            foreach ($colArray as $col) {
                // このifの条件は _showSubSum()と合わせておく必要がある
                if (($col['type'] == "numeric" || $col['type'] == "data" || $col['type'] == "textbox" || $col['type'] == "date" || $col['type'] == "datetime" || $col['type'] == "schedule")
                        && (!isset($col['hide']) || !$col['hide']) && (!isset($col['visible']) || $col['visible'])) {
                    $value = $row[$col['field']];

                    // セルのマージ
                    $join = false;
                    if (isset($col['sameCellJoin']) && $col['sameCellJoin'] == true) {
                        if (!isset($joinStartRow[$col['field']])) {
                            $joinStartRow[$col['field']] = $y;
                        }

                        // このセルを上のセルとマージするかどうかの判断
                        if (isset($upCellValue[$col['field']]) && $upCellValue[$col['field']] === $value) {
                            $join = true;
                            // parentColumnの処理
                            if (isset($col['parentColumn']) && $col['parentColumn'] != "") {
                                if ($arrY == 0) {
                                    $join = false;
                                } else if (isset($arr[$arrY][$col['parentColumn']]) && $arr[$arrY - 1][$col['parentColumn']] != $arr[$arrY][$col['parentColumn']]) {
                                    $join = false;
                                }
                            }
                        }

                        if ($isJoinMode && $join) {
                            $value = "";
                        }

                        // マージ処理
                        //  このセルが最終行か、このセルがマージ対象でないとき（上のセルまででマージ区間が終わったとき）
                        $prevY = $y - ($isInsertSubSumRow ? 2 : 1);   // 上の行が小計行だった場合に対応
                        if (($join && $arrY == $recordCount - 1) || (!$join && $joinStartRow[$col['field']] < $prevY)) {
                            $joinCount++;
                            // Excel2003ではセル結合箇所が多すぎるとファイル破壊がおきるため、13i以前は250箇所に制限していた。
                            // 15iは2007以降を前提としているため、10000箇所に増やした。
                            if ($isJoinMode && $joinCount < 10000) {
                                $worksheet->setMerge($joinStartRow[$col['field']], $x, ($join ? $y : $prevY), $x);
                            }
                        }
                        if (!$join) {
                            $joinStartRow[$col['field']] = $y;
                        }
                        $upCellValue[$col['field']] = $row[$col['field']];
                    }

                    // セルの色
                    // 優先順位はリスト画面と同じにする（行の色よりセルの色が優先）
                    if (!$isColorMode) {
                        $palletIndex = 8;    // 白
                    } else if ($cellColor = self::getCellColor($col, $row)) {
                        $palletIndex = $colorArr[$cellColor];    // セルの色が指定されている場合
                    } else if (isset($rowPalletIndex)) {
                        $palletIndex = $rowPalletIndex;            // 行の色が指定されている場合
                    } else {
                        $palletIndex = 8;    // 白
                    }

                    // セルに書き込み
                    self::_writeCell($worksheet, $y, $x, $value, $col['type'], $palletIndex, $format_data, $format_data_int, $format_data_num, $format_data_date, $format_data_wrap, $excelDateType, false);
                    $x++;

                    // 小計計算
                    // gen_data_list.php では集計行表示のところでやっているが、エクセル出力の場合はここでないと
                    // セル結合判断ができない
                    if ($existSubSum) {
                        foreach ($subSumArray as $key => $subSumVal) {
                            if (isset($col['field']) && $key === $col['field'] && is_numeric($value) && !$join) {
                                // 小数点以下の数値は4桁程度までしか正確に計算できない。　
                                // 以下のようにすればもっと精度は高まるが、表示速度が低下する。ag.cgi?page=ProjectDocView&pid=1574&did=198830
                                // 　$subSumArray[$key] = Gen_Math::add($val, $row[$key]);
                                // ※ただし、上記の設定をした場合でも小数点以下の桁数が多い場合は丸められてしまうこともある。
                                //　 精度はphp.iniのbcmath.scaleの設定次第。PHP内でbcscaleにより設定することもできる。
                                $subSumArray[$key] = $subSumVal + $value;
                            }
                        }
                    }
                }
            }
            $y++;
        }

        // 最後の小計行を表示
        if ($existSubSum) {
            self::_showSubSum($worksheet, $y, $colArray, $subSumCriteria, $subSumCriteriaCache, $subSumArray, $colorArr,
                $subsumColor, $format_data, $format_data_int, $format_data_num, $format_data_date, $format_data_wrap, $excelDateType);
        }

        // ファイルを送信
        $workbook->close();
    }

    static function _writeCell($worksheet, $y, $x, $value, $type, $palletIndex, $format_data, $format_data_int, $format_data_num, $format_data_date, $format_data_wrap, $excelDateType, $isBold)
    {
        if ($value === "") {
            // ●ブランク
            // 以前は下記のように2重書き込みをしていた。ブランク書き込みをするとなぜか罫線が表示されないことがあったため。
            //  $worksheet->write($y, $x, " ", $format_data[$palletIndex]);
            //  $worksheet->write($y, $x, "", $format_data[$palletIndex]);
            // しかし文字コードをUTF化するためにSpreadsheet_Excel_Writerを0.9.3にアップグレードし、フォーマットを変更（BIFF5 ⇒ 8）
            // したところ、2重書き込みがある場合にExcel2007以降で問題が発生するようになった。
            // （ファイルを開いたときに「ファイルエラー」が表示される。2重書き込みしなければ問題ない。またExcel2003では問題ない）
            // 同環境では前述の罫線の問題は発生しないようなので、本来の形に戻した。
            $worksheet->write($y, $x, "", $format_data[$palletIndex]);
        } else if (($type == "numeric" || $type == "textbox") && Gen_String::isNumeric($value)) {
            // ●数字
            if (GEN_DECIMAL_POINT_EXCEL == -1 && ((int) $value == $value)) {
                // 自然丸めは書式「#,###.#######」で実現しているが、小数部がない場合、右はじにドットが残って
                // しまう。それを回避するため、この場合専用の書式を適用する。
                if ($isBold) {
                    $format_data_int[$palletIndex]->setBold(1);
                }
                $worksheet->writeNumber($y, $x, $value, $format_data_int[$palletIndex]);
            } else {
                if ($isBold) {
                    $format_data_num[$palletIndex]->setBold(1);
                }
                $worksheet->writeNumber($y, $x, $value, $format_data_num[$palletIndex]);  // カンマあり。桁数は $format_data_num の中で指定済み。
            }
        } else if (($type == "date") && Gen_String::isDateString($value) && $excelDateType == "1") {
            // $col['type']が日付型であり、値が日付型であり、自社情報にて出力形式が指定されている場合。
            // ●日付
            if ($isBold) {
                $format_data_date[$palletIndex]->setBold(1);
            }
            // 値はエクセル形式の日付値（1900/1/1を1とし、そこからの経過日数）で指定する。
            // PHP日付の基準日は 1970/1/1 なので、両者の差を足す必要がある。（25569秒）
            $worksheet->write($y, $x, Gen_Math::add(Gen_Math::div(strtotime($value . " 00:00:00 GMT"), 86400), 25569), $format_data_date[$palletIndex]);
        } else if (($type == "datetime") && Gen_String::isDateTimeString($value) && $excelDateType == "1") {
            // $typeが日時であり、値が日時であり、自社情報にて出力形式が指定されている場合。
            // ●日時
            if ($isBold) {
                $format_data_date[$palletIndex]->setBold(1);
            }
            $worksheet->writeString($y, $x, date('Y/n/j H:i:s', strtotime($value)), $format_data_date[$palletIndex]);
        } else {    // data or date or 値が非数値
            // ●文字として貼り付け（頭0が切れない、数値フォーマットしない）
            if (strpos($value, '<br>') !== false) {
                // 改行あり
                $value = str_replace('<br>', "\n", $value);
                if ($isBold) {
                    $format_data_wrap[$palletIndex]->setBold(1);
                }
                $worksheet->writeString($y, $x, $value, $format_data_wrap[$palletIndex]);
            } else {
                // 改行なし
                if ($type == "numeric" && !Gen_String::isNumeric($value) && strlen($value) == 0) {
                    // 数字フォーマットで空欄の時に書式を設定する。
                    // 書式を設定しないと、エクセルで計算式を指定した際にエラーが発生する。
                    if ($isBold) {
                        $format_data_int[$palletIndex]->setBold(1);
                    }
                    $worksheet->writeBlank($y, $x, $format_data_int[$palletIndex]);
                } else {
                    if ($isBold) {
                        $format_data[$palletIndex]->setBold(1);
                    }
                    $worksheet->writeString($y, $x, $value, $format_data[$palletIndex]);
                }
            }
        }

    }

    static function _showSubSum($worksheet, $y, $colArray, $subSumCriteria, $subSumCriteriaCache, $subSumArray, $colorArr,
            $subsumColor, $format_data, $format_data_int, $format_data_num, $format_data_date, $format_data_wrap, $excelDateType)
    {
        $x = 0;
        foreach($colArray as $col) {
            // このifの条件は sqlToExcel() の「一行分の処理」のところと合わせておく必要がある
            if (($col['type'] == "numeric" || $col['type'] == "data" || $col['type'] == "textbox" || $col['type'] == "date" || $col['type'] == "datetime" || $col['type'] == "schedule")
                    && (!isset($col['hide']) || !$col['hide']) && (!isset($col['visible']) || $col['visible'])) {
                $val = "";
                if (isset($col['field'])) {
                    $isCriteria = false;
                    if ($col['field'] == $subSumCriteria) {
                        $val = "[ Σ ] " . $subSumCriteriaCache;
                        $isCriteria = true;
                    } else {
                        foreach ($subSumArray as $key => $sumVal) {
                            if ($col['field'] == $key) {
                                $val = $sumVal;
                                break;
                            }
                        }
                    }
                    // セルに書き込み
                    self::_writeCell($worksheet, $y, $x, $val, $col['type'], $colorArr[$subsumColor], $format_data, $format_data_int, $format_data_num, $format_data_date, $format_data_wrap, $excelDateType, true);
                }
                $x++;
            }
        }
    }

    static function _initSubSum($arr, $arrY, $colArray, $subSumCriteria, &$subSumCriteriaDateType, &$subSumCriteriaDateTypeStr, &$subSumCriteriaCache, &$subSumArray)
    {
        $subSumCriteriaCache = $arr[$arrY][$subSumCriteria];
        $subSumCriteriaDateTypeStr = "";
        if (isset($subSumCriteriaDateType)) {
            switch ($subSumCriteriaDateType) {
                case 0: $subSumCriteriaDateTypeStr = "Y"; break;
                case 1: $subSumCriteriaDateTypeStr = "Y-m"; break;
                default: $subSumCriteriaDateTypeStr = "Y-m-d";
            }
            if (Gen_String::isDateString($subSumCriteriaCache)) {
                $subSumCriteriaCache = date($subSumCriteriaDateTypeStr, strtotime($subSumCriteriaCache));
            }
        }
        $subSumArray = array();
        foreach ($colArray as $col) {
            if ($col['type'] == "numeric" && $col['field'] != "") {
                $subSumArray[$col['field']] = 0;
            }
        }
    }

    static function _makeSheet($workbook, $title, $showArray, $colArray, $detailRow)
    {
        // ワークシートを作成
        // BIFF8以前では、シート名が30文字を超えるとエラーになる
        $sheetTitle = substr($title, 0, 30);

        // シート名に特殊な文字が含まれているとエラーになることがある
        if (!@iconv('UTF-8','UTF-16LE',$sheetTitle)) {
            $sheetTitle = "";
        }
        $worksheet = &$workbook->addWorksheet($sheetTitle);

        $worksheet->setInputEncoding("utf-8");

        // 用紙をA4横にする
        $worksheet->setPaper(9);
        $worksheet->setLandscape();

        // 1ページに収まるようにする
        $worksheet->fitToPages(1, 1); // 縦,横ページ数
        // 枠線（罫線とは別に印刷時に表示されるセル枠。[ファイル]-[ページ設定]のシートタブ）を非表示にする
        $worksheet->hideGridlines();

        // シートタイトルを書き込み
        $format_title = &$workbook->addFormat(array('size' => 15));
        $worksheet->write(0, 0, $title, $format_title);

        // 発行日時を書き込み
        $worksheet->write(0, 2, _g("発行日時：") . date("Y-m-d H:i:s"));

        // $showArrayを書き込み
        if (is_array($showArray)) {
            foreach ($showArray as $showData) {
                if (is_numeric($showData[0]) && $showData[0] >= 0 && $showData[0] < 65535
                        && is_numeric($showData[1]) && $showData[1] >= 0 && $showData[1] < 65535) {
                    $worksheet->write($showData[1], $showData[0], $showData[2]);
                }
            }
        }

        // 見出し行の書式
        $format_label = &$workbook->addFormat(array('align' => 'center', 'fgColor' => 22, 'pattern' => 1, 'border' => 1));

        // 列見出しを書き込み, 列幅をセット
        $x = 0;
        foreach ($colArray as $col) {
            if (($col['type'] == "numeric" || $col['type'] == "data" || $col['type'] == "textbox" || $col['type'] == "date" || $col['type'] == "datetime" || $col['type'] == "schedule")
                    && (!isset($col['hide']) || !$col['hide']) && (!isset($col['visible']) || $col['visible'])) {
                // 見出しを書き込み
                $label = (isset($col['label_noEscape']) ? $col['label_noEscape']: $col['label']);
                $label = str_replace("<br>", "", $label);
                $label = str_replace("<BR>", "", $label);
                $worksheet->writeString($detailRow, $x, $label, $format_label);
                // 列幅調整。widthは実際の幅の6倍で指定されている（列指定は画面表示と共通化されているため）
                if ($col['width'] == 0) {
                    $worksheet->setColumn($x, $x, 0, 0, 1);     // 列を非表示に
                } else {
                    $worksheet->setColumn($x, $x, (int) ($col['width'] / 6));
                }

                $x++;
            }
        }

        return $worksheet;
    }

    // HTMLの色表現（#xxxxxx）をRGB値に変換する
    static function html2rgb($color)
    {
        if ($color[0] == '#')
            $color = substr($color, 1);

        if (strlen($color) == 6)
            list($r, $g, $b) = array($color[0] . $color[1],
                $color[2] . $color[3],
                $color[4] . $color[5]);
        elseif (strlen($color) == 3)
            list($r, $g, $b) = array($color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2]);
        else
            return false;

        $r = hexdec($r);
        $g = hexdec($g);
        $b = hexdec($b);

        return array($r, $g, $b);
    }

    // -------- 以下、function.gen_data_list とだいたい同じ ---------
    // 行の色
    static function getRowBgColor($rowColorCondition, $row)
    {
        if (is_array($rowColorCondition)) {
            // 色付け条件
            if ($colorCode = self::evalConditionArray($rowColorCondition, $row)) {
                return $colorCode;
            }
        }
        return false;
    }

    // セルの色
    static function getCellColor($col, $row)
    {
        // セル色の決定
        if (isset($col['colorCondition'])) {
            if ($colorCode = self::evalConditionArray($col['colorCondition'], $row)) {
                return $colorCode;
            }
        }
        return false;
    }

    // 与えられた条件配列を評価し、合致する条件のkeyを返す。
    static function evalConditionArray($condArr, $row)
    {
        if (!is_array($condArr))
            return false;

        while (list($key, $exp1) = each($condArr)) {
            // 条件式の[...]をフィールドの値に置き換える
            $exp1 = self::bracketToFieldValue($exp1, $row);

            // evalは文字列をPHPコードとして実行する関数。returnを入れることにより
            // 式の評価結果（真偽）を返している。文字列の最後にはセミコロンが必要。
            if (eval("return(" . $exp1 . ");")) {
                return $key;
            }
        }
        return false;
    }

    // 文字列内の[...]をフィールドの値に置き換える
    static function bracketToFieldValue($sourceStr, $row)
    {
        $matches = "";
        $res = $sourceStr;
        if (preg_match_all("(\[[^\]]*\])", $res, $matches) > 0) {
            foreach ($matches[0] as $match) {
                $matchStr = $match;
                $matchStr = str_replace('[', '', $matchStr);
                $matchStr = str_replace(']', '', $matchStr);
                $res = str_replace($match, @$row[$matchStr], $res);
            }
        }
        return $res;
    }

}