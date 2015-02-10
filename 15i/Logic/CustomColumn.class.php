<?php

class Logic_CustomColumn
{
    // あらたに追加したテーブル（あるいは画面）でカスタム項目を扱いたい場合、次のことを行う必要がある。
    // これらを行えば、List/Edit/Entry/Model/Report/WordConvert にカスタム項目を扱う機能が自動追加される。
    // ・スキーマ変更（カラムを追加）
    // ・このクラスのgetCustomColumnArray()に追記
    // ・Modelクラスを変更
    //      _regist() で、カスタム項目を追加したテーブルのキー値を返すようにする
    //          ※明細テーブルがある場合は array(ヘッダのキー値、明細のキー値) を返す
    // ・明細テーブルがあるクラスのみ
    //      Listクラスで $this->isDetailMode （ヘッダモード・明細モードのフラグ）を設定
    //      Modelクラスの_getKeyColumn() で array(ヘッダのキー項目、明細のキー項目) を返すようにする
    
    // カスタム項目を他の画面のリストに出したい場合、出したい画面のListクラスのsetQueryParam()で次の設定が必要。
    //　※ちなみに帳票についてはなにも設定しなくても自動的に各帳票に出てくる
    //      array(カスタム項目があるテーブル名, テーブル名のエイリアス※1, classGroup※2, parentColumn※3, 明細カスタム項目取得(省略可)※4)
    //          ※1 テーブル名のエイリアス： ListのSQL内でテーブルにエイリアスをつけている場合、そのエイリアスを指定する。
    //          ※2 classGroup:　order_headerのように複数の画面で登録が行われるテーブルの場合、登録画面のクラスグループ（ex: Manufacturing_Order）を指定する
    //          ※3 parentColumn: そのテーブルのカスタム項目をsameCellJoinで表示する際、parentColumn となるカラム。
    //                  例えば受注画面であれば、customer_master は received_header_id, item_master は received_detail_id となる。
    //          ※4 明細カスタム項目取得(省略可): これをtrueにすると明細カスタム項目も取得する。ただしSQLのfromに明細カスタム項目テーブルがJOINされている必要がある。
    //                  estimate_detail, received_detail, delivery_detail, order_detail
    
