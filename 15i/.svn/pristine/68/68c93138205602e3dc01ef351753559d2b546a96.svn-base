<?php

class Logic_BomCsv
{

    //************************************************
    // CSVデータのエクスポート
    //************************************************
    // 品目マスタの内容を指定されたファイルにエクスポート。

    static function CsvExport($filename, $itemId, $offset)
    {
        global $gen_db;

        // 見出し（1行目）
        $title =
                _g("親品目コード") . "," . _g("子品目コード") . "," . _g("員数") . "\n";

        if (is_numeric($itemId)) {
            // 指定された親品目以下の品目を出力
            // 構成展開データの取得
            // 画面で逆展開しているときも、CSVは常に正展開で出力する
            Logic_Bom::expandBom($itemId, 1, false, true, false);

            // データ（2行目以降）
            $query = "
            select
                item_master.item_code,
                child_item_master.item_code as child_item_code,
                bom_master.quantity
            from
                temp_bom_expand
                inner join bom_master on temp_bom_expand.item_id = bom_master.item_id
                inner join item_master on temp_bom_expand.item_id = item_master.item_id
                inner join item_master as child_item_master on bom_master.child_item_id = child_item_master.item_id
            order by
                item_master.item_code,
                seq,
                child_item_master.item_code
            ";
        } else {
            // すべての品目を出力
            // データ（2行目以降）
            $query = "
            select
                item_master.item_code,
                child_item_master.item_code as child_item_code,
                quantity
            from
                bom_master
                inner join item_master on bom_master.item_id = item_master.item_id
                inner join item_master as child_item_master on bom_master.child_item_id = child_item_master.item_id
            order by
                item_master.item_code,
                seq,
                child_item_master.item_code
            ";
        }

        if (!Gen_String::isNumeric($offset)) {
            $offset = 1;
        }

        return Gen_Csv::CsvExport($filename, $title, $query, $offset);
    }

    //************************************************
    // CSVデータのインポート
    //************************************************

    static function CsvImport($filename, $isAllowUpdate)
    {
        global $gen_db;

        $prepare = "
        PREPARE prepare1 (int, int, numeric, int) as
        insert into bom_master (
            item_id,
            child_item_id,
            quantity,
            seq,
            record_creator,
            record_create_date,
            record_create_func
        )
        values (
            $1,
            $2,
            $3,
            $4,
            '" . $_SESSION['user_name'] . "',
            '" . date('Y-m-d H:i:s') . "',
            '" . __CLASS__ . "::" . __FUNCTION__ . "'
        )
        ";
        $gen_db->query($prepare);

        //    上書き機能は混乱をまねくため、使用しなくなった（インポート画面の上書きチェックボックスを廃止）が、
        //    機能としては残してある。
        $prepare = "
        PREPARE prepare2 (int, int, numeric, int) as
        update
            bom_master
        set
            quantity = $3,
            seq = $4,
            record_updater = '" . $_SESSION['user_name'] . "',
            record_update_date = '" . date('Y-m-d H:i:s') . "',
            record_update_func = '" . __CLASS__ . "::" . __FUNCTION__ . "'
        where
            item_id = $1
            and child_item_id = $2
        ";
        $gen_db->query($prepare);

        $keyArray = array("item_id" => "0", "child_item_id" => "1");    // 上書き判断用。キーフィールド名 => 列番号

        return Gen_Csv::CsvImport($filename, "prepare1", 4, "Logic_BomCsv", "checkCsvData", "afterEntry", $isAllowUpdate, "prepare2", "bom_master", $keyArray, $gen_db);
    }

    //************************************************
    // CSVデータのチェックとコード→ID変換
    //************************************************
    // ここで渡されるパラメータは、すべてquoteParamずみなのでそのままSQLにうめこんでよい。

