<?php
// 実行前に Logic_Mrp の vacuum 部分をコメントアウトしておくこと

define('ROOT_DIR', dirname(__FILE__) . '/../');
define('COMPONENTS_DIR' , ROOT_DIR . 'Components/');
define('BASE_DIR' , ROOT_DIR . 'Base/');
define('LOGIC_DIR' , ROOT_DIR . 'Logic/');
define('APP_DIR', realpath(ROOT_DIR . 'htdocs') . '/');

define('SEPARATOR', '/');

spl_autoload_register('gen_autoload');

require_once(COMPONENTS_DIR . "Db.class.php");
require_once(COMPONENTS_DIR . "String.class.php");
require_once(COMPONENTS_DIR . "Converter.class.php");
require_once(COMPONENTS_DIR . "Validator.class.php");

require_once (COMPONENTS_DIR . 'Spyc.php');
$serverConfig = Spyc::YAMLLoad(ROOT_DIR . '../gen_server_config.yml');
define("GEN_POSTGRES_HOST", $serverConfig['postgresql']['host']);
define("GEN_POSTGRES_PORT", $serverConfig['postgresql']['port']);
define("GEN_POSTGRES_USER", $serverConfig['postgresql']['user']);

$config = Spyc::YAMLLoad(ROOT_DIR . 'gen_config.yml');
define("GEN_DATABASE_NAME", $config['database']);
define("GEN_ITEM_ORDER_COUNT", $config['item_order_count']);
define("GEN_ITEM_PROCESS_COUNT", $config['item_process_count']);
define('GEN_PREFIX_ESTIMATE_NUMBER', $config['prefix']['estimate_number']);                 // 見積番号
define('GEN_PREFIX_RECEIVED_NUMBER', $config['prefix']['received_number']);                 // 受注番号
define('GEN_PREFIX_DELIVERY_NUMBER', $config['prefix']['delivery_number']);                 // 納品書番号
define('GEN_PREFIX_BILL_NUMBER', $config['prefix']['bill_number']);                         // 請求書番号
define('GEN_PREFIX_PARTNER_ORDER_NUMBER', $config['prefix']['partner_order_number']);       // 注文書番号
define('GEN_PREFIX_ORDER_NO_MANUFACTURING', $config['prefix']['order_no_manufacturing']);   // 製造指示 オーダー番号
define('GEN_PREFIX_ORDER_NO_PARTNER', $config['prefix']['order_no_partner']);               // 注文書 オーダー番号
define('GEN_PREFIX_ORDER_NO_SUBCONTRACT', $config['prefix']['order_no_subcontract']);       // 外製指示 オーダー番号
define("GEN_MRP_DAYS", $config['mrp_days']);
define("GEN_FOREIGN_CURRENCY_PRECISION", 4);
define('SYSTEM_REPORT_TEMPLATES_DIR', ROOT_DIR . 'ReportTemplates/');
define('GEN_FILES_DIR', $serverConfig['files_dir'] . "/");
define('GEN_TEMP_DIR', $serverConfig['temp_dir'] . "/");
define('GEN_CUSTOM_COLUMN_COUNT', 10);
define('GEN_MULTI_EDIT_COUNT', 50);
define('GEN_DECIMAL_POINT_LIST', $config['decimal_point']['list']);        // List画面（表）
define('GEN_DECIMAL_POINT_EDIT', $config['decimal_point']['edit']);        // Edit画面（コントロール） javascriptも含む
define('GEN_DECIMAL_POINT_REPORT', $config['decimal_point']['report']);    // レポート（自然丸め(-1)は無効）
define('GEN_DECIMAL_POINT_EXCEL', $config['decimal_point']['excel']);      // Excel（List画面とあわせておくとよい）
define('GEN_DECIMAL_POINT_DROPDOWN', $config['decimal_point']['dropdown']); // ドロップダウン（自然丸め(-1)は無効）
define('GEN_REPORT_MAX_PAGES', $config['report']['max_pages']);
define('GEN_REPORT_MAX_SECONDS', $config['report']['max_seconds']);
define("GEN_LOT_MANAGEMENT", $config['lot_management']);
$cacheDirBase = $config['temp_dir'] . "/" . $config['database'];
define('GEN_TCPDF_CACHE_DIR', $cacheDirBase . "/tcpdf/");

ini_set('date.timezone', 'Asia/Tokyo');
ini_set("bcmath.scale", 8);

global $gen_db;
$gen_db = new Gen_Db();