    static function getCustomColumnArray()
    {
        return
            //  "classGroup" => array("カスタム項目が存在するテーブル", _g("カスタム項目のprefix"), "カスタム項目のprefix", キー項目, 明細テーブル)
            //      ※ prefixは、getTextと非getTextの両方が必要。
            //          $a = "非getText"; $b = _g($a); のようにする手もあるが、それだとPOEditで文字列が拾われない可能性がある。
            //      ※ キー項目は、ListBase で カスタム項目の sameCellJoin の parentColumnを決めるためのもの。
            array(
                "Manufacturing_Received" => array("received_header", _g("受注"), "受注", "received_header_id", "received_detail"),
                "Mobile_Received" => array("received_header", _g("受注"), "受注", "received_header_id", "received_detail"),
                "Delivery_Delivery" => array("delivery_header", _g("納品"), "納品", "delivery_header_id", "delivery_detail"),
                "Delivery_PayingIn" => array("paying_in", _g("入金"), "入金", "paying_in_id", false),
                "Manufacturing_Estimate" => array("estimate_header", _g("見積"), "見積", "estimate_header_id", "estimate_detail"),

                "Manufacturing_Plan" => array("plan", _g("計画"), "計画", "plan_id", false),
                "Manufacturing_Order" => array("order_header", _g("製造指示"), "製造指示", "order_header_id", false),
                "Manufacturing_Achievement" => array("achievement", _g("実績"), "実績", "achievement_id", false),

                "Partner_Order" => array("order_header", _g("注文"), "注文", "order_header_id", "order_detail"),
                "Mobile_PartnerOrder" => array("order_header", _g("注文"), "注文", "order_header_id", "order_detail"),
                "Partner_Accepted" => array("accepted", _g("受入"), "受入", "accepted_id", false),
                "Partner_Subcontract" => array("order_header", _g("外製"), "外製", "order_header_id", false),
                "Partner_SubcontractAccepted" => array("accepted", _g("外製受入"), "外製受入", "accepted_id", false),
                "Partner_Payment" => array("payment", _g("支払"), "支払", "payment_id", false),
                
                "Stock_Inout" => array("item_in_out", _g("入出庫"), "入出庫", "item_in_out_id", false),
                "Stock_Move" => array("location_move", _g("移動"), "移動", "move_id", false),
                "Stock_SeibanChange" => array("seiban_change", _g("製番引当"), "製番引当", "change_id", false),

                "Master_Item" => array("item_master", _g("品目"), "品目", "item_id", false),
                "Mobile_ItemMaster" => array("item_master", _g("品目"), "品目", "item_id", false),
                "Master_Customer" => array("customer_master", _g("取引先"), "取引先", "customer_id", false),
                "Mobile_CustomerMaster" => array("customer_master", _g("取引先"), "取引先", "customer_id", false),
                "Master_ItemGroup" => array("item_group_master", _g("品目グループ"), "品目グループ", "item_group_id", false),
                "Master_Location" => array("location_master", _g("ロケ"), "ロケ", "location_id", false),
                "Master_Section" => array("section_master", _g("部門"), "部門", "section_id", false),
                "Master_Worker" => array("worker_master", _g("従業員"), "従業員", "worker_id", false),
                "Master_Process" => array("process_master", _g("工程"), "工程", "process_id", false),
                "Master_Equip" => array("equip_master", _g("設備"), "設備", "equip_id", false),
                "Master_Waster" => array("waster_master", _g("不適合理由"), "不適合理由", "waster_id", false),
            );
    }
    
    static function getCustomColumnParamByClassGroup($classGroup, $isEdit = false)
    {
        $arr = self::getCustomColumnArray();
        if (!isset($arr[$classGroup])) {
            return false;
        } else {
            $custParamArr = self::_getCustomColumnParam($arr[$classGroup], !$isEdit);
            if ($isEdit) {
                return array($arr[$classGroup][2] => array($custParamArr[1], ($arr[$classGroup][4] !== false)));
            } else {
                return $custParamArr;
            }
        }
    }
    
    static function getCustomColumnParamByTableName($tableName, $tagPrefix = "")
    {
        $arr = self::getCustomColumnArray();
        foreach($arr as $key => $paramArr) {
            if ($tagPrefix == "" || $tagPrefix == $paramArr[2]) {
                if ($paramArr[0] == $tableName) {
                    // ヘッダカスタム項目（明細テーブルがない場合は全カスタム項目）
                    return self::_getCustomColumnParam($arr[$key], true, $tagPrefix, true, false);
                } else if ($paramArr[4] == $tableName) {
                    // 明細カスタム項目
                    return self::_getCustomColumnParam($arr[$key], true, $tagPrefix, false, true);
                }
            }
        }
        return false;
    }
    
    static function getCustomColumnParamByTableName2($tableName, $classGroup = "")
    {
        $arr = self::getCustomColumnArray();
        foreach($arr as $key => $paramArr) {
            if ($paramArr[0] == $tableName && ($classGroup == "" || $key == $classGroup)) {
                return self::_getCustomColumnParam($arr[$key], true);
            }
        }
        return false;
    }
    
    static function getCustomColumnKeyByTableName($tableName)
    {
        $arr = self::getCustomColumnArray();
        foreach($arr as $key => $paramArr) {
            if ($paramArr[0] == $tableName) {
                return $paramArr[3];
            }
        }
        return false;
    }
    
