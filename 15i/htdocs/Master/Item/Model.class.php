<?php

class Master_Item_Model extends Base_ModelBase
{

    var $csvUpdateMode = false;

    protected function _getKeyColumn()
    {
        return 'item_id';
    }

    protected function _setDefault(&$param, $entryMode)
    {
        global $gen_db;

        // 品目コードが空欄のときは品目名と同じにする。
        // 品目名が空欄のときは品目コードと同じにする。
        // 両方空欄のときは valid error になる。
        if ($param['item_code'] === '' || $param['item_code'] === null)
            $param['item_code'] = $param['item_name'];
        if ($param['item_name'] === '' || $param['item_name'] === null)
            $param['item_name'] = $param['item_code'];

        // 手配先デフォルト
        for ($i = 0; $i < GEN_ITEM_ORDER_COUNT; $i++) {
            if (!isset($param["partner_class_{$i}"]) || $param["partner_class_{$i}"] === null || $param["partner_class_{$i}"] === "") {
                // 手配区分なしのときの値
                if ($i == 0) {
                    // 標準手配先は必ず設定
                    $name = "partner_class_{$i}";
                    $param[$name] = '3';    
                    $name = "order_user_id_{$i}";
                    $param[$name] = '0';
                } else {
                    $name = "partner_class_{$i}";
                    $param[$name] = '';
                    $name = "order_user_id_{$i}";
                    $param[$name] = '';
                }
                $name = "item_sub_code_{$i}";
                $param[$name] = '';
                $name = "multiple_of_order_measure_{$i}";
                $param[$name] = 1;
                $name = "default_lot_unit_{$i}";
                $param[$name] = 0;
                $name = "default_lot_unit_2_{$i}";
                $param[$name] = 0;
                $name = "default_order_price_{$i}";
                $param[$name] = 0;
                $name = "default_order_price_2_{$i}";
                $param[$name] = 0;
                $name = "default_order_price_3_{$i}";
                $param[$name] = 0;
                $name = "order_price_limit_qty_1_{$i}";
                $param[$name] = "";
                $name = "order_price_limit_qty_2_{$i}";
                $param[$name] = "";
            } elseif ($param["partner_class_{$i}"] == "3") {
                // 内製のときの値
                $name = "order_user_id_{$i}";
                $param[$name] = 0;
                $name = "item_sub_code_{$i}";
                $param[$name] = '';
                $name = "default_order_price_{$i}";
                $param[$name] = 0;
                $name = "default_order_price_2_{$i}";
                $this->$name = 0;
                $name = "default_order_price_3_{$i}";
                $param[$name] = 0;
                $name = "order_price_limit_qty_1_{$i}";
                $param[$name] = "";
                $name = "order_price_limit_qty_2_{$i}";
                $param[$name] = "";
            }
        }

        // 税率デフォルト（非課税）
        if (isset($param['tax_class']) && $param['tax_class'] == "1") {
            $param['tax_rate'] = 0;
        }

        // 上書きモードの処理（csv）
        if ($this->csvUpdateMode && !isset($param['item_id']) && $param['item_code'] != "") {
            $query = "select item_id from item_master where item_code = '{$param['item_code']}'";
            $param['item_id'] = $gen_db->queryOneValue($query);
            if ($param['item_id'] === null)
                unset($param['item_id']);
        }

        switch ($entryMode) {
            case "csv":
                // code -> id
                self::_codeToId($param, "item_group_code", "item_group_id", "", "", "item_group_master");
                self::_codeToId($param, "item_group_code_2", "item_group_id_2", "item_group_code", "item_group_id", "item_group_master");
                self::_codeToId($param, "item_group_code_3", "item_group_id_3", "item_group_code", "item_group_id", "item_group_master");

                self::_codeToId($param, "default_location_code", "default_location_id", "location_code", "location_id", "location_master");
                self::_codeToId($param, "default_location_code_2", "default_location_id_2", "location_code", "location_id", "location_master");
                self::_codeToId($param, "default_location_code_3", "default_location_id_3", "location_code", "location_id", "location_master");

                for ($i = 0; $i < GEN_ITEM_ORDER_COUNT; $i++) {
                    self::_codeToId($param, "order_user_code_{$i}", "order_user_id_{$i}", "customer_no", "customer_id", "customer_master");
                }

                for ($i = 0; $i < GEN_ITEM_PROCESS_COUNT; $i++) {
                    self::_codeToId($param, "process_code_{$i}", "process_id_{$i}", "process_code", "process_id", "process_master");
                    self::_codeToId($param, "subcontract_partner_code_{$i}", "subcontract_partner_id_{$i}", "customer_no", "customer_id", "customer_master");
                }

                if ($param['item_group_code'] == '')
                    $param['item_group_id'] = '';
                if ($param['item_group_code_2'] == '')
                    $param['item_group_id_2'] = '';
                if ($param['item_group_code_3'] == '')
                    $param['item_group_id_3'] = '';

                if ($param['default_location_code'] == '')
                    $param['default_location_id'] = '0';
                if ($param['default_location_code_2'] == '')
                    $param['default_location_id_2'] = '0';
                if ($param['default_location_code_3'] == '')
                    $param['default_location_id_3'] = '0';

                if (@$param['end_item'] == "1") {
                    $param['end_item'] = "true";
                }

                if (@$param['dummy_item'] == "1") {
                    $param['dummy_item'] = "true";
                }
                
                break;
        }

        // 工程がひとつも指定されていないとき、標準工程を登録する。
        // ※ちなみに工程マスタに標準工程が存在しなかったときは、regist() において自動追加される
        $existProc = false;
        for ($i = 0; $i < GEN_ITEM_PROCESS_COUNT; $i++) {
            if (isset($param["process_id_{$i}"]) && $param["process_id_{$i}"] != '') {
                $existProc = true;
            }
        }
        if (!$existProc) {
            $param['process_id_0'] = "0";
            $param['default_work_minute_0'] = "0";
            $param['pcs_per_day_0'] = "0";
            $param['process_lt_0'] = "0";
            $param['subcontract_unit_price_0'] = "0";
            $param['charge_price_0'] = "0";
            $param['overhead_cost_0'] = "0";
        }
    }
    
    // setDefaultの直後（convert/validateの前）に実行される。
    //  setDefaultはclickEditのときには実行されないが（いろいろ問題が発生するので）、
    //  このfunctionはclickEditのときにも実行される。
    public function beforeLogic(&$param)
    {
        global $gen_db;
        
        $param['order_class_change_error'] = false;
        $param['check_seiban_stock'] = false;
        if (isset($param['item_id']) && is_numeric($param['item_id'])) {
            // 構成表マスタに含まれている場合、管理区分の切り替えを禁止する
            if (Logic_Bom::existBom($param['item_id'])) {
                $query = "select order_class from item_master where item_id = '{$param['item_id']}'";
                if ($gen_db->queryOneValue($query) != $param['order_class']) {
                    $param['order_class_change_error'] = 'err';
                }
            }

            // 管理区分が製番からそれ以外に変更された場合、チェック用に製番在庫数を取得しておく。
            // 詳細は _getColumns() の order_class の validate のコメントを参照。
            if ($param['order_class'] != "0") {
                $query = "select order_class from item_master where item_id = '{$param['item_id']}'";
                $orderClass = $gen_db->queryOneValue($query);
                if ($orderClass == "0") {
                    Logic_Stock::createTempStockTable(null, $param['item_id'], null, "sum", "sum", false, true, false);
                    $param['check_seiban_stock'] = true;
                }
            }
        }
    }