$_SESSION['user_name'] = "test";
// adminで1度はログインしていることが前提
$query = "select session_id from session_table where user_id = -1 order by login_date desc limit 1";
$_SESSION['session_id'] = $gen_db->queryOneValue($query);
$_SESSION['user_id'] = -1;
define('ADMIN_NAME', "admin");

function _g($str)
{
    return $str;
}


class TestCommon {
    static function makeItem($param = array())
    {
        global $gen_db;

        $code = Gen_String::makeRandomString(15);

        // デフォルト値。必須項目だけ指定してある。引数の指定が優先される
        $default = array(
            'item_code' => $code,
            'item_name' => $code,

            'order_class' => 1,     // 0:製番　1:MRP　2:ロット
            'default_selling_price' => 0,
            'stock_price' => 0,
            'received_object' => 0, // 0:受注対象 1:非対象
            'maker_name' => '',
            'spec' => '',
            'comment' => '',
            'comment_2' => '',
            'comment_3' => '',
            'comment_4' => '',
            'comment_5' => '',
            'without_mrp' => 0,    // 所要量計算に含める　0:含める   1:除外
            'lead_time' => 0,
            'safety_lead_time' => 0,
            'safety_stock' => 0,
            'use_by_days' => 0,
            'lot_header' => '',
        );
        foreach($default as $col=>$val) {
            if (!isset($param[$col])) $param[$col] = $val;
        }
        // 品目マスタModelクラスを使用して登録
        self::_registByModel('Master', 'Item', $param);

        return $param['item_id'];
    }

    static function makeCustomer($param = array())
    {
        global $gen_db;

        $code = Gen_String::makeRandomString(15);

        // デフォルト値。必須項目だけ指定してある。引数の指定が優先される
        $default = array(
            'customer_no' => $code,
            'customer_name' => $code,
            'classification' => 0,      // 0: 得意先、 1:サプライヤー、 2: 発送先
            'report_language' => 0,     // 0: 日本語、 1:英語
            'bill_pattern' => 0,        // 0: 都度、 1: 締め
            'monthly_limit_date' => 31,
            'tax_category' => 0,        // 0: 請求書単位、1: 納品書単位、2: 納品明細単位
            'zip' => '',
            'address1' => '',
            'address2' => '',
            'tel' => '',
            'fax' => '',
            'e_mail' => '',
            'person_in_charge' => '',
            'rounding' => 'round',
            'precision' => 0,
            'delivery_port' => ''
        );
        foreach($default as $col=>$val) {
            if (!isset($param[$col])) $param[$col] = $val;
        }
        // 取引先マスタModelクラスを使用して登録
        self::_registByModel('Master', 'Customer', $param);

        return $gen_db->getSequence('customer_master_customer_id_seq');
    }

    static function makeCurrencyAndRate($param = array(), $date, $rate)
    {
        global $gen_db;

        $code = Gen_String::makeRandomString(15);

        // デフォルト値。必須項目だけ指定してある。引数の指定が優先される
        $default = array(
            'currency_name' => $code,
        );
        foreach($default as $col=>$val) {
            if (!isset($param[$col])) $param[$col] = $val;
        }

        // 通貨マスタ登録
        $gen_db->insert('currency_master', $param);
        $currencyId = $gen_db->queryOneValue("select currency_id from currency_master where currency_name = '{$param['currency_name']}'");

        // レートの登録
        $data = array(
            'currency_id' => $currencyId,
            'rate_date' => $date,
            'rate' => $rate,
        );
        $gen_db->insert('rate_master', $data);

        return $currencyId;
    }

    // 複数明細行に対応
    static function makeReceived($params = array())
    {
        global $gen_db;

        // パラメータは array(array(..)) の形にそろえる
        if (!isset($params[0]) || !is_array($params[0])) {
            $params = array($params);
        }

        // 明細行ごとにパラメータ準備
        foreach($params as &$param) {
            // デフォルト値。必須項目だけ指定してある。引数の指定が優先される
            $default = array(
                // customer_id, item_id は引数で必ず指定する
                'received_date' => date('Y-m-d'),
                'guarantee_grade' => 0,
                'received_quantity' => 1,
                'product_price' => 0,
                'dead_line' => date('Y-m-d', strtotime('+1 day')),
                'remarks' => '',
                );
            foreach($default as $col=>$val) {
                if (!isset($param[$col])) $param[$col] = $val;
            }
        }

        // Modelクラスを使用して登録
        self::_registByModel('Manufacturing', 'Received', $params);

        return $gen_db->getSequence("received_header_received_header_id_seq");
    }

