<?php

class Gen_Csv
{

    //************************************************
    // CSVデータのエクスポート
    //************************************************
    // クエリを実行し、結果をCSVファイルとしてエクスポート。
    // 引数
    //    $filename    エクスポートするファイル名（フルパス指定。ファイルが存在しなければ作成される）
    //    $title        CSVファイルの1行目の内容（見出しをカンマ区切り文字列の形で指定）
    //    $query        データを取得するSQL文。
    //    $gen_db
    // 戻り
    //    成功すればtrue、失敗すればfalse

    static function CsvExport($filename, $title, $query, $beginPos = 1)
    {
        global $gen_db;

        set_time_limit(1000);

        // エクスポートファイルを開く
        if (!($fp = fopen($filename, 'w'))) {
            return false;
        }

        // UTF-8の場合、BOMを付加する。
        // Excel(2007以降)でCSVファイルを開いた時の文字化けを回避するため。
        // 　ExcelでBOMなしのUTF-8のCSVファイルを開くと文字化けする。CSV以外(txt等)なら大丈夫。
        // 　また、Excel2003以前の場合はこれでも文字化けする。テキストファイルウィザードなら正常に開ける。
        // ⇒ 実装中止。下記のような問題が判明したため ag.cgi?page=ProjectDocView&pid=1516&did=175597
        //  ・CSVエクスポートしたファイルを、Excel2010で編集し、上書き保存すると、カンマが全てタブに置き換わってしまう
        //　・CSVエクスポートしたファイルを、Excel2010で編集し、名前を付けて保存しようとすると、デフォルトで「Unicodeテキスト」形式が選択される
//        if (GEN_CSV_EXPORT_TO_ENCODING == "UTF-8")
//            fputs($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));
        
        // タイトル行（1行目）
        fputs($fp, mb_convert_encoding($title, GEN_CSV_EXPORT_TO_ENCODING, "UTF-8"));

        // データ（2行目以降）
        // 大量データ取得時のメモリ不足を避けるため、getArrayではなくfetchとする。
        $beginPos--;
        if (!is_numeric($beginPos) || $beginPos < 0)
            $beginPos = 0;
        $res = $gen_db->query("$query offset $beginPos limit " . (GEN_CSV_EXPORT_MAX_COUNT + 1));
        if ($beginPos > 0 || $gen_db->numRows($res) > GEN_CSV_EXPORT_MAX_COUNT) {
            $msg = "\n" . sprintf(_g("%s - %s 件目のデータが表示されています。"), $beginPos + 1, $beginPos + GEN_CSV_EXPORT_MAX_COUNT) . "\n\n";
            fputs($fp, mb_convert_encoding($msg, GEN_CSV_EXPORT_TO_ENCODING, "UTF-8"));
        }
        $lineCount = 1;

        while ($row = $gen_db->fetchRow($res, null)) {
            $rowStr = "";

            foreach ($row as $col) {
                // 項目内にダブルコーテーションが含まれていた場合はクオートする（二重ダブル
                // コーテーションにする）。
                // ExcelでCSVを開いたとき、項目の途中でダブルコーテーションが出てくるのは問題
                // ないが、項目の先頭や末尾、またダブルコーテーション囲みされた項目の途中で
                // 出てくる場合、クオートしておかないと正しく解釈されない。

                $col = str_replace("\"", "\"\"", $col);

                // 項目内にカンマやダブルコーテーションが含まれていたときは、項目をダブルコー
                // テーションで囲む。
                // そうしないとExcelで開いたときや再インポートのときに正しく解釈されない。
                // 全項目をダブルコーテーション囲みしてもよかったかもしれないが、CSVを扱う
                // ソフトによってはダブルコーテーションが邪魔になる場合もあるため、
                // （たとえば「""」が、空文字をダブルコーテーションで囲んだものか、1文字の
                //   ダブルコーテーションをクオートしたものか判断できないなど）
                // Excelの仕様に倣って、問題になる項目だけを囲むようにした。

                if (strpos($col, "\"") || strpos($col, ",")) {
                    $col = "\"" . $col . "\"";
                }

                // 項目内の改行コードはカット
                $col = str_replace("\n", "", $col);
                $col = str_replace("\r", "", $col);

                $rowStr .= mb_convert_encoding($col, GEN_CSV_EXPORT_TO_ENCODING, "UTF-8") . ",";
            }
            if ($rowStr != "") {
                $rowStr = substr($rowStr, 0, strlen($rowStr) - 1);    // 最後のカンマを取る
                fputs($fp, $rowStr . "\n");
            }

            $lineCount++;
            if ($lineCount > GEN_CSV_EXPORT_MAX_COUNT) {
                break;
            }
        }

        fclose($fp);

        return true;
    }