    protected function _getColumns()
    {
        // 標準工程以外の工程が存在するかどうか判断する式
        $procExistCheckScript = "[[process_id_0]]!='0'";
        for ($i = 1; $i < GEN_ITEM_PROCESS_COUNT; $i++) {
            $procExistCheckScript .= "||[[process_id_{$i}]]!=''";
        }

        // 工程LTの合計を算出する式
        $procLtSumScriptPHP = "";
        $procLtSumScriptJS = "";
        for ($i = 0; $i < GEN_ITEM_PROCESS_COUNT; $i++) {
            if ($procLtSumScriptPHP != "")
                $procLtSumScriptPHP .= "+";
            if ($procLtSumScriptJS != "")
                $procLtSumScriptJS .= "+";
            $procLtSumScriptPHP .= "Gen_String::nz([[process_lt_{$i}]])";
            $procLtSumScriptJS .= "parseInt(gen.util.nz([[process_lt_{$i}]]),10)";
        }

        // 空欄の工程LTがあるかどうかを判断する式
        $procLtBlankCheckScript = "([[process_id_0]]!='0'&&[[process_id_0]]!=''";
        for ($i = 0; $i < GEN_ITEM_PROCESS_COUNT; $i++) {
            if ($i != 0) {
                $procLtBlankCheckScript .= "||([[process_id_{$i}]]!=''";
            }
            $procLtBlankCheckScript .= "&&[[process_lt_{$i}]]=='')";
        }

        // 工程製造能力の合計を算出する式
        $procPPDSumScriptPHP = "";
        $procPPDSumScriptJS = "";
        for ($i = 0; $i < GEN_ITEM_PROCESS_COUNT; $i++) {
            if ($procPPDSumScriptPHP != "")
                $procPPDSumScriptPHP .= "+";
            if ($procPPDSumScriptJS != "")
                $procPPDSumScriptJS .= "+";
            $procPPDSumScriptPHP .= "Gen_String::nz([[pcs_per_day_{$i}]])";
            $procPPDSumScriptJS .= "parseInt(gen.util.nz([[pcs_per_day_{$i}]]),10)";
        }

        $columns = array(
            array(
                "column" => "item_id",
                "pattern" => "id",
            ),
            array(
                "column" => "item_code",
                "convert" => array(
                    array(
                        "cat" => "trimEx",
                    ),
                ),
                // 「ユーザー指定できるが全体としてユニークでなければならない」値は、
                // validateでの重複チェックだけでなく、このlockNumberの指定が必要。
                // くわしくは ModelBase の lockNumber処理の箇所のコメントを参照。
                "lockNumber" => true,
                "validate" => array(
                    array(
                        "cat" => "eval",
                        "msg" => _g("品目コード・品目名のどちらかを指定してください。"),
                        // _setDefault() で 品目コード・品目名のいずれかが空欄のときはもう一方を代入するようにしているので、
                        // サーバー側バリデーションでは品目コードだけをチェックすればよい。
                        // クライアント側バリデーションでは両方をチェックする必要がある。
                        "evalPHP" => "\$res=($1!='');",
                        "evalJS" => "res=($1!=''||[[item_name]]!='');",
                    ),
                    array(
                        "cat" => "notContainTwoByteAlphaNum",
                        "msg" => _g('品目コードに全角アルファベットや全角数字は使用できません。')
                    ),
                    // 新規登録時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('品目コードはすでに使用されています。別のコードを指定してください。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[item_id]]!=''", // 修正はスキップ
                        "param" => "select item_id from item_master where item_code = $1"
                    ),
                    // 修正時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('品目コードはすでに使用されています。別のコードを指定してください。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[item_id]]==''", // 新規登録はスキップ
                        // 更新のときは、自分自身の番号をチェック対象としない（自分自身と重複するのは当然）
                        "param" => "select item_id from item_master where item_code = $1
                            and item_id <> [[item_id]]"
                    ),
                ),
            ),
            array(
                "column" => "item_name",
                "convert" => array(
                    array(
                        "cat" => "trimEx",
                    ),
                ),
            // _setDefault() で 品目コード・品目名のいずれかが空欄のときはもう一方を代入するようにしているし、
            // 両方空欄のときは item_code でバリデーションされるので、ここでは入力チェックを行わなくてよい。
            ),
            array(
                "column" => "end_item",
                "pattern" => "bool",
            ),
            array(
                "column" => "order_class",
                "convert" => array(
                    array(
                        "cat" => "nullBlankToValue",
                        "param" => "1", // デフォルト MRP
                    ),
                ),
                "validate" => array(
                    array(
                        "cat" => "selectString",
                        "msg" => _g('管理区分が正しくありません。') . (GEN_LOT_MANAGEMENT ? _g('0(製番) 1(MRP) 2(ロット) のいずれかを指定してください。') : _g('0(製番) 1(MRP) のいずれかを指定してください。')),
                        "param" => array(Gen_Option::getOrderClass('model')),
                    ),
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('親品目がMRP品目であるため、この品目の管理区分を「製番」にすることはできません。'),
                        "skipValidatePHP" => "$1=='1' || [[item_id]]==''", // 製番品目のみチェック
                        "param" => "select * from bom_master inner join item_master on bom_master.item_id = item_master.item_id
                            where child_item_id = [[item_id]] and order_class = '1'",
                    ),
                    array(
                        "cat" => "eval",
                        "msg" => _g('この品目は構成表マスタに登録されているため、管理区分を変更することはできません。'),
                        "evalPHP" => "\$res=([[order_class_change_error]]!='err');", // check_bom_order_class は _setDefault() でセット
                        "evalJS" => "res=true;",
                    ),
                    // 15i以降、製番在庫が残った状態で管理区分をMRPやロットに切り替えることを禁止した。
                    // ag.cgi?page=ProjectDocView&ppid=1516&pbid=187709
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('製番在庫が残っているため管理区分を変更することができません。棚卸登録画面等で製番在庫をすべて0にしてください。'),
                        "skipValidatePHP" => "[[check_seiban_stock]]!='1'", // check_seiban_stock は _setDefault() でセット
                        "param" => "select * from temp_stock where seiban <> '' and logical_stock_quantity <> 0",
                    ),
                    // ロット品目は他の品目の子品目とはなれない。
                    // ロット管理は単階層である。つまり実績で使用ロットを指定して在庫を引き落とすということができないので、部材や中間品のロット管理はできない。
                    // 実際に出荷する品目（最終製品）だけをロット品目とする必要がある。
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('親品目が存在するため、この品目の管理区分を「ロット」にすることはできません。'),
                        "skipValidatePHP" => "$1!='2' || [[item_id]]==''", // ロット品目の更新時のみチェック
                        "param" => "select * from bom_master inner join item_master on bom_master.item_id = item_master.item_id
                            where child_item_id = [[item_id]]",
                    ),
                ),
            ),
            array(
                "column" => "item_group_id",
                "pattern" => "item_group_id",
                "label" => _g("品目グループ1"),
            ),
            array(
                "column" => "item_group_id_2",
                "pattern" => "item_group_id",
                "label" => _g("品目グループ2"),
            ),
            array(
                "column" => "item_group_id_3",
                "pattern" => "item_group_id",
                "label" => _g("品目グループ3"),
            ),
            array(
                "column" => "default_selling_price",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                    array(
                        "cat" => "nullBlankToValue",
                        "param" => 0,
                    ),
                ),
                "validate" => array(
                    array(
                        "cat" => "minNum",
                        "msg" => _g('標準販売単価が正しくありません。0以上の数字を指定してください。'),
                        "param" => 0
                    ),
                ),
            ),
            array(
                "column" => "default_selling_price_2",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                    array(
                        "cat" => "nullBlankToValue",
                        "param" => 0,
                    ),
                ),
                "validate" => array(
                    array(
                        "cat" => "minNum",
                        "msg" => _g('標準販売単価2が正しくありません。0以上の数字を指定してください。'),
                        "param" => 0
                    ),
                ),
            ),
            array(
                "column" => "default_selling_price_3",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                    array(
                        "cat" => "nullBlankToValue",
                        "param" => 0,
                    ),
                ),
                "validate" => array(
                    array(
                        "cat" => "minNum",
                        "msg" => _g('標準販売単価3が正しくありません。0以上の数字を指定してください。'),
                        "param" => 0
                    ),
                ),
            ),
            array(
                "column" => "selling_price_limit_qty_1",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                    array(
                        "cat" => "nullBlankToValue",
                        "param" => '',
                    ),
                ),
                "skipValidatePHP" => "$1===''",
                "skipValidateJS" => "$1===''",
                "validate" => array(
                    array(
                        "cat" => "minNum",
                        "msg" => _g('販売単価1適用数が正しくありません。0以上の数字を指定してください。'),
                        "param" => 0
                    ),
                ),
            ),
            array(
                "column" => "selling_price_limit_qty_2",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                    array(
                        "cat" => "nullBlankToValue",
                        "param" => '',
                    ),
                ),
                "skipValidatePHP" => "$1===''",
                "skipValidateJS" => "$1===''",
                "validate" => array(
                    array(
                        "cat" => "minNum",
                        "msg" => _g('販売単価2適用数が正しくありません。0以上の数字を指定してください。'),
                        "param" => 0
                    ),
                ),
            ),
            array(
                "column" => "stock_price",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                    array(
                        "cat" => "nullBlankToValue",
                        "param" => 0,
                    ),
                ),
                "validate" => array(
                    array(
                        "cat" => "minNum",
                        "msg" => _g('在庫評価単価が正しくありません。0以上の数字を指定してください。'),
                        "param" => 0
                    ),
                ),
            ),
            array(
                "column" => "payout_price",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                    array(
                        "cat" => "nullBlankToValue",
                        "param" => 0,
                    ),
                ),
                "validate" => array(
                    array(
                        "cat" => "minNum",
                        "msg" => _g('支給単価が正しくありません。0以上の数字を指定してください。'),
                        "param" => 0
                    ),
                ),
            ),
            array(
                "column" => "safety_stock",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                    array(
                        "cat" => "nullBlankToValue",
                        "param" => 0,
                    ),
                ),
                "validate" => array(
                    array(
                        "cat" => "minNum",
                        "msg" => _g('安全在庫数が正しくありません。0以上の数字を指定してください。'),
                        "param" => 0
                    ),
                ),
            ),
            array(
                "column" => "without_mrp",
                "convert" => array(
                    array(
                        "cat" => "nullBlankToValue",
                        "param" => 0
                    ),
                ),
            ),
            array(
                "column" => "without_mrp",
                "validate" => array(
                    array(
                        "cat" => "selectString",
                        "msg" => _g('所要量計算に含めるかどうかの指定が正しくありません。0(含める) 1(含めない) のいずれかを指定してください。'),
                        "param" => array(array(0, 1))
                    ),
                ),
            ),
            array(
                "column" => "quantity_per_carton",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                    array(
                        "cat" => "nullBlankToValue",
                        "param" => 1,
                    ),
                ),
                "validate" => array(
                    array(
                        "cat" => "minNum",
                        "msg" => _g('入数が正しくありません。1以上の数字を指定してください。'),
                        "param" => 1
                    ),
                ),
            ),
            array(
                "column" => "default_location_id",
                "pattern" => "location_id",
                "label" => _g("標準ﾛｹｰｼｮﾝ（受入）"),
            ),
            array(
                "column" => "default_location_id_2",
                "pattern" => "location_id",
                "label" => _g("標準ﾛｹｰｼｮﾝ（使用）"),
            ),
            array(
                "column" => "default_location_id_3",
                "pattern" => "location_id",
                "label" => _g("標準ﾛｹｰｼｮﾝ（完成）"),
            ),
            array(
                "column" => "received_object",
                "convert" => array(
                    array(
                        "cat" => "nullBlankToValue",
                        "param" => 0,
                    ),
                ),
                "validate" => array(
                    array(
                        "cat" => "selectString",
                        "msg" => _g('受注対象の指定が正しくありません。0(受注対象) 1(非対象) のいずれかを指定してください。'),
                        "param" => array(array(0, 1))
                    ),
                ),
            ),
            array(
                "column" => "tax_class",
                "convert" => array(
                    array(
                        "cat" => "nullBlankToValue",
                        "param" => 0,
                    ),
                ),
                "validate" => array(
                    array(
                        "cat" => "selectString",
                        "msg" => _g('課税区分の指定が正しくありません。0(課税) 1(非課税) のいずれかを指定してください。'),
                        "param" => array(array(0, 1))
                    ),
                ),
            ),
            array(
                "column" => "tax_rate",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                    array(
                        "cat" => "nullBlankToValue",
                        "param" => '',
                    ),
                ),
                "skipValidatePHP" => "[[tax_class]]=='1'||([[tax_class]]=='0'&&$1==='')",
                "skipValidateJS" => "[[tax_class]]=='1'||([[tax_class]]=='0'&&$1==='')",
                "validate" => array(
                    array(
                        "cat" => "minNum",
                        "msg" => _g('税率には0以上の数値を入力してください。'),
                        "param" => 0,
                    ),
                ),
            ),
            array(
                "column" => "dummy_item",
                "pattern" => "bool",
            ),
            array(
                "column" => "dummy_item",
                "dependentColumn" => "order_class",
                "validate" => array(
                    // (15i以降)ロット品目をダミーとするのは禁止。
                    //  ダミー品目をロット品目にすると、納品時の子品目引き落としが行われないという問題があるため。ag.cgi?page=ProjectDocView&ppid=1574&pbid=196054
                    //  ※ちなみに15iでは一時期、製番品目についても上記と同じ制限をかけていた。
                    //  　ダミー製番では所要量計算において子品目のオーダーが出てこないとか（ag.cgi?page=ProjectDocView&pid=1574&did=196010）、
                    //  　受注登録したときの子品目使用予定がフリー製番になってしまう、といった問題があったため。
                    //  　しかしやはりダミー製番を用いたいケースはあり（受注と子品目を紐付きにしたい）、実際に13iでもダミー製番で運用しているユーザーが
                    //  　結構存在することが分かったため、ダミー製番は許可することとした。
                    //  　上に挙げた問題については個別にロジック改善して解消した。ag.cgi?page=ProjectDocView&pid=1574&did=196010
                    array(
                        "cat" => "eval",
                        "msg" => _g('管理区分が「ロット」の場合、ダミー品目にすることはできません。'),
                        "skipHasError" => true,
                        "skipValidateJS" => "[[order_class]]!='2'",     
                        "skipValidatePHP" => "[[order_class]]!='2'",
                        "evalJS" => "res=(!$('#dummy_item').is(':checked'));",   // チェックボックスの場合、JSに '$1' は使えない（valueは常にtrueになる）。
                        "evalPHP" => "\$res=($1=='false');",
                    ),
                    
                    // 受注や入出庫が存在するときはダミーフラグの切り替えを禁止する。
                    // 　受注が存在するときにダミーフラグを切り替えると、所要量計算や納品で矛盾が発生する。
                    //   　ダミー品目は受注時に子品目使用予約を登録し、納品時に削除する。また納品時に子品目の在庫を引き落とす。
                    //　　 さらに、所要量計算においては独立需要に含められない。（ダミー品目のオーダーは発行する必要がない。
                    //　　 子品目については上記のとおり受注時に使用予約が登録されるのでオーダーが出てくる）
                    // 　また、製番在庫が存在するときに品目をダミーにすると動きが不自然になる。
                    //　　 ag.cgi?page=ProjectDocView&ppid=1516&pbid=170037
                    // ※もっとも、受注時点でダミーフラグを記録しておき、所要量計算や納品ではそのフラグを参照するようにすれば
                    //　 前者は解決する。後者についても工夫によってなんとかなりそう。
                    //　 しかし、ダミーフラグの途中変更は運用・サポート的にも非常に複雑になるので、禁止することにした。
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('この品目の受注または入出庫が登録されているため、「ダミー品目」を切り替えることはできません。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[item_id]]==''", // 新規登録はスキップ
                        "param" => "select item_master.item_id from item_master 
                            left join received_detail on item_master.item_id = received_detail.item_id
                            left join item_in_out on item_master.item_id = item_in_out.item_id
                            where item_master.item_id = [[item_id]] and dummy_item <> $1 and (received_detail_id is not null or item_in_out_id is not null)"
                    ),
                ),
            ),
            array(
                "column" => "use_by_days",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                    array(
                        "cat" => "nullBlankToValue",
                        "param" => '',
                    ),
                ),
                "skipValidatePHP" => "$1===''",
                "skipValidateJS" => "$1===''",
                "validate" => array(
                    array(
                        "cat" => "integer",
                        "msg" => _g('消費期限日数には整数を指定してください。'),
                    ),
                    array(
                        "cat" => "minNum",
                        "msg" => _g('消費期限日数には0以上の数字を指定してください。'),
                        "param" => 0
                    ),
                ),
            ),
            // ---------------------------
            // 手配先別項目（リードタイム）
            // ---------------------------
            array(
                "column" => "lead_time",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                ),
                "dependentColumn" => "partner_class_0",
                "validate" => array(
                    array(
                        "cat" => "range",
                        // 内製の場合のみ空欄OK（空欄の場合、工程から決定される）。注文・外製はLT必須
                        "skipValidatePHP" => "([[partner_class_0]]=='3'&&$1==='')",
                        "skipValidateJS" => "([[partner_class_0]]=='3'&&$1==='')",
                        "msg" => _g('手配区分が「内製」の場合を除き、リードタイムは入力必須です。0から365日の日数を指定してください。'),
                        "param" => array(0, 365),
                    ),
                    array(
                        "cat" => "integer",
                        "skipValidatePHP" => "$1===''",
                        "skipValidateJS" => "$1===''",
                        "msg" => _g('リードタイムには整数を指定してください。'),
                    ),
                    // 内製の場合、ひとつでもLT未設定の工程があれば、全体LTも空欄にしなければならない
                    array(
                        "cat" => "eval",
                        "skipValidatePHP" => "$1===''",
                        "skipValidateJS" => "$1===''",
                        "msg" => _g("工程リードタイムが設定されていない工程があります。工程リードタイムをすべて設定するか（固定リードタイム）、このリードタイムを空欄にしてください（自動計算）。"),
                        "skipHasError" => true,
                        "evalPHP" => "\$res=([[partner_class_0]]!='3'||!({$procLtBlankCheckScript}));",
                        "evalJS" => "res=([[partner_class_0]]!='3'||!({$procLtBlankCheckScript}));",
                    ),
                    // LTと工程LTの矛盾チェック
                    array(
                        // 以下がすべて成立する場合にエラーとする
                        // ・手配区分が内製
                        // ・標準工程以外の工程が存在する
                        // ・品目LT入力済みで、その品目LTが工程LTの合計と一致しない
                        //  （詳細はregistのコメント参照）
                        "cat" => "eval",
                        "skipValidatePHP" => "$1===''",
                        "skipValidateJS" => "$1===''",
                        "msg" => _g("手配区分が「内製」の場合、リードタイムを工程リードタイムの合計と一致させるか、もしくは空欄（自動設定）にする必要があります。"),
                        "skipHasError" => true,
                        "evalPHP" => "\$res=([[partner_class_0]]!='3'||!({$procExistCheckScript})||$1==(int)({$procLtSumScriptPHP}));",
                        "evalJS" => "res=([[partner_class_0]]!='3'||!({$procExistCheckScript})||$1=={$procLtSumScriptJS});",
                    ),
                ),
            ),
            array(
                "column" => "safety_lead_time",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                    array(
                        "cat" => "nullBlankToValue",
                        "param" => 0,
                    ),
                ),
                "validate" => array(
                    array(
                        "cat" => "range",
                        "msg" => _g('安全リードタイムには0から365日の日数を指定してください。'),
                        "param" => array(0, 365),
                    ),
                    array(
                        "cat" => "integer",
                        "msg" => _g('安全リードタイムには整数を指定してください。'),
                    ),
                ),
            ),
            array(
                "column" => "partner_class_0",
                "convert" => array(
                    array(
                        "cat" => "nullBlankToValue",
                        "param" => 3, // 内製
                    ),
                ),
                "validate" => array(
                    array(
                        "cat" => "required",
                        "msg" => _g('手配区分は必ず指定する必要があります。'),
                    ),
                ),
            ),
        );

        // ---------------------------
        // 手配先別項目
        // ---------------------------
        for ($i = 0; $i < GEN_ITEM_ORDER_COUNT; $i++) {

            // ******* 手配区分 *******
            $columns[] = array(
                "column" => "partner_class_{$i}",
                "convert" => array(
                    array(
                        "cat" => "blankToNull",
                    ),
                ),
                "validate" => array(
                    array(
                        "cat" => "selectString",
                        "skipValidatePHP" => "$1===null",
                        "skipValidateJS" => "$1===null",
                        "msg" => sprintf(_g("%sが正しくありません。"), ($i == 0 ? _g("標準手配区分") : _g("手配区分") . $i)),
                        "param" => array(array("0", "1", "2", "3")),
                    ),
                    array(
                        "cat" => "eval",
                        "skipValidatePHP" => "$1!==null",
                        "skipValidateJS" => "$1!==null",
                        "msg" => sprintf(_g("%1\$sを指定しているときは、%2\$sも指定してください。"), ($i == 0 ? _g("標準手配先") : _g("代替手配先") . $i), ($i == 0 ? _g("標準手配区分") : _g("手配区分") . $i)),
                        "skipHasError" => true,
                        "evalPHP" => "\$res=([[order_user_id_{$i}]]===null||[[order_user_id_{$i}]]==='');",
                        "evalJS" => "res=([[order_user_id_{$i}]]===null||[[order_user_id_{$i}]]===''||[[order_user_id_{$i}]]==='0');",
                    ),
                ),
            );

            // ******* 手配先コード *******
            $orderUserName = ($i == 0 ? _g("標準手配先") : _g("代替手配先") . $i);
            $validArr = array();
            $validArr[] = array(
                "cat" => "numeric",
                "msg" => sprintf(_g("%sを指定してください。"), $orderUserName),
            );
            $validArr[] = array(
                "cat" => "existRecord",
                "msg" => sprintf(_g("%sを指定してください。%sは取引先マスタ（サプライヤー）に登録されている必要があります。"), $orderUserName, $orderUserName),
                "skipHasError" => true,
                "param" => "select customer_id from customer_master where customer_id = $1 and classification=1"
            );
            // 手配先重複チェック
            $dpArr = array("partner_class_{$i}");
            for ($j = 0; $j < GEN_ITEM_ORDER_COUNT; $j++) {
                if ($j == $i)
                    continue;
                $dpArr[] = "order_user_id_{$j}";
                $validArr[] = array(
                    "cat" => "eval",
                    "msg" => sprintf(_g("%s が %s と重複しています。"), ($i == 0 ? _g("標準手配先") : _g("手配先") . $i), ($j == 0 ? _g("標準手配先") : _g("代替手配先") . $j)),
                    "skipHasError" => true,
                    "evalPHP" => "\$res=([[order_user_id_{$i}]]!=[[order_user_id_{$j}]]);",
                    "evalJS" => "res=([[order_user_id_{$i}]]!=[[order_user_id_{$j}]]);",
                );
            }
            $columns[] = array(
                "column" => "order_user_id_{$i}",
                "dependentColumn" => $dpArr, // このカラムが変化した時もここのバリデーションが行われる
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                    array(
                        "cat" => "blankToNull",
                    ),
                ),
                "skipValidatePHP" => "[[partner_class_{$i}]]===null||[[partner_class_{$i}]]===''||[[partner_class_{$i}]]=='3'", // 内製はスキップ
                "skipValidateJS" => "[[partner_class_{$i}]]===null||[[partner_class_{$i}]]===''||[[partner_class_{$i}]]=='3'",
                "validate" => $validArr,
            );

            // ******* 手配単位 *******
            $columns[] = array(
                "column" => "order_measure_{$i}",
                "convert" => array(
                    array(
                        "cat" => "blankToNull",
                    ),
                ),
            );

            // ******* 手配単位倍数 *******
            $columns[] = array(
                "column" => "multiple_of_order_measure_{$i}",
                "dependentColumn" => "partner_class_{$i}",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                    array(
                        "cat" => "nullBlankToValue",
                        "param" => 1,
                    ),
                ),
                "skipValidatePHP" => "[[partner_class_{$i}]]===null||[[partner_class_{$i}]]==='3'",
                "skipValidateJS" => "[[partner_class_{$i}]]===''||[[partner_class_{$i}]]==='3'",
                "validate" => array(
                    array(
                        "cat" => "minNum",
                        "msg" => sprintf(_g("%sが正しくありません。0より大きい数字を指定してください。"), ($i == 0 ? _g("標準手配単位倍数") : _g("手配単位倍数") . $i)),
                        "param" => 0.0000000001,
                    ),
                ),
            );

            // ******* 最低ロット数 *******
            $columns[] = array(
                "column" => "default_lot_unit_{$i}",
                "dependentColumn" => "partner_class_{$i}",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                    array(
                        "cat" => "nullBlankToValue",
                        "param" => 0,
                    ),
                ),
                "skipValidatePHP" => "[[partner_class_{$i}]]===''||[[partner_class_{$i}]]===null",
                "skipValidateJS" => "[[partner_class_{$i}]]===''",
                "validate" => array(
                    array(
                        "cat" => "minNum",
                        "msg" => sprintf(_g("%1\$s の %2\$sが正しくありません。0以上の数字を指定してください。"), ($i == 0 ? _g("標準手配先") : _g("代替手配先") . $i), _g("最低ロット数")),
                        "param" => 0,
                    ),
                ),
            );

            // ******* 手配ロット数 *******
            $columns[] = array(
                "column" => "default_lot_unit_2_{$i}",
                "dependentColumn" => "partner_class_{$i}",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                    array(
                        "cat" => "nullBlankToValue",
                        "param" => 0,
                    ),
                ),
                "skipValidatePHP" => "[[partner_class_{$i}]]===''||[[partner_class_{$i}]]===null",
                "skipValidateJS" => "[[partner_class_{$i}]]===''",
                "validate" => array(
                    array(
                        "cat" => "minNum",
                        "msg" => sprintf(_g("%1\$s の %2\$sが正しくありません。0以上の数字を指定してください。"), ($i == 0 ? _g("標準手配先") : _g("代替手配先") . $i), _g("手配ロット数")),
                        "param" => 0,
                    ),
                ),
            );

            // 以下、内製の場合はスキップ
            // ******* 購入単価 *******
            $columns[] = array(
                "column" => "default_order_price_{$i}",
                "dependentColumn" => "partner_class_{$i}",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                    array(
                        "cat" => "nullBlankToValue",
                        "param" => 0,
                    ),
                ),
                "skipValidatePHP" => "[[partner_class_{$i}]]===''||[[partner_class_{$i}]]===null||[[partner_class_{$i}]]=='3'", // 内製はスキップ
                "skipValidateJS" => "[[partner_class_{$i}]]===''||[[partner_class_{$i}]]=='3'",
                "validate" => array(
                    array(
                        "cat" => "minNum",
                        "msg" => sprintf(_g("%1\$s の %2\$sが正しくありません。0以上の数字を指定してください。"), ($i == 0 ? _g("標準手配先") : _g("代替手配先") . $i), _g("購入単価1")),
                        "param" => 0,
                    ),
                ),
            );
            $columns[] = array(
                "column" => "default_order_price_2_{$i}",
                "dependentColumn" => "partner_class_{$i}",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                    array(
                        "cat" => "nullBlankToValue",
                        "param" => 0,
                    ),
                ),
                "skipValidatePHP" => "[[partner_class_{$i}]]===''||[[partner_class_{$i}]]===null||[[partner_class_{$i}]]=='3'", // 内製はスキップ
                "skipValidateJS" => "[[partner_class_{$i}]]===''||[[partner_class_{$i}]]=='3'",
                "validate" => array(
                    array(
                        "cat" => "minNum",
                        "msg" => sprintf(_g("%1\$s の %2\$sが正しくありません。0以上の数字を指定してください。"), ($i == 0 ? _g("標準手配先") : _g("代替手配先") . $i), _g("購入単価2")),
                        "param" => 0,
                    ),
                ),
            );
            $columns[] = array(
                "column" => "default_order_price_3_{$i}",
                "dependentColumn" => "partner_class_{$i}",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                    array(
                        "cat" => "nullBlankToValue",
                        "param" => 0,
                    ),
                ),
                "skipValidatePHP" => "[[partner_class_{$i}]]===''||[[partner_class_{$i}]]===null||[[partner_class_{$i}]]=='3'", // 内製はスキップ
                "skipValidateJS" => "[[partner_class_{$i}]]===''||[[partner_class_{$i}]]=='3'",
                "validate" => array(
                    array(
                        "cat" => "minNum",
                        "msg" => sprintf(_g("%1\$s の %2\$sが正しくありません。0以上の数字を指定してください。"), ($i == 0 ? _g("標準手配先") : _g("代替手配先") . $i), _g("購入単価3")),
                        "param" => 0,
                    ),
                ),
            );

            // ******* 購入単価 適用数*******
            $columns[] = array(
                "column" => "order_price_limit_qty_1_{$i}",
                "dependentColumn" => "partner_class_{$i}",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                    array(
                        "cat" => "nullBlankToValue",
                        "param" => "",
                    ),
                ),
                "skipValidatePHP" => "$1===''||[[partner_class_{$i}]]===''||[[partner_class_{$i}]]===null||[[partner_class_{$i}]]=='3'", // 内製はスキップ
                "skipValidateJS" => "$1===''||[[partner_class_{$i}]]===''||[[partner_class_{$i}]]=='3'",
                "validate" => array(
                    array(
                        "cat" => "minNum",
                        "msg" => sprintf(_g("%1\$s の %2\$sが正しくありません。0以上の数字を指定してください。"), ($i == 0 ? _g("標準手配先") : _g("代替手配先") . $i), _g("購入単価1適用数")),
                        "param" => 0,
                    ),
                ),
            );
            $columns[] = array(
                "column" => "order_price_limit_qty_2_{$i}",
                "dependentColumn" => "partner_class_{$i}",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                    array(
                        "cat" => "nullBlankToValue",
                        "param" => "",
                    ),
                ),
                "skipValidatePHP" => "$1===''||[[partner_class_{$i}]]===''||[[partner_class_{$i}]]===null||[[partner_class_{$i}]]=='3'", // 内製はスキップ
                "skipValidateJS" => "$1===''||[[partner_class_{$i}]]===''||[[partner_class_{$i}]]=='3'",
                "validate" => array(
                    array(
                        "cat" => "minNum",
                        "msg" => sprintf(_g("%1\$s の %2\$sが正しくありません。0以上の数字を指定してください。"), ($i == 0 ? _g("標準手配先") : _g("代替手配先") . $i), _g("購入単価2適用数")),
                        "param" => 0,
                    ),
                ),
            );
        }


        // ---------------------------
        // 工程別項目
        // ---------------------------

        for ($i = 0; $i < GEN_ITEM_PROCESS_COUNT; $i++) {

            // ******* 工程  *******
            $validArr = array();
            $validArr[] = array(
                "cat" => "numeric",
                "msg" => sprintf(_g("工程 %s が正しくありません。"), ($i + 1)),
            );
            $validArr[] = array(
                "cat" => "existRecord",
                "msg" => sprintf(_g("工程 %s が工程マスタに登録されていません。"), ($i + 1)),
                "skipHasError" => true,
                "param" => "select process_id from process_master where process_id = $1"
            );
            // 工程重複チェック
            $dpArr = array();
            for ($j = 0; $j < GEN_ITEM_PROCESS_COUNT; $j++) {
                if ($i == $j)
                    continue;
                $dpArr[] = "process_id_{$j}";
                $validArr[] = array(
                    "cat" => "eval",
                    "msg" => sprintf(_g("工程 %s が 工程 %s と重複しています。"), ($i + 1), ($j + 1)),
                    "skipHasError" => true,
                    "evalPHP" => "\$res=([[process_id_{$i}]]!=[[process_id_{$j}]]);",
                    "evalJS" => "res=([[process_id_{$i}]]!=[[process_id_{$j}]]);",
                );
            }
            $columns[] = array(
                "column" => "process_id_{$i}",
                "dependentColumn" => $dpArr, // 工程重複チェックのエラー解除用
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                    array(
                        "cat" => "blankToNull",
                    ),
                ),
                "skipValidatePHP" => "$1===null",
                "skipValidateJS" => "$1===null",
                "validate" => $validArr,
            );

            // ******* 標準加工時間 *******
            $columns[] = array(
                "column" => "default_work_minute_{$i}",
                "dependentColumn" => "process_id_{$i}",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                    array(
                        "cat" => "nullBlankToValue",
                        "param" => 0,
                    ),
                ),
                "skipValidatePHP" => "[[process_id_{$i}]]==''||[[process_id_{$i}]]==null",
                "skipValidateJS" => "[[process_id_{$i}]]==''",
                "validate" => array(
                    array(
                        "cat" => "minNum",
                        "msg" => sprintf(_g("工程 %s の標準加工時間が正しくありません。0以上の数字を指定してください。(数字は半角で入力してください)"), ($i + 1)),
                        "param" => 0,
                    ),
                ),
            );

            // ******* 製造能力 *******
            $columns[] = array(
                "column" => "pcs_per_day_{$i}",
                "dependentColumn" => "process_id_{$i}",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                    array(
                        "cat" => "nullBlankToValue",
                        "param" => 0,
                    ),
                ),
                "skipValidatePHP" => "[[pcs_per_day_{$i}]]==''",
                "skipValidateJS" => "[[pcs_per_day_{$i}]]==''",
                "validate" => array(
                    array(
                        "cat" => "minNum",
                        "msg" => sprintf(_g("工程 %s の製造能力が正しくありません。0以上の数字を指定してください。(数字は半角で入力してください)"), ($i + 1)),
                        "param" => 0,
                    ),
                ),
            );
            $columns[] = array(
                "column" => "pcs_per_day_{$i}",
                "dependentColumn" => "process_lt_{$i}",
                "skipValidatePHP" => "[[process_id_{$i}]]==''||[[process_lt_{$i}]]!=''",
                "skipValidateJS" => "[[process_id_{$i}]]==''||[[process_lt_{$i}]]!=''",
                "validate" => array(
                    // 工程LT空欄（LTを製造能力により決定）のとき、製造能力は0不許可
                    array(
                        "cat" => "notEqualString",
                        "msg" => sprintf(_g("工程 %s の製造能力が正しくありません。リードタイムが空欄のとき、製造能力を0にすることはできません。"), ($i + 1)),
                        "param" => '0',
                    ),
                ),
            );

            // ******* 工賃 *******
            $columns[] = array(
                "column" => "charge_price_{$i}",
                "dependentColumn" => "process_id_{$i}",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                    array(
                        "cat" => "nullBlankToValue",
                        "param" => 0,
                    ),
                ),
                "skipValidatePHP" => "[[process_id_{$i}]]==''||[[process_id_{$i}]]==null",
                "skipValidateJS" => "[[process_id_{$i}]]==''",
                "validate" => array(
                    array(
                        "cat" => "minNum",
                        "msg" => sprintf(_g("工程 %s の工賃が正しくありません。0以上の数字を指定してください。(数字は半角で入力してください)"), ($i + 1)),
                        "param" => 0,
                    ),
                ),
            );

            // ******* 外製先 *******
            $columns[] = array(
                "column" => "subcontract_partner_id_{$i}",
                "pattern" => "customer_id",
                "label" => sprintf(_g("工程 %s の外製先"), ($i + 1)),
                "addwhere" => "classification=1",
            );
            $columns[] = array(
                "column" => "subcontract_partner_id_{$i}",
                "dependentColumn" => "process_id_{$i}",
                "skipValidatePHP" => "[[subcontract_partner_id_{$i}]]==''",
                "skipValidateJS" => "[[subcontract_partner_id_{$i}]]==''",
                "validate" => array(
                    array(
                        "cat" => "eval",
                        "msg" => sprintf(_g("工程 %s の外製先が指定されていますが、工程が指定されていません。"), ($i + 1)),
                        "skipHasError" => true,
                        "evalPHP" => "\$res=[[process_id_{$i}]]!='0'&&[[process_id_{$i}]]!=''&&[[process_id_{$i}]]!=null;",
                        "evalJS" => "res=[[process_id_{$i}]]!='0'&&[[process_id_{$i}]]!='';",
                    ),
                ),
            );

            // ******* 外製単価 *******
            $columns[] = array(
                "column" => "subcontract_unit_price_{$i}",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                    array(
                        "cat" => "nullBlankToValue",
                        "param" => 0,
                    ),
                ),
                "validate" => array(
                    array(
                        "cat" => "minNum",
                        "msg" => sprintf(_g("工程 %s の外製単価が正しくありません。0以上の数字を指定してください。"), ($i + 1)),
                        "param" => 0,
                    ),
                ),
            );

            // ******* 工程リードタイム *******
            $columns[] = array(
                "column" => "process_lt_{$i}",
                "dependentColumn" => "process_id_{$i}",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                ),
                "skipValidatePHP" => "[[process_lt_{$i}]]==''",
                "skipValidateJS" => "[[process_lt_{$i}]]==''",
                "validate" => array(
                    array(
                        "cat" => "minNum",
                        "msg" => sprintf(_g("工程 %s の工程リードタイムが正しくありません。0以上の数字を指定してください。(数字は半角で入力してください)"), ($i + 1)),
                        "param" => 0,
                    ),
                    array(
                        "cat" => "integer",
                        "msg" => sprintf(_g("工程 %s の工程リードタイムが正しくありません。整数を指定してください。"), ($i + 1)),
                    ),
                ),
            );
            $columns[] = array(
                "column" => "process_lt_{$i}",
                "dependentColumn" => "pcs_per_day_{$i}",
                "skipValidatePHP" => "[[process_id_{$i}]]==''",
                "skipValidateJS" => "[[process_id_{$i}]]==''",
                "validate" => array(
                    array(
                        "cat" => "eval",
                        "msg" => sprintf(_g("工程 %s の工程リードタイムもしくは製造能力のどちらかを指定する必要があります。"), ($i + 1)),
                        "skipHasError" => true,
                        "evalPHP" => "\$res=(!($1=='' && [[pcs_per_day_{$i}]]==''));",
                        "evalJS" => "res=(!($1=='' && [[pcs_per_day_{$i}]]==''));",
                    ),
                ),
            );

            // ******* 固定経費 *******
            $columns[] = array(
                "column" => "overhead_cost_{$i}",
                "dependentColumn" => "process_id_{$i}",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                    array(
                        "cat" => "nullBlankToValue",
                        "param" => 0,
                    ),
                ),
                "skipValidatePHP" => "[[process_id_{$i}]]==''||[[process_id_{$i}]]==null",
                "skipValidateJS" => "[[process_id_{$i}]]==''",
                "validate" => array(
                    array(
                        "cat" => "minNum",
                        "msg" => sprintf(_g("工程 %s の固定経費が正しくありません。0以上の数字を指定してください。(数字は半角で入力してください)"), ($i + 1)),
                        "param" => 0,
                    ),
                ),
            );
        }

        return $columns;
    }

    protected function _regist(&$param, $isFirstRegist)
    {
        global $gen_db;

        // 製番在庫のときは安全在庫数を0にする
        if ($param['order_class'] == "0") {
            $param['safety_stock'] = 0;
        }

        //        品目マスタ登録時のLT関連の処理
        //            標準手配先が内製の場合
        //                品目LTが空欄： 工程LTの合計を品目LTとする。
        //                品目LT入力済： 工程LTの合計と品目LTが異なっていたらvalidエラーとする。
        //                          ただし工程が標準工程しかない場合（=工程管理しない場合）、標準工程のLT = 品目LTとする。
        //            標準手配先が内製以外の場合
        //                品目LTが空欄： 0とする。
        //                品目LT入力済： そのまま品目LTとする。
        //
        //            ※所要量計算のコードを書き換えなくてすむように、上記のような仕様（品目LTと工程LTを別個
        //             に持ち、標準手配先が内製の場合のみ両者が一致するようにする。所要量計算では従来どおり
        //             品目LT、工程別納期設定には工程LTを使用する）とした。
        //
        //            <-------- 品目LT -------->|<-- 安全LT -->|
        //            <- 工程1LT -><- 工程2LT ->|              |
        //                                    納期          需要日
        //
        //            ※ちなみに内製の場合は強制的に工程LTの合計を品目LTとしてしまえばよいように思えるが、
        //             それだと工程管理しないユーザーにとって不便。
        //            ※LTは所要量計算にのみ関係がある。また所要量計算において参照されるのは標準手配先のみ。
        //             したがって標準手配先だけを考慮すればよい。
        //
        //        ※従来のLT/安全LTは、所要量計算にのみ関係があった。指示書・注文書を手発行する場合、発注日
        //         も納期もユーザーが指定するためLTは関係なかった。
        //         しかし今回の工程LTは、指示書を手発行する際にも関係あることに注意（内部的に各工程の納期を
        //         決定するのに使用される）
        //
        //        ※10iからはLT/工程LTを空欄にすることができるようになった（可変LT）。
        //            そのため、LTの自動計算はすべての工程のLTが指定されているときのみ行われるようになった。

        if ($param['partner_class_0'] == "3") {
            // 標準手配先が内製
            if ($param['lead_time'] == "") {
                // 品目LT未設定
                $param['lead_time'] = null;
                for ($i = 0; $i < GEN_ITEM_PROCESS_COUNT; $i++) {
                    if (isset($param["process_id_{$i}"]) && $param["process_id_{$i}"] != '') {
                        if ($param["process_lt_{$i}"] == '') {
                            // 1工程でもLT空欄だった場合、品目LTも空欄とする
                            $param['lead_time'] = null;
                            break;
                        } else {
                            $param['lead_time'] += $param["process_lt_{$i}"];
                        }
                    }
                }
            } else {
                $count = 0;
                for ($i = 0; $i < GEN_ITEM_PROCESS_COUNT; $i++) {
                    if (@$param["process_id_{$i}"] != "")
                        $count++;
                }
                if ($count == 1 && $param['process_id_0'] == "0") {
                    // 品目LTが設定されているが、工程が標準工程しかない　⇒　品目LTを標準工程のLTとして設定
                    $param['process_lt_0'] = $param['lead_time'];
                }
            }
        } else {
            // 内製以外
            if ($param['lead_time'] == "") {
                $param['lead_time'] = null;
            }
        }

        // 登録処理
        // ***** item_master *****
        if (isset($param['item_id']) && is_numeric($param['item_id'])) {
            $key = array("item_id" => $param['item_id']);
        } else {
            $key = null;
        }
        $data = array(
            'item_code' => $param['item_code'],
            'item_name' => $param['item_name'],
            'order_class' => $param['order_class'],
            'default_selling_price' => $param['default_selling_price'],
            'default_selling_price_2' => $param['default_selling_price_2'],
            'default_selling_price_3' => $param['default_selling_price_3'],
            'selling_price_limit_qty_1' => (is_numeric($param['selling_price_limit_qty_1']) ? $param['selling_price_limit_qty_1'] : null),
            'selling_price_limit_qty_2' => (is_numeric($param['selling_price_limit_qty_2']) ? $param['selling_price_limit_qty_2'] : null),
            'stock_price' => $param['stock_price'],
            'without_mrp' => $param['without_mrp'],
            'received_object' => $param['received_object'],
            'lead_time' => $param['lead_time'],
            'safety_lead_time' => $param['safety_lead_time'],
            'safety_stock' => $param['safety_stock'],
            'maker_name' => $param['maker_name'],
            'spec' => $param['spec'],
            'use_by_days' => (is_numeric($param['use_by_days']) ? $param['use_by_days'] : null),
            'lot_header' => $param['lot_header'],
            'comment' => $param['comment'],
            'comment_2' => $param['comment_2'],
            'comment_3' => $param['comment_3'],
            'comment_4' => $param['comment_4'],
            'comment_5' => $param['comment_5'],
            'item_group_id' => @$param['item_group_id'],
            'item_group_id_2' => @$param['item_group_id_2'],
            'item_group_id_3' => @$param['item_group_id_3'],
            'default_location_id' => @$param['default_location_id'],
            'default_location_id_2' => @$param['default_location_id_2'],
            'default_location_id_3' => @$param['default_location_id_3'],
            'payout_price' => @$param['payout_price'],
            'measure' => @$param['measure'],
            'rack_no' => @$param['rack_no'],
            'quantity_per_carton' => @$param['quantity_per_carton'],
            'tax_class' => @$param['tax_class'],
            'tax_rate' => (isset($param['tax_rate']) && is_numeric($param['tax_rate']) ? $param['tax_rate'] : null),
            'end_item' => @$param['end_item'],
            'dummy_item' => @$param['dummy_item'],
            'dropdown_flag' => @$param['dropdown_flag'],
        );
        // ちなみにフィールドllcは、CREATE TABLE で default 0 の設定がされている。
        // そのため更新の場合はそのまま、新規の場合は0が入る。
        $gen_db->updateOrInsert('item_master', $key, $data);

        if (isset($param['item_id']) && is_numeric($param['item_id'])) {
            $item_id = $param['item_id'];
        } else {
            $item_id = $gen_db->getSequence("item_master_item_id_seq");
            $param['item_id'] = $item_id;
        }

        // ***** item_order_master *****
        // clickEdit用。内製、かつ手配先が指定されている場合の処理。
        // 一般の登録では _setDefault() において、内製の場合は手配先を消すという処理をしているので、そのような状況は生じない。
        // しかし clickEditだけは _setDefault()が実行されないので、このような状況が生じ得る。
        // そもそも clickEditでは手配区分と手配先を同時に変更できないため、いったんはこのような登録を許可せざるを得ない。
        // しかしそのまま登録すると矛盾するので、手配区分と手配先のどちらを変更したかを調べ、それに応じて処理を行う。
        for ($i = 0; $i < GEN_ITEM_ORDER_COUNT; $i++) {
            if ($param["partner_class_{$i}"] == '3' && isset($param["order_user_id_{$i}"]) && $param["order_user_id_{$i}"] != '' && $param["order_user_id_{$i}"] != '0') {
                $query = "select partner_class from item_order_master where item_id = '{$item_id}' and line_number = '{$i}'";
                $pClass = $gen_db->queryOneValue($query);
                if ($pClass !== null) {
                    if ($pClass === "3") {
                        // 元から内製だった場合。今回の登録で手配先が指定されたということなので、手配区分を発注にする
                        $param["partner_class_{$i}"] = '0';
                    } else {
                        // 元が内製以外だった場合。今回の登録で内製に変わったということなので、手配先を消す（内製：0 にする）
                        $param["order_user_id_{$i}"] = "0";
                    }
                }
            }
        }

        // 更新の場合、いったん削除
        $query = "delete from item_order_master where item_id = '{$item_id}'";
        $gen_db->query($query);

        for ($i = 0; $i < GEN_ITEM_ORDER_COUNT; $i++) {
            if ($param["order_user_id_{$i}"] != null) {
                // 登録
                $data = array(
                    'item_id' => $item_id,
                    'line_number' => $i,
                    'order_user_id' => $param["order_user_id_{$i}"],
                    'default_order_price' => @$param["default_order_price_{$i}"],
                    'default_order_price_2' => @$param["default_order_price_2_{$i}"],
                    'default_order_price_3' => @$param["default_order_price_3_{$i}"],
                    'order_price_limit_qty_1' => (is_numeric(@$param["order_price_limit_qty_1_{$i}"]) ? @$param["order_price_limit_qty_1_{$i}"] : null),
                    'order_price_limit_qty_2' => (is_numeric(@$param["order_price_limit_qty_2_{$i}"]) ? @$param["order_price_limit_qty_2_{$i}"] : null),
                    'default_lot_unit' => @$param["default_lot_unit_{$i}"],
                    'default_lot_unit_2' => @$param["default_lot_unit_2_{$i}"],
                    // 本来、default_lot_unit は「手配ロット数1」で、default_lot_unit_limit はそれを適用する上限数である。
                    // しかし09iでは default_lot_unit と  default_lot_unit_limit に同じ数を登録することで、
                    // default_lot_unit を 「最低ロット数」、default_lot_unit_2を「手配ロット数」として使用している
                    'default_lot_unit_limit' => @$param["default_lot_unit_{$i}"],
                    'item_sub_code' => @$param["item_sub_code_{$i}"],
                    'partner_class' => @$param["partner_class_{$i}"],
                    'order_measure' => @$param["order_measure_{$i}"],
                    'multiple_of_order_measure' => (is_numeric($param["partner_class_{$i}"]) ? $param["multiple_of_order_measure_{$i}"] : 1),
                );
                $gen_db->insert('item_order_master', $data);
            }
        }

        // ***** item_process_master *****
        // 更新の場合、いったん削除
        $query = "delete from item_process_master where item_id = '{$item_id}'";
        $gen_db->query($query);

        for ($i = 0; $i < GEN_ITEM_PROCESS_COUNT; $i++) {
            if (isset($param["process_id_{$i}"]) && $param["process_id_{$i}"] !== null && $param["process_id_{$i}"] !== null) {
                // 登録
                $data = array(
                    'item_id' => $item_id,
                    'process_id' => $param["process_id_{$i}"],
                    'machining_sequence' => $i,
                    'default_work_minute' => $param["default_work_minute_{$i}"],
                    'pcs_per_day' => $param["pcs_per_day_{$i}"],
                    'charge_price' => $param["charge_price_{$i}"],
                    'process_lt' => (is_numeric($param["process_lt_{$i}"]) ? $param["process_lt_{$i}"] : null),
                    'overhead_cost' => @$param["overhead_cost_{$i}"],
                    'subcontract_partner_id' => @$param["subcontract_partner_id_{$i}"],
                    'subcontract_unit_price' => @$param["subcontract_unit_price_{$i}"],
                    'process_remarks_1' => @$param["process_remarks_1_{$i}"],
                    'process_remarks_2' => @$param["process_remarks_2_{$i}"],
                    'process_remarks_3' => @$param["process_remarks_3_{$i}"],
                );
                $gen_db->insert('item_process_master', $data);
            }
        }

        // id(keyColumnの値)を戻す。keyColumnがないModelではfalseを戻す。
        return $item_id;
    }

}
