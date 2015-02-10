<?php

class Mobile_Received_Summary extends Base_MobileListBase
{
    var $from;
    var $to;
    var $keyName;

    function setSearchCondition(&$form)
    {
        global $gen_db;
        // 検索条件
        $query = "select item_group_id, item_group_name from item_group_master order by item_group_code";
        $option_item_group = $gen_db->getHtmlOptionArray($query, false, array(null=>_g("(すべて)")));

        $form['gen_searchControlArray'] =
            array(
                array(
                    'label'=>_g('集計対象'),
                    'type'=>'select',
                    'field'=>'key',
                    'options'=>array("0"=>_g("得意先"), "1"=>_g("品目"), "2"=>_g("部門")),
                    'nosql'=>'true',
                    'default'=>'0',
                ),
                array(
                    'label'=>_g('期間'),
                    'type'=>'select',
                    'field'=>'period',
                    'options'=>array("0"=>_g("今月"), "1"=>_g("先月"), "2"=>_g("過去1ヶ月"), "3"=>_g("今年"), "4"=>_g("昨年"), "5"=>_g("過去1年")),
                    'nosql'=>'true',
                    'default'=>'0',
                ),
                array(
                    'label'=>_g('得意先'),
                    'type'=>'textbox',
                    'field'=>'customer_no',
                    'field2'=>'customer_name',
                ),
                array(
                    'label'=>_g('品目'),
                    'type'=>'textbox',
                    'field'=>'item_code',
                    'field2'=>'item_name',
                ),
                array(
                    'label'=>_g('品目グループ'),
                    'type'=>'select',
                    'field'=>'item_group_id',
                    'options'=>$option_item_group,
                ),
                );
    }

    function convertSearchCondition($converter, &$form)
    {
    }

    function beforeLogic(&$form)
    {
    }

    function setQueryParam(&$form)
    {
        global $gen_db;

        // 期間
        switch(@$form['gen_search_period']) {
            case '1':   // 先月
                $from = date('Y-m-1', strtotime(date('Y-m-1') . ' -1 month'));
                $to = date('Y-m-t', strtotime(date('Y-m-1') . ' -1 month'));
                break;
            case '2':   // 過去1ヶ月
                $from = date('Y-m-d', strtotime('-1 month +1 day'));
                $to = date('Y-m-d');
                break;
            case '3':   // 今年
                $from = date('Y-1-1');
                $to = date('Y-12-31');
                break;
            case '4':   // 昨年
                $from = date('Y-1-1', strtotime('-1 year'));
                $to = date('Y-12-31', strtotime('-1 year'));
                break;
            case '5':   // 過去1年
                $from = date('Y-m-d', strtotime('-1 year +1 day'));
                $to = date('Y-m-d');
                break;
            default:   // 今月
                $from = date('Y-m-1');
                $to = date('Y-m-t');
                break;
        }

        $this->from = $from;
        $this->to = $to;

        // 集計
        switch (@$form['gen_search_key']) {
            case "1":
                $this->keyName = _g("品目名");
                $keyColumn = "received_detail.item_id";
                $selectColumn = "item_name";
                break;
            case "2":
                $this->keyName = _g("部門名");
                $keyColumn = "received_header.section_id";
                $selectColumn = "section_name";
                break;
            default:
                $this->keyName = _g("得意先名");
                $keyColumn = "received_header.customer_id";
                $selectColumn = "customer_name";
                break;
        }

        $this->selectQuery = "
            select
                max({$selectColumn}) as key
                ,sum(received_quantity * product_price) as amount
            from
                received_header
                inner join received_detail on received_header.received_header_id = received_detail.received_header_id
                left join customer_master on received_header.customer_id = customer_master.customer_id
                left join item_master on received_detail.item_id = item_master.item_id
                left join section_master on received_header.section_id = section_master.section_id
            [Where]
                and received_date between '{$from}' and '{$to}'
                -- 確定のみ
                and coalesce(guarantee_grade,0)=0
            group by
                {$keyColumn}
            [Orderby]
            ";

        $this->orderbyDefault = 'amount desc';
    }

    function setViewParam(&$form)
    {
        global $gen_db;

        $this->tpl = "mobile/list.tpl";

        $form['gen_pageTitle'] = _g("受注ランキング");
        $form['gen_listAction'] = "Mobile_Received_Summary";
        $form['gen_idField'] = "key";

        $form['gen_headerLeftButtonURL'] = "index.php?action=Mobile_Home";
        $form['gen_headerLeftButtonIcon'] = "arrow-l";
        $form['gen_headerLeftButtonText'] = _g("戻る");

        $form['gen_message_noEscape'] = sprintf(_g("%1\$s から %2\$s"), h($this->from), h($this->to));
        $form['gen_sumColumnArray'] = array("受注金額："=>"amount");

        $keyCurrency = $gen_db->queryOneValue("select key_currency from company_master");

        $form['gen_columnArray'] =
            array(
                array(
                    'sortLabel'=>$this->keyName,
                    'label'=>'',
                    'field'=>'key',
                    'fontSize'=>12,
                    'after'=>'<br>',
                ),
                array(
                    'sortLabel'=>_g("受注金額"),
                    'label'=>$keyCurrency,
                    'field'=>'amount',
                    'type'=>'numeric',  // aggregateのために必要
                    'labelFontSize'=>12,
                    'fontSize'=>14,
                    'numberFormat'=>true,
                    'after'=>'<br>',
                ),
            );
    }
}