    //************************************************
    // CSVデータのインポート
    //************************************************
    // CSVファイルからデータを読み込み、データベースに登録する。

    static function CsvImportForModel($fileName, $modelName, $colArray, $allowUpdate, $classification)
    {
        global $gen_db;

        // 実行時間制限を変更。PHPがセーフモードの場合は効かない
        // このスクリプトの中だけで有効
        set_time_limit(GEN_CSV_IMPORT_MAX_SECOND);

        if (!($fp = fopen($fileName, 'r'))) {
            return array(false, 0, _g('ファイルを開けません。'));
        }
        
        
        $isTabSeparate = false;
        $headerNumberOfLines = 1;
        
        // CSVフォーマット
        $listAction = str_replace("_Model", "_List", $modelName);
        if ($classification != "") {
            $listAction .= "_" . $classification;
        }
        $settingName = "gen_csv_format_{$listAction}";
        $format = false;
        if (isset($_SESSION['gen_setting_user']->$settingName) && $_SESSION['gen_setting_user']->$settingName != "") {
            $query = "select format_data from csv_format where action = '{$listAction}' and format_name = '{$_SESSION['gen_setting_user']->$settingName}'";
            $formatJson = str_replace("￥", "\\", $gen_db->queryOneValue($query));
            if ($formatJson != "") {
                $format = json_decode($formatJson);
                $isTabSeparate = (isset($format->gen_isTab) && $format->gen_isTab);
                $headerNumberOfLines = (isset($format->gen_headerNumberOfLines) && Gen_String::isNumeric($format->gen_headerNumberOfLines) ? 
                    $format->gen_headerNumberOfLines : 1);
            }
        }
        // CSVフォーマットの数式処理用
        // 同様の処理が Config_Setting_AjaxCsvFormatRegist にもある。
        // 下の2行は、式の一部がセルやシートの名前とみなされる場合（"=test" など）に Fatal Error になるのを避けるための定義。
        //  CALCULATION_REGEXP_NAMEDRANGE をダミー値にしているのがポイント
        define('CALCULATION_REGEXP_CELLREF','(((\w*)|(\'[^\']*\')|(\"[^\"]*\"))!)?\$?([a-z]{1,3})\$?(\d+)');
        define('CALCULATION_REGEXP_NAMEDRANGE','DUMMY_STRING');
        require_once ROOT_DIR . "/PHPExcel/PHPExcel/Calculation.php";
        $calculation = PHPExcel_Calculation::getInstance();
        // 数値誤差対策。ag.cgi?page=ProjectDocView&pid=1574&did=238992
        // 上の PHPExcel_Calculation::getInstance() の実行時に、環境によっては ini_set('precision',16) される。
        // （PHPExcel/PHPExcel/Calculation.php 1722行目）
        // すると (float)0.94 が 0.939999... になってしまう。
        // しかし下のように設定しておくとこの現象は発生しない。
        // 対処療法的であり、別の数値で問題が発生する可能性はあるが・・。
        ini_set('precision',14);

        $gen_db->begin();

        $msg = "";
        $errorMsg = array();

        // 読み込み

        $totalCount = 1;
        $errorCount = 0;
        $uniqueArray = array();
        $headerArray = array();
        $headerChache = "";
        $lineNo = 1;

        // ちなみに、index.php で ini_set("auto_detect_line_endings", true); しているので、Macで作成された
        // ファイル（改行コード：CR）であっても正しく読み込める。

        // ヘッダ読み飛ばし
        for ($i=1; $i<=$headerNumberOfLines; $i++){
            fgets($fp);
        }
        
        // 行ループ
        while ($data = fgets($fp)) {
            // 機種依存文字のチェック（SJISのみ）
            //    機種依存文字があるとLinuxサーバーでの動作に問題がある（UTF変換したときに文字化けしたり
            //    カンマを読み違えたりする。ちなみにFormからのPOST/GETは文字変換が発生しないため大丈夫）
            $dependPos = -1;
            if (GEN_CSV_IMPORT_FROM_ENCODING == "SJIS")
                $dependPos = Gen_String::checkSjisDependencyChar($data);
            if ($dependPos >= 0) {
                // 機種依存文字発見
                // ホントはここで該当文字を表示したいが、機種依存文字なのでSJIS⇒UTF変換できず、うまくいかない。

                $arr = explode(",", $data);
                $pos = 0;
                $colCount2 = 1;
                foreach ($arr as $col) {
                    $endPos = $pos + mb_strlen($col, "SJIS");
                    if ($dependPos <= $endPos) {
                        // 発見
                        break;
                    }
                    $pos = $endPos + 1;
                    $colCount2++;
                }

                $errorMsg[] = array($totalCount + 1, sprintf(_g("この行のデータに機種依存文字が含まれているため、登録できません。[項目 %1\$s, 文字位置 %2\$s]"), $colCount2, ($dependPos - $pos + 1)));

                $errorCount++;
                $totalCount++;
                continue;
            }

            // 文字コードをUTFに変換
            $data = mb_convert_encoding($data, "UTF-8", GEN_CSV_IMPORT_FROM_ENCODING);

            // 行末の改行コードをカット（ereg_replaceだと「行末の改行」がうまく検出できない）
            if (substr($data, -1) == "\n") {
                $data = substr($data, 0, strlen($data) - 1);
            }
            if (substr($data, -1) == "\r") {
                $data = substr($data, 0, strlen($data) - 1);
            }

            // 文字列をカンマで分割して配列に格納
            $dataArray = Gen_Csv::splitExt($data, $isTabSeparate);

            // データのサニタイズ（quoteParam）
            Gen_Csv::_quoteParam($dataArray);

            $param = array();
            $i = 0;
            $cnt = count($dataArray);
            $dupErr = '';
            $isFirstRegist = true;
            foreach ($colArray as $key => $col) {
                // 条件の後半は、最終項目のデータがnullの時にデータ配列の数がひとつ少なくなることがあるために入れた
                if (isset($col['field']) && ($i < $cnt || $format)) {
                    // データのセット
                    if (isset($format->$col['field'])) {
                        // CSVフォーマットが指定されているとき
                        list($type, $value) = explode("[sep]", $format->$col['field']);
                        switch ($type) {
                            case "0":   // 列参照
                                if (Gen_String::isNumeric($value) && $value <= count($dataArray)) {
                                    $regValue = $dataArray[$value - 1];
                                } else {
                                    $regValue = "";
                                }
                                break;
                            case "1":   // 固定値/数式
                                try {
                                    $matches = "";
                                    $res = $value;
                                    // 数式内の [列番号] を列の値に変換
                                    if (preg_match_all("(\[[0-9]+\])", $res, $matches) > 0) {
                                        foreach ($matches[0] as $match) {
                                            $matchStr = $match;
                                            $matchStr = str_replace('[', '', $matchStr);
                                            $matchStr = str_replace(']', '', $matchStr);
                                            if (Gen_String::isNumeric($matchStr) && $matchStr <= count($dataArray)) {
                                                $res = str_replace($match, "\"" . $dataArray[$matchStr - 1] . "\"", $res);
                                            }
                                        }
                                    }
                                    // 数式を計算
                                    $regValue = $calculation->calculateFormula($res);
                                } catch(Exception $e) {
                                    // フォーマット登録時にエラーチェックしているはずだが一応
                                    $regValue = "";
                                }
                                break;
                            case "2":   // ブランク
                                $regValue = "";
                                break;
                            case "3":   // ファイル名
                                if (isset($_FILES['uploadFile']['name'])) {
                                    $regValue = $_FILES['uploadFile']['name'];
                                } else {
                                    $regValue = "";
                                }
                                break;
                            default:
                                $regValue = "";
                        }
                    } else {
                        $regValue = $dataArray[$i];
                    }
                    
                    // 日付の変換
                    if (isset($col['isDate']) && $col['isDate']) {
                        if (!Gen_String::isDateString($regValue)) {
                            if (Gen_String::isNumeric($regValue)) {
                                if (strlen($regValue) == 8) {
                                    // yyyymmdd
                                    $regValue = substr($regValue, 0, 4) . "/" . substr($regValue, 4, 2) . "/" . substr($regValue, 6,2);
                                } else {
                                    // yymmdd
                                    $regValue = "20" . substr($regValue, 0, 2) . "/" . substr($regValue, 2, 2) . "/" . substr($regValue, 4,2);
                                }
                            } else {
                                // yyyy-mm-dd
                                $regValue = str_replace("-", "/", $regValue);
                                // yy/mm/dd
                                $dateArr = explode("/", $regValue);
                                if (strlen($dateArr[0]) == 2) {
                                    $dateArr[0] = "20" . $dateArr[0];
                                    $regValue = join("/", $dateArr);
                                }
                            }
                        }
                    }

                    // headerチェック
                    // headerフラグが設定されている連続するデータは同一headerデータとして扱う
                    // 連続していないデータはファイル内のuniqueチェック対象となる
                    if (isset($col['header']) && $col['header']) {
                        // headerが指定されている時
                        if ($regValue != "") {
                            // header_id取得
                            if (isset($col['table']) && $col['table'] != "" && isset($col['id']) && $col['id'] != "") {
                                $query = "select {$col['id']} from {$col['table']} where {$col['field']} ='{$regValue}'";
                                $param[$col['id']] = $gen_db->queryOneValue($query);
                            }

                            // 複数明細登録
                            if ($headerChache === $regValue) {
                                $isFirstRegist = false;
                                $param['gen_line_no'] = $lineNo;
                                // 初期登録されていないデータである場合、既存のデータであるため初期化してエラー検知とする
                                if (!in_array($regValue, $headerArray)) {
                                    $param[$col['id']] = null;  // 初期化
                                }

                            // 初期登録
                            } else {
                                $lineNo = 1;    // 初期化
                                $param['gen_line_no'] = $lineNo;
                                // 初期登録時にid取得できた場合、既存の登録データであるため初期化してエラー検知とする
                                if (isset($param[$col['id']]) && is_numeric($param[$col['id']])) {
                                    $param[$col['id']] = null;          // 初期化
                                } else {
                                    $headerArray[] = $regValue;    // CSVデータとして保持
                                }
                            }

                            // 行番号インクリメント
                            $lineNo++;
                        }
                        // キャッシュ
                        $headerChache = $regValue;
                    }

                    // uniqueチェック
                    if (isset($col['unique']) && $col['unique'] && $regValue != "" && $isFirstRegist) {
                        if (isset($uniqueArray[$col['field']][$regValue])) {
                            $dupErr = sprintf(_g("CSVファイル内で%1\$sが重複しています（%2\$s行目と%3\$s行目の「%4\$s」）。どちらかを変更してください。"), $col['label'], $uniqueArray[$col['field']][$regValue], $totalCount + 1, $regValue);
                            break;
                        }
                        $uniqueArray[$col['field']][$regValue] = $totalCount + 1;
                    }
                    
                    // 変換&チェック終了
                    $param[$col['field']] = $regValue;
                    $i++;
                }
            }

            // uniqueエラー
            if ($dupErr != "") {
                $errorMsg[] = array($totalCount + 1, $dupErr);

                $dupErr = "";
                $errorCount++;
                $totalCount++;
                continue;
            }

            // model
            if ($isFirstRegist) {
                unset($model);
                $model = new $modelName();
            }
            if ($allowUpdate) {
                $model->csvUpdateMode = true;     // 上書き許可
            }
            $model->setDefault($param, "csv");
            if (method_exists($model, "beforeLogic")) {
                $model->beforeLogic($param);
            }

            // convert
            $converter = new Gen_Converter($param);
            $model->convert($converter);

            // validate
            $validator = new Gen_Validator($param);
            $model->validate($validator, $param);

            if ($validator->hasError()) {
                foreach ($validator->errorList as $error) {
                    $errorMsg[] = array($totalCount + 1, $error);
                }
                $errorCount++;
            } else {
                // regist
                // ここでエラー数のチェックをしているのは、1件でもエラーが発生していた場合は
                // どうせ全体を失敗させるので regist() しても無駄だということのほかに、
                // 複数明細登録（例：受注）の際、1行目で valid error になったときに2行目を
                // regist してしまうとSQLエラーになることがある、という理由もある。
                if ($errorCount == 0) {
                    $model->regist($param, $isFirstRegist);
                }
            }
            unset($converter);
            unset($validator);

            $totalCount++;

            // 件数制限
            if ($_SESSION['user_id'] !== -1 && $totalCount > GEN_CSV_IMPORT_MAX_COUNT + 1) {
                $msg = sprintf(_g("データの件数が %s 件を超えています。データの件数を減らしてください。データは1件も登録されませんでした。"), GEN_CSV_IMPORT_MAX_COUNT);
                $gen_db->rollback();
                return array(false, 0, $msg);
            }
        }
        fclose($fp);

        // 1行でもエラーになったら全体を失敗させる
        if ($errorCount > 0) {
            $gen_db->rollback();
            $successCount = 0;
            $msg = array();            
            foreach ($errorMsg as $e) {
                $msg[] = $e;    // 0: 行、1: メッセージ
            }
        } else {
            $gen_db->commit();
            $successCount = $totalCount - $errorCount - 1;
            $msg = sprintf(_g("%s件のデータを登録しました。"), $successCount);
        }

        // 成功フラグ, 成功件数, メッセージ
        return array($errorCount == 0, $successCount, $msg);
    }

