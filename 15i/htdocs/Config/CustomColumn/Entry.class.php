<?php

class Config_CustomColumn_Entry
{
    function execute(&$form)
    {
        global $gen_db;

        // CSRF対策
        if (!Gen_Reload::reloadCheck(@$form['gen_page_request_id'])) {
            return 'action:Config_CustomColumn_Edit';
        }
        
        // 既存値の読み出し（カスタム項目以外の変換値を消されないように）
        $wordConv = array();
        if (isset($_SESSION['gen_setting_company']->wordconvert)) {
            $wordConv = $_SESSION['gen_setting_company']->wordconvert;
        }
        if (is_object($wordConv)) {
            $wordConv = get_object_vars($wordConv);
        }
        
        // セレクタ選択肢の読み出し
        $customColumnOptions = array();
        if (isset($_SESSION['gen_setting_company']->customcolumnoptions)) {
            $customColumnOptions = $_SESSION['gen_setting_company']->customcolumnoptions;
        }
        if (is_object($customColumnOptions)) {
            $customColumnOptions = get_object_vars($customColumnOptions);
        }
        
        // 明細フラグの読み出し
        $customColumnIsDetail = array();
        if (isset($_SESSION['gen_setting_company']->customcolumnisdetail)) {
            $customColumnIsDetail = $_SESSION['gen_setting_company']->customcolumnisdetail;
        }
        if (is_object($customColumnIsDetail)) {
            $customColumnIsDetail = get_object_vars($customColumnIsDetail);
        }
        
        // 登録
        //  $wordConv から、カスタム項目の値はすべてunsetする。（カスタム項目以外の用語変換の値だけ残る）
        //  $customWordConv にあらためてカスタム項目の値が作成される。
        //　このように変数を分けているのは、カスタム項目の値が用語変換より前にくるようにするため。
        $customWordConv = array();
        foreach($form as $key=>$val) {
            if (substr($key, 0, 12) == "word_source_") {
                $num = substr($key, 12);
                if (is_numeric($num)) {
                    $dist = $form['word_dist_'.$num];
                    if ($val != "") {
                        list($table, $column) = Logic_CustomColumn::getCustomColumnFromName($val);
                        if ($dist == "") {
                            // 既存のカスタム項目を削除する場合は、データも削除しておく。
                            if (isset($wordConv[$val])) {
                                if ($table != '' && $column != '') {
                                    $table = $gen_db->quoteParam($table);
                                    $column = $gen_db->quoteParam($column);
                                    $query = "update {$table} set {$column} = null";
                                    $gen_db->query($query);
                                }
                            }
                            // セレクタ選択肢・明細フラグも削除
                            unset($customColumnOptions[$column]);
                            unset($customColumnIsDetail[$column]);
                        } else {
                            // WordConvertでは画面表示値のどの部分が置き換えられるかわからないため、出力時に適切なXSS対策
                            // （HTMLエスケープ）が行えない。それで、危険な文字は登録できないようにする。
                            if (preg_match("[<|>|&|'|\">]", $dist) > 0) {
                                $form['error_msg'] = _g("カスタム項目名に次の文字を含めることはできません。") . " < > & ' \"";
                                return 'action:Config_CustomColumn_Edit';
                            }
                            
                            $customWordConv[$val] = $dist;
                            
                            // セレクタ選択肢
                            if (isset($form['word_dist_'.$num.'_select'])) {
                                $opts = $form['word_dist_'.$num.'_select'];
                                if (preg_match("[<|>|&|'|\">]", $opts) > 0) {
                                    $form['error_msg'] = _g("セレクタ選択肢に次の文字を含めることはできません。") . " < > & ' \"";
                                    return 'action:Config_CustomColumn_Edit';
                                }
                                // 選択肢の中から重複および空文字を削除
                                //  空文字はあってもいい気がするが、許可してしまうとListの表示条件やフィルタで「すべて」を
                                //  指定するのに困る。代わりに「(なし)」などの選択肢を設定してもらえばいい
                                $optArr = array_unique(explode(";", $opts));
                                foreach($optArr as $key => $opt) {
                                    if ($opt == "") {
                                        unset($optArr[$key]);
                                    }
                                }
                                $customColumnOptions[$val] = join(";", $optArr);
                            }
                            
                            // 明細フラグ
                            if (isset($form['word_dist_'.$num.'_isdetail'])) {
                                $customColumnIsDetail[$val] = true;
                            } else {
                                unset($customColumnIsDetail[$val]);
                            }
                        }
                        unset($wordConv[$val]);
                    }
                }
            }
        }
        
        // 上で作成した、カスタム項目の値と用語変換の値をマージする。
        // その際、必ずカスタム項目の値が用語変換よりも前にくるようにする。ag.cgi?page=ProjectDocView&ppid=1574&pbid=207452
        $wordConv = array_merge($customWordConv, $wordConv);
        
        $_SESSION['gen_setting_company']->wordconvert = $wordConv;
        $_SESSION['gen_setting_company']->customcolumnoptions = $customColumnOptions;
        $_SESSION['gen_setting_company']->customcolumnisdetail = $customColumnIsDetail;
        Gen_Setting::saveSetting();

        // JavaScript用の変換ファイルを更新しておく
        Gen_String::updateJSConvertFile(true);

        // データアクセスログ
        Gen_Log::dataAccessLog(_g("フィールド・クリエイター設定"), "", "");

        $form['result'] = "success";
        // $form['classGroup']がセットされていればそれも引き渡される
        return 'action:Config_CustomColumn_Edit';
    }
}