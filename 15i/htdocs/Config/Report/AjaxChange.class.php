<?php

class Config_Report_AjaxChange extends Base_AjaxBase
{

    function _execute(&$form)
    {
        // 選択テンプレート情報の更新
        Gen_PDF::updateSelectedTemplateInfo($form['report'], $form['file']);

        // 全ユーザーの選択テンプレートを共通にする場合は、下記を有効にする
        // （10iの仕様。gen_templates.datの1行目に選択テンプレートを記録）
        // 15iではシステムテンプレートとユーザーテンプレートのディレクトリが別になったため、このままでは使えない
        //$file = REPORT_TEMPLATES_DIR . $form['report'] . "/gen_templates.dat";
        //$arr = file($file);
        //$arr[0] = str_replace('&amp;', '&', $form['file']) ."\n";
        //file_put_contents($file, join($arr,""));
        //
        // データアクセスログ
        Gen_Log::dataAccessLog(_g("帳票設定"), "", _g("帳票変更:") . $form['file']);

        return;
    }

}