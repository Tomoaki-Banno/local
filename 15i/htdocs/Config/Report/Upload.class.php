<?php

class Config_Report_Upload
{

    function execute(&$form)
    {
        // トークンの確認（CSRF対策）
        //　　Ajax用のものを流用。トークンについての詳細はAjaxBaseのコメントを参照。
        if (!isset($form['gen_ajax_token']) || $_SESSION['gen_ajax_token'] != $form['gen_ajax_token']) {
            $form['response_noEscape'] = json_encode(array("status" => "tokenError", "success" => false, "msg" => ""));
            return 'simple.tpl';
        }

        // 帳票編集画面のアクセス権が「アクセス禁止」ならこのクラスが実行されることはないが、
        // 「読み取りのみ」の場合は実行されてしまう場合があるので、チェックしておく必要がある。
        $permission = Gen_Auth::sessionCheck("config_report");
        if ($permission != 2) {
            $form['response_noEscape'] = json_encode(array("msg" => _g("アクセス権がありません。")));
            return 'simple.tpl';
        }

        // テンプレートの登録処理
        $errorMsg = self::registTemplate($form);

        if ($errorMsg !== "") {
            if (!is_array($errorMsg)) {
                $errorMsg = array($errorMsg);
            }
        }

        $form['response_noEscape'] = json_encode(array("msg" => $errorMsg));
        return 'simple.tpl';
    }

