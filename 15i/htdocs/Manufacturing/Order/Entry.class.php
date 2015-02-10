<?php

require_once("Model.class.php");

class Manufacturing_Order_Entry extends Base_EntryBase
{

    function setParam(&$form)
    {
        // 基本パラメータ
        $this->listAction = "Manufacturing_Order_List";
        $this->errorAction = "Manufacturing_Order_Edit";
        $this->modelName = "Manufacturing_Order_Model";
        $this->newRecordNotKeepField = array("order_no");
    }

    function setLogParam($form)
    {
        global $gen_db;

        if (isset($form['order_header_id'])) {
            $id = $form['order_header_id'];
        } else {
            global $gen_db;
            $id = $gen_db->getSequence("order_header_order_header_id_seq");
        }

        if (is_numeric($id)) {
            // 外製工程が存在する場合は、親の製造オーダー番号を取得する。
            $orderNo = $gen_db->queryOneValue("select coalesce(subcontract_parent_order_no, order_no) from order_detail where order_header_id = '{$id}'");
        }
        $this->log1 = _g("製造指示登録");
        $this->log2 = "[" . _g("オーダー番号") . "] {$orderNo}";
        $this->afterEntryMessage = sprintf(_g("オーダー番号 %s の製造指示を登録しました。"), $orderNo);
    }

}