    // 複数明細行に対応
    static function makeDelivery($params = array())
    {
        global $gen_db;

        // パラメータは array(array(..)) の形にそろえる
        if (!isset($params[0]) || !is_array($params[0])) {
            $params = array($params);
        }

        // 明細行ごとにパラメータ準備
        foreach($params as &$param) {
            // デフォルト値。必須項目だけ指定してある。引数の指定が優先される
            $default = array(
                // received_detail_id は引数で必ず指定する
                'delivery_date' => date('Y-m-d'),
                'inspection_date' => date('Y-m-d'),
                'delivery_quantity' => 1,
                'delivery_price' => 0,
                );
            foreach($default as $col=>$val) {
                if (!isset($param[$col])) $param[$col] = $val;
            }
        }

        // Modelクラスを使用して登録
        self::_registByModel('Delivery', 'Delivery', $params);

        return $gen_db->getSequence("delivery_header_delivery_header_id_seq");
    }

    // 複数明細行に対応
    static function makeOrder($params = array())
    {
        global $gen_db;

        // パラメータは array(array(..)) の形にそろえる
        if (!isset($params[0]) || !is_array($params[0])) {
            $params = array($params);
        }

        // 明細行ごとにパラメータ準備
        foreach($params as &$param) {
            // デフォルト値。必須項目だけ指定してある。引数の指定が優先される
            $default = array(
                // partner_id, item_id は引数で必ず指定する
                'order_no'=>'',
                'classification' => 1,  // 注文書
                'order_date' => date('Y-m-d'),
                'item_price' => 0,
                'order_detail_quantity' => 1,
                'order_detail_dead_line' => date('Y-m-d', strtotime('+1 day')),
                'multiple_of_order_measure' => 1,
                'remarks_header' => '',
                'remarks' => '',
                );
            foreach($default as $col=>$val) {
                if (!isset($param[$col])) $param[$col] = $val;
            }
        }

        // Modelクラスを使用して登録
        self::_registByModel('Partner', 'Order', $params);

        return $gen_db->getSequence("order_header_order_header_id_seq");
    }




    // Modelクラスを使用した登録処理。
    private static function _registByModel($name1, $name2, &$params)
    {
        global $gen_db;

        require_once(APP_DIR . "{$name1}/{$name2}/Model.class.php");

        $modelName = $name1. '_' .$name2. '_Model';
        $model = new $modelName();

        // パラメータは array(array(..)) の形にそろえる
        $isWrap = false;
        if (!isset($params[0]) || !is_array($params[0])) {
            $params = array($params);
            $isWrap = true;
        }

        $isFirstRegist = true;
        $lineNo = 1;

        foreach($params as &$param) {
            $model->setDefault($param, "");
            if (method_exists($model, "beforeLogic")) {
                $model->beforeLogic($param);
            }

            $converter = new Gen_Converter($param);
            $model->convert($converter);

            $validator = new Gen_Validator($param);
            $model->validate($validator, $param);

            if ($validator->hasError()) {
                foreach ($validator->errorList as $error) {
                    // バリデーションでエラーが発生した場合、出力ウィンドウにメッセージ出力して終了
                    var_dump(mb_convert_encoding($error, "Shift_JIS", "UTF-8"));
                }
                trigger_error('');
            } else {
                // 登録
                $param['gen_line_no'] = $lineNo;
                $model->regist($param, $isFirstRegist);
                $lineNo++;
                $isFirstRegist = false;
            }
        }

        if ($isWrap) {
            $params = $params[0];
        }
    }
}
function h($str)
{
    return $str;
}
    function gen_autoload($className)
    {
        $nodes = explode('_', $className);

        switch ($nodes[0]) {
            // rtrimは*_DIRの末尾についている/を削除するためにある。
            // array_sliceはクラスファイル内に、そのクラスを補助・あるいは関連する
            // 小目的クラスを定義できるようにするためにある。
            // (Logic_Receivedと同ファイルにLogic_Received_Subなど))
            case 'Base':
                $nodes[0] = rtrim(BASE_DIR, '/');
                $nodes = array_slice($nodes, 0, 2);
                break;
            case 'Gen':
                $nodes[0] = rtrim(COMPONENTS_DIR, '/');
                $nodes = array_slice($nodes, 0, 2);
                break;
            case 'Logic':
                $nodes[0] = rtrim(LOGIC_DIR, '/');
                $nodes = array_slice($nodes, 0, 2);
                break;
            default :
                // 上記以外のクラス
                return;
        }
        $fileName = join('/', $nodes) . '.class.php';
        require_once($fileName);
    }

?>
