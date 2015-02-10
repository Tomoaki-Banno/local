<?php

require_once("Model.class.php");

class Manufacturing_CustomerEdi_Edit extends Base_EditBase
{

    function convert($converter, &$form)
    {
        $converter->nullBlankToValue('received_date', date("Y-m-d"));
    }

    // 既存データSQL実行前に処理
    function setQueryParam(&$form)
    {
        global $gen_db;

        $this->keyColumn = 'received_header_id';

        $keyCurrency = $gen_db->queryOneValue("select key_currency from company_master");
        $this->selectQuery = "
            select
                received_header.*
                ,customer_master.price_percent
                ,case when currency_name is null then '{$keyCurrency}' else currency_name end as currency_name
                ,coalesce(received_header.record_update_date, received_header.record_create_date) as gen_last_update
                ,coalesce(received_header.record_updater, received_header.record_creator) as gen_last_updater
            from
                received_header
                left join customer_master on received_header.customer_id = customer_master.customer_id
                left join currency_master on customer_master.currency_id = currency_master.currency_id
            [Where]
                and received_header.customer_id = '{$_SESSION["user_customer_id"]}'
        ";

        // データロックの判断基準となるフィールドを指定
        $form["gen_salesDateLockFieldArray"] = array("received_date");
    }

    // 既存データSQL実行後に処理
    // 画面表示のための設定
    function setViewParam(&$form)
    {
        global $gen_db;

        $this->modelName = "Manufacturing_CustomerEdi_Model";

        $form['gen_pageTitle'] = _g("発注登録");
        $form['gen_entryAction'] = "Manufacturing_CustomerEdi_Entry";
        $form['gen_listAction'] = "Manufacturing_CustomerEdi_List";
        $form['gen_onLoad_noEscape'] = "onLoad()";
        $form['gen_beforeEntryScript_noEscape'] = "beforeEntry()";
        $form['gen_last_update'] = "";  // 最終更新非表示
        $form['gen_last_updater'] = "";  // 入力者非表示

        $isReload = (isset($form['gen_validError']) && $form['gen_validError']) || isset($form['gen_editReload']);

        $form['gen_javascript_noEscape'] = "
            var isPageDataModified = false;	// データ（品目）が変更されたかどうかのフラグ

            // ページロード
            function onLoad() {
                " . ($isReload ? "
                // リロード（新規登録モードでのバリデーションエラーによる差し戻し、および項目変更・並べ替え・リセット・明細行数変更）のときのみ、
                // 各行の品目名や引当可能数等を再取得する。
                // ちなみに通常時はSQLによってそれらが取得されるのでこれを実行する必要はない。
                // また、EditListに関しては修正モードのエラー時にもSQL取得される。
                $('[id^=line_no]').each(function(){
                    lineNo = this.innerHTML;
                    onItemIdChange(lineNo, true);
                });
                " : "") . "

                calcTotalAmount();
                setCustomerData();
            }

            // 取引先の情報設定
            function setCustomerData() {
                var customerId = '" . h($_SESSION["user_customer_id"]) . "';
                if (!gen.util.isNumeric(customerId)) {
                    $('#currency_name').val('');
                    return;
                }
                var p = {
                    " . (isset($form['received_header_id']) ? "receivedHeaderId : " . h($form['received_header_id']) : "") . "
                };
                gen.ajax.connect('Manufacturing_CustomerEdi_AjaxCustomerParam', p, 
                    function(j) {
                        $('#currency_name').val(j.currency_name);
                    });
            }

            // 品目が変わったらAjaxで品目情報や各種デフォルト値を取得して表示する。
            function onItemIdChange(lineNo, isReloadInit) {
                if (!gen.util.isNumeric(itemId = $('#item_id_'+lineNo).val())) {
                    gen.edit.editListClearLine('list1',lineNo,true);
                    return;
                }

                if (!isReloadInit) isPageDataModified = true;

                var p = {
                    itemId : itemId
                    ,qty : $('#received_quantity_'+lineNo).val()
                };
                var id = $('#received_detail_id_'+lineNo).val();
                if (id != undefined && id != '')
                    p.received_detail_id = id;

                gen.ajax.connect('Manufacturing_CustomerEdi_AjaxItemParam', p, 
                    function(j) {
                        if (j.status=='success') {
                            $('#item_name_'+lineNo).text(j.item_name);
                            $('#product_price_'+lineNo).text(j.selling_price);
                            $('#tax_class_'+lineNo).text(j.tax_class);
                            $('#measure_'+lineNo).text(j.measure);
                            onQtyChagne(lineNo);     // 単価は設定済みなので更新しない
                        } else {
                            alert('" . _g("指定された品目は登録されていません。") . "');
                        }
                    });

                // 希望納期デフォルト（1行目は明日、2行目以降は上の行の希望納期をコピー）
                if ($('#dead_line_'+lineNo).val()=='') {
                    var dl = gen.date.getDateStr(gen.date.calcDate(new Date(),1));  // 明日
                    if (lineNo>1) {
                        dl = $('#dead_line_'+(lineNo-1)).val();
                    }
                    $('#dead_line_'+lineNo).val(dl);
                }
            }

            // 発注数変更イベント
            function onQtyChagne(lineNo) {
                var qty = $('#received_quantity_'+lineNo).val();
                if (!gen.util.isNumeric(qty)) return;
                var price = $('#product_price_'+lineNo).html();
                if (!gen.util.isNumeric(price)) return;

                 // 発注金額・販売粗利の計算（各明細行）
                if (gen.util.isNumeric(qty) && gen.util.isNumeric(price)) {
                    $('#amount_'+lineNo).html(gen.util.addFigure(gen.util.decCalc(qty,price,'*')));
                } else {
                    $('#amount_'+lineNo).html('');
                }
                calcTotalAmount();
            }

            // 発注合計金額の計算
            function calcTotalAmount() {
               var total = 0;
               $('[id^=amount_]').each(function(){
                    var amount = gen.util.delFigure(this.innerHTML);
                    if (gen.util.isNumeric(amount)) {
                        total = gen.util.decCalc(total, amount, '+');
                    }
               });
               $('#total_amount').val(gen.util.addFigure(total));
            }

            // 登録前処理
            function beforeEntry() {
                // EditListのすべての行に対する処理（idがline_noで始まるすべてのエレメント、つまり行番号divをたどる）
                var str = '';
                $('[id^=line_no]').each(function(){
                    lineNo = this.innerHTML;

                    var item_id = $('#item_id_'+lineNo).val();
                    var deadline = $('#dead_line_'+lineNo).val();
                    if (item_id != '' || deadline != '') {
                        if (item_id == '') {     // 本当はほかの項目もチェックすべき
                            alert(lineNo+'" . _g("行目： 品目を指定してください。") . "'); str = ''; return false;
                        }
                        if (deadline == '') {
                            alert(lineNo+'" . _g("行目： 希望納期を指定してください。") . "'); str = ''; return false;
                        }
                        if (str!='') str += ',';
                        str += lineNo + ':' + deadline;
                    }
                });

                if (str == '') {
                    alert('" . _g("登録するデータがありません。") . "');
                    return;
                }

                // 休業日チェック
                gen.ajax.connect('Manufacturing_CustomerEdi_AjaxDeadlineCheck', {deadline : str}, 
                    function(j) {
                       if (j.result == 'incorrect') {
                           alert(j.lineNo+'" . _g("行目： 希望納期が正しくありません。") . "'); 
                           return;
                       } else if (j.result == 'holiday') {
                           if (!window.confirm('" . _g("指定された希望納期に休業日が含まれていますが、このまま登録してもよろしいですか？") . "')) {
                                return;
                           }
                       }
                       document.forms[0].submit();
                    });
            }
        ";

        $existDelivery = false;
        if (isset($form['received_header_id'])) {
            $existDelivery = Logic_Delivery::hasDeliveryByReceivedHeaderId($form['received_header_id']);
            if ($existDelivery) {
                $form['gen_message_noEscape'] = "<font color='blue'>" . _g("この発注は出荷済みです（もしくは一部出荷済みです）。ヘッダ項目、および出荷済み行は修正できません。") . "</font><br>";
            }
        }

        $keyCurrency = $gen_db->queryOneValue("select key_currency from company_master");
        $form['gen_editControlArray'] = array(
            array(
                'label' => _g('発注番号'),
                'type' => 'textbox',
                'name' => 'received_number',
                'value' => @$form['received_number'],
                'size' => '12',
                'readonly' => true,
                'hidePin' => true,
                'helpText_noEscape' => _g('自動採番されますので、指定する必要はありません。')
            ),
            array(
                'label' => _g('自社注番'),
                'type' => 'textbox',
                'name' => 'customer_received_number',
                'value' => @$form['customer_received_number'],
                'ime' => 'off',
                'size' => '12',
                'hideHistory' => true,  // 履歴表示は不可。他社が登録した内容まで見えてしまうので。
                'readonly' => $existDelivery,
            ),
            // 与信限度額・売掛残高表示は廃止
            // ag.cgi?page=ProjectDocView&ppid=1574&pbid=204238
            array(
                'label' => _g("取引通貨"),
                'type' => 'textbox',
                'name' => "currency_name",
                'value' => '',
                'size' => '5',
                'readonly' => 'true',
            ),
            array(
                'label' => _g('合計金額'),
                'type' => 'textbox',
                'name' => 'total_amount',
                'value' => '',
                'size' => '12',
                'style' => 'text-align:right',
                'readonly' => true,
            ),
            array(
                'label' => _g('発注日'),
                'type' => 'calendar',
                'name' => 'received_date',
                'value' => @$form['received_date'],
                'ime' => 'off',
                'size' => '8',
                'readonly' => $existDelivery,
                'require' => true,
            ),
            array(
                'type' => 'literal',
            ),
            // ********** List **********
            array(
                'type' => "list",
                'listId' => 'list1', // リストのID。ページ内に複数リストがある場合、ユニークになるようにすること
                'rowCount' => 2, // 1セルに格納するコントロールの数（1セルの行数）
                'keyColumn' => 'received_detail_id', // 明細行のキーとなるカラム
                'readonlyCondition' => (isset($form['gen_record_copy']) ? "" : "('[delivery_quantity]'>0 || ('[delivery_quantity]'==0 && '[delivery_completed]'=='t'))"), // list内だけで使えるプロパティ
                'query' => // Listデータを取得するSQL。 EditBaseで実行され、結果配列が'data'という名前で格納される
                isset($form['received_header_id']) ? "
                        select
                            received_detail.*
                            ,use_plan_qty as reserve_quantity
                            ,item_master.item_name as item_name
                            ,item_master.default_selling_price
                            ,item_master.order_class
                            ,item_master.measure
                            " . (isset($form['gen_record_copy']) ? ",null as seiban" : "") . "
                            " . (isset($form['gen_record_copy']) ? ",''" : ",delivery_quantity") . " as delivery_quantity
                            " . (isset($form['gen_record_copy']) ? ",''" : ",case when delivery_completed then '" . _g("完") . "' else
                                '" . _g("未(残") . " ' || (COALESCE(received_quantity,0) - COALESCE(delivery_quantity,0)) || ')' end") . " as delivery
                            ,received_quantity - coalesce(use_plan_qty,0) as manufacturing_quantity
                            ,case tax_class when 1 then '" . _g("非課税") . "' else '" . _g("課税") . "' end as tax_class
                            ,case when foreign_currency_id is null then product_price else foreign_currency_product_price end as product_price
                            ,case when foreign_currency_id is null then product_price else foreign_currency_product_price end * received_quantity as amount
                            ,case when foreign_currency_id is null then sales_base_cost else foreign_currency_sales_base_cost end as sales_base_cost
                            ,(case when foreign_currency_id is null then product_price else foreign_currency_product_price end
                                - coalesce(case when foreign_currency_id is null then sales_base_cost else foreign_currency_sales_base_cost end,0))
                                * received_quantity as sales_gross_margin
                        from
                            received_detail
                            left join (select received_detail_id, sum(quantity) as use_plan_qty from use_plan group by received_detail_id) as t_use on received_detail.received_detail_id = t_use.received_detail_id
                            left join item_master on received_detail.item_id = item_master.item_id
                            /* 納品済み数 */
                            left join (
                                select
                                    received_detail_id
                                    ,SUM(delivery_quantity) as delivery_quantity
                                from
                                    delivery_detail
                                    inner join delivery_header on delivery_detail.delivery_header_id = delivery_header.delivery_header_id
                                group by
                                    received_detail_id
                                ) as t_delivery on received_detail.received_detail_id = t_delivery.received_detail_id
                        where
                            received_header_id = '{$form['received_header_id']}'
                        order by
                            line_no
                        " : "",
                'controls' => array(
                    array(
                        'label' => _g('品目コード'),
                        'type' => 'dropdown',
                        'name' => 'item_id',
                        'size' => '18',
                        'dropdownCategory' => 'item_customer_user',
                        'onChange_noEscape' => "onItemIdChange([gen_line_no]);",
                        'placeholder' => _g('品目コード'),
                        'require' => true,
                    ),
                    array(
                        'label' => _g('品目名'),
                        'type' => 'div',
                        'name' => 'item_name',
                        'size' => '20',
                    ),
                    array(
                        'label' => _g('数量'),
                        'type' => 'textbox',
                        'name' => 'received_quantity',
                        'ime' => 'off',
                        'size' => '7',
                        'style' => "text-align:right",
                        'require' => true,
                        'onChange_noEscape' => 'onQtyChagne([gen_line_no])',
                    ),
                    array(
                        'label' => _g('単位'),
                        'type' => 'div',
                        'name' => 'measure',
                        'style' => "text-align:right",
                        'size' => '7',
                        'helpText_noEscape' => _g('品目マスタ「管理単位」です。')
                    ),
                    array(
                        'label' => _g('単価'),
                        'type' => 'div',
                        'name' => 'product_price',
                        'style' => "text-align:right",
                        'size' => '6',
                    ),
                    array(
                        'label' => "",
                        'type' => 'literal',
                    ),
                    array(
                        'label' => _g('金額'),
                        'type' => 'div',
                        'name' => 'amount',
                        'size' => '8',
                        'numberFormat' => '', // 桁区切り
                        'style' => "text-align:right",
                        'helpText_noEscape' => _g('「発注数  × 発注単価」で計算されます。'),
                    ),
                    array(
                        'label' => _g('課税区分'),
                        'type' => 'div',
                        'name' => 'tax_class',
                        'size' => '8',
                        'style' => "text-align:center;vertical-align:bottom",
                        'helpText_noEscape' => sprintf(_g('品目マスタ「課税区分」（課税/非課税）です。なお、取引通貨が%s以外の場合は常に非課税扱いとなります。'), h($keyCurrency)),
                    ),
                    array(
                        'label' => _g('希望納期'),
                        'type' => 'calendar',
                        'name' => 'dead_line',
                        'size' => '8',
                        'require' => true,
                        'isCalendar' => true,
                        'hideSubButton' => true,
                    ),
                    array(
                        'label' => "",
                        'type' => 'literal',
                    ),
                    array(
                        'label' => _g('発注登録備考'),
                        'type' => 'textbox',
                        'name' => 'remarks',
                        'ime' => 'on',
                        'size' => '20',
                    ),
                    array(
                        'label' => _g('出荷状況'),
                        'type' => 'div',
                        'name' => 'delivery',
                        'style' => 'text-align:left',
                        'size' => '20',
                    ),
                ),
            ),
        );
    }

}
