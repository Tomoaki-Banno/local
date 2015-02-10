<?php

class Config_AdminParam_Param
{

    function execute(&$form)
    {
        //-------------------------------------------------
        // gen_config.yml 設定値（デフォルト値）
        //-------------------------------------------------
        // アプリケーション設定（デフォルト）ファイル（gen_config.default.yml）
        $defaultFile = ROOT_DIR . '/gen_config.default.yml';
        if (!file_exists($defaultFile)) {
            throw new Exception('gen_config.default.yml がありません。');
        }
        $defaultConfig = Spyc::YAMLLoad($defaultFile);

        //-------------------------------------------------
        // 表示データ作成
        //-------------------------------------------------
        // メッセージ
        $form['gen_dataMessage_noEscape'] = 
            "<span style='background-color:#ccffcc'>" . _g("グリーン") . "</span>：" . _g("サーバー負荷を考慮する項目") . "&nbsp;&nbsp;&nbsp;&nbsp;" .
            "<span style='background-color:#ffcc99'>" . _g("ベージュ") . "</span>：" . _g("設定変更項目") . "<br><br>";

        $style = "background-color: #ccffcc;";
        $styleParam = "background-color: #ffcc99;";

        $infoArr = array();
        $i = 1;
        // 基本情報
        $infoArr[$i++] = array(
            "title" => _g("基本情報"), "count" => 5,
            "name" => _g("接続先データベース"),
            "data" => GEN_DATABASE_NAME,
            "remarks" => ""
        );
        $infoArr[$i++] = array(
            "name" => _g("ログイン有効期限"),
            "data" => GEN_LOGIN_LIMIT,
            "remarks" => ""
        );
        $infoArr[$i++] = array(
            "name" => _g("有効期限表示日数"),
            "data" => GEN_LOGIN_LIMIT_DAYS_NOTICE,
            "remarks" => _g("ログイン画面に何日前から有効期限警告を表示するか"),
        );
        $infoArr[$i++] = array(
            "name" => _g("構築区分"),
            "data" => GEN_SERVER_INFO_CLASS,
            "remarks" => _g("10：製品版 / 20：体験版 / 30：サポート版 / 40：公開検証版 / 90：開発版")
        );
        $infoArr[$i++] = array(
            "name" => _g("製品グレード"),
            "data" => GEN_GRADE,
            "remarks" => "Si / Mi"
        );
        // CSV
        $infoArr[$i++] = array(
            "title" => _g("CSV"), "count" => 5,
            "name" => _g("最大インポート行数"),
            "style" => $style,
            "data" => GEN_CSV_IMPORT_MAX_COUNT,
            "default" => $defaultConfig['csv']['import']['max_lines'],
            "styleParam" => (GEN_CSV_IMPORT_MAX_COUNT == $defaultConfig['csv']['import']['max_lines'] ? "" : $styleParam),
            "remarks" => ""
        );
        $infoArr[$i++] = array(
            "name" => _g("最大インポート時間"),
            "style" => $style,
            "data" => GEN_CSV_IMPORT_MAX_SECOND,
            "default" => $defaultConfig['csv']['import']['max_seconds'],
            "styleParam" => (GEN_CSV_IMPORT_MAX_SECOND == $defaultConfig['csv']['import']['max_seconds'] ? "" : $styleParam),
            "remarks" => "[Sec]"
        );
        $infoArr[$i++] = array(
            "name" => _g("インポート文字コード"),
            "data" => GEN_CSV_IMPORT_FROM_ENCODING,
            "default" => $defaultConfig['csv']['import']['from_encoding'],
            "styleParam" => (GEN_CSV_IMPORT_FROM_ENCODING == $defaultConfig['csv']['import']['from_encoding'] ? "" : $styleParam),
            "remarks" => "SJIS / UTF-8"
        );
        $infoArr[$i++] = array(
            "name" => _g("最大エクスポート行数"),
            "style" => $style,
            "data" => GEN_CSV_EXPORT_MAX_COUNT,
            "default" => $defaultConfig['csv']['export']['max_lines'],
            "styleParam" => (GEN_CSV_EXPORT_MAX_COUNT == $defaultConfig['csv']['export']['max_lines'] ? "" : $styleParam),
            "remarks" => ""
        );
        $infoArr[$i++] = array(
            "name" => _g("エクスポート文字コード"),
            "data" => GEN_CSV_EXPORT_TO_ENCODING,
            "default" => $defaultConfig['csv']['export']['to_encoding'],
            "styleParam" => (GEN_CSV_EXPORT_TO_ENCODING == $defaultConfig['csv']['export']['to_encoding'] ? "" : $styleParam),
            "remarks" => "SJIS / UTF-8"
        );
        // Excel
        $infoArr[$i++] = array(
            "title" => _g("Excel"), "count" => 1,
            "name" => _g("最大出力行数"),
            "style" => $style,
            "data" => GEN_EXCEL_EXPORT_MAX_COUNT,
            "default" => $defaultConfig['excel']['max_lines'],
            "styleParam" => (GEN_EXCEL_EXPORT_MAX_COUNT == $defaultConfig['excel']['max_lines'] ? "" : $styleParam),
            "remarks" => _g("※Excelの書式限界に注意")
        );
        // 帳票
        $infoArr[$i++] = array(
            "title" => _g("帳票"), "count" => 2,
            "name" => _g("最大出力ページ数"),
            "style" => $style,
            "data" => GEN_REPORT_MAX_PAGES,
            "default" => $defaultConfig['report']['max_pages'],
            "styleParam" => (GEN_REPORT_MAX_PAGES == $defaultConfig['report']['max_pages'] ? "" : $styleParam),
            "remarks" => ""
        );
        $infoArr[$i++] = array(
            "name" => _g("最大出力時間"),
            "style" => $style,
            "data" => GEN_REPORT_MAX_SECONDS,
            "default" => $defaultConfig['report']['max_seconds'],
            "styleParam" => (GEN_REPORT_MAX_SECONDS == $defaultConfig['report']['max_seconds'] ? "" : $styleParam),
            "remarks" => "[Sec]"
        );
        // アップロード
        $infoArr[$i++] = array(
            "title" => _g("アップロード"), "count" => 2,
            "name" => _g("アップロードファイル最大サイズ"),
            "style" => $style,
            "data" => GEN_MAX_UPLOAD_FILE_SIZE,
            "default" => $defaultConfig['upload_file_size'],
            "styleParam" => (GEN_MAX_UPLOAD_FILE_SIZE == $defaultConfig['upload_file_size'] ? "" : $styleParam),
            "remarks" => "[Byte] " . _g("php.ini の upload_max_filesize や post_max_size のほうが小さければ、そちらが優先される")
        );
        $infoArr[$i++] = array(
            "name" => _g("帳票テンプレート(.xls)最大サイズ"),
            "style" => $style,
            "data" => GEN_MAX_TEMPLATE_FILE_SIZE,
            "default" => $defaultConfig['template_file_size'],
            "styleParam" => (GEN_MAX_TEMPLATE_FILE_SIZE == $defaultConfig['template_file_size'] ? "" : $styleParam),
            "remarks" => "[Byte]&nbsp;&nbsp;" . _g("※PHPExcelのメモリ不足エラー注意")
        );
        // 各設定値
        $infoArr[$i++] = array(
            "title" => _g("各設定値"), "count" => 4,
            "name" => _g("品目手配先の最大値"),
            "data" => GEN_ITEM_ORDER_COUNT,
            "default" => $defaultConfig['item_order_count'],
            "styleParam" => (GEN_ITEM_ORDER_COUNT == $defaultConfig['item_order_count'] ? "" : $styleParam),
            "remarks" => _g("標準手配先 + 代替手配先")
        );
        $infoArr[$i++] = array(
            "name" => _g("品目工程の最大値"),
            "data" => GEN_ITEM_PROCESS_COUNT,
            "default" => $defaultConfig['item_process_count'],
            "styleParam" => (GEN_ITEM_PROCESS_COUNT == $defaultConfig['item_process_count'] ? "" : $styleParam),
            "remarks" => _g("※19以上のときは製造指示書カスタマイズが必要")
        );
        $infoArr[$i++] = array(
            "name" => _g("不適合理由の最大値"),
            "data" => GEN_WASTER_COUNT,
            "default" => $defaultConfig['waster_count'],
            "styleParam" => (GEN_WASTER_COUNT == $defaultConfig['waster_count'] ? "" : $styleParam),
            "remarks" => _g("実績登録") . "&nbsp;/&nbsp;" . _g("実績CSVインポート")
        );
        $infoArr[$i++] = array(
            "name" => _g("所要量計算の期間"),
            "style" => $style,
            "data" => GEN_MRP_DAYS,
            "default" => $defaultConfig['mrp_days'],
            "styleParam" => (GEN_MRP_DAYS == $defaultConfig['mrp_days'] ? "" : $styleParam),
            "remarks" => "[Days]"
        );
        // メンテナンス
        $infoArr[$i++] = array(
            "title" => _g("メンテナンス"), "count" => 4,
            "name" => _g("同一アカウントログイン許可"),
            "data" => (GEN_ALLOW_CONCURRENT_USE ? 'true' : 'false'),
            "default" => (@$defaultConfig['allow_concurrent_use'] ? 'true' : 'false'),
            "styleParam" => (GEN_ALLOW_CONCURRENT_USE == $defaultConfig['allow_concurrent_use'] ? "" : $styleParam),
            "remarks" => _g("同一アカウントでの複数デバイス(PC)・ブラウザからの同時使用を許可")
        );
        $infoArr[$i++] = array(
            "name" => _g("保存バックアップ最大数"),
            "style" => $style,
            "data" => GEN_BACKUP_MAX_NUMBER,
            "default" => $defaultConfig['backup_max_number'],
            "styleParam" => (GEN_BACKUP_MAX_NUMBER == $defaultConfig['backup_max_number'] ? "" : $styleParam),
            "remarks" => ""
        );
        if (!isset($defaultConfig['data_storage_size'])) {
            $defaultConfig['data_storage_size'] = $defaultConfig['backup_file_size_limit']; // 13i用
        }
        $infoArr[$i++] = array(
            "name" => _g("データストレージサイズ（DBバックアップ）"),
            "data" => GEN_DATA_STORAGE_SIZE,
            "default" => $defaultConfig['data_storage_size'],
            "styleParam" => (GEN_DATA_STORAGE_SIZE == $defaultConfig['data_storage_size'] ? "" : $styleParam),
            "remarks" => "[MB]"
        );
        $infoArr[$i++] = array(
            "name" => _g("ファイルストレージサイズ"),
            "data" => GEN_FILE_STORAGE_SIZE,
            "default" => $defaultConfig['file_storage_size'],
            "styleParam" => (GEN_FILE_STORAGE_SIZE == $defaultConfig['file_storage_size'] ? "" : $styleParam),
            "remarks" => "[MB]"
        );
        // 表示設定
        $infoArr[$i++] = array(
            "title" => _g("表示設定"), "count" => 6,
            "name" => _g("拡張ドロップダウン行数"),
            "data" => GEN_DROPDOWN_PER_PAGE,
            "default" => $defaultConfig['dropdown_per_page'],
            "styleParam" => (GEN_DROPDOWN_PER_PAGE == $defaultConfig['dropdown_per_page'] ? "" : $styleParam),
            "remarks" => ""
        );
        $infoArr[$i++] = array(
            "name" => _g("Edit画面の明細リスト最大行数"),
            "data" => GEN_EDIT_DETAIL_COUNT,
            "default" => $defaultConfig['edit_detail_count'],
            "styleParam" => (GEN_EDIT_DETAIL_COUNT == $defaultConfig['edit_detail_count'] ? "" : $styleParam),
            "remarks" => ""
        );
        $infoArr[$i++] = array(
            "name" => _g("グラフの横軸最大項目数"),
            "data" => GEN_CHART_HORIZ_MAX,
            "default" => $defaultConfig['chart_horiz_max'],
            "styleParam" => (GEN_CHART_HORIZ_MAX == $defaultConfig['chart_horiz_max'] ? "" : $styleParam),
            "remarks" => _g("コンパス") . "&nbsp;/&nbsp;" . _g("レポートセンター")
        );
        $infoArr[$i++] = array(
            "name" => _g("デフォルトロケーション名"),
            "data" => GEN_DEFAULT_LOCATION_NAME,
            "default" => $defaultConfig['default_location_name'],
            "styleParam" => (GEN_DEFAULT_LOCATION_NAME == $defaultConfig['default_location_name'] ? "" : $styleParam),
            "remarks" => ""
        );
        $infoArr[$i++] = array(
            "name" => _g("画面背景色"),
            "data" => GEN_BACKGROUND_COLOR,
            "default" => $defaultConfig['background_color'],
            "styleParam" => (GEN_BACKGROUND_COLOR == $defaultConfig['background_color'] ? "" : $styleParam),
            "remarks" => ""
        );
        $infoArr[$i++] = array(
            "name" => _g("List画面の1行おきカラー"),
            "data" => str_replace("#", "", GEN_LIST_ALTER_COLOR),
            "default" => $defaultConfig['alter_color'],
            "styleParam" => (str_replace("#", "", GEN_LIST_ALTER_COLOR) == $defaultConfig['alter_color'] ? "" : $styleParam),
            "remarks" => ""
        );
        // 小数点以下桁数
        $infoArr[$i++] = array(
            "title" => _g("小数点以下桁数"), "count" => 5,
            "name" => _g("List画面（表）"),
            "data" => GEN_DECIMAL_POINT_LIST,
            "default" => $defaultConfig['decimal_point']['list'],
            "styleParam" => (GEN_DECIMAL_POINT_LIST == $defaultConfig['decimal_point']['list'] ? "" : $styleParam),
            "remarks" => _g("[-1：自然丸め]") . "&nbsp;&nbsp;" . _g("※画面上で変更可能")
        );
        $infoArr[$i++] = array(
            "name" => _g("Edit画面（コントロール）"),
            "data" => GEN_DECIMAL_POINT_EDIT,
            "default" => $defaultConfig['decimal_point']['edit'],
            "styleParam" => (GEN_DECIMAL_POINT_EDIT == $defaultConfig['decimal_point']['edit'] ? "" : $styleParam),
            "remarks" => _g("[-1：自然丸め]") . "&nbsp;&nbsp;" . _g("※画面上で変更可能")
        );
        $infoArr[$i++] = array(
            "name" => _g("帳票"),
            "data" => GEN_DECIMAL_POINT_REPORT,
            "default" => $defaultConfig['decimal_point']['report'],
            "styleParam" => (GEN_DECIMAL_POINT_REPORT == $defaultConfig['decimal_point']['report'] ? "" : $styleParam),
            "remarks" => _g("※自然丸め不可")
        );
        $infoArr[$i++] = array(
            "name" => _g("Excel"),
            "data" => GEN_DECIMAL_POINT_EXCEL,
            "default" => $defaultConfig['decimal_point']['excel'],
            "styleParam" => (GEN_DECIMAL_POINT_EXCEL == $defaultConfig['decimal_point']['excel'] ? "" : $styleParam),
            "remarks" => _g("[-1：自然丸め]") . "&nbsp;&nbsp;" . _g("※List画面と合わせておくとよい")
        );
        $infoArr[$i++] = array(
            "name" => _g("拡張ドロップダウン"),
            "data" => GEN_DECIMAL_POINT_DROPDOWN,
            "default" => $defaultConfig['decimal_point']['dropdown'],
            "styleParam" => (GEN_DECIMAL_POINT_DROPDOWN == $defaultConfig['decimal_point']['dropdown'] ? "" : $styleParam),
            "remarks" => _g("※自然丸め不可")
        );
        // プレフィックス
        $infoArr[$i++] = array(
            "title" => _g("プレフィックス"), "count" => 8,
            "name" => _g("見積番号"),
            "data" => GEN_PREFIX_ESTIMATE_NUMBER,
            "default" => $defaultConfig['prefix']['estimate_number'],
            "styleParam" => (GEN_PREFIX_ESTIMATE_NUMBER == $defaultConfig['prefix']['estimate_number'] ? "" : $styleParam),
            "remarks" => ""
        );
        $infoArr[$i++] = array(
            "name" => _g("受注番号"),
            "data" => GEN_PREFIX_RECEIVED_NUMBER,
            "default" => $defaultConfig['prefix']['received_number'],
            "styleParam" => (GEN_PREFIX_RECEIVED_NUMBER == $defaultConfig['prefix']['received_number'] ? "" : $styleParam),
            "remarks" => ""
        );
        $infoArr[$i++] = array(
            "name" => _g("納品書番号"),
            "data" => GEN_PREFIX_DELIVERY_NUMBER,
            "default" => $defaultConfig['prefix']['delivery_number'],
            "styleParam" => (GEN_PREFIX_DELIVERY_NUMBER == $defaultConfig['prefix']['delivery_number'] ? "" : $styleParam),
            "remarks" => ""
        );
        $infoArr[$i++] = array(
            "name" => _g("請求書番号"),
            "data" => GEN_PREFIX_BILL_NUMBER,
            "default" => $defaultConfig['prefix']['bill_number'],
            "styleParam" => (GEN_PREFIX_BILL_NUMBER == $defaultConfig['prefix']['bill_number'] ? "" : $styleParam),
            "remarks" => ""
        );
        $infoArr[$i++] = array(
            "name" => _g("注文書番号"),
            "data" => GEN_PREFIX_PARTNER_ORDER_NUMBER,
            "default" => $defaultConfig['prefix']['partner_order_number'],
            "styleParam" => (GEN_PREFIX_PARTNER_ORDER_NUMBER == $defaultConfig['prefix']['partner_order_number'] ? "" : $styleParam),
            "remarks" => ""
        );
        $infoArr[$i++] = array(
            "name" => _g("注文書 オーダー番号"),
            "data" => GEN_PREFIX_ORDER_NO_PARTNER,
            "default" => $defaultConfig['prefix']['order_no_partner'],
            "styleParam" => (GEN_PREFIX_ORDER_NO_PARTNER == $defaultConfig['prefix']['order_no_partner'] ? "" : $styleParam),
            "remarks" => ""
        );
        $infoArr[$i++] = array(
            "name" => _g("製造指示 オーダー番号"),
            "data" => GEN_PREFIX_ORDER_NO_MANUFACTURING,
            "default" => $defaultConfig['prefix']['order_no_manufacturing'],
            "styleParam" => (GEN_PREFIX_ORDER_NO_MANUFACTURING == $defaultConfig['prefix']['order_no_manufacturing'] ? "" : $styleParam),
            "remarks" => ""
        );
        $infoArr[$i++] = array(
            "name" => _g("外製指示 オーダー番号"),
            "data" => GEN_PREFIX_ORDER_NO_SUBCONTRACT,
            "default" => $defaultConfig['prefix']['order_no_subcontract'],
            "styleParam" => (GEN_PREFIX_ORDER_NO_SUBCONTRACT == $defaultConfig['prefix']['order_no_subcontract'] ? "" : $styleParam),
            "remarks" => ""
        );

        $form['paramInfo'] = $infoArr;

        return 'config_adminparam_param.tpl';
    }

}
