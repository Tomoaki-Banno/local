<?php

class Logic_Personalize
{
    static function getPersonalizeParams()
    {
        return array(
            // リスト画面：　ピン、ソート、小計基準、保存された表示条件
            "page_info" => array(
                "action" => "text",
                "pin_info" => "text",
                "orderby" => "text",
                "subsum_criteria" => "text",
                "subsum_criteria_datetype" => "int",
                "saved_search_condition_info" => "text",
            ),
            // リスト画面（列）： 列の表示/非表示、並び順、列幅、小数点以下桁数、桁区切り、寄せ、折返し
            "column_info" => array(
                "action" => "text",
                "column_key" => "text",
                "column_number" => "int",
                "column_width" => "int",
                "column_hide" => "bool",
                "column_keta" => "int",
                "column_kanma" => "int",
                "column_align" => "int",
                "column_bgcolor" => "text",
                "column_wrapon" => "int",
                "column_filter" => "text",
            ),
            // リスト画面（表示条件）：　表示条件の表示/非表示
            "search_column_info" => array(
                "action" => "text",
                "column_key" => "text",
                "column_number" => "int",
                "column_hide" => "bool",
            ),
            // 編集画面：　項目の表示/非表示、並び順
            "control_info" => array(
                "action" => "text",
                "control_key" => "text",
                "control_number" => "int",
                "control_hide" => "bool",
            ),
            // DDのソート
            "dropdown_info" => array(
                "category" => "text",
                "orderby_field" => "text",
                "orderby_desc" => "int",
            ),
            // テンプレート選択状態
            "user_template_info" => array(
                "category" => "text",
                "template_name" => "text",
            ),
        );
    }
    
    static function getPersonalizeSettingParams()
    {
        return array(
            "myMenu" => "text",
            "aggregateType" => "text",
            "numberOfItems" => "int",
            "dashboardWidgetIds" => "text",
            "directEdit" => "bool",
            "listClickEnable" => "bool",
            "gen_slider_gen_menubar" => "bool",
            "gen_slider_gen_search_area" => "bool",
            
            // 対象外
            //  各EditListの行数（XXX_XXX_XXX_list1_count）
            //  listTableWidth, listTableBottom, demo_mode, profileImage, mrp_fix_date_xxx, welcomeMsgCreated, XXXLastMod
        );
    }
}
