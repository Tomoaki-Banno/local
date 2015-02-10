<?php

class Logic_EditGroup
{
    // Editクラスで、データ添付（添付ファイル・レコードに関連したスレッド）可能なクラスのクラス名とキーカラムのリスト。
    // EditBaseを継承しているクラスで、$this->keyColumn が設定されているクラスはすべてここに書く必要がある。

    // ここに書かれていなくてもEdit画面の動作には問題ないが、添付ファイルや関連スレッドの機能が使用できない。
    // 対象となるクラスを抽出するときは「$this->keyColumn = 」でソース全検索。

    static function getAttachableGroupList()
    {
        return array(
            'Config_PasswordChange' => array('Config_PasswordChange_Edit', 'user_id'),
            'Config_Schedule' => array('Config_Schedule_Edit', 'schedule_id'),
            'Delivery_Delivery' => array('Delivery_Delivery_Edit', 'delivery_header_id'),
            'Delivery_PayingIn' => array('Delivery_PayingIn_Edit', 'paying_in_id'),
            'Manufacturing_Achievement' => array('Manufacturing_Achievement_Edit', 'achievement_id'),
            'Manufacturing_CustomerEdi' => array('Manufacturing_CustomerEdi_Edit', 'received_header_id'),
            'Manufacturing_Estimate' => array('Manufacturing_Estimate_Edit', 'estimate_header_id'),
            'Manufacturing_Order' => array('Manufacturing_Order_Edit', 'order_header_id'),
            'Manufacturing_Plan' => array('Manufacturing_Plan_Edit', 'plan_id'),
            'Manufacturing_Received' => array('Manufacturing_Received_Edit', 'received_header_id'),
            'Master_AlertMail' => array('Master_AlertMail_Edit', 'mail_address_id'),
            'Master_Company' => array('Master_Company_Edit', 'company_id'),
            'Master_Currency' => array('Master_Currency_Edit', 'currency_id'),
            'Master_Customer' => array('Master_Customer_Edit', 'customer_id'),
            'Master_CustomerGroup' => array('Master_CustomerGroup_Edit', 'customer_group_id'),
            'Master_CustomerPrice' => array('Master_CustomerPrice_Edit', 'customer_price_id'),
            'Master_Equip' => array('Master_Equip', 'equip_id'),
            'Master_Item' => array('Master_Item_Edit', 'item_id'),
            'Master_ItemGroup' => array('Master_ItemGroup_Edit', 'item_group_id'),
            'Master_Location' => array('Master_Location_Edit', 'location_id'),
            'Master_PricePercentGroup' => array('Master_PricePercentGroup_Edit', 'price_percent_group_id'),
            'Master_Process' => array('Master_Process_Edit', 'process_id'),
            'Master_Rate' => array('Master_Rate_Edit', 'rate_id'),
            'Master_Section' => array('Master_Section_Edit', 'section_id'),
            'Master_TaxRate' => array('Master_TaxRate_Edit', 'tax_rate_id'),
            'Master_User' => array('Master_User_Edit', 'user_id'),
            'Master_Waster' => array('Master_Waster_Edit', 'waster_id'),
            'Master_Worker' => array('Master_Worker_Edit', 'worker_id'),
            'Partner_Accepted' => array('Partner_Accepted_Edit', 'accepted_id'),
            'Partner_Order' => array('Partner_Order_Edit', 'order_header_id'),
            'Partner_Payment' => array('Partner_Payment_Edit', 'payment_id'),
            'Partner_Subcontract' => array('Partner_Subcontract_Edit', 'order_header_id'),
            'Partner_SubcontractAccepted' => array('Partner_SubcontractAccepted_Edit', 'accepted_id'),
            'Stock_Inout' => array('Stock_Inout_Edit', 'item_in_out_id'),
            'Stock_Move' => array('Stock_Move_Edit', 'move_id'),
            'Stock_SeibanChange' => array('Stock_SeibanChange_Edit', 'change_id'),
        );
    }

    static function isAttachableGroup($actionGroup) {
        if (substr($actionGroup,-1) == "_") {
            $actionGroup = substr($actionGroup, 0, strlen($actionGroup) - 1);
        }

        $arr = self::getAttachableGroupList();
        return isset($arr[$actionGroup]);
    }
}
