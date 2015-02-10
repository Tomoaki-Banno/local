<?php

class Master_Item_Report extends Base_PDFReportBase
{

    protected function _getQuery(&$form)
    {
        // 印刷対象データを配列に列挙する
        $idArr = array();
        foreach ($form as $name => $value) {
            if (substr($name, 0, 6) == "check_") {
                $idArr[] = substr($name, 6, strlen($name) - 6);
            }
        }

        // 印刷対象データの取得
        $query = "
            select
             	1 as dummy

            from
                item_master
                " . self::getFromItemMasterChildren() . "
                " . self::getFromItemMasterProcess() . "
            where
                item_master.item_id in (" . join(",", $idArr) . ")
            order by
                -- テンプレート内の指定が優先されることに注意
                item_code
        ";

        return $query;
    }

    // テンプレート情報
    protected function _getReportParam()
    {
        $info = array();
        $info['reportTitle'] = _g("品目ラベル");
        $info['report'] = "Item";
        $info['pageKeyColumn'] = "dummy";

        // SQLのfromで指定されているテーブルのリスト。
        // ここで指定されたテーブルのカラムはSQL selectとタグリストに自動追加される。
        $info['tables'] = array(
            array("item_master", true, ""),
            array("item_master_children", true, ""),    // 品目グループ・標準ロケ・標準手配先関連。SQL from に「self::getFromItemMasterChildren()」が必要
            array("item_master_process", true, ""),    // 工程関連。SQL from に「self::getFromItemMasterProcess()」が必要。工程数×2のjoinが追加されるため、工程数が多い場合はパフォーマンスに影響あり
        );

        // タグリスト（この帳票固有のもの）
        $info['tagList'] = array(
        );

        return $info;
    }
    
    // 印刷フラグの更新
    protected function _setPrintFlag($form)
    {
    }

}
