<?php

class Manufacturing_Plan_List extends Base_ListBase
{

    var $isEditOk;

    function validate($validator, &$form)
    {
        // 未セットの場合はsetSearchConditionDefaultでデフォルト値が設定されるため、
        // エラーを出さなくてもよい。
        if (isset($form['gen_search_plan_Year']))
            $validator->range('gen_search_plan_Year', _g('年が正しくありません。'), 2006, date("Y") + 1);
        if (isset($form['gen_search_plan_Month']))
            $validator->range('gen_search_plan_Month', _g('月が正しくありません。'), 1, 12);
        if ($validator->hasError()) {
            $this->setViewParam($form);    // エラー時のために表示paramを取得しておく
        }

        return "list.tpl";        // if Error
    }

    function setSearchCondition(&$form)
    {
        global $gen_db;

        $query = "select item_group_id, item_group_name from item_group_master order by item_group_code";
        $option_item_group = $gen_db->getHtmlOptionArray($query, false, array(null => _g("(すべて)")));

        $form['gen_searchControlArray'] = array(
            array(
                'label' => _g('年月'),
                'type' => 'yearMonth',
                'field' => 'plan', // この名前の後に「_Year」と「_Month」
                'start_year' => date('Y') - 1,
                'end_year' => date('Y') + 1,
                'defaultYear' => date('Y'),
                'defaultMonth' => date('m'),
            ),
            array(
                'label' => _g('品目コード/名'),
                'field' => 'item_code',
                'field2' => 'item_name',
            ),
            array(
                'label' => _g('品目グループ'),
                'field' => 'item_group_id',
                'type' => 'select',
                'options' => $option_item_group,
                'hide' => true,
            ),
            array(
                'label' => _g('受注数'),
                'type' => 'select',
                'options' => array("notshow" => _g("表示しない"), "show" => _g("表示する"), "fixonly" => _g("確定のみ")),
                'field' => 'show_received_detail',
                'nosql' => true,
            ),
        );
    }

    function convertSearchCondition($converter, &$form)
    {
    }

    function beforeLogic(&$form)
    {
        global $gen_db;

        if (@$form['gen_search_show_received_detail'] == "show" || @$form['gen_search_show_received_detail'] == "fixonly") {

            // planテーブルに受注行を作成
            Logic_Plan::makeReceivedDataForPlan(
                    $form['gen_search_plan_Year']
                    , $form['gen_search_plan_Month']
                    , (@$form['gen_search_show_received_detail'] == "show")
            );

            // 受注品目の計画行が存在しない場合、ここで作成しておく（MRP品目のみ）
            $query = "
                select
                    t_received_plan.item_id
                from
                    (select
                        item_id
                        ,plan_year
                        ,plan_month
                    from
                        plan
                    where
                        classification = 1
                        and plan_year = '{$form['gen_search_plan_Year']}'
                        and plan_month = '{$form['gen_search_plan_Month']}'
                    ) as t_received_plan
                    inner join item_master on t_received_plan.item_id = item_master.item_id
                    left join (
                        select
                            item_id
                            ,plan_year
                            ,plan_month
                        from
                            plan
                        where
                            classification <> 1
                        ) as t_plan
                        on t_received_plan.item_id = t_plan.item_id
                        and t_received_plan.plan_year = t_plan.plan_year
                        and t_received_plan.plan_month = t_plan.plan_month
                where
                    t_plan.item_id is null
                    and order_class in (1,2)
            ";

            $res = $gen_db->getArray($query);
            if (is_array($res)) {
                foreach ($res as $row) {
                    $seiban = Logic_Seiban::getSeiban();
                    $query = "
                    insert into plan (
                        plan_year
                        ,plan_month
                        ,seiban
                        ,item_id
                        ,classification, plan_quantity,
                        day1,day2,day3,day4,day5,day6,day7,day8,day9,day10,
                        day11,day12,day13,day14,day15,day16,day17,day18,day19,day20,
                        day21,day22,day23,day24,day25,day26,day27,day28,day29,day30,
                        day31, remarks
                    )
                    values (
                        '{$form['gen_search_plan_Year']}',
                        '{$form['gen_search_plan_Month']}',
                        '{$seiban}',
                        '{$row['item_id']}',
                        0, 0,
                        0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                        0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                        0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                        0, ''
                    )
                    ";
                    $gen_db->query($query);
                }
            }
        }

        // データ登録・修正可能フラグの設定（インスタンス変数）
        //    現在処理月より前の月なら更新不可
        $start_date = Logic_SystemDate::getStartDate();
        $show_date = mktime(0, 0, 0, $form['gen_search_plan_Month'], 1, $form['gen_search_plan_Year']);
        $this->isEditOk = ($start_date <= $show_date);
    }

