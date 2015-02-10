<?php

class Master_Bom_AjaxEntry extends Base_AjaxBase
{

    function validate($validator, &$form)
    {
        $validator->existRecord('parent_item_id', '', 'select item_id from item_master where item_id = $1', false);
        return 'simple.tpl';    // if error
    }

    function _execute(&$form)
    {
        global $gen_db;

        // 引数 $form['entryData']に は、子品目として登録する品目のリストが入っている。
        // 品目ごとにコロン区切りになっており、1品目は「品目id;員数」という形（セミコロン区切り）。
        // つまり「品目1id;品目1員数:品目2id;品目2員数:・・」

        $items = explode(':', $form['entryData']);

        // トラン開始
        $gen_db->begin();

        // いったん子品目を全部削除
        $gen_db->query("delete from bom_master where item_id = '{$form['parent_item_id']}'");

        // 子品目登録
        $seq = 1;
        foreach ($items as $item) {
            $param = explode(';', $item);

            if (is_numeric($param[0]) && is_numeric($param[1]) && $param[0] != $form['parent_item_id']) {

                // 子品目idの妥当性チェック
                $query = "select item_id from item_master where item_id = {$param[0]}";

                if ($gen_db->existRecord($query)) {

                    // 登録処理
                    $data = array(
                        'item_id' => $form['parent_item_id'],
                        'child_item_id' => $param[0],
                        // 取数モードを実装したが、使用中止になった（tplでセレクタをコメントアウト）。理由はtplのセレクタの箇所を参照
                        'quantity' => (@$form['inzu_mode'] == "tori" ? (1 / $param[1]) : $param[1]),
                        // 子品目の順序を記録
                        'seq' => $seq++,
                    );
                    $gen_db->insert('bom_master', $data);
                }
            }
        }

        // LLC再計算
        if (Logic_Bom::calcLLC()) {
            $gen_db->commit();
            $status = 'success';
        } else {
            $gen_db->rollback();
            $status = 'failure';    // 構成ループ発生
        }

        // データアクセスログ
        $itemCode = $gen_db->queryOneValue("select item_code from item_master where item_id = '{$form['parent_item_id']}'");
        Gen_Log::dataAccessLog(_g("構成表マスタ"), "", "[" . _g("親品目コード") . "] " . $itemCode);

        return
            array(
                "status" => $status
            );
    }

}