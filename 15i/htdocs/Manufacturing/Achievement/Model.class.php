<?php

class Manufacturing_Achievement_Model extends Base_ModelBase
{

    protected function _getKeyColumn()
    {
        return 'achievement_id';
    }

    protected function _setDefault(&$param, $entryMode)
    {
        // Entry/BulkEntry/BarcodeEntry の headerArray/detailArray にないパラメータを生成するときは
        // ここで行う。

        global $gen_db;

        if (isset($param['order_detail_id']) && Gen_String::isNumeric($param['order_detail_id'])) {
            $query = "select order_class from order_detail inner join item_master on order_detail.item_id = item_master.item_id where order_detail_id = '{$param['order_detail_id']}'";
            $param['order_class'] = $gen_db->queryOneValue($query);
        }

        switch ($entryMode) {
            case "easy":
                $query = "select * from order_detail where order_detail_id = '{$param['order_detail_id']}'";
                $obj = $gen_db->queryOneRowObject($query);
                $param['achievement_date'] = $obj->order_detail_dead_line;
                $param['achievement_quantity'] = $obj->order_detail_quantity;
                $param['work_minute'] = 0;
                $param['break_minute'] = 0;
                $param['location_id'] = -1;          // 標準ロケ使用
                $param['child_location_id'] = -1;    // 標準ロケ使用
                $param['remarks'] = '';

                $param['begin_time'] = "";
                $param['end_time'] = "";
                $param['section_id'] = "";
                $param['equip_id'] = "";
                $param['worker_id'] = "";

                // 最終工程の工程idを取得（簡易登録では最終工程の実績として登録される）
                $query = "
                select
                    process_id
                from
                    order_process
                where
                    order_detail_id = '{$param['order_detail_id']}'
                    and machining_sequence =
                    (select max(machining_sequence) from order_process
                        where order_detail_id = '{$param['order_detail_id']}'
                    )
                ";
                $param['process_id'] = $gen_db->queryOneValue($query);

                for ($i = 1; $i <= GEN_WASTER_COUNT; $i++) {
                    $param["waster_id_{$i}"] = "";
                }

                break;

            case "bulk":
                $res = Logic_Achievement::getOrderProcessInfo($param['order_process_no']);
                $param['order_detail_id'] = $res->order_detail_id;
                $param['remarks'] = $res->remarks_header;
                $param['work_minute'] = Gen_Math::mul($res->default_work_minute, (Gen_String::isNumeric($param['achievement_quantity']) ? $param['achievement_quantity'] : 0));
                $param['break_minute'] = 0;
                $param['process_id'] = $res->process_id;
                for ($i = 1; $i <= GEN_WASTER_COUNT; $i++) {
                    $param["waster_id_{$i}"] = "";
                }

                if (isset($param['isZeroFinish']) && $param['isZeroFinish']) {
                    $param['achievement_quantity'] = "0";
                    $param['order_detail_completed'] = "true";
                }
                break;

            case "barcode":
                $res = Logic_Achievement::getOrderProcessInfo($param['order_process_no']);
                $param['order_detail_id'] = $res->order_detail_id;
                $param['process_id'] = $res->process_id;
                $param['break_minute'] = 0;
                if (@$param['accept_completed']) {
                    $param['order_detail_completed'] = "true";
                }
                break;

            case "csv":
                $res = Logic_Achievement::getOrderProcessInfo($param['order_process_no']);
                $param['order_detail_id'] = $res->order_detail_id;
                $param['process_id'] = $res->process_id;

                // コード => ID
                if ($param["location_code"] == '') {
                    $param["location_id"] = '0';
                } else if ($param["location_code"] == '-1') {
                    $param["location_id"] = '-1';
                } else {
                    self::_codeToId($param, "location_code", "location_id", "", "", "location_master");
                }

                if ($param["child_location_code"] == '') {
                    $param["child_location_id"] = '0';
                } else if ($param["child_location_code"] == '-1') {
                    $param["child_location_id"] = '-1';
                } else {
                    self::_codeToId($param, "child_location_code", "child_location_id", "location_code", "location_id", "location_master");
                }

                self::_codeToId($param, "section_code", "section_id", "", "", "section_master");
                self::_codeToId($param, "worker_code", "worker_id", "", "", "worker_master");
                self::_codeToId($param, "equip_code", "equip_id", "", "", "equip_master");
                for ($i = 1; $i <= GEN_WASTER_COUNT; $i++) {
                    self::_codeToId($param, "waster_code_{$i}", "waster_id_{$i}", "waster_code", "waster_id", "waster_master");
                }
                break;
        }
    }

