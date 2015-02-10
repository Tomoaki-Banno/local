<?php

class Partner_Order_DetailDelete extends Base_DeleteBase
{

    function dataExistCheck(&$form)
    {
        global $gen_db;

        // 対象データ存在チェック（リロード対策）

        $this->listAction = (isset($form['List']) ? "Partner_Order_List" : "Partner_Order_Edit");

        if (!is_numeric($form['order_detail_id'])) {
            // エラー表示時の画面が編集モードになるのを防ぐ
            unset($form['order_detail_id']);
            return false;
        }
        $query = "select order_detail_id from order_detail where order_detail_id = '{$form['order_detail_id']}'";
        if ($gen_db->existRecord($query)) {
            return true;
        } else {
            // エラー表示時の画面が編集モードになるのを防ぐ
            unset($form['order_detail_id']);
            return false;
        }
    }

    function validate($validator, &$form)
    {
        // すでに受入が登録されている場合は削除できない。
        // 本来は受入を連動削除すればいいのかもしれないが、付随データの処理や
        // 現在処理月チェックなどいろいろ面倒なので、削除禁止とした。
        if (Logic_Accepted::hasAcceptedByOrderDetailId($form['order_detail_id'])) {
            $validator->raiseError(_g("この注文に対し、すでに受入が登録されているため削除できません。"));
        }

        if ($validator->hasError()) {
            // エラー表示時の画面が編集モードになるのを防ぐ
            unset($form['order_detail_id']);
        }
        return "action:Partner_Order_Edit";        // if error
    }

    function setParam(&$form)
    {
        global $gen_db;

        // メッセージとログ
        $orderNumber = $gen_db->queryOneValue("select order_no from order_detail where order_detail_id = '{$form['order_detail_id']}'");

        $this->afterEntryMessage = sprintf(_g("オーダー番号 %s の注文明細データを削除しました。"), $orderNumber);
        $this->logTitle = _g("注文書明細");
        $this->logMsg = "[" . _g("オーダー番号") . "] " . $orderNumber;
    }

    function deleteExecute(&$form)
    {
        global $gen_db;

        // トランザクション開始
        $gen_db->begin();

        // 削除メイン
        // ここのロジックの場合、ホントはPhantomの可能性を考えてIsoLevelをSerializeにすべきだが、そこまでやってない
        $order_header_id = Logic_Order::deleteOrderDetail($form['order_detail_id']);

        // コミット
        $gen_db->commit();

        // Editでの再表示のために
        $form['order_header_id'] = $order_header_id;
        unset($form['order_detail_id']);
    }

}