    function setQueryParam(&$form)
    {
        // このSQLは、テーブルplanのインデックス plan_index1 により劇的に速くなった。
        // SQL文を書き換えると、インデックスが適用されなくなりかなり遅くなる可能性があるため注意すること。
        $this->selectQuery = "
            select
                case classification
                    when 0 then '" . _g("計画") . "'
                    when 1 then '" . _g("受注") . "'
                    when 2 then '" . _g("予約") . "'
                    when 3 then '" . _g("計算") . "'
                    else '---' end as classification_show
                ,classification
                ,plan.item_id
                ,plan_id
                ,plan_year
                ,plan_month
                ,item_code
                ,item_code || '[br]' || item_name as item_code_show
                ,seiban
                ,plan_quantity
                ,measure
                ,item_group_code_1
                ,item_group_name_1
                ,item_group_code_1 || '[br]' || item_group_name_1 as item_group_show_1
                ,item_group_code_2
                ,item_group_name_2
                ,item_group_code_2 || '[br]' || item_group_name_2 as item_group_show_2
                ,item_group_code_3
                ,item_group_name_3
                ,item_group_code_3 || '[br]' || item_group_name_3 as item_group_show_3
                ";
                for ($i = 1; $i <= 31; $i++) {
                    $this->selectQuery .= ",day{$i} ,coalesce(order{$i}, 0) as order{$i}";
                }
                // モード切替（$form['gen_columnMode']）以外で列が動的に変化する場合、
                // gen_record_update_date（最終更新日）、gen_record_updater（最終更新者）を
                // SQLに含めてはいけない（コメントアウトしていてもダメ）。
                // 列の数が変化したとき、これらのカラムの非表示設定が通常列に適用されてしまうことがあるため。
                // 計画画面で言えば、2月を表示して列リセットしたあとで3月を表示すると29日と30日が
                // 表示されない、といったことがおこってしまう。
                $this->selectQuery .= "
                ,coalesce(plan.record_update_date, plan.record_create_date) as gen_record_update_date
                ,coalesce(plan.record_updater, plan.record_creator) as gen_record_updater
            from
                plan
                left join item_master on plan.item_id = item_master.item_id
                left join (select item_group_id as gid1, item_group_code as item_group_code_1, item_group_name as item_group_name_1
                    from item_group_master) as t_group1 on item_master.item_group_id = t_group1.gid1
                left join (select item_group_id as gid2, item_group_code as item_group_code_2, item_group_name as item_group_name_2
                    from item_group_master) as t_group2 on item_master.item_group_id_2 = t_group2.gid2
                left join (select item_group_id as gid3, item_group_code as item_group_code_3, item_group_name as item_group_name_3
                    from item_group_master) as t_group3 on item_master.item_group_id_3 = t_group3.gid3
            [Where]
                " . (@$form['gen_search_show_received_detail'] == "notshow" || isset($form['gen_csvMode']) ? " and classification in (0,3)" : "" ) . "
                and order_class in (1,2)
                and classification <> 3
            [Orderby]
        ";

        $this->orderbyDefault = 'plan.item_id, classification desc, seiban';
        $this->customColumnTables = array(
            // array(カスタム項目があるテーブル名, テーブル名のエイリアス※1, classGroup※2, parentColumn※3, 明細カスタム項目取得(省略可)※4)
            //    ※1 テーブル名のエイリアス： ListのSQL内でテーブルにエイリアスをつけている場合、そのエイリアスを指定する。
            //    ※2 classGroup:　order_headerのように複数の画面で登録が行われるテーブルの場合、登録画面のクラスグループ（ex: Manufacturing_Order）を指定する
            //    ※3 parentColumn: そのテーブルのカスタム項目をsameCellJoinで表示する際、parentColumn となるカラム。
            //          例えば受注画面であれば、customer_master は received_header_id, item_master は received_detail_id となる。
            //    ※4 明細カスタム項目取得(省略可): これをtrueにすると明細カスタム項目も取得する。ただしSQLのfromに明細カスタム項目テーブルがJOINされている必要がある。
            //          estimate_detail, received_detail, delivery_detail, order_detail
            array("item_master", "", "", "plan_id"),
        );        
    }