    protected function _getColumns()
    {
        $columns = array(
            array(
                "column" => "achievement_id",
                "pattern" => "id",
            ),
            array(
                "column" => "achievement_date",
                "validate" => array(
                    array(
                        "cat" => "systemDateOrLater",
                        "msg" => _g('製造日')
                    ),
                ),
            ),
            array(
                "column" => "begin_time",
                "dependentColumn" => "end_time",
                "skipValidatePHP" => "$1==''",
                "skipValidateJS" => "$1==''",
                "validate" => array(
                    array(
                        "cat" => "timeString",
                        "msg" => _g('製造開始時刻が正しくありません。「10:00」のような形で指定してください。')
                    ),
                    // 秒を指定されると、製造時間（分）が割り切れない数値になることがある
                    array(
                        "cat" => "eval",
                        "msg" => _g('製造開始時刻に秒を指定することはできません。「時:分」の形で指定してください。'),
                        "evalPHP" => "\$res=($1=='' || count(explode(':',$1))<=2);",
                        "evalJS" => "res=($1=='' || $1.split(':').length<=2);"
                    ),
                ),
            ),
            array(
                "column" => "begin_time",
                "dependentColumn" => "end_time",
                "skipValidatePHP" => "$1==''&&[[end_time]]==''",
                "skipValidateJS" => "$1==''&&[[end_time]]==''",
                "validate" => array(
                    array(
                        "cat" => "eval",
                        "msg" => _g("製造終了時刻が指定されている場合、製造開始時刻も指定する必要があります。"),
                        "evalPHP" => "\$res=($1!='' || [[end_time]]=='');",
                        "evalJS" => "res=($1!='' || [[end_time]]=='');",
                    ),
                ),
            ),
            array(
                "column" => "end_time",
                "dependentColumn" => "begin_time",
                "skipValidatePHP" => "$1==''",
                "skipValidateJS" => "$1==''",
                "validate" => array(
                    array(
                        "cat" => "timeString",
                        "msg" => _g('製造終了時刻が正しくありません。「10:00」のような形で指定してください。')
                    ),
                    // 秒を指定されると、製造時間（分）が割り切れない数値になることがある
                    array(
                        "cat" => "eval",
                        "msg" => _g('製造終了時刻に秒を指定することはできません。「時:分」の形で指定してください。'),
                        "evalPHP" => "\$res=($1=='' || count(explode(':',$1))<=2);",
                        "evalJS" => "res=($1=='' || $1.split(':').length<=2);"
                    ),
                    array(
                        "cat" => "eval",
                        "msg" => _g("製造終了時刻が開始時刻より早くなっています。"),
                        "skipHasError" => true,
                        "evalPHP" => "\$val1=[[begin_time]];\$val2=$1;if(\$val1==''||\$val2==''){\$res=true;}else{\$res=(strtotime(date('2000/1/1 ').\$val1)<=strtotime(date('2000/1/1 '.\$val2)));};",
                        "evalJS" => "val1=[[begin_time]];val2=$1;if(val1==''||val2==''){res=true}else{res=(gen.date.parseDateStr('2000/1/1 '+val1)<=gen.date.parseDateStr('2000/1/1 '+val2))};",
                    ),
                ),
            ),
            array(
                "column" => "end_time",
                "dependentColumn" => array("begin_time", "work_minute"),
                "skipValidatePHP" => "$1==''&&[[begin_time]]==''",
                "skipValidateJS" => "$1==''&&[[begin_time]]==''",
                "validate" => array(
                    array(
                        "cat" => "eval",
                        "msg" => _g("製造開始時刻と製造時間が指定されている場合、製造終了時刻も指定する必要があります。"),
                        "skipHasError" => true,
                        "evalPHP" => "\$res=([[begin_time]]==''||$1!=''||[[work_minute]]==''||[[work_minute]]=='0');",
                        "evalJS" => "res=([[begin_time]]==''||$1!=''||[[work_minute]]==''||[[work_minute]]=='0');",
                    ),
                ),
            ),
            array(
                "column" => "work_minute",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                ),
                "validate" => array(
                    array(
                        "cat" => "numeric",
                        "msg" => _g('製造時間（分）が正しくありません。数字（分）で指定してください。'),
                    ),
                    array(
                        "cat" => "minNum",
                        "msg" => _g("製造時間（分）には0以上の数値を入力してください。"),
                        "param" => 0,
                        "skipHasError" => true,
                    ),
                    array(
                        "cat" => "eval",
                        "msg" => _g("製造時間（分）が製造開始時刻・終了時刻・休憩時間と矛盾しています。"),
                        "skipHasError" => true,
                        "evalPHP" => "\$val1=[[begin_time]];\$val2=[[end_time]];\$val3=$1;\$val4=[[break_minute]];if(!is_numeric(\$val4))\$val4=0;if(\$val1==''||\$val2==''){\$res=true;}else{\$res=(int)((strtotime(date('2000/1/1 '.\$val2))-strtotime(date('2000/1/1 '.\$val1)))/60)==(\$val3+\$val4);};",
                        "evalJS" => "val1=[[begin_time]];val2=[[end_time]];val3=$1;val4=[[break_minute]];if(isNaN(val4)||val4=='')val4=0;if(val1==''||val2==''){res=true}else{res=((gen.date.parseDateStr('2000/1/1 '+val2)-gen.date.parseDateStr('2000/1/1 '+val1))/60000)==gen.util.decCalc(val3,val4,'+')};",
                        "errorCol" => "work_minute",
                        "skipHasError" => true,
                    ),
                ),
            ),
            array(
                "column" => "break_minute",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                    array(
                        "cat" => "nullBlankToValue",
                        "param" => 0
                    )
                ),
                "validate" => array(
                    array(
                        "cat" => "numeric",
                        "msg" => _g('休憩時間が正しくありません。数字（分）で指定してください。'),
                    ),
                ),
            ),
            array(
                "column" => "order_detail_id",
                "pattern" => "order_detail_id_required",
                "addwhere" => "classification=0"
            ),
            array(
                "column" => "order_detail_id",
                "validate" => array(
                    array(
                        "cat" => "existRecord",
                        "msg" => _g('指定されたオーダー番号が正しくないか、そのオーダーの実績登録がすでに完了しています。'),
                        "skipValidatePHP" => "[[achievement_id]]!=''", // 修正モードではチェックしない
                        "skipValidateJS" => "[[achievement_id]]!=''", // 修正モードではチェックしない
                        "skipHasError" => true,
                        "param" => "select order_detail_id from order_detail where order_detail_id = $1 and (order_detail_completed = false or order_detail_completed is null)",
                    ),
                ),
            ),
            array(
                "column" => "achievement_quantity",
                // 2番目のチェックのためにはこれがあったほうがいいが、これを有効にすると end_timeを入力したときに即時
                // 1番目のチェックが働いてしまうのがうっとおしいので、やめた。
                //"dependentColumn" => "end_time",
                "convert" => array(
                    array(
                        "cat" => "strToNum",
                    ),
                ),
                "validate" => array(
                    array(
                        "cat" => "numeric",
                        "msg" => _g('製造数量が正しくありません。'),
                    ),
                    array(
                        "cat" => "eval",
                        "msg" => _g("製造開始時刻だけが指定されている場合、製造数は「0」にする必要があります。"),
                        "evalPHP" => "\$res=([[begin_time]]==''||[[end_time]]!=''||$1=='0');",
                        "evalJS" => "res=([[begin_time]]==''||[[end_time]]!=''||$1=='0');",
                    ),
                ),
            ),
            array(
                "column" => "order_detail_completed",
                "pattern" => "bool",
            ),
            array(
                "column" => "cost_1",
                "convert" => array(
                    array(
                        "cat" => "nullBlankToValue",
                        "param" => 0
                    ),
                    array(
                        "cat" => "strToNum",
                    ),
                ),
                "validate" => array(
                    array(
                        "cat" => "numeric",
                        "msg" => _g('製造経費1が正しくありません。'),
                    ),
                ),
            ),
            array(
                "column" => "cost_2",
                "convert" => array(
                    array(
                        "cat" => "nullBlankToValue",
                        "param" => 0
                    ),
                    array(
                        "cat" => "strToNum",
                    ),
                ),
                "validate" => array(
                    array(
                        "cat" => "numeric",
                        "msg" => _g('製造経費2が正しくありません。'),
                    ),
                ),
            ),
            array(
                "column" => "cost_3",
                "convert" => array(
                    array(
                        "cat" => "nullBlankToValue",
                        "param" => 0
                    ),
                    array(
                        "cat" => "strToNum",
                    ),
                ),
                "validate" => array(
                    array(
                        "cat" => "numeric",
                        "msg" => _g('製造経費3が正しくありません。'),
                    ),
                ),
            ),
            array(
                "column" => "location_id",
                "pattern" => "location_id",
                "label" => _g("ロケーション（完成品入庫）"),
            ),
            array(
                "column" => "child_location_id",
                "pattern" => "location_id",
                "label" => _g("ロケーション（使用部材出庫）"),
            ),
            array(
                "column" => "lot_id",
                "pattern" => "lot_id",
            ),
            array(
                "column" => "process_id",
                "pattern" => "process_id",
            ),
            array(
                "column" => "process_id",
                "validate" => array(
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('外製工程の実績は外製受入登録画面で登録してください。'),
                        "skipHasError" => true,
                        "param" => "select order_detail_id from order_process where order_detail_id = [[order_detail_id]] and process_id = $1 and coalesce(subcontract_partner_id,0) <> 0",
                    ),
                ),
            ),
            array(
                "column" => "section_id",
                "pattern" => "section_id",
            ),
            array(
                "column" => "worker_id",
                "pattern" => "worker_id",
            ),
            array(
                "column" => "equip_id",
                "pattern" => "equip_id",
            ),
            array(
                "column" => "remarks",
                "pattern" => "nullToBlank",
            ),
            array(
                "column" => "lot_no",
                "convert" => array(
                    array(
                        "cat" => "trimEx",
                    ),
                ),
                "skipValidatePHP" => "$1==''",
                "skipValidateJS" => "$1==''",
                // ロット品目に関しては、ロット番号の重複不可とする。
                // ロット管理機能では実績/受入とロット番号が1:1ということが前提となっているため。
                // ag.cgi?page=ProjectDocView&ppid=1574&pbid=224713
               "validate" => array(
                    // 新規登録時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('ロット番号はすでに使用されています。ロット管理品目の場合、ロット番号は重複できません。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[order_class]]!='2' || [[achievement_id]]!=''", // 修正はスキップ
                        "param" => "select lot_no from achievement where lot_no = $1 union select lot_no from accepted where lot_no = $1"
                    ),
                    // 修正時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('ロット番号はすでに使用されています。ロット管理品目の場合、ロット番号は重複できません。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[order_class]]!='2' || [[achievement_id]]==''", // 新規登録はスキップ
                        // 更新のときは、自分自身の番号をチェック対象としない（自分自身と重複するのは当然）
                        "param" => "select lot_no from achievement
                            where lot_no = $1 and achievement_id <> [[achievement_id]]
                            union select lot_no from accepted where lot_no = $1"
                    ),
                ),
            ),
            array(
                "column" => "use_by",
                "skipValidatePHP" => "$1==''",
                "skipValidateJS" =>  "$1==''",
            	"validate" => array(
                    array(
                        "cat" => "systemDateOrLater",
                        "msg" => _g('消費期限')
                    ),
                ),
            ),
        );
        for ($i = 1; $i <= GEN_WASTER_COUNT; $i++) {
            $columns[] = array(
                "column" => "waster_id_{$i}",
                "pattern" => "waster_id",
            );
            $columns[] = array(
                "column" => "waster_id_{$i}",
                "skipValidatePHP" => "($1===''||$1===null)&&([[waster_quantity_{$i}]]===''||[[waster_quantity_{$i}]]===null)", // 不適合数が入力されていない行はブランクOK
                "skipValidateJS" => "($1===''||$1===null)&&([[waster_quantity_{$i}]]===''||[[waster_quantity_{$i}]]===null)",
                "validate" => array(
                    array(
                        "cat" => "numeric",
                        "msg" => sprintf(_g('不適合理由%sが正しくありません。'), $i),
                    ),
                ),
            );
            $columns[] = array(
                "column" => "waster_quantity_{$i}",
                "skipValidatePHP" => "($1===''||$1===null)&&([[waster_id_{$i}]]===''||[[waster_id_{$i}]]===null)", // 不適合理由が選択されていない行はブランクOK
                "skipValidateJS" => "($1===''||$1===null)&&([[waster_id_{$i}]]===''||[[waster_id_{$i}]]===null)",
                "validate" => array(
                    array(
                        "cat" => "numeric",
                        "msg" => sprintf(_g('不適合数%sが正しくありません。'), $i),
                    ),
                ),
            );
        }

        return $columns;
    }

    protected function _regist(&$param, $isFirstRegist)
    {
        global $gen_db;

        // 更新の場合は、先に削除を行う
        // （入出庫等の調整があるので、単純にUpdateしてはダメ。いったん削除し、あらためて登録を行う）
        if (isset($param['achievement_id'])) {
            // ロット品目の場合は在庫製番を登録ごとに新規取得するので、
            // 更新の場合に在庫製番が変わらないよう、ここで既存の製番を取得しておく。
            $query = "select stock_seiban from achievement where achievement_id = '{$param['achievement_id']}'";
            $stockSeiban = $gen_db->queryOneValue($query);
            
            Logic_Achievement::deleteAchievement($param['achievement_id']);
        }

        // 不適合数の合計を求める
        $wasterQty = 0;
        for ($i = 1; $i <= GEN_WASTER_COUNT; $i++) {
            if (is_numeric(@$param["waster_id_{$i}"]) && is_numeric(@$param["waster_quantity_{$i}"])) {
                $wasterQty += @$param["waster_quantity_{$i}"];
            }
        }
        
        // 子品目引落数
        $childItemUsageArr = null;
        foreach($param as $key => $val) {
            if (substr($key, 0, 12) == "child_usage_") {
                $itemId = substr($key, 12);
                if (Gen_String::isNumeric($itemId)) {
                    $childItemUsageArr[$itemId] = $val;
                }
            }
        }

        // 受入と入出庫の登録、発注データの受入数等の調整、現在庫更新
        $achievementId = Logic_Achievement::entryAchievement(
            @$param['achievement_id']
            , $param['achievement_date']
            , @$param['begin_time']
            , @$param['end_time']
            , $param['order_detail_id']
            , $param['achievement_quantity']
            , $param['remarks']
            , $param['work_minute']
            , $param['break_minute']
            , @$param['location_id']
            , @$param['lot_no']
            , @$param['use_lot_no']
            , @$param['child_location_id']
            , @$param['order_detail_completed']
            , @$param['process_id']
            , @$param['section_id']
            , @$param['worker_id']
            , @$param['equip_id']
            , $wasterQty
            , $childItemUsageArr
            , @$param['use_by']
            , @$param['cost_1']
            , @$param['cost_2']
            , @$param['cost_3']
            , @$stockSeiban
        );

        // 不適合数・不適合理由登録
        for ($i = 1; $i <= GEN_WASTER_COUNT; $i++) {
            if (is_numeric(@$param["waster_id_{$i}"]) && is_numeric(@$param["waster_quantity_{$i}"])) {
                // 登録
                $data = array(
                    'achievement_id' => $achievementId,
                    'line_number' => $i,
                    'waster_id' => $param["waster_id_{$i}"],
                    'waster_quantity' => $param["waster_quantity_{$i}"],
                );
                $gen_db->insert('waster_detail', $data);
            }
        }

        // id(keyColumnの値)を戻す。keyColumnがないModelではfalseを戻す。
        return $achievementId;
    }

}
