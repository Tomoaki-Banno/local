<?php

require_once("Model.class.php");

class Master_AlertMail_Edit extends Base_EditBase
{

    var $alertArr;

    function setQueryParam(&$form)
    {
        // 通知カテゴリ
        $this->alertArr = array(
            'login_success' => _g("ログイン成功")
            , 'login_fail' => _g("ログイン失敗")
            , 'manufacturing_received_new' => _g("受注の新規登録")
            , 'manufacturing_received_edit' => _g("受注の修正")
            , 'delivery_delivery_new' => _g("納品の新規登録")
            , 'delivery_delivery_edit' => _g("納品の修正")
            , 'monthly_bill_new' => _g("請求書の新規発行")
            , 'monthly_bill_reprint' => _g("請求書の再印刷")
            , 'delivery_payingin_new' => _g("入金の新規登録")
            , 'delivery_payingin_edit' => _g("入金の修正")
            , 'manufacturing_estimate_new' => _g("見積の新規登録")
            , 'manufacturing_estimate_edit' => _g("見積の修正")
            , 'manufacturing_mrp_start' => _g("所要量計算の開始")
            , 'manufacturing_mrp_end' => _g("所要量計算の終了")
            , 'partner_order_new' => _g("注文書の新規登録")
            , 'partner_order_edit' => _g("注文書の修正")
            , 'partner_accepted_new' => _g("注文受入の新規登録")
            , 'partner_accepted_edit' => _g("注文受入の修正")
            , 'item_master_new' => _g("品目マスタの新規登録")
            , 'item_master_edit' => _g("品目マスタの修正")
            , 'stock_assesment_update' => _g("在庫評価単価の更新")
            , 'config_datadelete_delete' => _g("過去データの削除")
            , 'config_backup_backup' => _g("バックアップ")
            , 'config_restore_restore' => _g("バックアップの読み込み")
        );

        $queryCore = "";
        foreach ($this->alertArr as $key => $val) {
            $queryCore .= ",max(case when alert_id = '{$key}' then 'true' end) as alert_{$key}";
        }

        $this->keyColumn = 'mail_address_id';

        $this->selectQuery = "
            select
                mail_address_id
                ,max(mail_address) as mail_address
                {$queryCore}
            from
                mail_address_master
                left join (select mail_address_id as maid, alert_id from alert_mail_master) as t_alert
                    on mail_address_master.mail_address_id = t_alert.maid
            [Where]
            group by
                mail_address_id
        ";
    }

    function setViewParam(&$form)
    {
        $this->modelName = "Master_AlertMail_Model";

        $form['gen_pageTitle'] = _g('通知メールの設定');
        $form['gen_entryAction'] = "Master_AlertMail_Entry";
        $form['gen_listAction'] = "Master_AlertMail_List";
        $form['gen_onLoad_noEscape'] = "onLoad()";

        if (@$form['mail_address2'] == "")
            $form['mail_address2'] = @$form['mail_address'];
        if (@$form['mail_address2'] == "error")
            $form['mail_address2'] = "";    // EntryのValidator参照


        // 画面上部のメッセージ
        if (isset($form['mail_address_id'])) {
            // 修正モード
            $form['gen_message_noEscape'] =
                    _g("メールアドレスを変更した場合、そのアドレスはすぐには有効になりません。") . "<br>"
                    . _g("登録と同時にそのアドレスに送られる仮登録メール内のURLをクリックした時点で本登録となり、通知が有効になります。") . "<br><br>"
                    . _g("メールアドレスを変更しない場合は仮登録メールは送られず、すぐに設定が反映されます。") . "<br><br><br>";
        } else {
            // 新規モード
            $form['gen_message_noEscape'] =
                    _g("登録後、設定したメールアドレスに仮登録メールが送られます。") . "<br>"
                    . _g("そのメール内のURLをクリックした時点で本登録となり、通知が有効になります。") . "<br><br><br>";
        }

        $form['gen_javascript_noEscape'] = "
            function onLoad() {
                $('[id^=alert_]').each(function(){
                    $(this).after('<span id=\"checked_msg_'+this.id+'\"></span>');
                    onCheckChange(this.id);
                });
            }

            function onCheckChange(id) {
                var elm = $('#'+id);

                if (elm.is(':checked')) {
                    $('#checked_msg_'+id).css({'font-weight':'bold','color':'red'}).html('" . _g("通知する") . "');
                } else {
                    $('#checked_msg_'+id).css({'font-weight':'normal','color':'gray'}).html('" . _g("通知しない") . "');
                }
            }
        ";

        $form['gen_editControlArray'] = array(
            array(
                'label' => _g('メールアドレス'),
                'type' => 'textbox',
                'name' => 'mail_address',
                'value' => @$form['mail_address'],
                'require' => true,
                'ime' => 'off',
                'size' => '20',
                'hidePin' => true,
            ),
            array(
                'label' => _g('メールアドレスの確認入力'),
                'type' => 'textbox',
                'name' => 'mail_address2',
                'value' => @$form['mail_address2'],
                'ime' => 'off',
                'size' => '20',
                'require' => (isset($form['mail_address_id']) && !isset($form['gen_record_copy']) ? false : true), // 新規の場合のみ必須
                'hidePin' => true,
                'hideHistory' => true,
            ),
            array(
                'type' => 'literal',
                'denyMove' => true,
            ),
            array(
                'type' => 'literal',
                'denyMove' => true,
            ),
        );

        foreach ($this->alertArr as $key => $val) {
            $form['gen_editControlArray'][] = array(
                'label' => $val,
                'type' => 'select',
                'name' => "alert_{$key}",
                'type' => 'checkbox',
                'onvalue' => 'true',
                'value' => @$form["alert_{$key}"],
                'onChange_noEscape' => "onCheckChange('alert_{$key}')",
            );
        }
    }

}