    function registTemplate($form)
    {
        global $gen_db;

        // アップロードされたファイルのセキュリティチェック
        if (!is_uploaded_file(@$_FILES['uploadFile']['tmp_name']) || @$_FILES['uploadFile']['size'] == 0)
            return _g("テンプレートファイルが正しくありません。");

        // アップロードされたファイルの拡張子チェック
        if (mb_substr($_FILES['uploadFile']['name'], -4) !== ".xls")
            return _g("テンプレートファイルの拡張子が正しくありません。正しいファイル（.xls）であるか確認してください。");

        if ($_FILES['uploadFile']['size'] > GEN_MAX_TEMPLATE_FILE_SIZE)
            return _g("テンプレートファイルのサイズが大きすぎます。正しいテンプレートであるか確認してください。また、テンプレート内の不要なシートやセルは削除してください。");

        // PHPがセーフモードの場合は効かない。
        // このスクリプトの中だけで有効。
        set_time_limit(600);

        // レポートクラスからタグリストを取得
        require_once(Gen_File::safetyPathForAction($form['reportAction']));
        $obj = new $form['reportAction'];
        $param = $obj->getReportParam($form);
        $tag = array();
        foreach ($param['tagList'] as $tagInfo) {
            if (count($tagInfo) > 1) {    // タグカテゴリ見出しは除く
                $tag[] = $tagInfo[0];
            }
        }

        // テンプレートのチェック（エクセルファイルかどうか）
        // いまのところ .xlsx は非対応
        require_once(ROOT_DIR . 'PHPExcel/PHPExcel/IOFactory.php');
        $reader = PHPExcel_IOFactory::createReader('Excel5');
        if (!$reader->canRead($_FILES['uploadFile']['tmp_name'])) {
            return _g("テンプレートファイルが正しくありません。.xls形式のテンプレートファイルを指定してください。") . "<br>" . _g("（Excel 2007以降をご利用の方は「Excel 97-2003 ブック」形式でファイルを保存してください。）");
        }

        // load時にPHPExcel内でnoticeが発生したら、エラーとして停止する。
        // シートに画像が含まれている場合にPHPExcelがハングしてしまうことがある問題への対策。
        // かなり苦肉の策なので、PHPExcelのバグが解消された時点でここも修正したほうがいい。
        set_error_handler(array('Config_Report_Upload', 'uploadErrorHandler'));
        try {
            $xl = $reader->load($_FILES['uploadFile']['tmp_name']);
        } catch (Exception $e) {
            restore_error_handler();
            return _g("テンプレートの読み込み時にエラーが発生しました。シート内に含まれている画像をいったん削除し、再度「図の挿入」を行ってからアップロードしてみてください。");
        }
        restore_error_handler();

        // 15iでは不要なシートが含まれていると帳票発行時に問題がある。（13iでは問題なかった）
        // また、タグチェック処理は2シート分しか行われない。3シート以上含まれていると、無関係なシートを処理対象にしてしまうことがある。
        if ($xl->getSheetCount() > 2) {
            return _g("テンプレートに3つ以上のシートが含まれています。シートは1つ（2ページ以降のフォーマットを指定する場合は2つ）だけにしてください。");
        }

        // テンプレートのタグチェック
        $sheetIndexMax = ($xl->getSheetCount() > 1 ? 1 : 0);
        $errorMsg = array();
        for($sheetIndex=0; $sheetIndex<=$sheetIndexMax; $sheetIndex++) {
            $xl->setActiveSheetIndex($sheetIndex);
            $sheet = $xl->getActiveSheet();
            $yMax = $sheet->getHighestRow();      // 行：　1はじまり
            $xMax = PHPExcel_Cell::columnIndexFromString($sheet->getHighestColumn());   // 列：　0はじまり
            $mergeCells = $sheet->getMergeCells();
            $firstRepeatTag = false;
            for ($x = 0; $x <= $xMax; $x++) {   // $xは0はじまり
                for ($y = 1; $y <= $yMax; $y++) {
                    $value = $sheet->getCellByColumnAndRow($x, $y)->getValue();
                    // タグがあるセルだけを処理
                    if (strpos($value, "[[") === FALSE)
                        continue;
                    // タグを抽出
                    $matches = "";
                    if (preg_match_all("(\[\[[^\]]*\]\])", $value, $matches) == 0)
                        continue;
                    foreach ($matches[0] as $match) {
                        $colName = $match;
                        if ($colName == '[[ページ]]')
                            continue;
                        if ($colName == '[[Repeat]]') {
                            $a1 = self::xytoa($x + 1, $y);
                            $x1 = false;
                            foreach($mergeCells as $merge) {
                                if (substr($merge, 0, strlen($a1) + 1) == $a1 . ":") {
                                    $mp = explode(":", $merge);
                                    list($x1, $y1) = self::A1ToR1C0($mp[1]);
                                    break;
                                }
                            }
                            if (!$x1) {     // Repeatタグのセルがマージされていなかった場合
                                $x1 = $x;
                                $y1 = $y;
                            }
                            if ($firstRepeatTag) {
                                // 2つめ以降のRepeatタグ
                                if ($x != $firstRepeatTag[0]) {
                                    $errorMsg[] = array(self::xytoa($x + 1, $y), sprintf(_g("[[Repeat]]タグはすべて同じ列（横位置）に配置する必要があります。"), $match));
                                } else if (($x1 - $x + 1) != $firstRepeatTag[2] || ($y1 - $y + 1) != $firstRepeatTag[3]) {
                                    $errorMsg[] = array(self::xytoa($x + 1, $y), sprintf(_g("[[Repeat]]タグはすべて同じサイズにセル結合する必要があります。"), $match));
                                }
                            } else {
                                // 最初のRepeatタグ
                                $firstRepeatTag = array($x, $y, ($x1 - $x + 1), ($y1 - $y + 1));
                            }
                            continue;
                        }
                        if (substr($colName, 0, 10) == "[[orderby:") {
                            if (isset($param['denyOrderby'])) {
                                $errorMsg[] = array(self::xytoa($x + 1, $y), sprintf(_g("この帳票では[[orderby:]]タグを指定することはできません。レコードの並び順は固定となります。"), $match));
                            } else if ($x != 0 || $y != 1) {
                                $errorMsg[] = array(self::xytoa($x + 1, $y), sprintf(_g("[[orderby:]]タグは必ずシートの左上（A1セル）に指定する必要があります。"), $match));
                            } else {
                                $orderbyCol = str_replace("[[orderby:", "", str_replace("]]", "", $colName));
                                $orderbyColArr = explode(',', $orderbyCol);
                                foreach ($orderbyColArr as $orderbyColName) {
                                    $orderbyColName = str_replace(' desc', '', $orderbyColName);
                                    if (!in_array($orderbyColName, $tag))
                                        $errorMsg[] = array(self::xytoa($x + 1, $y), sprintf(_g("orderbyタグの中で指定されたタグ %s は使用できません。"), $orderbyColName));
                                }
                            }
                            continue;
                        }
                        if (substr($colName, 0, 10) == "[[pagekey:") {
                            if ($x != 0 || $y != 1) {
                                $errorMsg[] = array(self::xytoa($x + 1, $y), sprintf(_g("[[pagekey:]]タグは必ずシートの左上（A1セル）に指定する必要があります。"), $match));
                            } else {
                                $pageKeyCol = str_replace("[[pagekey:", "", str_replace("]]", "", $colName));
                                if ($pageKeyCol != "" && !in_array($pageKeyCol, $tag))
                                    $errorMsg[] = array(self::xytoa($x + 1, $y), sprintf(_g("pagekeyタグの中で指定されたタグ %s は使用できません。"), $pageKeyCol));
                            }
                            continue;
                        }
                        if (substr($colName, 0, 9) == "[[pdfkey:") {
                            if ($x != 0 || $y != 1) {
                                $errorMsg[] = array(self::xytoa($x + 1, $y), sprintf(_g("[[pdfkey:]]タグは必ずシートの左上（A1セル）に指定する必要があります。"), $match));
                            } else {
                                $pdfKeyCol = str_replace("[[pdfkey:", "", str_replace("]]", "", $colName));
                                if ($pdfKeyCol != "" && !in_array($pdfKeyCol, $tag))
                                    $errorMsg[] = array(self::xytoa($x + 1, $y), sprintf(_g("pdfkeyタグの中で指定されたタグ %s は使用できません。"), $pdfKeyCol));
                            }
                            continue;
                        }
                        if (substr($colName, 0, 10) == "[[groupby:") {
                            if ($x != 0 || $y != 1) {
                                $errorMsg[] = array(self::xytoa($x + 1, $y), sprintf(_g("[[groupby:]]タグは必ずシートの左上（A1セル）に指定する必要があります。"), $match));
                            } else {
                                $groupbyCol = str_replace("[[groupby:", "", str_replace("]]", "", $colName));
                                $groupbyColArr = explode(',', $groupbyCol);
                                foreach ($groupbyColArr as $groupbyColName) {
                                    $groupbyColName = str_replace(' desc', '', $groupbyColName);
                                    if (!in_array($groupbyColName, $tag))
                                        $errorMsg[] = array(self::xytoa($x + 1, $y), sprintf(_g("groupbyタグの中で指定されたタグ %s は使用できません。"), $groupbyColName));
                                }
                            }
                            continue;
                        }
                        if (substr($colName, 0, 12) == "[[querymode:") {
                            if ($x != 0 || $y != 1) {
                                $errorMsg[] = array(self::xytoa($x + 1, $y), sprintf(_g("[[querymode:]]タグは必ずシートの左上（A1セル）に指定する必要があります。"), $match));
                            } else {
                                if (!Gen_String::isNumeric(str_replace("[[querymode:", "", str_replace("]]", "", $colName)))) {
                                    $errorMsg[] = array(self::xytoa($x + 1, $y), sprintf(_g("[[querymode:]]タグの指定が正しくありません。"), $match));
                                }
                            }
                            continue;
                        }
                        if (substr($colName, 0, 11) == "[[pagecopy:") {
                            if ($x != 0 || $y != 1) {
                                $errorMsg[] = array(self::xytoa($x + 1, $y), sprintf(_g("[[pagecopy:]]タグは必ずシートの左上（A1セル）に指定する必要があります。"), $match));
                            } else {
                                if (!Gen_String::isNumeric(str_replace("[[pagecopy:", "", str_replace("]]", "", $colName)))) {
                                    $pageKeyCol = str_replace("[[pagecopy:", "", str_replace("]]", "", $colName));
                                    if ($pageKeyCol != "" && !in_array($pageKeyCol, $tag))
                                        $errorMsg[] = array(self::xytoa($x + 1, $y), sprintf(_g("pagecopyタグの中で指定されたタグ %s は使用できません。"), $pageKeyCol));
                                }
                            }
                            continue;
                        }
                        if (substr($colName, 0, 12) == "[[papersize:") {
                            if ($x != 0 || $y != 1) {
                                $errorMsg[] = array(self::xytoa($x + 1, $y), sprintf(_g("[[papersize:]]タグは必ずシートの左上（A1セル）に指定する必要があります。"), $match));
                            } else {
                                $arr = explode(',', str_replace("[[papersize:", "", str_replace("]]", "", $colName)));
                                if ((count($arr) < 2 || count($arr) > 3) || !Gen_String::isNumeric($arr[0]) || !Gen_String::isNumeric($arr[1])) {
                                    $errorMsg[] = array(self::xytoa($x + 1, $y), sprintf(_g("[[papersize:]]タグの指定が正しくありません。"), $match));
                                }
                            }
                            continue;
                        }
                        $colName = str_replace('[[', '', $colName);
                        $colName = str_replace('barcode:', '', $colName);
                        $colName = str_replace('image:', '', $colName);
                        $colName = str_replace('total:', '', $colName);
                        $colName = str_replace(']]', '', $colName);
                        if (!in_array($colName, $tag))
                            $errorMsg[] = array(self::xytoa($x + 1, $y), sprintf(_g("タグ %s は使用できません。"), $match));
                    }
                }
            }
        }
        if (count($errorMsg) > 0) {
            return $errorMsg;
        }

        // 保存ファイル名の決定
        // basename() は使用しない。日本語の文字化け問題があるため。
        $fileName = $_FILES['uploadFile']['name'];
        // rev.20150127 comment out
        // ファイルがUTF-8で渡されているため、SJISの機種依存文字の検出対応をする必要がない。
        //$fileName = Gen_String::cutSjisDependencyChar($fileName);
        $fileName = $gen_db->quoteParam($fileName);
        // カンマは全角カンマに変換する
        $fileName = str_replace(',', '、', $fileName);

        // 既存テンプレートチェック
        $templateInfoArr = Gen_PDF::getTemplateInfo($form['report']);
        $info = $templateInfoArr[2];
        $storage = new Gen_Storage("ReportTemplates");
        foreach ($info as $no => $infoOne) {
            if ($infoOne['file'] === $fileName) {
                if ($infoOne['isDefault'] === "true") {
                    // システムテンプレートは上書き不可
                    return _g("システム標準のテンプレートを上書きすることはできません。ファイル名を変更してください。");
                } else {
                    // 既存テンプレートを削除
                    $storage->delete($form['report'] . "/" . $infoOne['file']);
                    unset($info[$no]);
                }
            }
        }

        // 登録処理
        $storage->put($_FILES['uploadFile']['tmp_name'], true, $form['report'] . "/" . $fileName);
        unlink($_FILES['uploadFile']['tmp_name']);

        // gen_templates.dat の更新
        $info[] = array(
            "file" => $fileName,
            "comment" => str_replace(",", "、", $form['comment']),
            "isDefault" => "false",
            "uploader" => $_SESSION['user_name'],
        );
        Gen_PDF::putTemplateInfo($form['report'], $templateInfoArr[0], $info);

        // 選択テンプレート情報の更新
        Gen_PDF::updateSelectedTemplateInfo($form['report'], $fileName);

        // データアクセスログ
        Gen_Log::dataAccessLog($form['reportTitle'] . _g("帳票テンプレート登録"), "", "[" . _g("ファイル名") . "] $fileName");

        return "";
    }

    // エクセルのXY形式のセル位置をA1形式に変換
    function xytoa($x, $y)
    {
        $str = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        if ($x <= 26) {
            return substr($str, $x - 1, 1) . $y;
        } else {
            $x1 = substr($str, (int) (($x - 1) / 26) - 1, 1);
            $x2 = substr($str, $x % 26 - 1, 1);
            return $x1 . $x2 . $y;
        }
    }

    // セル位置をA1形式からR1C0形式に変換（結果は配列。0がRow(x)、1がCol(y)）
    function A1ToR1C0($a1)
    {
        $res = array();
        for ($i = 0; $i < strlen($a1); $i++) {
            if (is_numeric(substr($a1, $i, 1))) {
                $res[0] = PHPExcel_Cell::columnIndexFromString(substr($a1, 0, $i)) - 1;
                break;
            }
        }
        $res[1] = (int) substr($a1, $i);
        return $res;
    }

    // ファイルオープン時のnoticeを捕捉するためのハンドラ
    function uploadErrorHandler($errno, $errstr, $errfile, $errline)
    {
        throw new Exception($errstr, $errno);
    }
}
