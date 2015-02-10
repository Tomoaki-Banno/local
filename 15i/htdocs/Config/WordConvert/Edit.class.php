<?php

class Config_WordConvert_Edit 
{
    function execute(&$form)
    {
        // タブ、マイメニュー用
        $form['gen_pageTitle'] = _g("ネーム・スイッチャー設定");

        $wordConv = array();
        if (isset($_SESSION['gen_setting_company']->wordconvert)) {
            $wordConv = $_SESSION['gen_setting_company']->wordconvert;
        }
        if (is_object($wordConv)) {
            $wordConv = get_object_vars($wordConv);
        }
        
        // カスタム項目は除外
        $customArr = Logic_CustomColumn::getCustomColumnParamAll();
        $i = 1;
        $customWordArr = array();
        foreach ($customArr as $categoryName => $categoryParam) {
            foreach ($categoryParam[0] as $paramArr) {
                $customWordArr[] = $paramArr[2];
            }
        }
 
        // 既存値の読み出し
        if (!isset($form['error_msg'])) {   // エラーリダイレクトの場合は入力値をそのまま表示
            $i = 1;
            foreach($wordConv as $key => $val) {
                if (!in_array($key, $customWordArr)) {
                    // HTMLエスケープはtplで行われている
                    $form['word_source_'.$i] = $key;
                    $form['word_dist_'.$i] = $val;
                    $i++;
                }
            }
        }

        return 'config_wordconvert_edit.tpl';
    }
}