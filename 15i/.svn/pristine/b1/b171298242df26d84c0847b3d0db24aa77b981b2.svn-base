<?php

require_once("Model.class.php");

class Master_CustomerPrice_Edit extends Base_EditBase
{

    function setQueryParam(&$form)
    {
        $this->keyColumn = 'customer_price_id';
        $this->selectQuery = "
            select
                *
                ,coalesce(record_update_date, record_create_date) as gen_last_update
                ,coalesce(record_updater, record_creator) as gen_last_updater
            from
                customer_price_master
            [Where]
                -- for excel
            order by
                customer_id
                ,item_id
        ";
    }

    function setViewParam(&$form)
    {
        $this->modelName = "Master_CustomerPrice_Model";

        $form['gen_pageTitle'] = _g('得意先販売価格マスタ');
        $form['gen_entryAction'] = "Master_CustomerPrice_Entry";
        $form['gen_listAction'] = "Master_CustomerPrice_List";
        $form['gen_onLoad_noEscape'] = "onCustomerIdChange()";
        $form['gen_pageHelp'] = _g("販売価格");

        $form['gen_javascript_noEscape'] = "
            function onCustomerIdChange() {
                var oui = $('#customer_id').val();
                if (oui!='' && oui!='0') {
                    // 品目マスタのクラスを流用
                    gen.ajax.connect('Master_Item_AjaxLeadTime', {partner_id : oui}, 
                        function(j) {
                            $('#currency_name').val(j.currency_name);
                        });	
                } else {
                    $('#currency_name').val('');
                }
            }
        ";

        $form['gen_editControlArray'] = array(
            array(
                'label' => _g('得意先'),
                'type' => 'dropdown',
                'name' => 'customer_id',
                'value' => @$form['customer_id'],
                'size' => '12',
                'subSize' => '20',
                'dropdownCategory' => 'customer',
                'onChange_noEscape' => "onCustomerIdChange()",
                'require' => true,
            ),
            array(
                'label' => _g('品目'),
                'type' => 'dropdown',
                'name' => 'item_id',
                'value' => @$form['item_id'],
                'dropdownCategory' => 'item_received',
                'require' => true,
                'size' => '12',
                'subSize' => '20',
            ),
            array(
                'label' => _g("販売取引通貨"),
                'type' => 'textbox',
                'name' => "currency_name",
                'value' => '',
                'size' => '5',
                'readonly' => 'true',
                'helpText_noEscape' => _g("取引先マスタで設定した取引通貨が表示されます。「販売価格」はこの取引通貨で設定してください。"),
            ),
            array(
                'label' => _g('販売価格'),
                'type' => 'textbox',
                'name' => 'selling_price',
                'value' => @$form['selling_price'],
                'require' => true,
                'ime' => 'off',
                'size' => '8',
            ),
        );
    }

}
