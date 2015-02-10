<?php

class Config_Report_AjaxDelete extends Base_AjaxBase
{

    function _execute(&$form)
    {
        // 帳票編集画面のアクセス権が「アクセス禁止」ならこのクラスが実行されることはないが、
        // 「読み取りのみ」の場合は実行されてしまう場合があるので、チェックしておく必要がある。
        $permission = Gen_Auth::sessionCheck("config_report");
        if ($permission != 2) {
            return
                $obj = array(
                    'msg' => _g("アクセス権がありません。"),
                );
        }
        
        // テンプレートの削除処理
        $errorMsg = self::deleteTemplate($form);

        return
            $obj = array(
                'msg' => $errorMsg,
            );
    }

    function deleteTemplate($form)
    {
        global $gen_db;

        $templateInfoArr = Gen_PDF::getTemplateInfo($form['report']);

        // テンプレート存在チェック
        $file = $templateInfoArr[4] . "/" . $form['file'];
        if (!file_exists($file)) {
            $storage = new Gen_Storage("ReportTemplates");
            if (!$storage->exist($form['report'] . "/" . $form['file'])) {
                return _g("指定されたテンプレートは存在しません。");
            }
        }

        // テンプレートを削除
        $info = $templateInfoArr[2];
        foreach ($info as $no => $infoOne) {
            if ($infoOne['file'] === $form['file']) {
                if ($infoOne['isDefault'] === "true") {
                    // システムテンプレートは削除不可
                    return _g("システム標準のテンプレートを削除することはできません。");
                } else {
                    // テンプレートを削除
                    $storage->delete($form['report'] . "/" . $form['file']);
                    unset($info[$no]);
                }
            }
        }

        // 現在選択中のテンプレートが削除された場合、リスト中の最初のテンプレートを選択する
        if ($templateInfoArr[0] == $form['file']) {
            $data = array("template_name" => $info[0]['file']);
            $where = "category = '{$form['report']}' and template_name = '{$form['file']}'";
            $gen_db->update("user_template_info", $data, $where);
            $templateInfoArr[0] = $info[0]['file'];
        }

        // 全ユーザーの選択テンプレートを共通にする場合は、下記を有効にする
        //if ($templateInfoArr[0]==$form['file']) {
        //    $templateInfoArr[0] = $info[0]['file'];
        //}
        //
        // gen_templates.dat の更新
        Gen_PDF::putTemplateInfo($form['report'], $templateInfoArr[0], $info);

        // データアクセスログ
        Gen_Log::dataAccessLog($form['reportTitle'] . _g("帳票テンプレート削除"), "", "[" . _g("ファイル名") . "] {$form['file']}");

        return "";
    }

}