    //************************************************
    // CSVデータのインポート（旧方式）
    //************************************************
    // 09iで使用していた方式。
    // 10iでは、ほとんどの画面（ListBaseの画面）は上のCSVImportForModel()を使用するようになったため、このfunctionを
    // 使用しているのはBomのみ。
    // 引数
    //  $fileName       インポートするファイル名（フルパス指定）
    //  $prepareInsert  データをINSERTするPREPAREステートメント名。
    //  $colCount       データ項目数。インポートデータにこれ以上の列があった場合は無視される。
    //  $className      下の2つのコールバック関数が所属するクラス名
    //  $checkFunc      登録データをチェック&変換するためのコールバック関数の名前。
    //                  コールバック関数は、checkCsvData($line, &$dataArray, &$gen_db) のようにする。
    //                  $lineは行数、$dataArrayは1行分のデータ（1次元配列）。
    //                  チェックOKなら変換後の$dataArray、Badならメッセージ文字列を返すようにする。
    //  $afterFunc      1行登録するたびに呼び出されるコールバック関数の名前。
    //                  コールバック関数は、afterLogic(&$dataArray, &$gen_db) のようにする。
    //                  登録後処理の必要がなければ空文字を渡しておく。
    //  $isAllowUpdate  上書き許可（true/false）
    //  $prepareUpdate  データをUPDATEするPREPAREステートメント名。$isAllowUpdateがtrueのとき以外は無意味
    //  $keyTable       上書き判断のキーとなるテーブル名。$isAllowUpdateがtrueのとき以外は無意味
    //  $keyArray       上書き判断のキーとなるフィールドと列番号の配列。$isAllowUpdateがtrueのとき以外は無意味
    //                      array('フィールド名' => '列番号')  ※列番号は0はじまり
    //  $gen_db
    // 戻り
    //  成功すればメッセージ文字列（件数等）、失敗すればfalse