    static function checkCsvData($line, $dataArray, $isAllowUpdate)
    {
        global $gen_db;

        $errorMsg = "";

        if (count($dataArray) > 3) {
            $errorMsg = _g("データの項目数が多すぎます。");
            //$errorMsg = _g("データの項目数が多すぎます。") . "<BR>";
            return $errorMsg;
        }

        $dataArray[0] = trim(@$gen_db->quoteParam($dataArray[0]));
        $dataArray[1] = trim(@$gen_db->quoteParam($dataArray[1]));
        $dataArray[2] = trim(@$gen_db->quoteParam($dataArray[2]));

        $line++;

        // 親子一致チェック
        if ($dataArray[0] == $dataArray[1]) {
            $errorMsg = sprintf(_g("%1\$s 行目 : 親品目と子品目が同じです。"), $line);
            //$errorMsg = sprintf(_g("%1\$s 行目 : 親品目と子品目が同じです。"), $line) . "<BR>";
            return $errorMsg;
        }

        // 員数チェック
        if (!Gen_String::isNumeric($dataArray[2])) {
            $errorMsg = sprintf(_g("%1\$s 行目 : 員数が不正です。"), $line);
            //$errorMsg = sprintf(_g("%1\$s 行目 : 員数が不正です。"), $line) . "<BR>";
            return $errorMsg;
        }
        if ($dataArray[2] <= 0) {
            $errorMsg = sprintf(_g("%1\$s 行目 : 員数には0より大きい値を設定してください。"), $line);
            return $errorMsg;
        }

        // コード⇒id変換と、親品目・子品目妥当性チェック（マスタ存在、管理区分）
        if (mb_ereg("[ａ-ｚＡ-Ｚ０-９]", $dataArray[0])) {
            $errorMsg = sprintf(_g("%1\$s 行目 : 親品目コードに全角アルファベットや全角数字は使用できません。"), $line) . "";
            return $errorMsg;
        }
        $query = " select item_id, order_class from item_master where item_code = '{$dataArray[0]}'";
        $parent = $gen_db->queryOneRowObject($query);
        if (!$parent || !is_numeric($parent->item_id)) {
            $errorMsg = sprintf(_g("%1\$s 行目 : 親品目コードが品目マスタに登録されていません。"), $line);
            return $errorMsg;
        }
        $dataArray[0] = $parent->item_id;

        if (mb_ereg("[ａ-ｚＡ-Ｚ０-９]", $dataArray[1])) {
            $errorMsg = sprintf(_g("%1\$s 行目 : 子品目コードに全角アルファベットや全角数字は使用できません。"), $line) . "";
            return $errorMsg;
        }
        $query = " select item_id, order_class from item_master where item_code = '{$dataArray[1]}'";
        $child = $gen_db->queryOneRowObject($query);
        if (!is_numeric($child->item_id)) {
            $errorMsg = sprintf(_g("%1\$s 行目 : 子品目コードが品目マスタに登録されていません。"), $line);
            return $errorMsg;
        } else if ($parent->order_class == "1" and $child->order_class == "0") {
            $errorMsg = sprintf(_g("%1\$s 行目 : 親品目がMRP品目のとき、子品目に製番品目を指定することはできません。"), $line);
            return $errorMsg;
        }
        // ロット品目は他の品目の子品目とはなれない。
        // ロット管理は単階層である。つまり実績で使用ロットを指定して在庫を引き落とすということができないので、部材や中間品のロット管理はできない。
        // 実際に出荷する品目（最終製品）だけをロット品目とする必要がある。
        else if ($child->order_class == "2") {
            $errorMsg = sprintf(_g("%1\$s 行目 : 子品目にロット品目を指定することはできません。ロット品目は常に最上位である必要があります。"), $line);
            return $errorMsg;
        }
        $dataArray[1] = $child->item_id;

        // データ重複チェック
        $query = " select item_id from bom_master where item_id = {$dataArray[0]} and child_item_id = {$dataArray[1]}";
        if ($gen_db->existRecord($query)) {
            if ($isAllowUpdate) {
                // 既存データ削除
                //    $gen_db->query("DELETE from bom_master where item_id = {$dataArray[0]} and child_item_id = " . $dataArray[1]);
            } else {
                $errorMsg = sprintf(_g("%1\$s 行目 : すでに構成表マスタに登録されています。"), $line);
                return $errorMsg;
            }
        }

        $query = "select max(seq) from bom_master where item_id = '{$dataArray[0]}'";
        $maxSeq = $gen_db->queryOneValue($query);
        if (!is_numeric($maxSeq))
            $maxSeq = -1;
        $dataArray[3] = $maxSeq + 1;

        // ループチェックとLLC計算は行ごとに行うと効率が悪いので、呼び出し側にて一括で行う。

        return $dataArray;
    }

    //************************************************
    // 登録後処理
    //************************************************

    static function afterEntry($dataArray)
    {
    }

}