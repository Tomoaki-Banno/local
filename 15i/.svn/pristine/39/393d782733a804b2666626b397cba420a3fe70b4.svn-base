<?php

require_once("Model.class.php");

class Manufacturing_Achievement_Edit extends Base_EditBase
{

    function convert($converter, &$form)
    {
        $converter->nullBlankToValue('achievement_date', date("Y-m-d"));
        $converter->nullBlankToValue('work_minute', 0);
    }

    // 既存データSQL実行前に処理
    function setQueryParam(&$form)
    {
        $this->keyColumn = 'achievement_id';

        $this->selectQuery = "
            select
                achievement.achievement_id
                ,achievement.achievement_date
                -- 以前は時刻フォーマットを表示側で調整していたが、履歴入力機能の関係でここで調整するようにした
                ,to_char(achievement.begin_time, 'HH24:MI') as begin_time
                ,to_char(achievement.end_time, 'HH24:MI') as end_time
                ,achievement.order_header_id
                ,achievement.order_detail_id
                ,achievement.lot_no
                ,achievement.use_lot_no
                ,achievement.item_id
                ,achievement.achievement_quantity
                ,achievement.product_price
                ,achievement.order_seiban
                ,achievement.stock_seiban
                ,achievement.work_minute
                ,achievement.break_minute
                ,achievement.location_id
                ,achievement.child_location_id
                ,achievement.process_id
                ,achievement.equip_id
                ,achievement.worker_id
                ,achievement.section_id
                ,achievement.middle_process
                ,achievement.cost_1
                ,achievement.cost_2
                ,achievement.cost_3
                ,achievement.remarks

                ,order_header.order_date
                ,item_master.item_name
                ,item_master.item_code
                ,case when order_detail_completed or order_process.process_completed then 'true' else '' end as order_detail_completed

                ,achievement.use_by
            ";
        for ($i = 1; $i <= GEN_WASTER_COUNT; $i++) {
            $this->selectQuery .= "
                ,waster_id_{$i}
                ,waster_quantity_{$i}
            ";
        }
        $this->selectQuery .= "
                ,coalesce(achievement.record_update_date, achievement.record_create_date) as gen_last_update
                ,coalesce(achievement.record_updater, achievement.record_creator) as gen_last_updater
            from
                achievement
                inner join order_detail on achievement.order_detail_id = order_detail.order_detail_id
                left join order_header on order_detail.order_header_id = order_header.order_header_id
                left join item_master on order_detail.item_id = item_master.item_id
                left join order_process on achievement.order_detail_id = order_process.order_detail_id and achievement.process_id = order_process.process_id
                left join (
                    select
                        achievement_id as ach_id
            ";
            for ($i = 1; $i <= GEN_WASTER_COUNT; $i++) {
                $this->selectQuery .= "
                        ,max(case when line_number = {$i} then waster_detail.waster_id else null end) as waster_id_{$i}
                        ,max(case when line_number = {$i} then waster_detail.waster_quantity else null end) as waster_quantity_{$i}
                ";
            }
        $this->selectQuery .= "
                    from
                        waster_detail
                    group by
                        achievement_id
                    ) t_waster on achievement.achievement_id = t_waster.ach_id
            [Where]
        ";

        // データロックの判断基準となるフィールドを指定
        $form["gen_dateLockFieldArray"] = array("achievement_date");
    }

