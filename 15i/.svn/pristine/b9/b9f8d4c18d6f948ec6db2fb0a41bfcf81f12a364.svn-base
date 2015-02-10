<?php

require_once(APP_DIR . "/Manufacturing/Received/Model.class.php");

class Mobile_Received_Entry extends Base_EntryBase
{
    function setParam(&$form)
    {
        global $gen_db;

        // 基本パラメータ
        $this->errorAction = "Mobile_Received_Edit";
        $this->newRecordNextAction = "Mobile_Received_Edit";
        $this->modelName = "Manufacturing_Received_Model";  // 流用
        $this->newRecordNotKeepField = array(
        );
        
        if (isset($form['customer_no'])) {
            $form['customer_id'] = $gen_db->queryOneValue("select customer_id from customer_master where customer_no = '{$form['customer_no']}' and classification = 0");
        }
        // 得意先エラーのときに発送先もエラー表示されてしまう現象に対処
        if (!is_numeric($form['customer_id'])) {
            $form['delivery_customer_id'] = $gen_db->queryOneValue("select min(customer_id) from customer_master where classification=0");
        }
        $form['received_date'] = date('Y-m-d');
        $form['guarantee_grade'] = "0";     // 確定
        // 2013-02-22 エターナルさんの仕様に合わせて納品同時登録するようにしていたが、
        // 　運用方法が変わったことに伴い、元に戻すことになった。
        //   ag.cgi?page=ProjectDocView&pid=1543&did=167619
//        $form['delivery_regist'] = "true";  // 同時に納品を登録

        $keyName = "item_code_";
        $keyLen = strlen($keyName);
        foreach($form as $key => $val) {
            if (substr($key, 0, $keyLen) == $keyName) {
                $line = substr($key, $keyLen);
                if (is_numeric($line)) {
                    $form['item_id_' . $line] = $gen_db->queryOneValue("select item_id from item_master where item_code = '{$val}'");
                    $form['dead_line_' . $line] = date('Y-m-d');
                    $form['remarks_' . $line] = "";
                    $form['seiban_' . $line] = "";
                    $form['reserve_quantity_' . $line] = 0;
                    $form['cost_' . $line] = 0;
                    if ((!isset($form['product_price_' . $line]) || $form['product_price_' . $line]==="") && is_numeric($form['customer_id']) 
                            && is_numeric($form['item_id_' . $line]) && is_numeric($form['received_quantity_' . $line])) {
                        $form['product_price_' . $line] = Logic_Received::getSellingPrice($form['item_id_' . $line], $form['customer_id'], $form['received_quantity_' . $line]);
                    }
                    
                    $this->newRecordNotKeepField[] = $key;
                }
            }
        }
        
        // 登録項目（ヘッダ）
        $this->headerArray = array(
            "received_header_id",
            "received_number",
            "estimate_header_id",
            "customer_received_number",
            "customer_id",
            "delivery_customer_id",
            "received_date",
            "worker_id",
            "section_id",
            "guarantee_grade",
            "remarks_header",
            "remarks_header_2",
            "remarks_header_3",
            "delivery_regist",
        );

        // 登録項目（明細）
        $this->detailArray = array(
            // Modelプロパティ名を指定。$formのキーとしては、これに「_」+行番号 がついた形となる。
            // 最初の項目が行キー。（ここに値が入っている行が登録対象になる）
            "item_id",
            "received_detail_id",
            "received_quantity",
            "product_price",
            "dead_line",
            "remarks",
            "seiban",
            "reserve_quantity",
            "sales_base_cost",
            "cost",
        );
    }

    function setLogParam($form)
    {
        $this->log1 = _g("受注（モバイル）");
        $this->logCategory = _g("登録");
        $this->log2 = "";
        $this->afterEntryMessage = _g("受注を登録しました。");
    }
}