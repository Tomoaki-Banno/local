<?php

class Config_CustomColumn_Edit 
{
    function execute(&$form)
    {
        // タブ、マイメニュー用
        $form['gen_pageTitle'] = _g("フィールド・クリエイター設定");
        
        // 全画面分の表示はadminのみ行えるようにした
        // ag.cgi?page=ProjectDocView&pPID=1574&pbid=233466
        if (!isset($form['classGroup'])) {
            if (Gen_Auth::getCurrentUserId() != -1) {
                return 'systemutility_showerror_cannotuse.tpl';
            }
        }

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

        // カスタム項目
        if (isset($form['classGroup'])) {
            $customArr = Logic_CustomColumn::getCustomColumnParamByClassGroup($form['classGroup'], true);
        } else {
            $customArr = Logic_CustomColumn::getCustomColumnParamAll();
        }

        $i = 1;
        $customCatArr = array();
        $convNoArr = array();
        $convDetailNoArr = array();
        foreach ($customArr as $categoryName => $categoryParam) {
            if ($categoryName == _g("入出庫")) {
                $categoryName = _g("入庫・出庫・支給（共通）");
            }
            $customCatArr[] = array($categoryName, $categoryParam[1]);
            foreach ($categoryParam[0] as $customColumn => $paramArr) {
                // $paramArr[2]はgetTextがかかっていないため、日本語以外の言語でもカスタム項目名が
                // 日本語のまま表示されてしまうが、それは仕方ない。理由は Logic_CustomColumn::getCustomColumnFromName()
                // のコメントを参照。
                $form['word_source_'.$i] = $paramArr[2];
                if (isset($wordConv[$paramArr[2]])) {
                    if (!isset($form['error_msg'])) {   // エラーリダイレクトの場合は入力値をそのまま表示
                        $form['word_dist_'.$i] = $wordConv[$paramArr[2]];
                        if (isset($customColumnOptions[$paramArr[2]])) {
                            $form['word_dist_'.$i.'_select'] = $customColumnOptions[$paramArr[2]];
                        }
                        if (isset($customColumnIsDetail[$paramArr[2]])) {
                            $form['word_dist_'.$i.'_isdetail'] = $customColumnIsDetail[$paramArr[2]];
                        }
                    }
                    unset($wordConv[$paramArr[2]]);
                    $convNoArr[] = $i;
                    if (isset($customColumnIsDetail[$paramArr[2]]) && $customColumnIsDetail[$paramArr[2]]) {
                        $convDetailNoArr[] = $i;
                    }
                } else {
                    if (!isset($form['error_msg'])) {   // エラーリダイレクトの場合は入力値をそのまま表示
                        $form['word_dist_'.$i] = "";
                        $form['word_dist_'.$i.'_select'] = "";
                        $form['word_dist_'.$i.'_isdetail'] = "";
                    }
                }
                $i++;
            }
        }

        $form['custom_cat_arr'] = $customCatArr;
        $form['conv_no_csv'] = join(',', $convNoArr);
        $form['conv_detail_no_csv'] = join(',', $convDetailNoArr);

        return 'config_customcolumn_edit.tpl';
    }
}