    static function CsvImport($fileName, $prepareInsert, $colCount, $className, $checkFunc, $afterFunc, $isAllowUpdate, $prepareUpdate, $keyTable, $keyArray)
    {
        global $gen_db;

        // 実行時間制限を変更。PHPがセーフモードの場合は効かない
        // このスクリプトの中だけで有効
        set_time_limit(GEN_CSV_IMPORT_MAX_SECOND);

        if (!($fp = fopen($fileName, 'r'))) {
            return false;
        }

        $gen_db->begin();

        $msg = "";
        $errorMsg = "";

        // 読み込み

        $totalCount = 1;
        $errorCount = 0;

        fgets($fp);        // 1行目読み飛ばし
        // 行ループ
        while ($data = fgets($fp)) {
            // 機種依存文字のチェック（SJISのみ）
            //    機種依存文字があるとLinuxサーバーでの動作に問題がある（UTF変換したときに文字化けしたり
            //    カンマを読み違えたりする。ちなみにFormからのPOST/GETは文字変換が発生しないため大丈夫）
            if (GEN_CSV_IMPORT_FROM_ENCODING == "SJIS")
                $dependPos = Gen_String::checkSjisDependencyChar($data);
            if ($dependPos >= 0) {
                // 機種依存文字発見
                // ホントはここで該当文字を表示したいが、機種依存文字なのでSJIS⇒UTF変換できず、うまくいかない。
                // 見出し行も行数に含める

                $arr = explode(",", $data);
                $pos = 0;
                $colCount2 = 1;
                foreach ($arr as $col) {
                    $endPos = $pos + mb_strlen($col, "SJIS");
                    if ($dependPos <= $endPos) {
                        // 発見
                        $errorMsg .= $colCount2 . ", " . _g("文字位置") . " " . ($dependPos - $pos + 1) . "]<br>";
                        break;
                    }
                    $pos = $endPos + 1;
                    $colCount2++;
                }
                $errorMsg[] = array($totalCount + 1, sprintf(_g("この行のデータに機種依存文字が含まれているため、登録できません。[項目 %1\$s, 文字位置 %2\$s]"), $colCount2, ($dependPos - $pos + 1)));

                $errorCount++;
                $totalCount++;
                continue;
            }

            // 文字コードをUTFに変換
            $data = mb_convert_encoding($data, "UTF-8", GEN_CSV_IMPORT_FROM_ENCODING);

            // 行末の改行コードをカット（ereg_replaceだと「行末の改行」がうまく検出できない）
            if (substr($data, -1) == "\n")
                $data = substr($data, 0, strlen($data) - 1);
            if (substr($data, -1) == "\r")
                $data = substr($data, 0, strlen($data) - 1);

            // 文字列をカンマで分割して配列に格納
            $dataArray = Gen_Csv::splitExt($data);

            // データのサニタイズ（quoteParam）
            Gen_Csv::_quoteParam($dataArray);

            // 登録前処理
            // （おもに、データをチェック(およびコード変換)してデータベースに登録する処理）
            $res = call_user_func(array($className, $checkFunc), $totalCount, $dataArray, $isAllowUpdate, $gen_db);

            if (is_array($res)) {
                $prepare = $prepareInsert;

                // データベースに登録
                if ($prepare != "") {
                    $dataArray = $res;
                    $entryData = "";

                    // 登録データの準備。 $colCount列目以上のデータは無視される。
                    for ($i = 0; $i < $colCount; $i++) {
                        if ($entryData != "") {
                            $entryData .= ",";
                        }
                        if ($dataArray[$i] === "null") {
                            $entryData .= "null";
                        } else {
                            // データはすでに一括サニタイズしてあるため、ここでquoteしなくてもよい。
                            $entryData .= "'{$dataArray[$i]}'";
                        }
                    }

                    if (is_array($keyArray)) {
                        $keyWhere = "";
                        foreach ($keyArray as $field => $colNumber) {
                            if ($keyWhere != "") {
                                $keyWhere .= " and ";
                            }
                            $keyWhere .= "{$field} = '{$dataArray[$colNumber]}'";
                        }
                        $query = "select * from {$keyTable} where {$keyWhere}";
                        if ($gen_db->existRecord($query)) {
                            // 上書き
                            if ($isAllowUpdate) {
                                $prepare = $prepareUpdate;
                            }
                        }
                    }

                    // 登録処理
                    $gen_db->query("EXECUTE {$prepare} ({$entryData})");
                }

                // 登録後処理
                if ($className != "" && $afterFunc != "") {
                    call_user_func(array($className, $afterFunc), $dataArray, $gen_db);
                }
            } else {
                // エラーメッセージが返ってきたとき
                $errorMsg[] = array($totalCount + 1, $res);
                //$errorMsg .= $res . "<BR>";
                $errorCount++;
            }

            $totalCount++;

            // 件数制限
            if ($_SESSION['user_id'] !== -1 && $totalCount > GEN_CSV_IMPORT_MAX_COUNT + 1) {
                $msg = sprintf(_g("データの件数が %s 件を超えています。データの件数を減らしてください。データは1件も登録されませんでした。"), GEN_CSV_IMPORT_MAX_COUNT);
                $gen_db->rollback();
                return array(false, 0, $msg);
            }
        }

        fclose($fp);
        
        // 1行でもエラーになったら全体を失敗させる
        if ($errorCount > 0) {
            $gen_db->rollback();
            $successCount = 0;
            $msg = array();            
            foreach ($errorMsg as $e) {
                $msg[] = $e;    // 0: 行、1: メッセージ
            }
        } else {
            $gen_db->commit();
            $successCount = $totalCount - $errorCount - 1;
            $msg = sprintf(_g("%s件のデータを登録しました。"), $successCount);
        }

//        $gen_db->commit();
//
//        $msg = sprintf(_g("%s件のデータを登録しました。"), $totalCount - $errorCount - 1) . "<BR>";
//        if ($errorCount > 0) {
//            $msg .= sprintf(_g("%s件のデータは下記のエラーのため登録されませんでした。"), $errorCount) . "<BR><BR>";
//            $msg .= $errorMsg;
//        }

        return $msg;
    }

