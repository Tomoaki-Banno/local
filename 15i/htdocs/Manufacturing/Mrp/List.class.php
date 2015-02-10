<?php

class Manufacturing_Mrp_List extends Base_ListBase
{

    var $from;
    var $to;

    function validate($validator, &$form)
    {
        // 未セットの場合はsetSearchConditionDefaultでデフォルト値が設定されるため、
        // エラーを出さなくてもよい。
        if (isset($form['gen_search_plan_year']))
            $validator->range('gen_search_plan_year', _g('年が正しくありません。'), 2006, date("Y") + 1);
        if (isset($form['gen_search_plan_month']))
            $validator->range('gen_search_plan_month', _g('月が正しくありません。'), 1, 12);
        if ($validator->hasError()) {
            $this->setViewParam($form); // エラー時のために表示paramを取得しておく
        }
        return "list.tpl";
    }

    function setSearchCondition(&$form)
    {
        global $gen_db;

        $query = "select item_group_id, item_group_name from item_group_master order by item_group_code";
        $option_item_group = $gen_db->getHtmlOptionArray($query, false, array(null => _g("(すべて)")));

        $form['gen_searchControlArray'] = array(
            array(
                'label' => _g('品目コード/名'),
                'field' => 'item_code',
                'field2' => 'item_name',
                'hide' => true,
            ),
            array(
                'label' => _g('品目グループ'),
                'field' => 'item_group_id',
                'type' => 'select',
                'options' => $option_item_group,
                'hide' => true,
            ),
            array(
                'label' => _g('親品目'),
                'type' => 'select',
                'type' => 'dropdown',
                'size' => '150',
                'field' => 'parent_item_id',
                'dropdownCategory' => 'item',
                'nosql' => true,
                'rowSpan' => 2,
                'helpText_noEscape' => _g('この項目を指定すると、その品目より下位の構成品目のみが表示されます。この項目を指定した場合は、結果オーダーがない品目も表示されます。'),
            ),
            array(
                'label' => _g('手配先名'),
                'field' => 'customer_name',
            ),
            array(
                'label' => _g('工程名'),
                'field' => 'process_name',
                'hide' => true,
            ),
            array(
                'label' => _g('手配区分'),
                'type' => 'select',
                'field' => 'category',
                'options' => array("" => _g("(すべて)"), "3" => _g("内製"), "0" => _g("注文"), "1" => _g("外製"), "99" => _g("製番引当")),
                'nosql' => 'true',
                'default' => '',
            ),
            array(
                'label' => _g('工程の表示'),
                'type' => 'select',
                'field' => 'show_process',
                'options' => Gen_Option::getTrueOrFalse('search'),
                'nosql' => 'true',
                'default' => 'false',
            ),
            array(
                'label' => _g('日付モード'),
                'field' => 'date_mode',
                'type' => 'select',
                'options' => array('0' => _g('日次'), '1' => _g('週次'), '2' => _g('月次')),
                'default' => '0',
                'nosql' => true,
                'helpText_noEscape' => _g('リスト横軸の日付間隔を指定します。') . "<br><br>" . _g('「週次」の場合は日曜日から土曜日までが1週とみなされます。') . "<br><br>" . _g('「週次」の場合は日曜日から土曜日、「月次」の場合は1日から末日までとなるよう、日付範囲が自動的に修正されます。')
            ),
        );
    }

    function convertSearchCondition($converter, &$form)
    {
        $converter->nullBlankToValue('mrp_date', date('Y-m-d', time() + (3600 * 24 * 10)));         // 10日後
        $converter->nullBlankToValue('fix_order_date', date('Y-m-d', time() + (3600 * 24 * 10)));   // 10日後
    }

    function beforeLogic(&$form)
    {
    }