    function setCsvParam(&$form)
    {
        $form['gen_importLabel'] = _g("計画");
        $form['gen_importMsg_noEscape'] = _g("※データは新規登録されます。（既存データの上書きはできません）");
        $form['gen_allowUpdateCheck'] = false;

        $form['gen_csvArray'] = array(
            array(
                'label' => _g('年'),
                'field' => 'plan_year',
            ),
            array(
                'label' => _g('月'),
                'field' => 'plan_month',
            ),
            array(
                'label' => _g('品目コード'),
                'field' => 'item_code',
            ),
        );
        $dayArr = Gen_Option::getDays('list');
        for ($i = 1; $i <= 31; $i++) {
            $form['gen_csvArray'][] = array(
                'label' => $dayArr[$i],
                'field' => 'day' . $i,
            );
        }
    }

    function setViewParam(&$form)
    {
        global $gen_db;

        $form['gen_pageTitle'] = _g("計画登録");
        $form['gen_menuAction'] = "Menu_Manufacturing";
        $form['gen_listAction'] = "Manufacturing_Plan_List";
        $form['gen_editAction'] =
                ($this->isEditOk ? "Manufacturing_Plan_Edit&plan_year={$form['gen_search_plan_Year']}&plan_month={$form['gen_search_plan_Month']}" : '');
        $form['gen_deleteAction'] =
                ($this->isEditOk ? "Manufacturing_Plan_Delete" : '');
        $form['gen_idField'] = 'plan_id';
        $form['gen_excel'] = "true";
        $form['gen_excelTitle'] = sprintf(_g("計画リスト（%1\$s年%2\$s月）"), $form['gen_search_plan_Year'], $form['gen_search_plan_Month']);     // 【2008】
        $form['gen_pageHelp'] = _g("計画登録");

        $form['gen_dataRowHeight'] = 35;        // データ部の1行の高さ

        $form['gen_rowColorCondition'] = array(
            "#facea6" => "'[classification]'=='1'", // 受注
            "#fae0a6" => "'[classification]'=='2'", // 予約
        );
        $form['gen_colorSample'] = array(
            "facea6" => array(_g("オレンジ"), _g("受注")),
            "fae0a6" => array(_g("イエロー"), _g("予約")),
        );

        // クリックによるダイレクトデータ入力用スクリプト。
        // アクセス権はフィールドリスト内でチェック（Ajax側でもチェックしている）
        $form['gen_javascript_noEscape'] = "
             function dayClick(id, day) {
                var elm = document.getElementById('data_' + id + '_' + day);
                var def = elm.innerHTML;
                if ((value = window.prompt('" . _g("計画数を入力してください") . "',def)) != null) {
                    if (gen.util.isNumeric(value)) {
                        gen.ajax.connect('Manufacturing_Plan_AjaxEntry', {plan_id : id, day : day, value : value},
                            function(j) {
                                if (j.success == 'false') {
                                    alert('" . _g("登録に失敗しました。") . "');
                                }
                            });
                        elm.innerHTML = value;
                    } else {
                        alert('" . _g("数字を入力してください。") . "');
                    }
                }
             }
        ";

        $form['gen_message_noEscape'] = _g("製番品目は表示されません。");

        $form['gen_dataMessage_noEscape'] = _g("受注行に表示される数量は、受注数のうち引当も納品も行われていない数です。");

        if ($form['gen_readonly'] != "true") {
            $form['gen_dataMessage_noEscape'] .= "<BR>" . _g("計画行のセルをクリックすると直接データを入力できます。");
        }
        
        $form['gen_colorSample'] = array(
            "ffcc66" => array(_g("オレンジ"), _g("未オーダー")),
            "ffff66" => array(_g("イエロー"), _g("一部オーダー済み")),
            "d7d7d7" => array(_g("シルバー"), _g("オーダー済み")),
            "ffeaef" => array(_g("ピンク"), _g("休業日")),
        );

        if (!$this->isEditOk) {
            $form['gen_message_noEscape'] = "<BR><font color=red>" . _g("この月はロック済であるため、登録や修正を行えません。") . "</font>";
        }

        if ($this->isEditOk) {
            $form['gen_fixColumnArray'] = array(
                array(
                    'label' => _g('明細'),
                    'type' => 'edit',
                    'showCondition' => "('[classification]'=='0' || '[classification]'=='3')", // classが「受注」「予約」の時は修正不可
                ),
            );
        } else {
            // 過去月の時は修正不可
            $form['gen_fixColumnArray'] = array(
                array(
                    'label' => _g('明細'),
                    'width' => '37',
                    'type' => 'literal',
                    'literal_noEscape' => "",
                ),
            );
        }

        $form['gen_fixColumnArray'][] = array(
            'label' => _g('削除'),
            'width' => '42',
            'type' => 'delete_check',
            'deleteAction' => 'Manufacturing_Plan_BulkDelete',
            // readonlyであれば表示しない
            'showCondition' => ($form['gen_readonly'] != 'true' ? "[classification]=='0'" : "false"),
            // ここでhelpを表示するとタイトル列が乱れる
            'align' => 'center',
        );

        $form['gen_fixColumnArray'][] = array(
            'label' => _g('品目コード・品目名'),
            'field' => 'item_code_show',
            'width' => '180',
        );
        $form['gen_fixColumnArray'][] = array(
            'label' => _g('区分'),
            'field' => 'classification_show',
            'width' => '40',
            'align' => 'center',
            'helpText_noEscape' => '<b>' . _g('計画') . '：</b> ' . _g('この画面で登録された計画データ') . "<br>"
            . '<b>' . _g('受注') . '：</b> ' . _g('受注登録画面で登録された受注（確定）データ') . "<br>"
            . '<b>' . _g('予約') . '：</b> ' . _g('受注登録画面で登録された受注（予約）データ')
        );
        $form['gen_fixColumnArray'][] = array(
            'label' => _g('合計'),
            'field' => 'plan_quantity',
            'width' => '60',
            'type' => 'numeric',
        );
        $form['gen_fixColumnArray'][] = array(
            'label' => _g('単位'),
            'field' => 'measure',
            'type' => 'data',
            'width' => '35',
        );
        for ($i = 1; $i <= 3; $i++) {
            $form['gen_fixColumnArray'][] = array(
                'label' => _g("品目グループ") . $i,
                'field' => "item_group_show_{$i}",
                'type' => 'data',
                'hide' => true,
            );
        }

        $monthLastDay = date('d', mktime(0, 0, 0, $form['gen_search_plan_Month'] + 1, 0, $form['gen_search_plan_Year']));

        $dateBase = $form['gen_search_plan_Year'] . "-" . $form['gen_search_plan_Month'] . "-";
        $query = "select holiday_master.holiday from holiday_master where holiday between '{$dateBase}-1' and '{$dateBase}-{$monthLastDay}'";
        $holidays = $gen_db->getArray($query);
        $holidayArr = array();
        if (is_array($holidays)) {
            foreach ($holidays as $h) {
                $holidayArr[] = date('Y-n-j', strtotime($h['holiday']));
            }
        }
        $dayArr = Gen_Option::getDays('list');
        for ($i = 1; $i <= $monthLastDay; $i++) {
            $dateStr = (int) $form['gen_search_plan_Year'] . "-" . (int) $form['gen_search_plan_Month'] . "-" . $i;
            $form['gen_columnArray'][] = array(
                'label_noEscape' => $dayArr[$i] . "<br>(" . Gen_String::weekdayStr($dateStr) . ")",
                'width' => '70',
                'type' => 'numeric',
                'cellId' => "data_[id]_{$i}",
                'field' => "day{$i}",
                // クリックによるダイレクトデータ入力用。アクセス権もチェック
                'onClick' => "dayClick('[id]',{$i})",
                'onClickCondition' => "('[classification]'=='0' or '[classification]'=='3') " .
                " and " . ($form['gen_readonly'] == "true" ? "false" : "true") .
                " and " . ($this->isEditOk ? "true" : "false"),
                'colorCondition' => array(
                    "#ffcc66" => "[day{$i}] >  [order{$i}] and [order{$i}] == 0 and ('[classification]'=='0'||'[classification]'=='3')", // 未オーダー
                    "#ffff66" => "[day{$i}] >  [order{$i}] and [order{$i}] != 0 and ('[classification]'=='0'||'[classification]'=='3')", // 一部オーダー
                    "#d7d7d7" => "[day{$i}] <= [order{$i}] and [order{$i}] != 0 and ('[classification]'=='0'||'[classification]'=='3')", // オーダーずみ
                    "#ffeaef" => in_array($dateStr, $holidayArr) ? "true" : "false", // 休日
                ),
                'denyMove' => true, // 日付列は列順序固定
                'zeroToBlank' => true,
            );
        }
    }

}