    // 既存データSQL実行後に処理
    // 画面表示のための設定
    function setViewParam(&$form)
    {
        global $gen_db;

        $this->modelName = "Manufacturing_Achievement_Model";

        $form['gen_pageTitle'] = _g("実績登録");
        $form['gen_entryAction'] = "Manufacturing_Achievement_Entry";
        $form['gen_listAction'] = "Manufacturing_Achievement_List";
        $form['gen_onLoad_noEscape'] = "onLoad();";
        $form['gen_beforeEntryScript_noEscape'] = "onEntry()";
        $form['gen_pageHelp'] = _g("製造実績");

        $form['gen_focus_element_id'] = "order_detail_id_show";

        // 新規モードではPOST URLに非ロックフラグを追加する。
        //  オーダー番号の項目は編集（or コピー）モードではロックされるようになっており、
        //  その際のモード判断は isset($form['order_detail_id']) で行っている。
        //  しかしそれだけだと、新規モードでオーダー番号を選択した状態で「リセット」リンクを
        //  クリックした時に誤動作する。（リセット時に order_detail_id が POSTされるため。）
        //  それで、新規モードの場合はオーダー番号の非ロックフラグをたてるようにする。
        if (!isset($form['order_detail_id'])) {
            $form['gen_editActionWithKey'] .= "&noLock";
        }

        $msg1 = _g("指定された番号は注文書のオーダー番号です。注文書の受入登録は[受入登録]画面で行ってください。");
        $msg2 = _g("指定された番号は外製指示書のオーダー番号です。外製指示書の受入登録は[外製受入登録]画面で行ってください。");
        $msg3 = _g("オーダー番号が正しくありません。");
        $msg4 = _g("この登録により、出庫ロケーションにおける以下の品目の在庫が0を下回ります。よろしいですか？");
        $wasterCount = GEN_WASTER_COUNT;
        $processId = h(@$form['process_id']);  // ヒアドキュメント内では「@」が使えない
        $achId = h(@$form['achievement_id']);
        $isNew = (isset($form['achievement_id']) ? "false" : "true");
        $form['gen_javascript_noEscape'] = "
            var processRemained = new Array;
            var isFinalProcessSubcontract = false;

            function onLoad() {
                var elm = $('#achivement_child_usage');
                if (elm.length == 0) {
                    $('#gen_edit_area').append('<div style=\"height:50px\"></div><div id=\"achivement_child_usage\" style=\"text-align:left\"></div>');
                }
                onOrderIdChange();
            }

            // オーダー変更イベント（ページロード時にも実行）。Ajaxリクエストを行う
            function onOrderIdChange() {
                $('#item_code').val('');
                $('#item_name').val('');
                if ({$isNew} && $('#work_minute').val()=='') $('#work_minute').val(0);     // 登録済の製造時間が表示されるように
                $('#order_seiban').val('');
                $('#plan_quantity').val('');
                $('#remained_quantity').val('');
                var orderDetailId = $('#order_detail_id').val();
                // オーダー番号指定チェック。通信後のエラー表示のため、ボックスが空欄でない限りあえて通すようにした
                if ($('#order_detail_id_show').val() == '') return;
                // Ajaxリクエストが終了するまで、登録ボタンを押せないようにする（ただしオーダー変更直後は押せてしまう・・）
                // 製番はAjaxで取得した品目をPOSTし、サーバー側で登録するので、Ajax完了まで登録させないことが重要
                gen.edit.submitDisabled();
                gen.ajax.connect('Manufacturing_Achievement_AjaxOrderParam', {order_detail_id : orderDetailId" . (isset($form['achievement_id']) ? ", achievement_id : " . $form['achievement_id'] : "") . "},
                    function(j) {
                        if (j != '') {
                            // オーダー種類不正の判定
                            if (j.classification != '0') {
                                if (j.classification=='1') {
                                   alert('{$msg1}');
                                } else {
                                   alert('{$msg2}');
                                }
                                gen.edit.submitEnabled('" . h($form['gen_readonly']) . "');
                                return;
                            }
                            $('#item_code').val(j.item_code);
                            $('#item_name').val(j.item_name);
                            $('#order_seiban').val(j.order_seiban);
                            $('#plan_quantity').val(gen_round(j.plan_quantity));
                            $('#remained_quantity').val(gen_round(j.remained_quantity));
                            if ({$isNew}) {;   // 備考デフォルトは新規モードのみ記入。修正モードで既存の備考が上書きされないように。
                                $('#remarks').val(j.remarks);
                            }
                            if ($('#achievement_quantity').val() == '') {;
                                $('#achievement_quantity').val(gen_round(j.remained_quantity));
                            }
                            if ({$isNew} && gen.util.isNumeric(j.default_location_id_3))
                                $('#location_id').val(j.default_location_id_3);

                            $('#achievement_quantity').get(0).select();
                            isFinalProcessSubcontract = (j.isFinSub == 1);

                            // 工程セレクタの選択肢を設定
                            processRemained = new Array();
                            if (j.str == '') {
                                alert('" . _g("このオーダーには未登録の工程が存在しませんので、実績登録を行うことはできません。") . "');
                                $('#order_detail_id').val('');
                                $('#order_detail_id_show').val('');
                            } else {
                                var sel = $('#process_id').get(0);
                                var len = sel.length;
                                // 工程セレクタの選択肢をいったんクリア
                                for (i=0;i<len;i++) {
                                   sel.options[0] = null;
                                }
                                // 工程セレクタの選択肢を設定
                                var proc = j.str.split(';');
                                for (i=0;i<proc.length;i+=4) {
                                    sel.options[parseInt(i/4)] = new Option(proc[i+1] + '. ' + proc[i+2], proc[i]);
                                    processRemained[parseInt(i/4)] = proc[i+3];
                                }
                                // selectedを設定
                                var selected = false;
                                // 修正・コピーモードのときは、もとの工程がselectedされるようにする
                                var oldSelected = '{$processId}';
                                if (oldSelected != '') {
                                    for (i=0;i<proc.length;i+=4) {
                                        if (oldSelected == proc[i]) {  // これまでselectedされていた項目があれば、それをselected
                                            sel.selectedIndex = parseInt(i/4);
                                            selected = true;
                                            break;
                                        }
                                    }
                                }
                                if (!selected) {
                                    for (i=0;i<proc.length;i+=4) {
                                        if (proc[i+3]>0) {               // 残数がある最初の項目をselected
                                            sel.selectedIndex = parseInt(i/4);
                                            break;
                                        }
                                    }
                                }
                                onProcessChange({$isNew});  // 工程変更イベント。新規のときだけ製造数デフォルトをセット
                            }
                            $('#achievement_quantity').focus().select();
                        } else {
                            $('#gen_message_noEscape').html(\"<font color='red'>{$msg3}</font>\");
                        }
                        gen.edit.submitEnabled('" . h($form['gen_readonly']) . "');
                    });
            }

            // 最終工程チェック
            function isFinalProcess() {
                if (isFinalProcessSubcontract) {
                    // 最終工程が外製工程の場合、最終工程は選択肢に出てこないので、最終工程ではありえない
                    return false;
                }
                var sel = $('#process_id').get(0);
                return (sel.length == (sel.selectedIndex)+1);
            }

            // 工程変更イベント
            function onProcessChange(isDefaultQtySet) {
                // 「最終工程のみ」項目のイネーブル
                var isFin = isFinalProcess()
                gen.ui.alterDisabled($('#lot_no'), !isFin);
                $('#lot_no+a').css('display', isFin ? '' : 'none');
                gen.ui.alterDisabled($('#use_lot_no'), !isFin);
                $('#use_lot_no+a').css('display', isFin ? '' : 'none');
                gen.ui.alterDisabled($('#use_by'), !isFin);
                $('#use_by+input').css('display', isFin ? '' : 'none')
                    .next('input').css('display', isFin ? '' : 'none')
                    .next('input').css('display', isFin ? '' : 'none')
                    .next('input').css('display', isFin ? '' : 'none');
                gen.ui.alterDisabled($('#location_id'), !isFin);
                if (isFin) {
                    showChildUsage($('#order_detail_id').val());
                } else {
                    hideChildUsage();
                }

                //　工程ごとの製造残を製造数デフォルトとして設定
                if (isDefaultQtySet) {
                    var sel = $('#process_id').get(0);
                    if (sel.selectedIndex >= processRemained.length) return;
                    $('#achievement_quantity').val(gen_round(processRemained[sel.selectedIndex]));
                }
            }

            // 子品目使用数リスト表示（最終工程用）
            function showChildUsage(orderDetailId) {
                gen.ajax.connect('Manufacturing_Achievement_AjaxChildItem', {order_detail_id : orderDetailId, achievement_id: " . (isset($form['achievement_id']) ? h($form['achievement_id']) : "null") . "},
                    function(j) {
                        var html = '';
                        if (j.children != '') {
//                            var child = j.children.split(';');
                            var html = '<span style=\"font-weight:bold;color:blue\">" . _g("●子品目使用数") . "</span>';
                            html += '&nbsp;&nbsp;<span style=\"font-size:11px\">" . _g("※空欄にすると標準使用数（製造数量 × 員数）+ 不適合使用数（不適合数 × 員数）が引き落とされます。使用しなかった品目は0を入力してください。") . "</span><br>';
                            html += '<table>';
                            html += '<tr>';
                            html += '<td style=\"width:150px\"></td>';
                            html += '<td style=\"width:250px\"></td>';
                            html += '<td style=\"width:60px;text-align:right\">" . _g('員数') . "</td>';
                            html += '<td width=\"5px\"></td>';
                            html += '<td style=\"width:60px;text-align:right\">" . _g('標準使用数') . "</td>';
                            html += '<td width=\"5px\"></td>';
                            html += '<td>" . _g('実使用数') . "</td>';
                            html += '</tr>';
                            $.each(j.children, function(i, arr) {
                            console.log(arr);
                                html += '<tr>';
                                html += '<td>' + gen.util.escape(arr[1]) + '</td>';
                                html += '<td>' + gen.util.escape(arr[2]) + '</td>';
                                html += '<td style=\"text-align:right\">' + gen.util.escape(arr[3]) + '</td>';
                                html += '<td></td>';
                                html += '<td style=\"text-align:right\" id=\"default_usage_' + gen.util.escape(arr[0]) + '\" data-inzu=\"' + gen.util.escape(arr[3]) + '\"></td>';
                                html += '<td></td>';
                                html += '<td><input type=text name=\"child_usage_' + gen.util.escape(arr[0]) + '\" value=\"' + gen.util.escape(arr[4]) + '\" style=\"width:60px\" onchange=\"onUsageChange(' + gen.util.escape(arr[0]) + ')\"></td>';
                                html += '</tr>';
                            });
                            html += '</table>';
                        }
                        $('#achivement_child_usage').html(html);
                        setDefaultChildUsage();
                    });
            }

            // 子品目使用数リスト非表示
            function hideChildUsage() {
                $('#achivement_child_usage').html('" . _g("※子品目の使用数は最終工程を選択したときのみ表示/登録できます。") . "');
            }

            // 子品目標準使用数の表示
            function setDefaultChildUsage() {
                var qty = $('#achievement_quantity').val();
                if (!gen.util.isNumeric(qty)) {
                    return;
                }
                $('[id^=default_usage_]').each(function(i,elm){
                    var inzu = $(this).attr('data-inzu');
                    if (gen.util.isNumeric(inzu)) {
                        $(this).html(qty * inzu);
                    }
                });
            }

            // 子品目使用数のクリア
            function clearChildUsage() {
                isFirst = true;
                $('[name^=child_usage_]').each(function(i,elm){
                    var uQty = $(this).val();
                    if (uQty != '') {
                        if (isFirst) {
                            if (!confirm('" . _g("子品目の使用数をクリア（標準の使用数に戻す）しますか？") . "')) {
                                return false;
                            }
                            isFirst = false;
                        }
                        $(this).val('');
                    }
                });
            }

            // 製造数量変更イベント
            function onQtyChange() {
                setDefaultChildUsage();
                clearChildUsage();
            }

            // 不適合数変更イベント
            function onWasterQtyChange() {
                clearChildUsage();
            }

            // 子品目使用数変更イベント
            function onUsageChange(no) {
                var childUsageElm = $('[name=child_usage_' + no + ']');
                var isChange = ($('#default_usage_' + no).html() != childUsageElm.val() && childUsageElm.val() != '');
                if (isChange) {
                    childUsageElm.after('<span id=\"usage_chage_flag_' + no + '\" style=\"color:red\"> " . _g("変更") ."</span>');
                } else {
                    $('#usage_chage_flag_' + no).remove();
                }
            }

            // 登録前処理（子品目在庫切れチェック）
            function onEntry() {
                // 着手登録のときはノーチェック
                // ちなみに中間工程はチェックする必要がある。不適合による子品目引落が行われるため。サーバー側の処理を参照
                if ($('#begin_time').val()!='' && $('#end_time').val()=='') {
                    document.forms[0].submit();
                    return;
                }

                gen.edit.submitDisabled();

                var p = {
                    order_detail_id: $('#order_detail_id').val(),
                    location_id: $('#child_location_id').val(),
                    quantity: $('#achievement_quantity').val(),
                    process_id: $('#process_id').val(),
                    achievement_id: '{$achId}'
                };
                if (!gen.util.isNumeric(p.order_detail_id) || !gen.util.isNumeric(p.location_id) || !gen.util.isNumeric(p.quantity)) {
                    document.forms[0].submit();
                    return;
                };

                // 不適合数
                var waster = 0;
                for (i=1;i<= $wasterCount ;i++) {
                    wq = $('#waster_quantity_' + i).val();
                    if (gen.util.isNumeric(wq)) waster += parseFloat(wq);
                }
                p.waster = waster;

                // 子品目使用数
                $('[name^=child_usage_]').each(function(){
                    var elm = $(this);
                    var val = elm.val();
                    if (gen.util.isNumeric(val)) {
                        var id = $(this).attr('name').split('_')[2];
                        p['use_' + id] = val;
                    }
                });

                gen.ajax.connect('Manufacturing_Achievement_AjaxLocationStockAlarm', p,
                    function(j) {
                        if (j.msg != '') {
                            if (!confirm('$msg4' + '\\n' + j.msg)) {
                                gen.edit.submitEnabled('" . h($form['gen_readonly']) . "');
                                return;
                            }
                        }
                        document.forms[0].submit();
                    });
            }

            // 製造開始・終了時刻から製造時間を計算
            function calcWorkTime() {
                var begin = $('#begin_time').val();
                var end = $('#end_time').val();
                var d = new Date();
                var tStr = d.getFullYear() + '/' + (d.getMonth()+1) + '/' + d.getDate() + ' ';
                if (isNaN(Date.parse(tStr + begin)) || isNaN(Date.parse(tStr + end)) || begin=='' || end=='') {
                    return;
                }
                var breakMin = $('#break_minute').val();
                if (!gen.util.isNumeric(breakMin)) {
                    breakMin = 0;
                }
                $('#work_minute').val(parseInt(Date.parse(tStr + end) - Date.parse(tStr + begin))/60000 - breakMin);
            }

            // 製造時間・製造開始時刻から製造終了時刻を計算
            function calcEndTime() {
                var begin = $('#begin_time').val();
                var workMin = $('#work_minute').val();
                var breakMin = $('#break_minute').val();
                if (!gen.util.isNumeric(breakMin)) {
                    breakMin = 0;
                }
                var d = new Date();
                var tStr = d.getFullYear() + '/' + (d.getMonth()+1) + '/' + d.getDate() + ' ';
                if (isNaN(Date.parse(tStr + begin)) || isNaN(workMin) || begin=='' || workMin=='') {
                    return;
                }
                var endTime = Date.parse(tStr + begin) + (workMin*60000) + (breakMin*60000) - Date.parse(tStr);
                var endHour = parseInt(endTime / 3600000);
                var endMin = parseInt((endTime - (endHour * 3600000)) / 60000);
                $('#end_time').val(endHour + ':' + ('0' + endMin).substr(-2));
            }

            // 従業員を選んだらその所属部門を部門のデフォルトとして設定
            function onWorkerChange() {
                var wid = $('#worker_id').val();
                if (wid == 'null') return;
                gen.ajax.connect('Manufacturing_Achievement_AjaxWorkerParam', {worker_id : wid},
                    function(j) {
                        document.getElementById('section_id').value = j.section_id;
                    });
            }
        ";

        $query = "select location_id, location_name from location_master order by location_code";
        $option_location_group = $gen_db->getHtmlOptionArray($query, false, array("0" => _g(GEN_DEFAULT_LOCATION_NAME)));

        $option_child_location_group[-1] = _g("(各部材の標準ロケ)");
        foreach ($option_location_group as $key => $val) {
            $option_child_location_group[$key] = $val;
        }

        $query = "select section_id, section_name from section_master order by section_code";
        $option_section = $gen_db->getHtmlOptionArray($query, true);

        $query = "select equip_id, equip_name from equip_master order by equip_code";
        $option_equip = $gen_db->getHtmlOptionArray($query, true);

        $query = "select waster_id, waster_name from waster_master order by waster_code";
        $option_waster = $gen_db->getHtmlOptionArray($query, true);

        $form['gen_editControlArray'] = array(
            array(
                'label' => _g('オーダー番号'), // 登録はorder_detail_idだが表示は「オーダー番号」
                'type' => 'dropdown',
                'name' => 'order_detail_id',
                'value' => @$form['order_detail_id'],
                'dropdownCategory' => 'manufacturing',
                'size' => '12',
                'readonly' => (isset($form['order_detail_id']) && !($form['order_detail_id'] == '') && !isset($form['noLock'])),
                'require' => true,
                'onChange_noEscape' => 'onOrderIdChange()',
                // ピンなし。この項目は新規モードと修正モードの判別に使用されているため。
                // もしピンをうつと、新規でも修正モード扱いになってしまう。
                'hidePin' => true,
                'helpText_noEscape' => _g("製造指示書のオーダー番号を選択します。") . "<br>" .
                        _g("製造指示書リスト画面に表示されているオーダーの「内製」工程のみが指定できます。") . "<br>" .
                        _g("「外製」工程が含まれている場合は、別途その工程だけ [購買管理]-[外製受入登録] 画面で登録してください。"),
            ),
            //  日付と時刻をワンボックスにする方法もあったが、
            //  時刻を使用しない（作業管理を行わない）場合も考慮し、日付と時刻を分けた。
            //  なお、09と08では「製造日」の意味がやや異なることに注意。
            //  08では「親品目を在庫入庫し、子品目を引き落とす日」であり、在庫を基準に考えればよかった。
            //  09ではそれに加えて作業員が実際に作業開始/完了した日時、という意味もある。
            //  作業完了日と在庫計上日が異なる場合は運用が難しいかもしれないが、レアケースだと思う。
            array(
                'label' => _g('製造日'),
                'type' => 'calendar',
                'name' => 'achievement_date',
                'value' => @$form['achievement_date'],
                'require' => true,
                'size' => '8',
                'helpText_noEscape' => _g('着手、もしくは製造を完了した日付を入力します。「2006-12-31」のような書式で入力するか、▽ボタンを押してカレンダー入力してください。'),
            ),
            array(
                'label' => _g('製造数量'),
                'type' => 'textbox',
                'name' => 'achievement_quantity',
                'value' => @$form['achievement_quantity'],
                'require' => true,
                'onChange_noEscape' => 'onQtyChange()',
                'ime' => 'off',
                'size' => '8',
                'helpText_noEscape' => _g('製造数量（実績数）を入力します。') . '<br><br>'
                . _g('新規登録の場合、デフォルトで前工程の製造数量が表示されます。') . '<br>'
                . _g('前工程とは、完了している工程（工程の製造数量が計画数に満ちている、もしくは「工程完了」フラグがオンになっている工程）のうち、製造日が最後の工程です。製造日が同日であれば登録日時で判断されます。')
                . _g('必ずしも品目マスタの工程の登録順どおりではありません。'),
            ),
            array(
                'type' => 'literal',
                'denyMove' => true,
            ),
            array(
                'label' => _g('製造開始時刻'),
                'type' => 'textbox',
                'name' => 'begin_time',
                'value' => @$form['begin_time'],
                'size' => '10',
                'onChange_noEscape' => 'calcWorkTime()',
                'ime' => 'off',
                'helpText_noEscape' => _g('製造を開始した時刻を入力します。「10:30」のような書式で入力してください。作業管理を行わない場合、入力しなくてもかまいません。'),
            ),
            array(
                'label' => _g('製造終了時刻'),
                'type' => 'textbox',
                'name' => 'end_time',
                'value' => @$form['end_time'],
                'size' => '10',
                'onChange_noEscape' => 'calcWorkTime()',
                'ime' => 'off',
                'helpText_noEscape' => _g('製造を終了した時刻を入力します。「10:30」のような書式で入力してください。作業管理を行わない場合、入力しなくてもかまいません。'),
            ),
            array(
                'label' => _g('休憩時間(分)'),
                'type' => 'textbox',
                'name' => 'break_minute',
                'value' => @$form['break_minute'],
                'size' => '8',
                'onChange_noEscape' => 'calcWorkTime()',
                'ime' => 'off',
                'helpText_noEscape' => _g('休憩時間を分単位で登録します。') . '<br>' .
                        _g('「製造開始時刻」「終了時刻」から「製造時間（分）」を計算する際、この時間が差し引かれます。'),
            ),
            array(
                'label' => _g('製造時間（分）'),
                'type' => 'textbox',
                'name' => 'work_minute',
                'value' => @$form['work_minute'],
                'size' => '8',
                'onChange_noEscape' => 'calcEndTime()',
                'ime' => 'off',
                'helpText_noEscape' => _g('製造に要した時間を分単位で登録します。') . '<br>' .
                        _g('「製造開始時刻」「終了時刻」を入力すれば自動計算されます。開始時刻・終了時刻を入力せず、製造時間（分）だけを登録することもできます。') . '<br>' .
                        _g('ここで登録した数値は、原価リストの製造原価計算や、分析系の資料作成のために使用されます。' .
                        'この数値に品目マスタの工賃をかけた金額が製造原価になります。原価リストを利用しない場合、この数値を' .
                        '登録しなくてもかまいません。'),
            ),
            array(
                'label' => _g('品目コード'),
                'type' => 'textbox',
                'name' => 'item_code',
                'value' => @$form['item_code'],
                'size' => '15',
                'readonly' => true
            ),
            array(
                'label' => _g('品目名'),
                'type' => 'textbox',
                'name' => 'item_name',
                'value' => @$form['item_name'],
                'size' => '20',
                'readonly' => true
            ),
            array(
                'label' => _g('製番'),
                'type' => 'textbox',
                'name' => 'order_seiban',
                'value' => @$form['order_seiban'],
                'size' => '10',
                'readonly' => true,
            ),
            array(
                'label' => _g('計画数'),
                'type' => 'textbox',
                'name' => 'plan_quantity', // 非DB項目
                'value' => @$form['plan_quantity'],
                'size' => '8',
                'readonly' => true, // readonly。値はPOSTされることに注意
                'helpText_noEscape' => _g('前工程の製造数量が表示されます。前工程が存在しない場合は製造指示書の製造数量です。') . '<br>'
                ._g('「前工程」の定義については、製造数量の項目のチップヘルプを参照してください。'),
            ),
            array(
                'label' => _g('製造経費1'),
                'type' => 'textbox',
                'name' => 'cost_1',
                'value' => @$form['cost_1'],
                'size' => '8',
                'ime' => 'off',
                'helpText_noEscape' => _g("製造にかかった経費を登録します。製番品目の場合、原価リストに「製造経費」として反映され、原価に加算されます。MRP品目の場合は原価に反映されません。"),
            ),
            array(
                'label' => _g('製造残'),
                'type' => 'textbox',
                'name' => 'remained_quantity', // 非DB項目
                'value' => @$form['remained_quantity'],
                'size' => '8',
                'readonly' => true, // readonly。値はPOSTされることに注意
                'helpText_noEscape' => _g('計画数から、完成数（最終工程の製造済数）を引いた数が表示されます。'),
            ),
            array(
                'label' => _g('製造経費2'),
                'type' => 'textbox',
                'name' => 'cost_2',
                'value' => @$form['cost_2'],
                'size' => '8',
                'ime' => 'off',
            ),
            array(
                'label' => _g('実績備考'),
                'type' => 'textbox',
                'name' => 'remarks',
                'value' => @$form['remarks'],
                'ime' => 'on',
                'size' => '25'
            ),
            array(
                'label' => _g('製造経費3'),
                'type' => 'textbox',
                'name' => 'cost_3',
                'value' => @$form['cost_3'],
                'size' => '8',
                'ime' => 'off',
            ),
            array(
                'type' => 'literal',
                'denyMove' => true,
            ),
            array(
                'type' => 'literal',
                'denyMove' => true,
            ),
            array(
                'type' => 'literal',
                'denyMove' => true,
            ),
            array(
                'label' => _g('工程'),
                'type' => 'select',
                'name' => 'process_id',
                // 選択肢はオーダーが選ばれたときJavaSciptで再設定する
                'options' => array('0' => _g('標準工程')),
                'onChange_noEscape' => "onProcessChange(true)", // 工程ごとの残数を製造数デフォルトとして設定、など
                'selected' => @$form['process_id'],
                // 15iでは修正モードでの工程の変更を禁止した。子品目使用数の部分がややこしくなりそうなので。
                'readonly' => (isset($form['order_detail_id']) && !($form['order_detail_id'] == '')),
                'helpText_noEscape' => _g('外製工程は表示されません。外製工程は外製受入登録画面で登録してください。'),
            ),
            array(
                'label' => _g('設備'),
                'type' => 'select',
                'name' => 'equip_id',
                'options' => $option_equip,
                'selected' => @$form['equip_id'],
            ),
            array(
                'label' => _g('作業者'),
                'type' => 'dropdown',
                'dropdownCategory' => 'worker',
                'name' => 'worker_id',
                'value' => @$form['worker_id'],
                'onChange_noEscape' => 'onWorkerChange()',
                'size' => '12',
            ),
            array(
                'label' => _g('部門'),
                'type' => 'select',
                'name' => 'section_id',
                'options' => $option_section,
                'selected' => @$form['section_id'],
            ),
        );
        for ($i = 1; $i <= GEN_WASTER_COUNT; $i++) {
            $form['gen_editControlArray'][] = array(
                'label' => sprintf(_g('不適合理由%d'), $i),
                'type' => 'select',
                'name' => "waster_id_{$i}",
                'options' => $option_waster,
                'selected' => @$form["waster_id_{$i}"],
            );
            $form['gen_editControlArray'][] = array(
                'label' => sprintf(_g('不適合数%d'), $i),
                'type' => 'textbox',
                'name' => "waster_quantity_{$i}",
                'value' => @$form["waster_quantity_{$i}"],
                'onChange_noEscape' => 'onWasterQtyChange()',
                'ime' => 'off',
                'size' => '5'
            );
        }
        $form['gen_editControlArray'][] = array(
            'label_noEscape' => _g('ロケーション') . '<br>' . _g('(使用部材出庫)'),
            'type' => 'select',
            'name' => 'child_location_id',
            'options' => $option_child_location_group,
            'selected' => @$form['child_location_id'],
            'helpText_noEscape' => _g('製造に使用した子品目を出庫したロケーションを指定します。「(各部材の標準ロケ)」を指定すると、各部材の標準ロケーション（品目マスタ「標準ロケーション（使用）」）が出庫ロケーションになります。'),
        );
        // 完了フラグ。ag.cgi?page=ProjectDocView&pid=1574&did=208745
        // 最終工程の場合はオーダー完了、それ以外の場合は工程完了となる。
        // ここでオンにしなくても、計画数 >= 受入数 ならDB登録時に自動的にオンになる。
        // 計画数未達でも完了とみなしたいときは手動でオンにする。
        $form['gen_editControlArray'][] = array(
            'label' => _g('完了'),
            'type' => 'checkbox',
            'name' => 'order_detail_completed',
            'onvalue' => 'true', // trueのときの値。デフォルト値ではない
            'value' => @$form['order_detail_completed'],
            'helpText_noEscape' => _g('このチェックをオンにすると、製造数が計画数に満たなくても、製造が完了したものとみなされます。') . '<br>' .
                    _g('製造数が計画数を上回れば登録時に自動的にオンになります。') . '<br><br>' .
                    _g('●最終工程以外の場合') . '<br>' .
                    _g('工程が完了となります。（オーダーは完了になりません。）') . '<br>' .
                    _g('工程完了にしても在庫数や使用予定数への影響はありません。工程完了にすると、次回このオーダーの実績を登録する時にデフォルトで次工程が表示されるようになります。') .
                    _g('また、この工程での製造数が次工程でのデフォルト製造数として表示されるようになります。') . '<br><br>' .
                    _g('●最終工程の場合') . '<br>' .
                    _g('オーダーが完了となります。') . '<br>' .
                    _g('この時点ではじめて在庫数が変動します（製造品目の在庫が増え、使用子品目の在庫が減ります）。') . '<br><br>' .
                    _g('ちなみに「最終工程」とは、品目マスタの工程タブで最後（一番下）に登録された工程を指します。製造実績が最後に登録された工程、ということではありません。'),
        );
        $form['gen_editControlArray'][] = array(
            'type' => 'literal',
            'denyMove' => true,
        );
        $form['gen_editControlArray'][] = array(
            'type' => 'literal',
            'denyMove' => true,
        );
        $form['gen_editControlArray'][] = array(
            'label' => _g("最終工程のみ"),
            'type' => 'section',
            'denyMove' => true,
        );
        $form['gen_editControlArray'][] = array(
            'type' => 'literal',
            'denyMove' => true,
        );
        $form['gen_editControlArray'][] = array(
            'label' => _g('ロット番号'),
            'type' => 'textbox',
            'name' => 'lot_no',
            'value' => @$form['lot_no'],
            'size' => '12',
            'helpText_noEscape' => _g('最終工程のみ有効です。ロット管理を行う場合に、製造ロット番号を入力します。ロット管理を必要としない場合は、入力の必要はありません。') . '<br><br>'
            . '●' . _g('ロット品目（品目マスタ「管理区分」が「ロット」の品目）の場合') . '<br><br>'
            . _g('この項目を空欄にすると、品目マスタの「ロット頭文字」+通番がロット番号として自動設定されます。手入力することもできます。') . '<br><br>'
            . _g('製造実績ごとに在庫が分かれ、それぞれの在庫にここで指定したロット番号がつきます。') . '<br><br>'
            . _g('受注画面でロット引当処理を行うことにより、受注と製造ロットを結びつけることができます。') . '<br><br>'
            . '●' . _g('MRP/製番品目の場合') . '<br><br>'
            . _g('ロット番号が自動設定されることはありませんが、手入力することは可能です。') . '<br><br>'
            . _g('ロットごとに在庫が分かれることはありません。') . '<br><br>'
            . _g('この番号を納品登録画面や親品目の製造実績登録画面の「ロット番号」に入力することで、納品や製造と製造ロットを結びつけることができ、トレーサビリティを実現できます。')
        );
        $form['gen_editControlArray'][] = array(
            'label' => _g('使用ロット番号'),
            'type' => 'textbox',
            'name' => 'use_lot_no',
            'value' => @$form['use_lot_no'],
            'size' => '20',
            'helpText_noEscape' => _g('最終工程のみ有効です。製造に使用した子品目の製造・購買ロット番号を入力します。複数の子品目ロットがある場合はカンマ区切りで入力してください。') . '<br><br>' .
            _g('「製造・購買ロット番号」とは、内製品であれば製造実績画面の製造ロット番号、発注品であれば受入画面の購買ロット番号を指します。この登録により、製造に使用した部材・原料のロットを調べることができるようになり、トレーサビリティを実現できます。') . '<br><br>' .
            _g('ロット管理（トレーサビリティ）を必要としない場合は、入力の必要はありません。')
        );
        $form['gen_editControlArray'][] =
        array(
            'label'=>_g('消費期限'),
            'type'=>'calendar',
            'name'=>'use_by',
            'value'=>@$form['use_by'],
            'size'=>'8',
            'helpText_noEscape' => _g('最終工程のみ有効です。ロット品目において、製造ロットの消費期限管理を行いたい場合に入力します。') . '<br><br>' .
            _g('品目マスタ「消費期限日数」が設定されている場合、この項目を空欄にすると、製造日 + 消費期限日数 が自動的に設定されます。')
        );
        $form['gen_editControlArray'][] = array(
            'label_noEscape' => _g('ロケーション') . '<br>' . _g('(完成品入庫)'),
            'type' => 'select',
            'name' => 'location_id',
            'options' => $option_location_group,
            'selected' => @$form['location_id'],
            'helpText_noEscape' => _g('製造した品目を入庫したロケーションを指定します。オーダー番号を指定すると、自動的にデフォルト値が設定されます（品目マスタ「標準ロケーション（完成）」）。'),
        );
    }

}