    function setQueryParam(&$form)
    {
        global $gen_db;

        // 親品目が指定されている場合はtemp_bom_expandテーブルを準備
        if (is_numeric(@$form['gen_search_parent_item_id'])) {
            Logic_Bom::expandBom($form['gen_search_parent_item_id'], 0, false, false, false);
        }

        // 表示期間
        //　13iまでは「今日から、arrangement_quantityがある最後の日まで」だったが、
        //　それだと前日以前に計算した結果の一部が見えなくなってしまうという問題があった。
        //　15iでは「最終計算時の計算対象期間」を表示期間とすることにした。
        //　ただし開始日は遅くとも今日以前とする（製番引当が本日付で出ることがあるので）。
        //　また終了日は早くとも今日以降とする。
        $query = "select min(calc_date) as from, max(calc_date) as to from mrp";
        $obj = $gen_db->queryOneRowObject($query);
        $this->from = strtotime($obj->from);
        $today = strtotime(date('Y-m-d'));
        if ($this->from > $today) {
            $this->from = $today;
        }
        $this->to = strtotime($obj->to);
        if ($this->to < $today) {
            $this->to = $today;
        }

        // 以下、13i以前
        //// 表示期間の開始日
        //// 製番引当が本日付で出ることがあるので、計算期間初日からではなく今日から表示する
        //$today = strtotime(date('Y-m-d'));
        //$this->from = $today;
        //
        //// 表示期間の終了日
        //// 「arrangement_quantity <> 0」だとインデックスが使用されない
        //$query = "select max(arrangement_finish_date) from mrp where arrangement_quantity > 0 or arrangement_quantity < 0 ";
        //$lastDate = $gen_db->queryOneValue($query);
        //$this->to = (Gen_String::isDateString($lastDate) ? strtotime(date('Y-m-d', strtotime($lastDate))) : $this->from);
        //// 最低7日
        //if ($this->to - $today < (3600 * 24 * 6)) {
        //    $this->to = $today + (3600 * 24 * 6);
        //}

        // 日付モードによって開始日・終了日を修正
        switch ($form['gen_search_date_mode']) {
            case '1':   // 週次： 日曜日から土曜日までとなるように修正
                $this->from -= date('w', $this->from) * 3600 * 24;
                $this->to += (6 - date('w', $this->to)) * 3600 * 24;
                break;
            case '2':   // 月次： 1日から月末日までとなるように修正
                $this->from = strtotime(date('Y-m-01', $this->from));
                $this->to = strtotime(date('Y-m-t', $this->to));
                break;
            default:    // 日次： 最大60日
                if (($this->to - $this->from) > 3600 * 24 * 60) {
                    $this->to = $this->from + 3600 * 24 * 60;
                }
        }

        // Query
        $this->selectQuery = "
        select
            cast(t_mrp.item_id as text) || '_' || cast(t_mrp.process_id as text) || '_' || seiban as item_process_seiban
            ,t_mrp.item_id
            ,t_mrp.seiban

            ,max(item_code) as item_code
            ,max(item_name) as item_name
            ,max(maker_name) as maker_name
            ,max(spec) as spec
            ,max(rack_no) as rack_no
            ,max(comment) as comment
            ,max(t_mrp.order_class) as order_class
            ,max(machining_sequence) as machining_sequence
            ,max(case when machining_sequence=999999 then null else machining_sequence+1 end) as machining_sequence_show
            ,max(case when customer_id is null then 0 else customer_id end) as supplier_id
            ,max(coalesce(customer_no,process_code)) as supplier_code
            ,max(coalesce(customer_name,process_name)) as supplier_name
            ,max(case when customer_id is null then '" . _g("内製") . "' else customer_name end) as supplier_name_excel
            ,max(measure) as measure
            ,max(case
                when t_mrp.order_class = '99' then '" . _g("製番引当") . "'
                when item_order_master.partner_class = 3 then '" . _g("内製") . "'
                when item_order_master.partner_class = 0 then '" . _g("注文") . "'
                else '" . _g("外製") . "' end) as category
            ,max(case when t_mrp.order_class = '99' then 1 else 0 end) as is_allocate
            ,max(coalesce(alarm_flag,0)) as alarm_flag_line
            ,max(case when coalesce(order_flag,0)=1 then arrangement_finish_date else null end) as fix_date
            ,max(t_mrp.llc) as llc
            ,max(is_process) as is_process

       ";

        if (!isset($form['supplierListExcel'])) {
            // 画面・エクセル
            switch ($form['gen_search_date_mode']) {
                case '1':
                    $addStr = " +1 week";
                    break;
                case '2':
                    $addStr = " +1 month";
                    break;
                default :
                    $addStr = " +1 day";
                    break;
            }
            for ($day = $this->from; $day <= $this->to; $day = strtotime(date('Y-m-d', $day) . $addStr)) {        // 86400sec = 1day
                $spanFrom = date('Y-m-d', $day);
                $spanEnd = date('Y-m-d', strtotime($spanFrom . $addStr . ' -1 day'));
                $field = "day_" . date('Ymd', $day);
                $this->selectQuery .=
                        // 数量0の場合は非表示とした。表示条件で親品目を指定したときに0がたくさん表示され、見づらい場合があるため
                        ", sum(case when arrangement_finish_date between '{$spanFrom}' and '{$spanEnd}' and arrangement_quantity<>0 then arrangement_quantity end) as $field" .
                        ", max(case when arrangement_finish_date between '{$spanFrom}' and '{$spanEnd}' then alarm_flag else 0 end) as {$field}_alarm_flag";
            }
        } else {
            // 「取引先別エクセル出力（リスト）」

            $this->selectQuery .=
                    ", max(arrangement_finish_date) as date" .
                    ", sum(arrangement_quantity) as quantity";
        }

        $this->selectQuery .= "
        from
           (
            -- 通常行
            select
                item_id
                ,seiban
                ,-1 as process_id
                ,999999 as machining_sequence
                ,order_class
                ,order_flag
                ,alarm_flag
                ,llc
                ,arrangement_finish_date
                ,arrangement_quantity
                ,0 as is_process
            from
                mrp
        " . (isset($form['gen_search_show_process']) && $form['gen_search_show_process'] == "true" ? "
            -- 工程行
            union all
            select
                item_id
                ,seiban
                ,process_id
                ,machining_sequence
                ,order_class
                ,order_flag
                ,alarm_flag
                ,llc
                ,process_dead_line as arrangement_finish_date
                ,arrangement_quantity
                ,1 as is_process
            from
                mrp_process
            " : "") . "
            ) as t_mrp
            left join item_master on t_mrp.item_id = item_master.item_id
            left join item_order_master on t_mrp.item_id = item_order_master.item_id and item_order_master.line_number = 0
            " . (is_numeric(@$form['gen_search_parent_item_id']) ?
                        " inner join (select item_id as exp_item_id from temp_bom_expand group by item_id) as t_exp on t_mrp.item_id = t_exp.exp_item_id" : "") . "
            left join customer_master on item_order_master.order_user_id = customer_master.customer_id
            left join process_master on t_mrp.process_id = process_master.process_id
            /* 「arrangement_quantity <> 0」だとインデックスが使用されない（postgresは <> に対してインデックスを使わない）ため下のようにしている。 */
        [Where]
            and (arrangement_quantity > 0 or arrangement_quantity < 0)
            -- ダミー品目は表示しない
            and not coalesce(item_master.dummy_item, false)
            " . (@$form['gen_search_category'] == '0' ? " and item_order_master.partner_class = 0" : "") . "
            " . (@$form['gen_search_category'] == '1' ? " and item_order_master.partner_class in (1,2)" : "") . "
            " . (@$form['gen_search_category'] == '3' ? " and item_order_master.partner_class = 3" : "") . "
            " . (@$form['gen_search_category'] == '99' ? " and t_mrp.order_class = '99'" : "") . "
            " . (isset($form['supplierExcel']) || isset($form['supplierListExcel']) ? " and t_mrp.order_class <> '99' " : "") . "

        group by
            t_mrp.item_id, t_mrp.seiban, t_mrp.order_class, t_mrp.process_id
            " . (isset($form['supplierListExcel']) ? ", arrangement_finish_date" : "") . "
        [Orderby]
        ";

        // 「取引先別エクセル出力」機能のときは取引先順に並べる
        if (isset($form['supplierExcel']) || isset($form['supplierListExcel'])) {
            $form['gen_search_orderby_force'] = "supplier_id";
        }

        $this->orderbyDefault = 'seiban, llc desc, item_code, order_class, machining_sequence';