    static function getCustomColumnParamAll()
    {
        $arr = self::getCustomColumnArray();
        $resArr = array();
        foreach($arr as $key => $paramArr) {
            $custParamArr = self::_getCustomColumnParam($arr[$key], false);
            $resArr[$paramArr[2]] = array($custParamArr[1], ($paramArr[4] !== false));
        }
        return $resArr;
    }
    
    // カスタム項目名（getText/非wordConvert）からテーブルとカラムを取得
    static function getCustomColumnFromName($customColumnName)
    {
        $arr = self::getCustomColumnArray();
        $table = "";
        $column = "";
        foreach($arr as $paramArr) {
            for ($i=1; $i<=GEN_CUSTOM_COLUMN_COUNT; $i++) {
                // カラム名の番号は1から始まる数値だが、項目名は01から始まる2桁数値とする。
                // そうしないと用語変換時に「カスタム項目(文字)10」が「カスタム項目(文字)1」とかぶってしまう
                $no = str_pad($i, 2, "0", STR_PAD_LEFT);
                // ここは getText/wordConvert しないことに注意。
                //  この項目名はカスタム項目のキーとなっているため、もしここでgetTextしてしまうと
                //  表示言語ごとにカスタム項目の設定が分かれてしまう。
                //  そのため日本語以外でカスタム項目設定画面を見た場合にカスタム項目名が日本語のまま
                //  表示されてしまうが、やむを得ない。帳票タグ名と同じ。
                if (sprintf("%sカスタム項目(文字)",_($paramArr[2])) . $no == $customColumnName) {
                    $table = $paramArr[0];
                    $column = "custom_text_{$i}";
                    break 2;
                }
                if (sprintf("%sカスタム項目(数値)",_($paramArr[2])) . $no == $customColumnName) {
                    $table = $paramArr[0];
                    $column = "custom_numeric_{$i}";
                    break 2;
                }
                if (sprintf("%sカスタム項目(日付)",_($paramArr[2])) . $no == $customColumnName) {
                    $table = $paramArr[0];
                    $column = "custom_date_{$i}";
                    break 2;
                }
            }
        }
        return array($table, $column);
    }
    
    static function getCustomElementTypeAndOptions($customColumnName, $mode)
    {
        $options = null;
        switch ($mode) {
            case 0:                             // 文字
                $customColumnOptions = null;
                if (isset($_SESSION['gen_setting_company']->customcolumnoptions)) {
                    $customColumnOptions = $_SESSION['gen_setting_company']->customcolumnoptions;
                    if (is_object($customColumnOptions)) {
                        $customColumnOptions = get_object_vars($customColumnOptions);
                    }
                }
                if (is_array($customColumnOptions) && isset($customColumnOptions[$customColumnName]) && $customColumnOptions[$customColumnName] != "") {
                    $type = "select";
                    $optionsArr = explode(";", $customColumnOptions[$customColumnName]);
                    $options = array();
                    foreach($optionsArr as $row) {
                        // 登録時に危険な文字は排除しているが、一応エスケープしておく
                        $row = h($row);
                        $options[$row] = $row;
                    }
                } else {
                    $type = "textbox"; 
                }
                break;   
            case 1: $type = "textbox"; break;   // 数値
            case 2: $type = "calendar"; break;  // 日付
        }
        return array($type, $options);
    }
    