    //************************************************
    // 文字列をカンマで分割して配列に格納する(private)
    //************************************************
    // 基本的には $arr = explode(",", $data); の1行で済む処理なのだが、文字列内に
    // ダブルコーテーションが含まれていたときに、Excelと同じ基準で解釈するように
    // するために複雑化している。
    // 「Excelと同じ基準」とは・・
    //     ・項目の先頭にダブルコーテーションがあるとき、「ダブルコーテーション囲み」のはじまり
    //       とみなす。項目先頭以外の場所にあるダブルコーテーションは、囲みの始まりとはみなさない。
    //     ・ダブルコーテーション囲みが始まっている状態で、次に出てきたダブルコーテーションは
    //       ダブルコーテーション囲みのおわりとみなす（項目末尾でなくても）。ただし2つ続きのダブ
    //       ルコーテーションは終わりとみなさない（ダブルコーテーションのクオートとみなされる）
    //     ・ダブルコーテーション囲みのはじまりと終わりのダブルコーテーションは、項目の一部とは
    //       みなされない（消去される）
    //     ・ダブルコーテーション囲みの中のカンマは、区切りではなく項目の一部と解釈する。
    //     ・ダブルコーテーション囲みの中でダブルコーテーションが2つ続けて出てきたときは、
    //       クオートされたダブルコーテーションとみなし、ダブルコーテーション1つに変換する。
    //       囲みの外で連続ダブルコーテーションがあってもクオートとはみなさない。
    //  15i: タブ区切りに対応（第2引数）