        if (isset($form['supplierListExcel'])) {
            $this->orderbyDefault .= ", arrangement_finish_date";
        }
    }

    function setViewParam(&$form)
    {
        global $gen_db;

        $form['gen_pageTitle'] = _g("所要量計算");
        $form['gen_menuAction'] = "Menu_Manufacturing";
        $form['gen_listAction'] = "Manufacturing_Mrp_List";
        $form['gen_idField'] = "item_process_seiban";
        $form['gen_excel'] = "true";
        $form['gen_pageHelp'] = _g("所要量計算");

        $form['gen_goLinkArray'] = array(
            array(
                'id' => 'analyze',
                'value' => _g("結果分析画面へ"),
                'onClick' => "index.php?action=Manufacturing_Mrp_Analyze",
            ),
        );

        $form['gen_excelLinkArray'] = array(
            array(
                'icon' => 'img/report-excel.png',
                'label' => _g('取引先別エクセル出力'),
                'action' => $form['action'] . "&gen_excelMode&supplierExcel&gen_restore_search_condition=true",
            ),
            array(
                'icon' => 'img/report-excel.png',
                'label' => _g('取引先別エクセル出力(リスト)'),
                'action' => $form['action'] . "&gen_excelMode&supplierListExcel&gen_restore_search_condition=true",
            ),
        );

        // 「取引先別エクセル出力」機能（取引先別に複数シートに出力）
        if (isset($form['supplierExcel']) || isset($form['supplierListExcel'])) {
            $form['gen_excelTitleColumn'] = "supplier_name_excel";
            $form['gen_excelSheetKeyColumn'] = "supplier_id";

            // 列幅の問題で、別モード扱いする必要がある
            if (isset($form['supplierListExcel'])) {
                $form['gen_columnMode'] = 'supplierListExcel';
            }
        }

        // 受注製番削除による再計算実施対策
        $query = "select mrp_id from mrp where order_class = 0 and (arrangement_quantity > 0 or arrangement_quantity < 0) and seiban not in (select seiban from received_detail)";
        $notOrder = false;
        if ($gen_db->existRecord($query))
            $notOrder = true;

        // リロード対策
        // 実行後のList画面（URLはEntryになっている）でF5を押した場合や、実行後に他の画面に移ってから
        // 「戻る」で戻った場合に、計算が再実行されてしまう現象を防ぐ。
        // ここで作成したページリクエストIDを、javascriptのMRP実行部分で引数として埋め込んでいる。
        // Edit画面（Base継承）ならフレームワーク側で行われる処理だが、Listの場合は自前で行う必要がある。
        global $_SESSION;
        $reqId = sha1(uniqid(rand(), true));
        $_SESSION['gen_page_request_id'][] = $reqId;

        // スクリプト
        $form['gen_onLoad_noEscape'] = "mrpDatePresetChange();";
        if (@$form['nodata'] == "true") {
            $form['gen_onLoad_noEscape'] .= ";alert('" . _g("対象となるデータがありません。") . "')";
        }
        // gen_onLoad_noEscape は再表示ボタンでは呼ばれないが、gen_onPageInitは再表示でも呼ばれる。
        // tplで escape されているので、HTMLタグは埋め込めないことに注意。
        // （escapeをはずす場合は、ユーザー入力値やDB値をそのまま埋め込まないよう注意）
        $form['gen_onPageInit'] = "progress();";
        if ($form['gen_readonly'] != "true" && $form['gen_search_date_mode'] != '1' && $form['gen_search_date_mode'] != '2') {
            $form['gen_onPageInit'] .= "makeDDElm();";
        }

        $isDayMode = ($form['gen_search_date_mode'] != '1' && $form['gen_search_date_mode'] != '2');
        $form['gen_javascript_noEscape'] = "
        // 実行状況確認
        function progress() {
            gen.ajax.connect('Manufacturing_Mrp_AjaxMrpProgress', {},
                function(j) {
                    var msgElm = $('#msg');
                    var arr = j.data.split(',');
                    if (j.doing == 'false' || isNaN(arr[3])) {
                        if (msgElm.html().substr(0,8) == '" . _g("所要量計算実行中") . "'" . (@$form['mrp_flag'] == 'true' ? " || true" : "") . ") { // これまで実行中だった場合は、終了処理を行う
                            $('#mrpProgress').html('" . _g("100％  完了") . "<BR><BR><BR>');
                            $('#graph').css('width', (100 * 3) + 'px');
                            alert('" . _g("所要量計算が終了しました。") . "');
                            // データ表の表示を更新する。ちなみにreloadだとMRP実行開始時POSTの引数mrp_flagが残ってしまいうまくいかない
                            location.href = 'index.php?action=Manufacturing_Mrp_List&mrp_date=" . h(@$form['mrp_date']) . "';
                        } else {
                            msgElm.html('<span>" . _g("所要量計算は現在実行されていません。") . "<BR>" .
                            _g("最終実行時刻 ") . "　: ' + gen.util.escape(arr[0]) + '<BR>" .
                            _g("最終実行ユーザー") . "　: ' + gen.util.escape(arr[1]) + '</span>');
                            gen.list.table.setListSize();   // 縦幅が変わるので調整
                        }
                        $('#mrpProgress').html('');
                        $('#mrpStart').show();
                        " . ($form['gen_readonly'] == 'true' ? "" : "gen.ui.enabled($('#mrpStartButton'));") . "
                        $('#gen_dataTable').show();     // データ表を表示
                        $('.gray_msg').show();

                        tm = setTimeout('progress()',30000);    // 30秒後に再実行
                        $('#graph').css('width','0px');
                    } else if (j.doing == 'true') {
                        msgElm.html('" . _g("所要量計算実行中") . "<BR>" . _g("開始時刻") . " : ' + gen.util.escape(arr[0]));
                        $('#mrpProgress').html('" . _g("開始ユーザー") . " : ' + gen.util.escape(arr[1]) + '<BR>' + gen.util.escape(arr[2]) + '<BR>' + gen.util.escape(arr[3]) + '％');
                        $('#mrpStart').hide();      // 実行中は実行開始ボタンを隠す
                        gen.ui.disabled($('#mrpStartButton'));
                        $('#gen_dataTableInner').css('visibility','hidden'); // データ表も隠す。レイアウト崩れを防ぐためhide()は使わない
                        $('#graph').css('width',(gen.util.escape(arr[3]) * 3) + 'px');
                        $('.gray_msg').hide();

                        tm = setTimeout('progress()',1000);      // 1秒後に再実行
                    } else {
                        alert('" . _g("状況確認時にエラーが発生しました。") . "');
                    }
                }, true);
        }

        // 計算期間プリセット
        function mrpDatePresetChange() {
            var v = $('#mrp_date_preset').val();
            var d;
            switch(v) {
            case '1': d = '" . date("Y-m-d", strtotime("+7 day")) . "'; break;
            case '2': d = '" . date("Y-m-d", strtotime("+14 day")) . "'; break;
            case '3': d = '" . date("Y-m-d", strtotime("+21 day")) . "'; break;
            case '4': d = '" . date("Y-m-d", strtotime("+1 month")) . "'; break;
            case '5': d = '" . date("Y-m-d", strtotime("+2 month")) . "'; break;
            case '6': d = '" . date("Y-m-d", strtotime("+" . GEN_MRP_DAYS . " day")) . "'; break;
            default : return;
            }
            $('#mrp_date').val(d);
        }

        // 所要量計算実行
        function doMRP() {
            gen.ui.disabled($('#mrpStartButton'));

            var f1 = $('#form1');
            var mrpDate = $('#mrp_date').val();
            if (!gen.date.isDate(mrpDate)) {
                alert('" . _g("期間指定が正しくありません。") . "');
                " . ($form['gen_readonly'] == 'true' ? "" : "gen.ui.enabled($('#mrpStartButton'));") . "
                return;
            }
            var a = mrpDate.split('-');
            var today = new Date(); // 明日
            var toDate = new Date(eval(a[0]), eval(a[1])-1, eval(a[2]));
            if (toDate < today) {
                alert('" . _g("期間指定が正しくありません。終了日には開始日以降の日付を指定してください。") . "');
                " . ($form['gen_readonly'] == 'true' ? "" : "gen.ui.enabled($('#mrpStartButton'));") . "
                return;
            }
            if ((toDate - today) > (3600*24*1000*" . GEN_MRP_DAYS . ")) {   // 期間制限はgen_configで指定。
                alert('" . _g("%days日を超える期間を指定することはできません。") . "'.replace('%days'," . GEN_MRP_DAYS . "));
                " . ($form['gen_readonly'] == 'true' ? "" : "gen.ui.enabled($('#mrpStartButton'));") . "
                return;
            }
            if (!confirm('" . _g("明日から  %date までを対象期間として所要量計算を実行します。よろしいですか？") . "'.replace('%date',mrpDate))) {
                " . ($form['gen_readonly'] == 'true' ? "" : "gen.ui.enabled($('#mrpStartButton'));") . "
                return;
            }

            var postUrl = 'index.php?action=Manufacturing_Mrp_Mrp' +
                '&mrp_date=' + mrpDate +
                '&mrp_flag=true' +       // MRP実行中フラグ。このページが再ロードされたとき、実行中であることが認識できるように。
                '&gen_page_request_id=" . h($reqId) . "';

            if ($('#isNaiji')[0].checked) {
                postUrl += '&isNaiji=true';
            }
            if ($('#isNonSafetyStock')[0].checked) {
                postUrl += '&isNonSafetyStock=true';
            }
            location.href = postUrl;
        }

        // 確定処理
        function fixOrder() {
            gen.ui.disabled($('#fixOrderButton'));

            var man = $('#fix_man_order').is(':checked');
            var par = $('#fix_par_order').is(':checked');
            var sub = $('#fix_sub_order').is(':checked');
            var sei = $('#fix_seiban').is(':checked');

            if (!man && !par && !sub && !sei) {
                alert('" . _g("処理するカテゴリを指定してください。") . "');
                " . ($form['gen_readonly'] == 'true' ? "" : "gen.ui.enabled($('#fixOrderButton'));") . "
                return;
            }
            if (!gen.date.isDate($('#fix_order_date').val())) {
                alert('" . _g("オーダー日が正しくありません。") . "');
                " . ($form['gen_readonly'] == 'true' ? "" : "gen.ui.enabled($('#fixOrderButton'));") . "
                return;
            }

            var p = new Object();
            gen.ajax.connect('Manufacturing_Mrp_AjaxOrderCheck', p,
                function(j) {
                    if (j.status == 'success') {
                        alert('" . _g("所要量計算の結果に削除された受注製番が含まれています。所要量計算を再実行してください。") . "');
                        location.href = 'index.php?action=Manufacturing_Mrp_List&gen_restore_search_condition=true'
                        return;
                    } else {
                        var msg = '';
                        var print = false;
                        if ($('#fix_and_print').is(':checked')) {
                            print = true;
                            msg = '" . _g("指定されたカテゴリのオーダー確定と帳票印刷を行います。") . "';
                        } else {
                            msg = '" . _g("指定されたカテゴリのオーダー確定を行います。（帳票は印刷されません。）") . "';
                        }
                        msg += '" . _g("処理を実行してもよろしいですか？") . "';
                        if (!window.confirm(msg)) {
                            " . ($form['gen_readonly'] == 'true' ? "" : "gen.ui.enabled($('#fixOrderButton'));") . "
                            return;
                        }

                        gen.waitDialog.show('" . _g("お待ちください..") . "');

                        var url = 'index.php?action=Manufacturing_Mrp_BatchOrder';
                        url += '&fix_order_date=' + $('#fix_order_date').val();

                        if (print) {
                            // 印刷あり
                            var w1 = false;
                            var w2 = false;
                            var w3 = false;
                            var w4 = false;

                            url += '&print&windowOpen';
                            if ($('#fix_man_order').is(':checked'))
                                w1 = window.open(url+'&manufacturing');
                            if ($('#fix_par_order').is(':checked'))
                                w2 = window.open(url+'&partner');
                            if ($('#fix_sub_order').is(':checked'))
                                w3 = window.open(url+'&subcontract');
                            if ($('#fix_seiban').is(':checked'))
                                w4 = window.open(url+'&seiban');

                            // すべてのサブウィンドウが閉じたら（確定処理が終了したら）、ページをリロードする。
                            var f = function() {
                                if ((!w1 || w1.closed) && (!w2 || w2.closed) && (!w3 || w3.closed) && (!w4 || w4.closed)) {
                                    // Firefoxで 帳票発行後のリロード時にhttpヘッダが表示されてしまう現象に対処するため、5sec待つようにした。
                                    setTimeout(function(){location.href = 'index.php?action=Manufacturing_Mrp_List&gen_restore_search_condition=true'}, 5000);
                                } else {
                                    setTimeout(f, 1000);
                                }
                            };
                            setTimeout(f, 1000);
                        } else {
                            // 印刷なし
                            if ($('#fix_man_order').is(':checked')) url += '&manufacturing';
                            if ($('#fix_par_order').is(':checked')) url += '&partner';
                            if ($('#fix_sub_order').is(':checked')) url += '&subcontract';
                            if ($('#fix_seiban').is(':checked')) url += '&seiban';
                            location.href = url;
                        }
                    }
                });
        }

        // セルクリック
        // ちなみに製番引当・工程・確定済みセルはクリック自体ができなくなっている
        function dayClick(id, date, isAllocate) {
            if (" . ($isDayMode ? 'false' : 'true') . ") {
                alert('" . _g("日付モードが「週次」もしくは「月次」のときはデータの修正を行えません。「日次」を選択してください。") . "');
                return;
            }
            var d = new Date();
            if (gen.date.parseDateStr(date) <= d && isAllocate != 1) {
                // 本日分は修正不可（次回の所要量計算に含まれないため）。ちなみに当日列が表示されるのは製番引当のため
                alert('" . _g("本日分のオーダーを修正することはできません。") . "');
                return;
            }
            if (" . ($form['gen_readonly'] == "true" ? "true" : "false") . ") {
                alert('" . _g("オーダーを修正する権限がありません。くわしくはシステム管理者にご相談ください。") . "');
                return;
            }
            var elm = $('#data_' + id + '_' + date);
            var orgVal = elm.html();
            var orgColor = elm.css('background-color');
            elm.html('" . _g("変更中") . "');
            elm.css('background-color','#66ff99');
            if ((value = window.prompt('" . _g("オーダー数を入力してください") . "',orgVal)) != null) {
                if (gen.util.isNumeric(value)) {
                	entryData(elm, id, date, value, orgVal, orgColor);
                } else {
                    alert('" . _g("数字を入力してください。") . "');
                    elm.html(orgVal);
                    elm.css('background-color',orgColor);
                }
            } else {
                elm.html(orgVal);
                elm.css('background-color',orgColor);
            }
        }

        function entryData(elm, item_process_seiban, date, value, orgVal, orgColor) {
            gen.ajax.connect('Manufacturing_Mrp_AjaxEntry', {item_process_seiban : item_process_seiban, date : date, value : value},
                function(j) {
                    if (j.success == 'false') {
                        alert('" . _g("登録に失敗しました。") . "');
                        elm.html(orgVal);
                        elm.css('background-color',orgColor);
                    }
                });
            elm.html(value=='0' ? '' : gen.util.addFigure(value));
            elm.css('background-color','#ffff66');
            if (orgVal=='') makeDDElmSub(elm.attr('id'));
    	}

        // 手動調整のリセット
        function resetHandAdjust() {
            if (!confirm('" . _g('セルクリックによる計算結果の手動調整をリセットします。これまでのすべての手動調整の内容がクリアされます。ただし、発行済みのオーダーが消えることはありません。\\n処理には時間がかかる場合があります。リセットしてよろしいですか？') . "')) return;
            gen.waitDialog.show('" . _g('お待ちください...') . "');
            gen.ajax.connect('Manufacturing_Mrp_AjaxResetHandAdjust', null,
                function(j) {
                    if (j.success == 'true') {
                        alert('" . _g("リセット処理が終了しました。再度 所要量計算を行うことをお勧めします。") . "');
                        gen.list.postForm();   // 再表示
                    } else {
                        gen.waitDialog.hide();
                        alert('" . _g("リセット処理に失敗しました。") . "');
                    }
                });
        }

        // ドラッグドロップオブジェクトの作成（リストロード時）
        function makeDDElm() {
            // 週次や月次、readonlyのときはDDオブジェクトを作成しない
            " . ($isDayMode ? '' : 'return;') . "
            " . ($form['gen_readonly'] == "true" ? "return;" : "") . "

            // 数字セルにDDオブジェクトを作成
            // ただしonclickが設定されていないセル（編集禁止セル。製番引当・工程・確定済みなど）には作成しない
            $('[id^=data_]').each(function(){
                if (gen.util.isNumeric(gen.util.delFigure(this.innerHTML)) && this.parentNode.onclick != undefined) {
                    makeDDElmSub(this.id);
                }
            });
        }

        function makeDDElmSub(id) {
            var divElm = $('#'+id);
            divElm.css('cursor','move');
            var dd = new YAHOO.util.DDProxy(id);
            dd.isTarget = false;
            var idPartArr = id.split('_');
            dd.d0 = $('#D0').get(0);
            var tl;	// targetのリスト
            //dd.setYConstraint(0, 0);  左記を有効にすると横方向にしか動かなくなるが、メニューバーや検索ウィンドウを開閉したあとでのD&Dで動きがおかしくなる

            dd.startDrag = function() {
                dd.val = divElm.html();
                var targetH = divElm.get(0).offsetHeight;	// 文字無しdivはheightが0なので広げておく
                var dragEl = this.getDragEl();
                dragEl.innerHTML = divElm.html();
                dragEl.style.textAlign = 'right';
                // ドラッグ開始の時点で、ドラッグ元と同じ行にtargetを作成
                tl = new Array();
                ";
        $i = 0;
        for ($day = $this->from; $day <= $this->to; $day = strtotime(date('Y-m-d', $day) . '+1 day')) {
            $dateStr = date('Y-m-d', $day);
            // onclickが設定されていないセル（編集禁止セル。製番引当・工程・確定済みなど）にはtargetを作成しない
            $form['gen_javascript_noEscape'] .= "if ($('#'+id).closest('td').attr('onclick') != undefined) {tl[{$i}] = makeDDTarget(id, '{$dateStr}', targetH);};";
            $i++;
        }
        $form['gen_javascript_noEscape'] .= "
            };
            dd.onDragEnter = function(e, targetId) {
             	dd.targetBgColor = $('#'+targetId).css('background-color');
             	$('#'+targetId).css('background-color','#ccccff');
            };
            dd.onDragOut = function(e, targetId) {
                $('#'+targetId).css('background-color',dd.targetBgColor);
            };
            dd.onDragDrop = function(e, targetId) {
            	// 本来は、スクロールでfix列とscroll列が重なった状態のときにドロップイベントが2回発生する問題に対処しなければならない。
            	// しかしこの画面では日付列の移動が禁止されており、fix列にターゲットが設定されることはないので、その処理は省いている。

                if (targetId.indexOf('data_')==-1) return;

                var targetDiv = $('#'+targetId);
                var targetIdPartArr = targetDiv.get(0).id.split('_');
                var targetDate = targetIdPartArr[4];
                var today = new Date();
                if (Date.parse(targetDate)<today) {
                    alert('" . _g('本日以前のデータを更新することはできません。') . "');
                    dd.onDragOut(e, targetId);
                    dd.endDrag(e);
                    return;
            	}

                // ドロップ先の更新
                targetEntry(targetDiv, dd.val, false);

                // ドラッグ元の更新
                targetEntry(divElm, '0', true);
                dd.unreg();	// 数字がなくなるのでDDできなくする
                divElm.css('cursor','default');
            };
            dd.endDrag = function(e) {
            	// ドラッグ終了したらtargetを削除
            	$.each(tl, function(i,val) {
                    if (val != undefined) {
                        val.isTarget = false;   // これが必要
                        delete val;
                    }
            	});
            };
        }

        function makeDDTarget(id, date, targetH) {
            idArr = id.split('_');
            if (idArr[4]==date) return;	// ドラッグ元はターゲットとしない
            ddid='data_'+idArr[1]+'_'+idArr[2]+'_'+idArr[3]+'_'+date;
            $('#'+ddid).css('height',targetH);
            return new YAHOO.util.DDTarget(ddid);
        }

        function targetEntry(targetDiv, val, noAdd) {
            var tId = targetDiv.get(0).id;
            var tIdArr = tId.split('_');
            var itemProcessSeiban = tIdArr[1]+'_'+tIdArr[2]+'_'+tIdArr[3];
            var targetVal = gen.util.delFigure(targetDiv.html());
            val = gen.util.delFigure(val);
            if (!noAdd && gen.util.isNumeric(targetVal) && gen.util.isNumeric(val)) val = parseFloat(targetVal) + parseFloat(val);
            entryData(targetDiv, itemProcessSeiban, tIdArr[4], val, targetVal, targetDiv.css('background-color'));
        }
        ";

        // 期間セレクタ作成
        $html_mrp_date_preset = "
            <select id='mrp_date_preset' onchange='mrpDatePresetChange()'>
            <option value='0'></option>
            <option value='1'" . (@$form['mrp_date_preset'] == '1' ? ' selected' : '') . ">" . _g('1週間') . " (" . date("Y-m-d", strtotime("+7 day")) . ")</option>
            <option value='2'" . (@$form['mrp_date_preset'] == '2' ? ' selected' : '') . ">" . _g('2週間') . " (" . date("Y-m-d", strtotime("+14 day")) . ")</option>
            <option value='3'" . (@$form['mrp_date_preset'] == '3' ? ' selected' : '') . ">" . _g('3週間') . " (" . date("Y-m-d", strtotime("+21 day")) . ")</option>
            <option value='4'" . (@$form['mrp_date_preset'] == '4' ? ' selected' : '') . ">" . _g('1ヶ月') . " (" . date("Y-m-d", strtotime("+1 month")) . ")</option>
            <option value='5'" . (@$form['mrp_date_preset'] == '5' ? ' selected' : '') . ">" . _g('2ヶ月') . " (" . date("Y-m-d", strtotime("+2 month")) . ")</option>
            <option value='6'" . (@$form['mrp_date_preset'] == '6' ? ' selected' : '') . ">" . sprintf(_g("%s日"), GEN_MRP_DAYS) . " (" . date("Y-m-d", strtotime("+" . GEN_MRP_DAYS . " day")) . ")</option>
            </select>
            <img src='img/pin02.png' id='gen_pin_off_mrp_date_preset' style='vertical-align: text-top; cursor:pointer;" . (in_array('mrp_date_preset', $form['gen_pins']) ? "display:none;" : "") . "' onclick=\"gen.pin.turnOn('Manufacturing_Mrp_List', 'mrp_date_preset', '');\">
            <img src='img/pin01.png' id='gen_pin_on_mrp_date_preset' style='vertical-align: text-top; cursor:pointer;" . (!in_array('mrp_date_preset', $form['gen_pins']) ? "display:none;" : "") . "' onclick=\"gen.pin.turnOff('Manufacturing_Mrp_List', 'mrp_date_preset', '');\">
        ";

        // 対象日付コントロール作成
        $html_date = Gen_String::makeCalendarHtml(
            array(
                'label' => "",
                'name' => "mrp_date",
                'value' => @$form['mrp_date'],
                'size' => '85',
                'nonSubButton' => true,
                'subText_noEscape' => " {$html_mrp_date_preset} " . _g("まで"),
            )
        );

        // オーダー日コントロール作成
        $html_fix_order_date = Gen_String::makeCalendarHtml(
            array(
                'label' => _g("オーダー日") . _g("："),
                'name' => "fix_order_date",
                'value' => @$form['fix_order_date'],
                'size' => '85',
                'nonSubButton' => true,
                'subText_noEscape' => " " . _g("まで"),
            )
        );

        $msg = "";
        // 内示書発行したが、データがなかったとき
        if (@$form['gen_nodata']) {
             $msg = "<script>alert('" . _g("対象となるデータがありません。") . "')</script>";
        }

        // ユーザのパーミッションを取得し、チェックボックスの状態を設定
        // 戻り値  -1: セッション不正  0: アクセス権限なし  1: 読み取りのみ  2: 読み書き可能
        $notPOrder = (Gen_Auth::sessionCheck('partner_order') == 2 ? "" : "disabled");
        $notSubOrder = (Gen_Auth::sessionCheck('partner_subcontract') == 2 ? "" : "disabled");
        $notManOrder = (Gen_Auth::sessionCheck('manufacturing_order') == 2 ? "" : "disabled");
        $notSeiban = (Gen_Auth::sessionCheck('stock_seibanchange') == 2 ? "" : "disabled");

        // 実行状況表示
        $msg .= "
        <table border='0'>
            <tr>
                <td align='left'>●" . _g("所要量計算実行") . "</td>
                <td width='20px'></td>
                <td align='left'><div class='gray_msg'>●" . _g("計算結果の確定") . "</div></td>
            </tr>
            <tr>
                <td valign='top' id='mrpStart'>
                    <table border='1' cellspacing='0' cellpadding='2' style='border-style: solid; border-color: #999999; border-collapse: collapse;'>
                        <tr><td style='background-color: #d5ebff; padding: 7px;'>
                            <table border='0' style='background-color: #d5ebff;'>
                                <tr><td align='center' colspan='2'>
                                    <span>
                                    " . sprintf(_g("対象日付： 明日（%s）から"), date("Y-m-d", strtotime("+1 day"))) . "{$html_date}
                                    </span>
                                </td></tr>
                                <tr><td align='left'>
                                    <input type='checkbox' value='true' id='isNaiji'" . ((@$form['isNaiji'] == 'true') ? " checked" : "") . ">
                                    " . _g("内示モード（受注「予約」も含める）") . "
                                    <img src='img/pin02.png' id='gen_pin_off_isNaiji' style='vertical-align: text-top; cursor:pointer;" . (in_array('isNaiji', $form['gen_pins']) ? "display:none;" : "") . "' onclick=\"gen.pin.turnOn('Manufacturing_Mrp_List', 'isNaiji', '');\">
                                    <img src='img/pin01.png' id='gen_pin_on_isNaiji' style='vertical-align: text-top; cursor:pointer;" . (!in_array('isNaiji', $form['gen_pins']) ? "display:none;" : "") . "' onclick=\"gen.pin.turnOff('Manufacturing_Mrp_List', 'isNaiji', '');\">
                                    <br>
                                    <input type='checkbox' value='true' id='isNonSafetyStock'" . ((@$form['isNonSafetyStock'] == 'true') ? " checked" : "") . ">
                                    " . _g("安全在庫数を含めない") . "
                                    <img src='img/pin02.png' id='gen_pin_off_isNonSafetyStock' style='vertical-align: text-top; cursor:pointer;" . (in_array('isNonSafetyStock', $form['gen_pins']) ? "display:none;" : "") . "' onclick=\"gen.pin.turnOn('Manufacturing_Mrp_List', 'isNonSafetyStock', '');\">
                                    <img src='img/pin01.png' id='gen_pin_on_isNonSafetyStock' style='vertical-align: text-top; cursor:pointer;" . (!in_array('isNonSafetyStock', $form['gen_pins']) ? "display:none;" : "") . "' onclick=\"gen.pin.turnOff('Manufacturing_Mrp_List', 'isNonSafetyStock', '');\">
                                </td><td width='230' align='center'>
                                    <br>
                                    <input type='button' class='gen-button' id='mrpStartButton' value='" . _g("所要量計算を開始する") . "' onClick='doMRP()' disabled='true'>
                                    " . ($form['gen_readonly'] == 'true' ? "<br><span style='color:blue'>" . _g('所要量計算を実行する権限がありません。') . "</span>" : "") . "
                                </td></tr>
                            </table>
                        </td></tr>
                    </table>
                </td>
                <td></td>
                <td valign='top'>
                    <div class='gray_msg'>
                        <table border='1' cellspacing='0' cellpadding='2' style='border-style: solid; border-color: #999999; border-collapse: collapse;'>
                            <tr><td style='background-color: #fdd9db; padding: 7px;'>
                                <table border='0' style='background-color: #fdd9db;'>
                                    <tr>
                                        <td align='left' width='140px' nowrap><input type='checkbox' id='fix_par_order' {$notPOrder}>&nbsp;<a href='index.php?action=Partner_Order_List' style='color:#000000' target='_blank'>" . _g("注文（注文書）") . "</a></td>
                                        <td width='5px' rowspan='4'></td>
                                        <td align='left' rowspan='4' nowrap>
                                            <div>{$html_fix_order_date}</div>
                                            <input type='checkbox' value='true' id='fix_and_print'" . ((@$form['fix_and_print'] == 'true') ? " checked" : "") . ">&nbsp;" . _g("同時に帳票を印刷する") . "
                                            <img src='img/pin02.png' id='gen_pin_off_fix_and_print' style='vertical-align: text-top; cursor:pointer;" . (in_array('fix_and_print', $form['gen_pins']) ? "display:none;" : "") . "' onclick=\"gen.pin.turnOn('Manufacturing_Mrp_List', 'fix_and_print', '');\">
                                            <img src='img/pin01.png' id='gen_pin_on_fix_and_print' style='vertical-align: text-top; cursor:pointer;" . (!in_array('fix_and_print', $form['gen_pins']) ? "display:none;" : "") . "' onclick=\"gen.pin.turnOff('Manufacturing_Mrp_List', 'fix_and_print', '');\">
                                            <br><br>
                                            <center>
                                                <input type='button' class='gen-button' id='fixOrderButton' value='" . _g("計算結果を確定する") . "' onClick='javascript:fixOrder()' " . ($form['gen_readonly'] == 'true' || $notOrder ? "disabled" : "") . ">
                                            " . ($form['gen_readonly'] == 'true' ? "<br><span style='color:blue'>" . _g('計算結果を確定する権限がありません。') . "</span>" : "") . "
                                            </center>
                                        </td>
                                    </tr><tr>
                                        <td align='left' width='140px' nowrap><input type='checkbox' id='fix_sub_order' {$notSubOrder}>&nbsp;<a href='index.php?action=Partner_Subcontract_List' style='color:#000000' target='_blank'>" . _g("外製（外製指示書）") . "</a></td>
                                    </tr><tr>
                                        <td align='left' width='140px' nowrap><input type='checkbox' id='fix_man_order' {$notManOrder}>&nbsp;<a href='index.php?action=Manufacturing_Order_List' style='color:#000000' target='_blank'>" . _g("内製（製造指示書）") . "</a></td>
                                    </tr><tr>
                                        <td align='left' width='140px' nowrap><input type='checkbox' id='fix_seiban' {$notSeiban}>&nbsp;<a href='index.php?action=Stock_SeibanChange_List' style='color:#000000' target='_blank'>" . _g("製番引当") . "</a></td>
                                    </tr>
                                    " . ($notOrder ? "<tr><td colspan='3' align='left' style='padding-top:5px;'><span style='color:red'>" . _g("所要量計算の結果に削除された受注製番が含まれています。") . "<br>" . _g("所要量計算を再実行してください。") . "</span></td></tr>" : "") . "
                                </table>
                            </td></tr>
                        </table>
                    </div>
                </td>
            </tr>
            <tr>
                <td align='left' colspan='3'>
                    <table border='0'>
                        <tr>
                            <td valign='middle' id='css_graph'>
                                <div class='graph_base' style='width:300px; height:30px; background:url(img/graph_base.gif) no-repeat; '>
                                   <div class='graph' id='graph' style='width:0px; height:30px;
                                     background:url(img/graph.gif) no-repeat; float:left;'>
                                   </div>
                               </div>
                            </td>
                            <td align='center' style='padding: 0px 0px 0px 20px; '>
                                <div id='msg' align='left'>" . _g("状況確認中") . "<BR>" . _g("しばらくお待ちください") . "... </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr><td align='center' colspan='3' id='mrpProgress'></td></tr>
        </table>

        <div class='gray_msg'>
            <br>
            <table border='0' cellspacing='0' cellpadding='0'>
                <tr>
                    <td>" . _g("未確定のセル（工程・製番引当を除く）をクリックしたり、数字をドラッグすると内容を編集（手動調整）できます。") . "</td>
                    <td><a class='gen_chiphelp' href='#' rel='p.helptext_reset' title='" . _g("手動調整") . "' tabindex='-1'><img class='imgContainer sprite-question' src='img/space.gif' style='border:none'></a>
                        <p class='helptext_reset' style='display:none;'>" . _g("手動調整をした値は確定しなくても有効在庫に加算されます。（[計画登録]画面で登録した計画を手動調整すると有効在庫が二重に加算されますのでご注意ください）<br>") .
                        _g("手動調整をした場合は、オーダーの確定後に必ず「手動調整をリセットする」をクリックして調整履歴をクリアしてください。 ") . "</p></td>
                    <td width='15'></td>
                    <td><a href='javascript:resetHandAdjust()' style='color:#000000'>" . _g("手動調整をリセットする") . "</a></td>
                </tr>
            </table>
        </div>
        ";

        $form['gen_message_noEscape'] = $msg;

        $form['gen_colorSample'] = array(
            "d7d7d7" => array(_g("シルバー"), _g("データ確定済み")),
            "dfdfdf" => array(_g("ライトグレー"), _g("工程")),
            "ffc0cb" => array(_g("レッド"), _g("LTと休業日を無視してオーダー納期調整したレコード")),
            "ffeaef" => array(_g("ピンク"), _g("休業日")),
        );

        $form['gen_fixColumnArray'] = array(
            array(
                'label' => _g('製番'),
                'field' => 'seiban',
                'width' => '100',
                'sameCellJoin' => true,
                'align' => 'center',
            ),
            array(
                'label' => _g('品目コード'),
                'field' => 'item_code',
                'width' => '100',
                'sameCellJoin' => true,
                'parentColumn' => 'seiban'
            ),
            array(
                'label' => _g('品目名'),
                'field' => 'item_name',
                'sameCellJoin' => true,
                'parentColumn' => 'seiban'
            ),
            array(
                'label' => _g('メーカー'),
                'field' => 'maker_name',
                'width' => '100',
                'sameCellJoin' => true,
                'parentColumn' => 'item_code',
                'hide' => true,
            ),
            array(
                'label' => _g('仕様'),
                'field' => 'spec',
                'width' => '100',
                'sameCellJoin' => true,
                'parentColumn' => 'item_code',
                'hide' => true,
            ),
            array(
                'label' => _g('棚番'),
                'field' => 'rack_no',
                'width' => '100',
                'sameCellJoin' => true,
                'parentColumn' => 'item_code',
                'hide' => true,
            ),
            array(
                'label' => _g('品目備考1'),
                'field' => 'comment',
                'width' => '100',
                'sameCellJoin' => true,
                'parentColumn' => 'item_code',
                'hide' => true,
            ),
            array(
                'label' => _g('手配区分'),
                'field' => 'category',
                'width' => '50',
                'align' => 'center',
                'sameCellJoin' => true,
                'parentColumn' => 'item_code'
            ),
            array(
                'label' => _g('単位'),
                'field' => 'measure',
                'type' => 'data',
                'width' => '35',
                'sameCellJoin' => true,
                'parentColumn' => 'item_code'
            ),
            array(
                'label' => _g('分析'),
                'type' => 'literal',
                'literal_noEscape' => "<span style='color:#0094ff'>" . _g('分析') . "</span>",
                'width' => '35',
                'align' => 'center',
                'link' => 'index.php?action=Manufacturing_Mrp_Analyze&item_id=[item_id]&seiban=[urlencode:seiban]',
                'showCondition' => "'[order_class]' == '1'",
                'sameCellJoin' => true,
                'parentColumn' => 'item_code'
            ),
            array(
                'label' => _g('工順'),
                'field' => 'machining_sequence_show',
                'width' => '40',
                'align' => 'center',
                'colorCondition' => array(
                    "#dfdfdf" => "'[is_process]'=='1'", // 工程レコード
                ),
            ),
            array(
                'label' => _g('工程コード/手配先コード'),
                'field' => 'supplier_code',
                'width' => '110',
                'align' => 'left',
                'colorCondition' => array(
                    "#dfdfdf" => "'[is_process]'=='1'", // 工程レコード
                ),
            ),
            array(
                'label' => _g('工程名/手配先名'),
                'field' => 'supplier_name',
                'width' => '110',
                'align' => 'left',
                'colorCondition' => array(
                    "#dfdfdf" => "'[is_process]'=='1'", // 工程レコード
                ),
            ),
        );

        if (!isset($form['supplierListExcel'])) {

            $colCount = 0;
            $query = "select holiday_master.holiday from holiday_master where holiday between '" . date('Y-m-d', $this->from) . "' and '" . date('Y-m-d', $this->to) . "'";
            $holidays = $gen_db->getArray($query);
            $holidayArr = array();
            if (is_array($holidays)) {
                foreach ($holidays as $h) {
                    $holidayArr[] = $h['holiday'];
                }
            }

            switch ($form['gen_search_date_mode']) {
                case '1':
                    $addStr = " +1 week";
                    break;
                case '2':
                    $addStr = " +1 month";
                    break;
                default :
                    $addStr = " +1 day";
                    break;
            }

            $isDayMode = ($form['gen_search_date_mode'] != '1' && $form['gen_search_date_mode'] != '2');

            for ($day = $this->from; $day <= $this->to; $day = strtotime(date('Y-m-d', $day) . $addStr)) {
                switch ($form['gen_search_date_mode']) {
                    case '1':
                        $label = date('m-d', $day);
                        break;
                    case '2':
                        $label = date('Y-m', $day);
                        break;
                    default :
                        $label = date('m-d', $day) . "(" . Gen_String::weekdayStr(date('Y-m-d', $day)) . ")";
                        break;
                }
                $field = "day_" . date('Ymd', $day);
                $dateStr = date('Y-m-d', $day);
                $colArr = array(
                    'label' => $label,
                    'field' => $field,
                    'width' => '70',
                    'type' => 'numeric',
                    'denyMove' => true, // 日付列は列順序固定。日付範囲を変更したときの表示乱れを防ぐため
                    // クリックによるダイレクトデータ入力用。ちなみにアクセス権はJSと登録クラスでチェック
                    'cellId' => "data_[id]_$dateStr",
                    'onClick' => "dayClick('[id]','$dateStr','[is_allocate]')", // onclickの有無は、D&D処理で編集可否の判断に使用されていることに注意
                    'onClickCondition' =>
                    // 確定済みセルは編集禁止
                    "strtotime('[fix_date]')<'{$day}' " .
                    // 製番引当・工程行は編集禁止。
                    // 現在の方式（裏で計画レコードを登録）では、製番引当を修正すると次回の所要量計算で内製や注文が出てしまう。
                    // ちなみに、クリック不可にするよりクリック時にメッセージを出すほうが親切だが、
                    // D&D処理のところで（makeDDElm(), makeDDElmSum()）onclickの有無を編集可否の判断に使用しているため、
                    // やむをえずこういう形になっている。
                    " and '[is_process]'!='1' and '[is_allocate]'!='1' ",
                    'colorCondition' => array(
                        "#d7d7d7" => "strtotime('[fix_date]')>='{$day}'", // 確定済データ
                        "#dfdfdf" => "'[is_process]'=='1'", // 工程レコード
                        "#ffc0cb" => $isDayMode ? "'[{$field}_alarm_flag]'=='1'" : "false", // アラーム（間に合わない品目）の色付け
                        "#ffeaef" => $isDayMode && in_array($dateStr, $holidayArr) ? "true" : "false", // 休日
                    ),
                );
                $form['gen_columnArray'][] = $colArr;
                $colCount++;
            }
        } else {
            // 「取引先別エクセル出力（リスト）」
            $form['gen_columnArray'][] = array(
                'label' => _g('オーダー日'),
                'field' => 'date',
                'width' => '70',
                'type' => 'date',
            );
            $form['gen_columnArray'][] = array(
                'label' => _g('オーダー数'),
                'field' => 'quantity',
                'width' => '70',
                'type' => 'numeric',
            );
        }

        // 「取引先別エクセル出力」機能のときはOrder Byを強制的に指定しているので、カラムが必要
        if (isset($form['supplierExcel']) || isset($form['supplierListExcel'])) {
            $form['gen_columnArray'][] = array(
                'label' => "supplier_id",
                'field' => 'supplier_id',
                'width' => '0',
                'type' => 'numeric',
            );
        }
    }

}