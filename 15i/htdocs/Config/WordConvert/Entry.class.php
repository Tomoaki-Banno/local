<?php

class Config_WordConvert_Entry
{
    function execute(&$form)
    {
        global $gen_db;

        // CSRF対策
        if (!Gen_Reload::reloadCheck(@$form['gen_page_request_id'])) {
            return 'action:Config_WordConvert_Edit';
        }
        
        // 既存値の読み出し（カスタム項目を消されないように）
        $wordConv = array();
        if (isset($_SESSION['gen_setting_company']->wordconvert)) {
            $wordConv = $_SESSION['gen_setting_company']->wordconvert;
        }
        if (is_object($wordConv)) {
            $wordConv = get_object_vars($wordConv);
        }
        
        // カスタム項目値以外を消去
        // ちなみに $wordConvの中では、用語変換よりカスタム項目が前にきている必要がある。
        // そうしないとカスタム項目の動きに問題が発生することがある。
        //  ag.cgi?page=ProjectDocView&ppid=1574&pbid=207452
        // ここではそのようになるので問題ない。
        $customArr = Logic_CustomColumn::getCustomColumnParamAll();
        $customArr2 = array();
        foreach ($customArr as $categoryName => $categoryParam) {
            foreach ($categoryParam[0] as $paramArr) {
                $customArr2[] = $paramArr[2];
            }
        }
        foreach($wordConv as $key => $val) {
            if (!in_array($key, $customArr2)) {
                unset($wordConv[$key]);
            }
        }
        
        // 登録
        foreach($form as $key=>$val) {
            if (substr($key, 0, 12) == "word_source_") {
                $num = substr($key, 12);
                if (is_numeric($num)) {
                    $dist = $form['word_dist_'.$num];
                    if ($val != "" && $dist != "") {
                        // WordConvertでは画面表示値のどの部分が置き換えられるかわからないため、出力時に適切なXSS対策
                        // （HTMLエスケープ）が行えない。それで、危険な文字は登録できないようにする。
                        if (preg_match("[<|>|&|'|\">]", $dist) > 0) {
                            $form['error_msg'] = _g("置換後の名称に次の文字を含めることはできません。< > & ' \"");
                            return 'action:Config_WordConvert_Edit';
                        }
                        $wordConv[$val] = $dist;
                    }
                }
            }
        }
        
        // $form値をクリアしておく
        //  ここでクリアしておかないと、登録時に行をスキップしていた場合、登録後のEdit画面で再取得された値と二重表示
        //  になってしまう場合がある（Editで取得される値は行をつめた状態なので）
        foreach($form as $key=>$val) {
            if (substr($key, 0, 12) == "word_source_") {
                unset($form[$key]);
            } else if (substr($key, 0, 10) == "word_dist_") {
                unset($form[$key]);
            }
        }
        
        // 用語変換登録
        $_SESSION['gen_setting_company']->wordconvert = $wordConv;
        Gen_Setting::saveSetting();
        
        // JavaScript用の変換ファイルを更新しておく
        Gen_String::updateJSConvertFile(true);

        // データアクセスログ
        Gen_Log::dataAccessLog(_g("ネーム・スイッチャー設定"), "", "");

        $form['result'] = "success";
        return 'action:Config_WordConvert_Edit';
    }
}