    static function splitExt($data, $isTab = false)
    {
        $separator = ($isTab ? "\t" : ",");
        if (strpos($data, "\"") === FALSE) {
            // 行にダブルコーテーションが含まれていないときの処理
            $dataArray = explode($separator, $data);   
        } else {
            // 行内にダブルコーテーションが含まれていたときの処理
            $x = 0;
            $y = 0;
            $bInner = false;
            $dataArray = array();
            $dataArray[$y] = "";

            while ($x < strlen($data)) {
                $pickup = substr($data, $x, 1);

                if ($bInner) {
                    // ダブルコーテーション囲みの内部
                    if ($pickup == "\"") {
                        // ダブルコーテーション発見
                        $x++;
                        if ($x > strlen($data))
                            break;
                        $pickup = substr($data, $x, 1);

                        if ($pickup == "\"") {
                            // 次の文字もダブルコーテーションだったときは
                            // 項目にダブルコーテーションをひとつ挿入
                            $dataArray[$y] .= "\"";
                        } else {
                            // 次の文字がダブルコーテーション以外だったときは
                            // ダブルコーテーション囲みの終わり
                            $bInner = false;
                            $x--;
                        }
                    } else {
                        // ダブルコーテーション以外
                        // ここではカンマも項目区切りとみなさない
                        $dataArray[$y] .= $pickup;
                    }
                } else {
                    // ダブルコーテーション囲みの外
                    if ($pickup == "\"" && $dataArray[$y] == "") {
                        // ダブルコーテーションがあり、かつ項目の始まりだったら
                        // ダブルコーテーション囲みの開始（ダブルコーテーション自体は
                        // 項目に含めない）
                        $bInner = true;
                    } else if ($pickup == $separator) {
                        // カンマがあれば項目区切り
                        $y++;
                        $dataArray[$y] = "";
                    } else {
                        // その他は項目の一部
                        $dataArray[$y] .= $pickup;
                    }
                }
                $x++;
            }
        }

        return $dataArray;
    }

    //************************************************
    // 配列内のデータをすべてサニタイズ（quoteParam）する(private)
    //************************************************
    static function _quoteParam(&$arr)
    {
        global $gen_db;

        for ($i = 0; $i < count($arr); $i++) {
            $arr[$i] = $gen_db->quoteParam($arr[$i]);
        }
    }

}