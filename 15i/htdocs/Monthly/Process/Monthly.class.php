<?php

class Monthly_Process_Monthly
{

    function execute(&$form)
    {
        global $gen_db;

        // タブ、マイメニュー用。この画面では画面表示にも使用
        $form['gen_pageTitle'] = _g("データロック");
        $form['gen_pageHelp'] = _g("データロック");

        $form['addMsg_noEscape'] = _g("※ 販売管理（売掛）データだけをロックする場合は、[販売管理] - [販売データロック] 画面で処理を行ってください。") . "<br>"
            . _g("※ 購買管理（買掛）データだけをロックする場合は、[購買管理] - [購買データロック] 画面で処理を行ってください。");

        //--------------------------------------------
        //  過去データロック
        //--------------------------------------------
        $form['bgColor1'] = '#d5ebff';
        $form['titleMsg1'] = _g("過去データロック");
        $form['target1'] = _g("対象：　すべてのデータ");
        $form['ajaxAction1'] = 'Monthly_Process_AjaxMonthly';

        $date = Logic_SystemDate::getStartDate();
        $minDate = strtotime(date('Y-01-01', strtotime("-5 years")));    // tplの年月指定セレクタは5年前まで
        if ($date < $minDate)
            $date = $minDate;
        $lockDate = strtotime(date('Y-m-01', $date) . " -1 month");
        $form['lockMsg1'] = sprintf(_g("現在、%1\$s年 %2\$s月 およびそれ以前のデータがロックされています。"), date('Y', $lockDate), date('m', $lockDate));
        $form['default_date'] = date('Y-m-01', $lockDate);

        //--------------------------------------------
        //  データロック対象外
        //--------------------------------------------
        $form['gen_objectLock'] = true;
        $form['bgColor2'] = '#f5f5f5';
        $form['titleMsg2'] = _g("データロック対象外");
        $form['ajaxAction2'] = 'Monthly_Process_AjaxUnlock';

        $form['checked1'] = "";     // 受注登録
        $form['checked2'] = "";     // 製造指示登録
        $form['checked3'] = "";     // 注文登録
        $form['checked4'] = "";     // 外製指示登録
        $target2_noEscape = "";
        $query = "select unlock_object_1, unlock_object_2, unlock_object_3, unlock_object_4 from company_master";
        $res = $gen_db->queryOneRowObject($query);
        if (isset($res->unlock_object_1) && $res->unlock_object_1 == 1) {
            $form['checked1'] = " checked";
            $target2_noEscape .= "[" . _g("受注登録") . "]";
        }
        if (isset($res->unlock_object_2) && $res->unlock_object_2 == 1) {
            $form['checked2'] = " checked";
            $target2_noEscape .= " [" . _g("製造指示登録") . "]";
        }
        if (isset($res->unlock_object_3) && $res->unlock_object_3 == 1) {
            $form['checked3'] = " checked";
            $target2_noEscape .= " [" . _g("注文登録") . "]";
        }
        if (isset($res->unlock_object_4) && $res->unlock_object_4 == 1) {
            $form['checked4'] = " checked";
            $target2_noEscape .= " [" . _g("外製指示登録") . "]";
        }
        if ($target2_noEscape == "") {
            $form['lockMsg2'] = _g("現在、ロック対象外のデータはありません。");
            $form['target2_noEscape'] = _g("対象外：") . "<font color='blue'>　" . _g("なし") . "</font>";
        } else {
            $form['lockMsg2'] = _g("現在、以下のデータがロック対象外です。");
            $form['target2_noEscape'] = _g("対象外：") . "<font color='blue'>　" . trim($target2_noEscape) . "</font>";
        }

        return 'monthly_process_monthly.tpl';
    }

}