    private static function _getCustomColumnParam($paramArr, $isWordConvOnly, $tagPrefix = "", $isHeaderOnly = false, $isDetailOnly = false)
    {
        $customColumnTable = $paramArr[0];      // カスタム項目を追加したテーブル
        if ($tagPrefix == "") {
            $tagPrefix = $paramArr[2];          // 帳票タグにつくprefix (非getText/非wordConvert)
        }
        $customColumnDetailTable = $paramArr[4];      // カスタム項目を追加した明細テーブル
        
        if ($isWordConvOnly) {
            // 用語変換が設定されている項目のみを取得する場合
            $wordConv = array();
            if (isset($_SESSION['gen_setting_company']->wordconvert)) {
                $wordConv = $_SESSION['gen_setting_company']->wordconvert;
            }
            if (is_object($wordConv)) {
                $wordConv = get_object_vars($wordConv);
            }
        }
        
        if ($isHeaderOnly || $isDetailOnly) {
            $isDetailArr = array();
            if (isset($_SESSION['gen_setting_company']->customcolumnisdetail)) {
                $isDetailArr = $_SESSION['gen_setting_company']->customcolumnisdetail;
                if (is_object($isDetailArr)) {
                    $isDetailArr = get_object_vars($isDetailArr);
                }
            }
        }
 
        $customColumnArr = array();
        for ($i=1; $i<=GEN_CUSTOM_COLUMN_COUNT; $i++) {
            // ここは getText/wordConvert しないことに注意。理由は上の getCustomColumnFromName() 内のコメントを参照。
            $no = str_pad($i, 2, "0", STR_PAD_LEFT);
            $columnName = sprintf("%sカスタム項目(文字)", $paramArr[2]) . $no;
            $headerDetailCheck = !isset($isDetailArr) 
                    || ($isHeaderOnly && (!isset($isDetailArr[$columnName]) || !$isDetailArr[$columnName]))
                    || ($isDetailOnly && isset($isDetailArr[$columnName]) && $isDetailArr[$columnName]);
            if ((!$isWordConvOnly || isset($wordConv[$columnName])) && $headerDetailCheck) {
                // key: カラム、 value: array(モード(0:文字/1:数値/2:日付), カスタム項目名(getText/wordConvert), 非カスタム項目名(getText/非wordConvert), 帳票タグ(非getText/非wordConvert)
                $customColumnArr["custom_text_{$i}"] = array(0, _g($columnName) . ' *', $columnName, "{$tagPrefix}カスタム項目_文字{$no}");
            }
        }
        for ($i=1; $i<=GEN_CUSTOM_COLUMN_COUNT; $i++) {
            // ここは getText/wordConvert しないことに注意。理由は上の getCustomColumnFromName() 内のコメントを参照。
            $no = str_pad($i, 2, "0", STR_PAD_LEFT);
            $columnName = sprintf("%sカスタム項目(数値)", $paramArr[2]) . $no;
            $headerDetailCheck = !isset($isDetailArr) 
                    || ($isHeaderOnly && (!isset($isDetailArr[$columnName]) || !$isDetailArr[$columnName]))
                    || ($isDetailOnly && isset($isDetailArr[$columnName]) && $isDetailArr[$columnName]);
            if ((!$isWordConvOnly || isset($wordConv[$columnName])) && $headerDetailCheck) {
                $customColumnArr["custom_numeric_{$i}"] = array(1, _g($columnName) . ' *', $columnName, "{$tagPrefix}カスタム項目_数値{$no}");
            }
        }
        for ($i=1; $i<=GEN_CUSTOM_COLUMN_COUNT; $i++) {
            // ここは getText/wordConvert しないことに注意。理由は上の getCustomColumnFromName() 内のコメントを参照。
            $no = str_pad($i, 2, "0", STR_PAD_LEFT);
            $columnName = sprintf("%sカスタム項目(日付)", $paramArr[2]) . $no;
            $headerDetailCheck = !isset($isDetailArr) 
                    || ($isHeaderOnly && (!isset($isDetailArr[$columnName]) || !$isDetailArr[$columnName]))
                    || ($isDetailOnly && isset($isDetailArr[$columnName]) && $isDetailArr[$columnName]);
            if ((!$isWordConvOnly || isset($wordConv[$columnName])) && $headerDetailCheck) {
                $customColumnArr["custom_date_{$i}"] = array(2,  _g($columnName) . ' *', $columnName, "{$tagPrefix}カスタム項目_日付{$no}");
            }
        }
        return array($customColumnTable, $customColumnArr, $customColumnDetailTable);
    }

}