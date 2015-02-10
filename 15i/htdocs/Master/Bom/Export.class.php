<?php

class Master_Bom_Export
{

    function execute(&$form)
    {
        // ユニークな名称のテンポラリファイルを作成。
        $tempPathName = tempnam(GEN_TEMP_DIR, "");

        // CSVデータをテンポラリファイルに出力
        Logic_BomCsv::CsvExport($tempPathName, @$form['itemId'], @$form['gen_csvOffset']);

        // テンポラリファイルのダウンロード処理
        Gen_Download::DownloadFile($tempPathName, "bom_" . date('Ymd_Hi') . ".csv");

        // リスト画面を表示
        return "action:Master_Bom_List";
    }

}