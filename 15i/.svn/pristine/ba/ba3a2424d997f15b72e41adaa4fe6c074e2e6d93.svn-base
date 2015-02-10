<?php

class Delivery_DataLock_Lock
{

    function execute(&$form)
    {
        // タブ、マイメニュー用。この画面では画面表示にも使用
        $form['gen_pageTitle'] = _g("販売データロック");
        $form['gen_pageHelp'] = _g("データロック");
        $form['addMsg'] = _g("※ すべてのデータをロックする場合は、[メンテナンス] - [データロック] 画面で処理を行ってください。");

        $form['bgColor1'] = '#d5ebff';
        $form['target1'] = _g("対象：　[販売管理]メニューのデータ");
        $form['ajaxAction1'] = 'Delivery_DataLock_AjaxLock';

        $date = Logic_SystemDate::getSalesLockDate();

        $minDate = strtotime(date('Y-01-01', strtotime("-5 years")));    // tplの年月指定セレクタは5年前まで
        if ($date < $minDate)
            $date = $minDate;
        $lockDate = strtotime(date('Y-m-01', $date) . " -1 month");
        $form['lockMsg1'] = sprintf(_g("現在、%1\$s年 %2\$s月 およびそれ以前のデータがロックされています。"), date('Y', $lockDate), date('m', $lockDate));
        $form['default_date'] = date('Y-m-01', $lockDate);

        // tplは全体データロックのものを流用
        return 'monthly_process_monthly.tpl';
    }

}