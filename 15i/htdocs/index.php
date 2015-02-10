<?php
    // フロントコントローラ
 
    // error_reporting設定
    // macでどうしてもphp.iniが反映されないので 
    if ($_SERVER["SERVER_NAME"] == "127.0.0.1" || $_SERVER["SERVER_NAME"] == "localhost") {
        ini_set("error_reporting",E_ALL & ~E_DEPRECATED);
    }

    //------------------------------------------------------
    // ベンチマーク
    //------------------------------------------------------
    $bench = false;
    if ($bench) {
        require_once dirname(dirname(__FILE__)) . '/pear/Benchmark/Timer.php';
        $timer = new Benchmark_Timer();
        $timer->start();
    }

    //------------------------------------------------------
    // 設定ファイル
    //------------------------------------------------------
    //
    // サーバー設定ファイル（gen_server_config.yml）はサーバー内のアプリケーションすべてに
    // 適用する設定ファイル。
    // アプリケーション設定ファイル（gen_config.yml）はそのアプリケーションだけに
    // 適用する設定ファイル。
    //
    // 両者に同じ項目が記述されていた場合、アプリケーション設定ファイルの内容が優先される。

    require_once dirname(dirname(__FILE__)) . '/Components/Spyc.php';

    // サーバー設定ファイル（gen_server_config.yml）
    // アプリケーションディレクトリのひとつ上（/var/genesiss/）に配置
    $confFile = dirname(dirname(dirname(__FILE__))) . '/gen_server_config.yml';
    if (!file_exists($confFile)) {
        throw new Exception('gen_server_config.yml がありません。');
    }
    $serverConfig = Spyc::YAMLLoad($confFile);

    // アプリケーション設定ファイル（gen_config.yml）
    $confFile = dirname(dirname(__FILE__)) . '/gen_config.yml';
    if (!file_exists($confFile)) {
        throw new Exception('gen_config.yml がありません。');
    }
    $appConfig = Spyc::YAMLLoad($confFile);

    // キー重複の場合、後の配列の内容が優先される
    $config = array_merge($serverConfig, $appConfig);

    // PostgreSQL接続パラメータ
    define("GEN_POSTGRES_HOST", $config['postgresql']['host']);
    define("GEN_POSTGRES_PORT", $config['postgresql']['port']);
    define("GEN_POSTGRES_USER", $config['postgresql']['user']);

    // PostgreSQLのbinディレクトリ。Components/Db.class.php の バックアップ/リストア部分で使用。
    define('GEN_POSTGRES_BIN_DIR', $config['dir']['postgres_bin']);

    // PHPのディレクトリ。CLI版PHPを使う箇所（いまのところ所要量計算のみ）で使用。
    define('GEN_PHP_BIN_DIR', isset($config['dir']['php_bin']) ? $config['dir']['php_bin'] : "");

    // WinかLinuxか
    define('GEN_IS_WIN', $config['is_windows']);

    // HTTPS設定
    define('GEN_HTTPS_PROTOCOL', $config['https_protocol']);

    // PHPのエラー出力設定
    define('GEN_DISPLAY_ERRORS', $config['display_errors']);

    // Cookieのセキュア属性設定
    define('GEN_COOKIE_SECURE', $config['cookie_secure']);

    // セコムパスポート仮契約コード（ログイン画面のステッカー表示用）
    define('GEN_TRUST_CODE', @$config['trust_code']);

    // 事務所IPアドレス
    define('GEN_OFFICE_IP', @$config['office_ip']);

    // 接続先データベース
    define("GEN_DATABASE_NAME", $config['database']);

    // データストレージサイズ（バックアップファイルのサイズの上限）（MB）
    if (isset($config['data_storage_size'])) {
        $dataStorageSize = $config['data_storage_size'];    // 15i
    } else {
        $dataStorageSize = $config['backup_file_size_limit'];   // 13i
    }
    define("GEN_DATA_STORAGE_SIZE", $dataStorageSize);

    // ファイルストレージサイズ（レコード/チャットの添付ファイルや、品目画像の合計サイズの上限）（MB）
    define("GEN_FILE_STORAGE_SIZE", $config['file_storage_size']);

    // ログイン有効期限
    define("GEN_LOGIN_LIMIT", @$config['login_limit']);

    // login_limitが設定されている場合、ログイン画面に何日前から警告を表示するか。0は無期限
    define("GEN_LOGIN_LIMIT_DAYS_NOTICE", (isset($config['login_limit_days_notice']) ? $config['login_limit_days_notice'] : 0));

    // 構築区分（製品版=10, 体験版=20, サポート用=30, 公開検証用=40, 開発用=90）
    define("GEN_SERVER_INFO_CLASS", (isset($config['server_info_class']) ? $config['server_info_class'] : 90));

    // 製品グレード
    define("GEN_GRADE", @$config['grade']);

    // ロット管理機能
    define("GEN_LOT_MANAGEMENT", @$config['lot_management']);

    // 同一アカウントでの複数デバイス(PC)・ブラウザからの同時使用を許可するか
    define("GEN_ALLOW_CONCURRENT_USE", @$config['allow_concurrent_use']);

    // 画面の背景色
    define("GEN_BACKGROUND_COLOR", isset($config['background_color']) && $config['background_color'] != "" ? $config['background_color'] : "FFFFFF");

    // Edit画面の明細リスト最大行数
    define("GEN_EDIT_DETAIL_COUNT", $config['edit_detail_count']);

    // List画面で1行おきに色をつける場合は、そのカラーコードを指定する。
    define('GEN_LIST_ALTER_COLOR', '#' . $config['alter_color']);

    // 拡張ドロップダウンで、1ページに表示する行数
    define('GEN_DROPDOWN_PER_PAGE', $config['dropdown_per_page']);

    // 小数点以下の表示桁数を指定する。
    // 指定桁数が「-1」なら、「自然丸め」になる。
    //   「自然丸め」とは、小数点以下桁数無制限だが余分な0は表示しないスタイル。
    //    　（例：「75.1000」⇒「75.1」　「75.0000」⇒「75」）
    // ただしドロップダウンとレポートは自然丸めできない（パフォーマンスの関係）。

    define('GEN_DECIMAL_POINT_LIST', $config['decimal_point']['list']);        // List画面（表）
    define('GEN_DECIMAL_POINT_EDIT', $config['decimal_point']['edit']);        // Edit画面（コントロール） javascriptも含む
    define('GEN_DECIMAL_POINT_REPORT', $config['decimal_point']['report']);    // レポート（自然丸め(-1)は無効）
    define('GEN_DECIMAL_POINT_EXCEL', $config['decimal_point']['excel']);      // Excel（List画面とあわせておくとよい）
    define('GEN_DECIMAL_POINT_DROPDOWN', $config['decimal_point']['dropdown']); // ドロップダウン（自然丸め(-1)は無効）
    // 帳票出力
    define('GEN_REPORT_MAX_PAGES', $config['report']['max_pages']);
    define('GEN_REPORT_MAX_SECONDS', $config['report']['max_seconds']);

    // CSVインポート・エクスポートの設定。
    define('GEN_CSV_IMPORT_MAX_COUNT', $config['csv']['import']['max_lines']);          // 一度の処理での読み込みを許可する件数の設定。次行で設定する時間で読み込める件数にする
    define('GEN_CSV_IMPORT_MAX_SECOND', $config['csv']['import']['max_seconds']);       // CSV読み込み処理のタイムアウト秒数。
    define('GEN_CSV_IMPORT_FROM_ENCODING', $config['csv']['import']['from_encoding']);  // CSVインポート文字コード
    define('GEN_CSV_EXPORT_MAX_COUNT', $config['csv']['export']['max_lines']);          // 一度の処理でのエクスポートを許可する件数の設定
    define('GEN_CSV_EXPORT_TO_ENCODING', $config['csv']['export']['to_encoding']);      // CSVエクスポート文字コード

    // Excel出力
    define('GEN_EXCEL_EXPORT_MAX_COUNT', $config['excel']['max_lines']);   // 一度の処理での出力を許可する件数の設定

    // ひとつの品目に対する手配先の最大値（標準手配先 + 代替手配先）
    define('GEN_ITEM_ORDER_COUNT', $config['item_order_count']);

    // ひとつの品目に対する最大工程数。18まで。19以上のときは製造指示書カスタマイズが必要
    define('GEN_ITEM_PROCESS_COUNT', $config['item_process_count']);

    // 実績登録や実績CSVインポートでの不適合理由の数
    define("GEN_WASTER_COUNT", $config['waster_count']);

    // 所要量計算の期間
    define("GEN_MRP_DAYS", $config['mrp_days']);

    // アップロードを許可するファイルの最大サイズ（バイト）
    // php.iniの upload_max_filesize や post_max_size にも制限されるので、それらのほうが小さければそちらを制限値として使用する。
    // OS（ディストリビューション）によっては別のファイルサイズ制限がある場合もある。あとブラウザやルータのタイムアウトも。
    $maxUploadSize = $config['upload_file_size'];
    $uploadMaxFileSize = gen_getBytes(ini_get('upload_max_filesize'));
    $postMaxSize = gen_getBytes(ini_get('post_max_size'));
    if ($maxUploadSize > $uploadMaxFileSize)
        $maxUploadSize = $uploadMaxFileSize;
    if ($maxUploadSize > $postMaxSize)
        $maxUploadSize = $postMaxSize;
    define('GEN_MAX_UPLOAD_FILE_SIZE', $maxUploadSize);

    // 帳票テンプレートファイル(.xls)の最大サイズ（バイト）
    // サイズを大きくしすぎると、アップロード時や帳票表示時にPHPExcelでメモリ不足エラーが発生するので注意。
    // ちなみにシステムテンプレートはすべて100KB以内だが、画像を含んだ場合を考えてもう少し大きくしておく
    if (!isset($config['template_file_size']))
        $config['template_file_size'] = 512000;    // デフォルト500KB
    define('GEN_MAX_TEMPLATE_FILE_SIZE', $config['template_file_size']);

    // 既定ロケの名称
    define('GEN_DEFAULT_LOCATION_NAME', $config['default_location_name']);

    // 保存するバックアップファイルの最大数
    define("GEN_BACKUP_MAX_NUMBER", $config['backup_max_number']);

    // コンパス・レポートセンターでの、グラフの横軸の最大項目数
    define("GEN_CHART_HORIZ_MAX", $config['chart_horiz_max']);

    // サポートのリンク
    define("GEN_SUPPORT_LINK", isset($config['support_link']) ? $config['support_link'] : 'http://support.genesiss.jp/faqdb15/');
    define("GEN_FAQ_SEARCH_LINK", isset($config['faq_search_link']) ? $config['faq_search_link'] : 'http://support.genesiss.jp/faqdb15/faqdbblog/faqdbblog.php?bltnmode=sch&schstr=');

    // デスクトップ通知の更新間隔（分）。
    // 複数のタブでGenを開いている場合、それぞれのタブがこの間隔でサーバーに更新確認することになる。
    // ただしデスクトップ通知自体は、いくつタブがあろうと、最低でもこれだけの時間が経過しないと実行されないようにしてある。（Config_Setting_AjaxDesktopNotification）
    define("GEN_DESKTOP_NOTIFICATION_SPAN", isset($config['desktop_notification_span']) ? $config['desktop_notification_span'] : 10);

    // 自動採番のプレフィックス
    define('GEN_PREFIX_ESTIMATE_NUMBER', $config['prefix']['estimate_number']);                 // 見積番号
    define('GEN_PREFIX_RECEIVED_NUMBER', $config['prefix']['received_number']);                 // 受注番号
    define('GEN_PREFIX_DELIVERY_NUMBER', $config['prefix']['delivery_number']);                 // 納品書番号
    define('GEN_PREFIX_BILL_NUMBER', $config['prefix']['bill_number']);                         // 請求書番号
    define('GEN_PREFIX_PARTNER_ORDER_NUMBER', $config['prefix']['partner_order_number']);       // 注文書番号
    define('GEN_PREFIX_ORDER_NO_MANUFACTURING', $config['prefix']['order_no_manufacturing']);   // 製造指示書 オーダー番号
    define('GEN_PREFIX_ORDER_NO_PARTNER', $config['prefix']['order_no_partner']);               // 注文書 オーダー番号
    define('GEN_PREFIX_ORDER_NO_SUBCONTRACT', $config['prefix']['order_no_subcontract']);       // 外製指示書 オーダー番号

    // 外貨単価の小数点以下切り捨て桁数
    define("GEN_FOREIGN_CURRENCY_PRECISION", 4);

    // 動的ファイルの配置ディレクトリ
    //  動的ファイル（実行中に登録・生成・更新されるファイル）はすべて S3、files_dir、temp_dir のいずれかに配置する。
    //      ソース内には動的ファイルが一切存在しないようにする。ロードバランサー使用時のソース同期時差による問題を回避するため。
    //  files_dir と temp_dirの使い分けなど、詳細は Config_Setting_FileUpload 冒頭の「●15iでの改善」の部分以降のコメントを参照。

    // S3: true, files_dir: false。デフォルトは files_dir
    //      S3 の場合も、すべての動的ファイルがS3に保存されるわけではない。Gen_Storage の冒頭を参照。
    define('GEN_USE_S3', isset($config['storage']) && $config['storage'] == "S3");

    // ----- files_dir -----
    // files_dir に配置するファイルはすべて Gen_Storage を利用してアクセスする。
    // 　ローカルストレージのほか、S3に保存することもできるようにするため。
    // 　各カテゴリのデータがどちらに保存されるかについては Gen_Storage の冒頭を参照。
    define('GEN_FILES_DIR', $config['files_dir'] . "/");

    // ----- temp_dir -----
    if (!isset($config['temp_dir'])) {
        $config['temp_dir'] = $config['files_dir'];
    }
    $cacheDirBase = $config['temp_dir'] . "/" . $config['database'];
    // Smartyキャッシュ
    if (!is_dir($cacheDirBase . "/Smarty/")) {
        mkdir($cacheDirBase . "/Smarty/", 0770, true);
    }
    define('SMARTY_COMPILE_DIR', $cacheDirBase . "/Smarty/templates_c/");
    if (!is_dir(SMARTY_COMPILE_DIR)) {
        mkdir(SMARTY_COMPILE_DIR, 0770, true);
    }
    define('SMARTY_CONFIG_DIR', $cacheDirBase . "/Smarty/configs/");
    if (!is_dir(SMARTY_CONFIG_DIR)) {
        mkdir(SMARTY_CONFIG_DIR, 0770, true);
    }
    define('SMARTY_CACHE_DIR', $cacheDirBase . "/Smarty/cache/");
    if (!is_dir(SMARTY_CACHE_DIR)) {
        mkdir(SMARTY_CACHE_DIR, 0770, true);
    }
    // 13iまでは GEN_TEMP_DIR は共通（gen_server_config.ymlで指定）だったが、
    // 複数ユーザーの一時ファイルが同一ディレクトリに混在することの危険性を考え、
    // temp_dir に移動した。
    define('GEN_TEMP_DIR', $cacheDirBase . "/Temp/");
    if (!is_dir(GEN_TEMP_DIR)) {
        mkdir(GEN_TEMP_DIR, 0770, true);
    }
    // tcpdf
    // tcpdfは、tcpdf/cache 内に画像表示用のキャッシュを保存する。
    // 15iでは、tcpdf本体に手を入れ、temp_dir にキャッシュを保存するようにした。
    //      tcpdf/config/tcpdf_config.php の「Gen Hack」の部分
    // ちなみに、13i以前への適用も検討したが、見送った。
    // 　修正しなくても問題が起きる可能性は小さいし（※1）この件が発覚した時点ですでにカスタマイズ版を
    // 　含めたDFC適用作業がかなり進行しており、再修正は大変なため。
    // 　動的ファイルを（smartyキャッシュ等も含め）すべて切り出したのに、これだけが残るという気持ち悪さ
    // 　はあるが。
    // 　※1キャッシュ生成とDLが一度に行われる限りは問題ない。以前に生成されたキャッシュを使いまわす
    //    　なら問題ありそうだが、恐らくtcpdf側で「キャッシュがなければ再作成する」という処理をしている
    //    　だろう。
    define('GEN_TCPDF_CACHE_DIR', $cacheDirBase . "/tcpdf/");
    if (!is_dir(GEN_TCPDF_CACHE_DIR)) {
        mkdir(GEN_TCPDF_CACHE_DIR, 0770, true);
    }

    // Amazon S3
    define('GEN_S3_BUCKET', $config['S3']['bucket']);
    // 以下の3項目は AWS SDK for PHP 2 (PHP5.3以上）を使用したときのみ有効。
    // 現状は AWS SDK for PHP 1 を使用しているため未使用。
    // 詳細は Components/S3.class.php の冒頭を参照。
    define('GEN_S3_KEY', $config['S3']['key']);
    define('GEN_S3_SECRET', $config['S3']['secret']);
    define('GEN_S3_REGION', $config['S3']['region']);


    if ($bench)
        $timer->setMarker('setting file read');

    //------------------------------------------------------
    // 各種設定（環境非依存で、ほとんど変更する必要がないもの）
    //------------------------------------------------------

    // 管理者ユーザー名
    define("ADMIN_LOGIN_ID", "admin");
    define("ADMIN_NAME", "e-commode");

    // カスタム項目の数（これを増やすときはスキーマ変更が必要）
    define('GEN_CUSTOM_COLUMN_COUNT', 10);

    // 一括編集の最大レコード数（この回数分アクションリダイレクトを繰り返すので、サーバー負荷に注意）
    define('GEN_MULTI_EDIT_COUNT', 50);

    // セッションの有効期限（最終アクセスからタイムアウトまでの時間。sec）
    //  詳しくはこのコード内の session.gc_maxlifetime の説明を参照。
    define ('GEN_SESSION_TIMEOUT', 7200);   // 120分

    // デフォルトAction（actionが指定されていなかったときに実行されるaction）
    define('LOGIN_ACTION', 'Login');

    // ファイルパスのセパレータ
    define('SEPARATOR', '/');    // Winは本来「\\」だが、このままでも問題ないようだ。

    // ディレクトリ設定（アプリケーション）
    define('ROOT_DIR', dirname(dirname(__FILE__)) . SEPARATOR);    // このファイルの1階層上。
    define('APP_DIR', dirname(__FILE__) . SEPARATOR);              // このファイルと同じ階層。
    define('BASE_DIR', ROOT_DIR . 'Base' . SEPARATOR);
    define('COMPONENTS_DIR', ROOT_DIR . 'Components' . SEPARATOR);
    define('LOGIC_DIR', ROOT_DIR . 'Logic' . SEPARATOR);
    define('DOWNLOAD_DIR', ROOT_DIR . 'Download' . SEPARATOR);
    define('SYSTEM_REPORT_TEMPLATES_DIR', ROOT_DIR . 'ReportTemplates' . SEPARATOR);
    define('REPORT_TEMPLATES_URL', 'ReportTemplates' . SEPARATOR);
    define('GEN_IS_BACKGROUND', $config['is_background_image']);
    if (GEN_IS_BACKGROUND) {
        define('BACKGROUND_IMAGE_URL', "{$config['background_image']['url']}/");
        define('BACKGROUND_IMAGE_PATH', dirname(dirname(dirname(__FILE__))) . SEPARATOR . $config['background_image']['path'] . SEPARATOR);
        define('BACKGROUND_IMAGE_DIR', $config['background_image']['dir']);
    }

    // ディレクトリ設定（PEAR）
    define('PEAR_DIR', ROOT_DIR . 'pear' . SEPARATOR);

    // ディレクトリ設定（Smarty）
    define('SMARTY_DIR', ROOT_DIR . 'smarty' . SEPARATOR);
    define('SMARTY_TEMPRATE_DIR', ROOT_DIR . 'tpl' . SEPARATOR . 'templates' . SEPARATOR);

    //------------------------------------------------------
    // PHP動作設定（php.iniの設定を上書き）
    //------------------------------------------------------

    // エラー時にメッセージを画面表示するかどうか。
    if (GEN_DISPLAY_ERRORS === true) {
        ini_set('display_errors', 'On');
    } else {
        ini_set('display_errors', 'Off');
    }
    // include_path に PEARディレクトリを追加
    ini_set('include_path', PEAR_DIR . ini_get('include_path'));
    // PHP 5.1.0 以降で利用可能なパラメータ。これを設定しないとMRPでコケる
    // Logic_ExecMrpでも設定している
    ini_set('date.timezone', 'Asia/Tokyo');
    // PHPソースの文字コードに合わせる
    ini_set('mbstring.internal_encoding', 'UTF-8');
    ini_set('mbstring.substitute_character', 'none');
    // autoだとたまに問題が生じるため指定。JISやASCIIを後ろの方にするのがポイント
    ini_set('mbstring.detect_order', 'SJIS,UTF-8,EUC-JP,JIS,ASCII');
    // エラーログの設定
    ini_set('log_errors', 'on');
    ini_set('log_errors_max_len', '4096');
    // SESSIONの生存期間(sec。デフォルトは1440 = 24分)。
    //  この期間を超えたSESSIONは session_startの際に1/100の確率で消去される。
    //  ※ あるサイトでGC（期限切れセッション消去処理が作動）したら、デフォルトでは同じサーバー上にあるすべてのサイトの
    //  　　期限切れセッションが消去される。つまり、1/100の確率というのはサーバー上の全サイトのアクセスに対する確率。
    //      session.save_path の変更で、サイト別にすることもできる。
    //  ※ 確率（1/100）は session.gc_probability、session.gc_divisor で変更可能。
    //  ※ ちなみにphp.iniで session.cookie_lifetime が0以外に設定されているときは、そちらが優先される（デフォルトは0）。
    //  13iでは 5400 = 90分に設定されていた。
    //  （13iの途中までは7200だったが、セコムのセキュリティ診断で「2時間放置してもセッションが切れない」という指摘があり短縮された）
    //  そのためアクセスの多いサーバーでは、一日に何度もセッション切れが発生する可能性があった。
    //  15iではこの期間を延長し、その代わりに*最終アクセス*から一定時間が経過したらセッションが切れるようにした。
    //  「GEN_SESSION_TIMEOUT」を参照。
    ini_set("session.gc_maxlifetime", "86400");     // 24H。GEN_SESSION_TIMEOUT よりも長くすること
    // Cookieのセキュア属性設定
    if (GEN_COOKIE_SECURE !== false)
        ini_set('session.cookie_secure', 1);
    // bcmathのスケール設定
    ini_set("bcmath.scale", 8);
    // fgets() や file() で読み込まれたデータの改行コードがCRであっても、改行を正しく判断できるようにするための設定。
    // https://gw.genesiss.jp/15i_e-commode/index.php?action=Menu_Chat&chat_detail_id=3969
    // http://jp2.php.net/manual/ja/filesystem.configuration.php#ini.auto-detect-line-endings
    ini_set("auto_detect_line_endings", true);

    // 以下の項目はスクリプトでは設定不可。php.iniで設定すること
    //    register_globals = Off            ; セキュリティ上の理由でOff
    //    output_buffering = Off            ; Off推奨
    //    magic_quotes_gpc = Off            ; Onだと文字化け等の原因になる。クオートは自前で
    //    mbstring.language = Japanese     ; mb_send_mailでのみ使用される。ほとんど無意味
    //
    //    ; 自動変換は文字コード判定に失敗し文字化けの原因になることがあるので無効とする。
    //    ; UTF-8を基準とし、それ以外を扱うときはmb_関数で明示的にコード変換を行う
    //    mbstring.encoding_translation = Off
    //    mbstring.http_input = pass;
    //    mbstring.http_output = pass;
    //
    //    ; ファイルアップロード機能
    //    file_uploads = On                ; 有効化（デフォルト）
    //    upload_max_filesize = 10M        ; 必要に応じサイズ調整（デフォ2M）
    //    post_max_size = 10M                ; upload_max_filesizeより大きくしておく（デフォ8M）
    //    max_input_time = -1                ; 時間制限。デフォの-1でいいだろう
    //
    //------------------------------------------------------
    // autoload
    //------------------------------------------------------
    spl_autoload_register('gen_autoload');

    //------------------------------------------------------
    // Action名の取得
    //------------------------------------------------------
    // Actionが指定されていなければ、ログイン画面に飛ばす

    $action = "";
    if (isset($_REQUEST['action'])) {
        $action = $_REQUEST['action'];
    }

    if ($action == "") {
        $action = LOGIN_ACTION;
    }

    if ($bench)
        $timer->setMarker('init');


    //------------------------------------------------------
    // セッションの開始
    //------------------------------------------------------
    set_time_limit(300);
    session_start();

    if ($bench)
        $timer->setMarker('session_start');

    //------------------------------------------------------
    // ファイルダウンロードモードのブラウザキャッシュの処理
    //------------------------------------------------------
    // ブラウザキャッシュについては、Gen_Download のコメントも参照。
    if ($action == 'download' && isset($_REQUEST['cat'])) {
        switch($_REQUEST['cat']) {
            //  ブラウザキャッシュが必要なカテゴリ（頻繁に表示される画像など）の場合、ここに最終更新日時の取得処理を記述する。
            //  ここに記述されていないカテゴリはブラウザキャッシュが行われない。
            //  重要：　ここに記述したカテゴリについては、必ず出力時のURLに gen_setting_company/user の XXXLastMod を strtotimeしたものを付与すること。
            //          　tplでの例：index.php?action=download&cat=profileimage&{$smarty.session.gen_setting_user->profileImageLastMod|strtotime}
            //          そうしないと、ファイルを変更してもブラウザキャッシュが優先されてなかなか更新されない、という現象が起きてしまう。
            //          また、ファイル削除時には XXXLastMod を unsetしておくこと。
            case 'companylogo':
                // file_exists しているのは、他ユーザーがロゴ変更した場合のため。gen_setting_company は他ユーザーによる書き換えが再ログイン時まで反映されない
                $storage = new Gen_Storage("CompanyLogo");
                if (isset($_SESSION['gen_setting_company']->companyLogoFileLastMod) && $storage->exist($_SESSION['gen_setting_company']->companyLogoFile))
                    $lastModified = strtotime($_SESSION['gen_setting_company']->companyLogoFileLastMod);
                break;
            case 'profileimage':
                $storage = new Gen_Storage("ProfileImage");
                if (isset($_SESSION['gen_setting_user']->profileImageLastMod) && $storage->exist($_SESSION['gen_setting_user']->profileImage))
                    $lastModified = strtotime($_SESSION['gen_setting_user']->profileImageLastMod);
                break;
            case 'jsgettext':
                Gen_String::initGetText();  // languageの取得とmessages.jsonファイルの更新
                $language = $_SESSION['gen_language'];
                $lastModPropertyName = "jsGetText{$language}LastMod";
                $storage = new Gen_Storage("JSGetText");
                if (isset($_SESSION['gen_setting_company']->$lastModPropertyName) && $storage->exist($language . "/messages.json"))
                    $lastModified = $_SESSION['gen_setting_company']->$lastModPropertyName;
                break;
        }
        ///もしキャッシュ更新確認リクエストなら
        if (isset($lastModified)) {
            if (isset($_SERVER["HTTP_IF_MODIFIED_SINCE"])){
                $ifModSince = strtotime($_SERVER["HTTP_IF_MODIFIED_SINCE"]);
                if($ifModSince == $lastModified){
                    // ファイル更新がなければ、キャッシュ有効を返す。
                    header("HTTP/1.1 304 Not Modified");
                    exit;
                }
            }
        }
    }

    //------------------------------------------------------
    // XSS対策
    //------------------------------------------------------
    // http://www.atmarkit.co.jp/ait/articles/1403/24/news005_2.html

    header("X-Content-Type-Options: nosniff");

    //------------------------------------------------------
    // データベース コネクション作成
    //------------------------------------------------------

    $gen_db = new Gen_Db();
    if ($bench)
        $timer->setMarker('db init');

    $gen_db->connect();
    if ($bench)
        $timer->setMarker('connect');

    //------------------------------------------------------
    // company_setting の読み出し
    //------------------------------------------------------
    // 15iで追加。
    // 13iまではログイン時のみ読み出しを行っていたが、それだと他ユーザーが company_setting を更新した場合、
    // それが即時反映されない。15iではカスタム項目や名称変換などに company_setting を使用するようになった
    // ため、それでは問題が生じるようになった。
    // ちなみにこの処理はデータベースコネクション作成より後に行う必要があるので、ここで実行している。
    // ここより前のファイルダウンロード処理のところでも company_setting を使用しているが、そこについては
    // 即時反映でなくてもよいと判断した。
    // また、この処理はログインの際は実行しないようにした。スキーマバージョン 2014010134以前のバックアップを
    // 読み込んだ直後、スキーマ自動更新が行われる前にエラーになるのを防ぐため。
    // ログイン時にはログイン処理内で読み出しが行われる。
    if ($action != LOGIN_ACTION && $action != 'Logout' && isset($_SESSION["gen_setting_company_update_time"])) {
        $query = "select company_setting_update_time from company_master";
        $companySettingUpdateTime = $gen_db->queryOneValue($query);
        if (strtotime($_SESSION["gen_setting_company_update_time"]) < strtotime($companySettingUpdateTime)) {
            Gen_Setting::loadCompanySetting();
        }
    }

    //------------------------------------------------------
    // ファイルダウンロードモード
    //------------------------------------------------------
    if ($action == 'download' && isset($_REQUEST['cat'])) {
        // セッションチェック
        $sessionRes = Gen_Auth::sessionCheck("");
        // jsgettextだけは非ログイン状態でも取得できる必要がある。（ログイン画面用。とくにiPhone版）
        if (($sessionRes == -1 || $sessionRes == -2 || $sessionRes == -3) && $_REQUEST['cat'] != "jsgettext") {
            throw new Exception();
        } else {
            // ダウンロード処理
            $downloadFile = "";
            switch($_REQUEST['cat']) {
                // ブラウザキャッシュが必要なカテゴリ（頻繁に表示される画像など）の場合、ここだけではなく
                // 上の「ブラウザキャッシュの処理」部分にも処理を記述しておくこと。

                case 'files':
                    $storage = new Gen_Storage("Files");
                    $downloadFile = $storage->get($_REQUEST['file']);
                    if (file_exists($downloadFile)) {
                        $quoteFile = $gen_db->quoteParam($_REQUEST['file']);
                        $query = "select action_group, original_file_name from upload_file_info
                            where file_name = '{$quoteFile}'";
                        $obj = $gen_db->queryOneRowObject($query);
                        if ($obj) {
                            $downloadName = $obj->original_file_name;
                            // アクセス権チェック
                            $actionGroup = strtolower($obj->action_group);
                            if (substr($actionGroup, -1) == '_')
                                    $actionGroup = substr($actionGroup, 0, strlen($actionGroup) - 1);
                            $sessionRes = Gen_Auth::sessionCheck($actionGroup);
                            if ($sessionRes != 1 && $sessionRes != 2) {
                                throw new Exception();
                            }
                        }
                    } else {
                        // ファイルが実際には存在しなかった場合。
                        // 本来は例外処理するべきだが、開発段階では起こりうることなので die にしてある。
                        die();
                        //throw new Exception();
                    }
                    break;
                case 'chatFiles':
                    $storage = new Gen_Storage("ChatFiles");
                    $downloadFile = $storage->get($_REQUEST['file']);
                    if (file_exists($downloadFile)) {
                        $userId = Gen_Auth::getCurrentUserId();
                        $quoteFile = $gen_db->quoteParam($_REQUEST['file']);
                        // 該当ファイルがアップされたスレッドのメンバーになっているユーザーのみ
                        $query = "select original_file_name from chat_detail
                            inner join chat_user on chat_detail.chat_header_id = chat_user.chat_header_id and chat_user.user_id = '{$userId}'
                            where file_name = '{$quoteFile}'";
                        $obj = $gen_db->queryOneRowObject($query);
                        if ($obj) {
                            $downloadName = $obj->original_file_name;
                        } else {
                            throw new Exception();
                        }
                    } else {
                        throw new Exception();
                    }
                    break;
                case 'companylogo':
                    // アクセス権チェックは無し（セッションがあればOK）
                    if (!isset($_SESSION['gen_setting_company']->companyLogoFile)) {
                        $query = "select image_file_name from company_master";
                        $_SESSION['gen_setting_company']->companyLogoFile = $gen_db->queryOneValue($query);
                        Gen_Setting::saveSetting();
                    }
                    if ($_SESSION['gen_setting_company']->companyLogoFile == "") {
                        $downloadFile = "img/space.gif";
                        $downloadName = "space.gif";
                    } else {
                        $storage = new Gen_Storage("CompanyLogo");
                        $downloadName = $_SESSION['gen_setting_company']->companyLogoFile;
                        $downloadFile = $storage->get($downloadName);
                        if (!file_exists($downloadFile)) {
                            $downloadFile = "img/space.gif";
                            $downloadName = "space.gif";
                        }
                    }
                    break;
                case 'itemimage':
                    // アクセス権チェックは無し（セッションがあればOK。製造指示書帳票などを考慮して、品目画像は品目マスタへのアクセス権がなくてもDLできることとした）
                    $storage = new Gen_Storage("ItemImage");
                    $downloadFile = $storage->get($_REQUEST['file']);
                    if (!file_exists($downloadFile)) {
                        throw new Exception();
                    }
                    $imageFile = $gen_db->quoteParam($_REQUEST['file']);
                    $query = "select original_image_file_name from item_master where image_file_name = '{$imageFile}'";
                    $downloadName = $gen_db->queryOneValue($query);
                    break;
                case 'jsgettext':
                    // アクセス権チェックは無し（セッションがあればOK）
                    $storage = new Gen_Storage("JSGetText");
                    $downloadFile = $storage->get($language . "/messages.json");
                    $downloadName = "messages.json";
                    break;
                case 'profileimage':
                    // 自分のプロフィール画像
                    if (!isset($_SESSION['gen_setting_user']->profileImage)) {
                        $userId = Gen_Auth::getCurrentUserId();
                        if ($userId == -1) {    // adminはプロフィール画像なし
                            $_SESSION['gen_setting_user']->profileImage = "";
                        } else {
                            $query = "select image_file_name from user_master where user_id = '{$userId}'";
                            $_SESSION['gen_setting_user']->profileImage = $gen_db->queryOneValue($query);
                        }
                    }
                    if ($_SESSION['gen_setting_user']->profileImage == "") {
                        $downloadFile = "img/user1.png";
                        $downloadName = "user1.png";
                    } else {
                        $storage = new Gen_Storage("ProfileImage");
                        $downloadName = $_SESSION['gen_setting_user']->profileImage;
                        $downloadFile = $storage->get($downloadName);
                        if (!file_exists($downloadFile)) {
                            $downloadFile = "img/user1.png";
                            $downloadName = "user1.png";
                        }
                    }
                    break;
                case 'userprofileimage':
                    // 他ユーザーのプロフィール画像
                    // アクセス権チェックは無し（セッションがあればOK）
                    $downloadName = "";
                    if (isset($_REQUEST['userId']) && Gen_String::isNumeric($_REQUEST['userId'])) {
                        $imageUserId = $gen_db->quoteParam($_REQUEST['userId']);
                        $query = "select image_file_name from user_master where user_id = '{$imageUserId}'";
                        $downloadName = $gen_db->queryOneValue($query);
                    }
                    if ($downloadName == "") {
                        $downloadFile = "img/user1.png";
                        $downloadName = "user1.png";
                    } else {
                        $storage = new Gen_Storage("ProfileImage");
                        $downloadFile = $storage->get($downloadName);
                        if (!file_exists($downloadFile)) {
                            $downloadFile = "img/user1.png";
                            $downloadName = "user1.png";
                        }
                    }
                    if ($downloadName == "" || !file_exists($downloadFile)) {
                        $downloadFile = "img/user1.png";
                        $downloadName = "user1.png";
                    }
                    break;
                case 'reporttemplate':
                    // Gen_Storageを介さずに動的に（ユーザー指定のパラメータを使って）ファイルパスを決めてアクセスする場合、
                    // ディレクトリトラバーサル対策のため必ず　Gen_File::safetyPath() を使用する必要がある。
                    $downloadFile = Gen_File::safetyPath(SYSTEM_REPORT_TEMPLATES_DIR . $_REQUEST['repcat'], $_REQUEST['file']);
                    if (!file_exists($downloadFile)) {
                        $storage = new Gen_Storage("ReportTemplates");
                        $downloadFile = $storage->get($_REQUEST['repcat'] . "/" . $_REQUEST['file']);
                    }
                    if (!file_exists($downloadFile)) {
                        throw new Exception();
                    }
                    $downloadName = $_REQUEST['file'];
                    break;
                default:
                    throw new Exception("cat不正");
            }
            if ($downloadFile == "" || !file_exists($downloadFile)) {
                $downloadFile = "img/space.gif";
                $downloadName = "space.gif";
            }
            Gen_Download::DownloadFile($downloadFile, $downloadName, false, isset($lastModified) ? $lastModified : null);
        }
    }

    //------------------------------------------------------
    // $formの初期化
    //------------------------------------------------------

    $form = array();


    //------------------------------------------------------
    // $formに定数をセット
    //------------------------------------------------------
    // Actionクラス内なら$formにセットしなくても定数を利用できるが、
    // .tplでの利用のためにセットしておく。

    $form['GEN_DECIMAL_POINT_LIST'] = GEN_DECIMAL_POINT_LIST;
    $form['GEN_DECIMAL_POINT_EDIT'] = GEN_DECIMAL_POINT_EDIT;
    $form['GEN_DECIMAL_POINT_REPORT'] = GEN_DECIMAL_POINT_REPORT;
    $form['GEN_DECIMAL_POINT_EXCEL'] = GEN_DECIMAL_POINT_EXCEL;
    $form['GEN_DECIMAL_POINT_DROPDOWN'] = GEN_DECIMAL_POINT_DROPDOWN;

    if ($bench)
        $timer->setMarker('const');

    //------------------------------------------------------
    // エラーハンドラの設定。
    //------------------------------------------------------
    //  これを設定することにより、php.iniの設定にかかわらず、Fatal（致命的）エラー以外の
    //  エラーの画面表示と自動ログが行われなくなる。error_reportingの設定にかかわらず、Fatalを除く全レベルのエラーと警告が捕捉される。
    if (isset($_SESSION['gen_setting_user']->demo_mode) && $_SESSION['gen_setting_user']->demo_mode)
        set_error_handler("demoErrorHandler");

    if ($bench)
        $timer->setMarker('form init');

    //------------------------------------------------------
    // iPhone / iPad
    //------------------------------------------------------

    $Agent = $_SERVER['HTTP_USER_AGENT'];
    $form['gen_iPad'] = preg_match("/iPad/", $Agent);
    $form['gen_iPhone'] = preg_match("/iPhone/", $Agent);
    $form['gen_mobile'] = $form['gen_iPhone'];     // for mobile

    //------------------------------------------------------
    // actionの実行
    //------------------------------------------------------
    // 実行時間設定
    $_SESSION['access_time'] = date('Y-m-d H:i:s');

    // 戻り値が「action:」だったときにはActionリダイレクトになるので、
    // ループにしている
    $actionChainCount = 0;    // バグによりActionリダイレクト無限ループになったときの対処

    while (true) {

        //------------------------------------------------------
        // セッションとパーミッションのチェック
        //------------------------------------------------------
        // actionリダイレクトの際にもそのつどチェックする必要があるので、ループ内で行う
        //
        // ログイン・ログアウトのほか、通知メール本登録もセッションチェックしない
        //　　携帯からのアクセスを考えると、ログインしていなくても本登録できるようにしておかないと不便
        $sessionError = false;
        if ($action != 'Login' && $action != 'Logout' && $action != 'Master_AlertMail_Regist') {
            // クラスグループ（actionクラス名の第2階層まで。Master_Item_xxx なら Master_Item）
            $actionNameSep = explode("_", $action);

            if (count($actionNameSep) >= 2) {
                $classGroup = $actionNameSep[0] . "_" . $actionNameSep[1];
            } else {
                $classGroup = $action;
            }

            // 戻り値  -1: セッション不正  0: アクセス権限なし  1: 読み取りのみ  2: 読み書き可能
            // 管理者ユーザー（admin。ユーザー名はAuthの冒頭で定義）は全画面読み書き可能の結果が返ってくる
            $sessionRes = Gen_Auth::sessionCheck(strtolower($classGroup));
            if ($actionNameSep[0] == 'Dropdown'
                    || $actionNameSep[1] == 'ForTest'
                    || ($actionNameSep[0] == 'SystemUtility' && $actionNameSep[1] == 'ShowError')
                    || ($actionNameSep[0] == 'Config' && $actionNameSep[1] == 'Setting')
                    || ($actionNameSep[0] == 'Config' && $actionNameSep[1] == 'Download')
                    || ($actionNameSep[0] == 'Master' && $actionNameSep[1] == 'Holiday' && $actionNameSep[2] == 'AjaxHolidayRead')
                    || ($form['gen_mobile'] && $actionNameSep[0] == 'Mobile' && $actionNameSep[1] == 'Common')     // for mobile
                    || ($form['gen_mobile'] && $actionNameSep[0] == 'Mobile' && $actionNameSep[1] == 'ShowError')     // for mobile
            ) {
                // アクセス権チェックなし。セッションのみチェック
                if ($sessionRes == -1 || $sessionRes == -2 || $sessionRes == -3) {
                    $action = LOGIN_ACTION;
                    $sessionError = true;
                }
            } else {
                // アクセス権チェックあり
                switch ($sessionRes) {
                    case 0:     // アクセス権限なし（「アクセス権限がありません」画面へ飛ばす）
                        if ($form['gen_mobile']) {
                            $action = "Mobile_ShowError_PermissionError";  // for mobile
                        } else if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                            $action = "SystemUtility_ShowError_AjaxPermissionError";    // for ajax
                        } else {
                            $action = "SystemUtility_ShowError_PermissionError";
                        }
                        break;
                    case 1:     // 読み取りのみ
                        $form['gen_readonly'] = true;
                        //  editやdetailで表示されるメッセージ。
                        //  各クラスで上書きされる場合がある（アクセス権以外の理由でreadonlyを設定している場合）ことに注意。
                        $form['gen_readonlyMessage'] = _g("登録を行う権限がありません。");
                        break;
                    case 2:     // 読み書き可能
                        $form['gen_readonly'] = false;
                        break;
                    case -2:    // セッションIDなし（たいていの場合は、同一アカウントで他のデバイス/ブラウザからログインしたため）
                    case -3:    // タイムアウト
                        // リスト画面での再表示ボタン押下にも対応するためjavascriptでリダイレクト処理。
                        $script = "<script type=\"text/javascript\">";
                        $script .= "var url = 'index.php?action=" . LOGIN_ACTION . "&" . ($sessionRes == -2 ? "gen_concurrent_error" : "gen_session_timeout") . "=true';";
                        $script .= "location.href = url;";
                        $script .= "</script>";
                        print($script);
                        exit();
                        $deviceToken = (isset($form['deviceToken']) ? $form['deviceToken'] : false);
                        Gen_Auth::logout($deviceToken);
                        $action = LOGIN_ACTION;
                        $sessionError = true;
                        break;
                    default:    // セッション不正（ログイン画面へ飛ばす）
                        $action = LOGIN_ACTION;
                        $sessionError = true;
                        break;
                }
            }
        }

        // ajaxリクエストがセッションエラーになった場合、ログイン画面に飛ばすのではなく、エラーフラグを返す
        if ($sessionError && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            $action = "SystemUtility_ShowError_AjaxSessionError";
        }

        if ($bench)
            $timer->setMarker('session check');

        //------------------------------------------------------
        //  i18n (getText)
        //------------------------------------------------------
        // actionループの前に実行したほうが効率良く思えるが、それだとログイン直後にうまくいかない（Loginからactionリダイレクトされるので）
        // サポートする言語が増えたときは、Gen_String::initGetText() 冒頭のリストを変更

        Gen_String::initGetText();

        if ($bench)
            $timer->setMarker('getText');

        //------------------------------------------------------
        // 実行するActionクラスファイルを決定
        //------------------------------------------------------

        $path = APP_DIR . str_replace('_', SEPARATOR, $action) . ".class.php";
        // ファイルが存在しなければ、ログイン画面に飛ばす
        if (!file_exists($path)) {
            $path = APP_DIR . str_replace('_', SEPARATOR, LOGIN_ACTION) . ".class.php";
            $action = LOGIN_ACTION;
        }
        if (!file_exists($path)) {
            throw new Exception("DEFAULT_ACTIONの設定が正しくありません。");
        }

        //------------------------------------------------------
        // Actionクラスのインスタンスを作成
        //------------------------------------------------------

        require_once(Gen_File::safetyPathForAction($action));
        $actionClass = new $action;

        //------------------------------------------------------
        // Actionクラスに渡す引数（Form値をデータベースサニタイジングしたもの）を準備する
        //------------------------------------------------------
        //
        // エクセルからのPOSTの場合、パラメータはurlencodeされたSJIS文字列である。
        // SJIS文字列に対しそのままquoteParam()すると一部の文字（「表」など）が文字化けしてしまう。
        // そこで、先にurldecodeとSJIS->UTF変換してからquoteする。
        //
        // ここで文字コード変換してしまうため、機種依存文字チェックは行えない。
        // エクセル側でチェックしておくこと。
        // gen_excel : 登録時使用
        // gen_sjis : ログイン時使用

        $isSjis = (isset($_REQUEST['gen_excel']) && $_REQUEST['gen_excel'] == "true") ||
                (isset($_REQUEST['gen_sjis']) && $_REQUEST['gen_sjis'] == "true");

        if (isset($_GET)) {
            foreach ($_GET as $key => $value) {
                // magic_quotes_gpc が On の場合、mb_convert_encoding(stripslashes(urldecode ..
                // とする必要がある。POSTも同様。
                if ($isSjis) {
                    // rev. 20130215 でExcelファイルの接続箇所も合わせて改善。
                    //$key = mb_convert_encoding(urldecode($key), 'UTF-8', 'sjis-win');
                    //$value = mb_convert_encoding(urldecode($value), 'UTF-8', 'sjis-win');
                    $key = mb_convert_encoding($key, 'UTF-8', 'sjis-win');
                    $value = mb_convert_encoding($value, 'UTF-8', 'sjis-win');
                }
                $form[$key] = $gen_db->quoteParam($value);
            }
        }
        if (isset($_POST)) {
            foreach ($_POST as $key => $value) {
                if ($isSjis) {
                    // rev. 20130215 でExcelファイルの接続箇所も合わせて改善。
                    //$key = mb_convert_encoding(urldecode($key), 'UTF-8', 'sjis-win');
                    $key = mb_convert_encoding($key, 'UTF-8', 'sjis-win');
                    // $value値をurldecodeすると'+'が半角空欄に変換されてしまう。
                    // 明確な動作が不明だがurldecodeされた$value値が渡されているようである。
                    $value = mb_convert_encoding($value, 'UTF-8', 'sjis-win');
                }
                $form[$key] = $gen_db->quoteParam($value);
            }
        }

        // ListBase, EditBase, EntryBase用。
        // 以前はもっと前の箇所で設定していたが、ユーザーが $form['gen_action_group'] や $form['gen_sctipt_path']
        // に任意の値を設定できてしまう（ローカルファイルインクルード攻撃が可能）問題があったため、$formの設定処理の後に
        // 行うようにした。
        if (isset($actionNameSep)) {
            $form['gen_action_group'] = $actionNameSep[0] . "_" . $actionNameSep[1] . "_";
            $form['gen_sctipt_path'] = APP_DIR . $actionNameSep[0] . SEPARATOR . $actionNameSep[1] . SEPARATOR;
        }

        //------------------------------------------------------
        // リロード対策
        //------------------------------------------------------
        // リロードチェック用メソッドが定義されていれば、それを呼び出す。
        // 戻り値（適用tplもしくはActionRedirect先）がセットされていればリロードエラーと
        // みなし、後の処理（validator, converter, execute）はスキップする。
        // リロードチェックは、validatorより前に行う必要がある。
        // （リロードを検出する前にvalidatorエラーになるのを避けるため）

        $reloadError = false;
        if (method_exists($actionClass, 'reloadCheck')) {
            if (($actionRes = $actionClass->reloadCheck($form)) != "") {
                $reloadError = true;
            }
        }

        if ($bench)
            $timer->setMarker('reload check');

        //------------------------------------------------------
        // admin用の用語変換モード
        //------------------------------------------------------

        if (isset($form['gen_adminWordConvertMode'])) {
            switch($form['gen_adminWordConvertMode']) {
                case 1: $_SESSION['adminWordConvertMode'] = 1; break;
                case 2: $_SESSION['adminWordConvertMode'] = 2; break;
                default: unset($_SESSION['adminWordConvertMode']);
            }
        }

        //------------------------------------------------------
        // カスタム項目
        //------------------------------------------------------
        // 入出庫画面にはカスタム項目が存在するが、使用数リストにだけは表示させない。
        if (isset($classGroup) && !($classGroup == "Stock_Inout" && isset($form['classification']) && $form['classification'] == "use")) {
            $customColumnArr = Logic_CustomColumn::getCustomColumnParamByClassGroup($classGroup);
            if ($customColumnArr) {
                $form['gen_customColumnClassGroup'] = $classGroup;
                $form['gen_customColumnTable'] = $customColumnArr[0];
                $form['gen_customColumnArray'] = $customColumnArr[1];
                $form['gen_customColumnDetailTable'] = $customColumnArr[2];
            }
        }

        //------------------------------------------------------
        // コンバータ
        //------------------------------------------------------

        if (method_exists($actionClass, 'convert') && !$reloadError) {
            $converter = new Gen_Converter($form);
            $actionClass->convert($converter, $form);
        }

        if ($bench)
            $timer->setMarker('converter');

        //------------------------------------------------------
        // バリデーション
        //------------------------------------------------------

        $validError = false;
        if (method_exists($actionClass, 'validate') && !$reloadError) {
            if (isset($validator)) {
                // validatorが既存のとき、つまりActionリダイレクトのとき。
                // 前回のValidatorはとっておき、別Validatorを作る（前Actionのエラーメッセージを消してしまわないために）
                $validator2 = new Gen_Validator($form);
                $actionRes = $actionClass->validate($validator2, $form);    // 戻り値がエラー時の適用テンプレート（もしくはActionリダイレクト先）になる

                // 今回validエラーなら、前Actionのvalidエラーは上書きする。
                // 今回エラーなしなら、前Actionのvalidエラーをそのままにする。
                // validエラーフラグ（$validError）は、前Actionではなく、今回のエラー状態を
                //    あらわすことに注意。たとえば前Actionでエラーで、今回エラーなしの場合、
                //  $validatorはエラーを持っているが、$validErrorはfalseである（つまりこの
                //    あとのexecuteは実行される）
                if ($validError = $validator2->hasError()) {
                    $validator = $validator2;
                }
            } else {
                // 初回実行のとき
                $validator = new Gen_Validator($form);
                $actionRes = $actionClass->validate($validator, $form);    // 戻り値がエラー時の適用テンプレート（もしくはActionリダイレクト先）になる
                $validError = $validator->hasError();
            }

            if ($validError) {
                $form['gen_validError'] = true;
            }
        }

        if ($bench)
            $timer->setMarker('validator');

        //------------------------------------------------------
        // Actionクラスのexecuteメソッドを実行
        //------------------------------------------------------
        //　バリデーションエラーやリロードエラーのときは実行しない

        if (!$validError && !$reloadError) {
            $actionRes = $actionClass->execute($form);
        }

        if ($bench)
            $timer->setMarker('execute');

        //------------------------------------------------------
        // Actionリダイレクトの処理
        //------------------------------------------------------
        // executeの戻りが action: であれば、別Actionへリダイレクトする
        //  Actionリダイレクトの際、$formは引き継がれることに注意

        if (substr($actionRes, 0, 7) == 'action:') {
            // バグによる無限ループ防止
            $actionChainCount++;
            $actionChainMax = 10;
            if (isset($form['gen_multiEditKey'])) {
                $actionChainMax = GEN_MULTI_EDIT_COUNT;
            }
            if ($actionChainCount > $actionChainMax) {
                throw new Exception("actionChainの実行回数が{$actionChainMax}回を超えました。無限ループに陥っている可能性があります。");
            }

            $action = substr($actionRes, 7);

            // actionリダイレクト後のメニュー表示の不具合を避けるための処理
            $form['action'] = $action;
            // unset($_REQUEST)では、GETやPOSTは消えない
            unset($_GET);
            unset($_POST);
        } else {
            break;
        }
    }

    if ($bench)
        $timer->setMarker('action redirect');

    //------------------------------------------------------
    // 適用するテンプレートを決定
    //------------------------------------------------------

    $tpl = str_replace('_', SEPARATOR, $actionRes);

    if (isset($_SESSION['session_id']) && $action != LOGIN_ACTION) {   // ログインではセッションIDが指定されていない

        $userId = Gen_Auth::getCurrentUserId();

        if ($tpl != "dropdown.tpl" && $tpl != "editmodal.tpl" && $tpl != "listtable.tpl"
                && $tpl != "login.tpl"  && $tpl != "simple.tpl") {

            //------------------------------------------------------
            // メニューバー
            //------------------------------------------------------

            // Stock_Inoutの特別処理
            $menuAction = @$form['action'];
            if ($menuAction == "Stock_Inout_List")
                $menuAction .= "&classification=" . @$form['classification'];
            if ($menuAction == "Stock_Inout_Import")
                $menuAction = "Stock_Inout_List&classification=" . @$form['classification'];
            $form['gen_actionForMenu'] = $menuAction;  // メニューバー、マイメニュー（common_header.tpl の JS）で使用。actionと同じだが、Stock_Inoutのみclassicationが追加されている

            // マイメニュー
            $myMenuHtml_noEscape = "";
            $isThisPageExist = false;
            if (isset($_SESSION['gen_setting_user']->myMenu)) {
                if ($_SESSION['gen_setting_user']->myMenu != "") {
                    $myMenuArr = explode(",", $_SESSION['gen_setting_user']->myMenu);
                    if (is_array($myMenuArr)) {
                        foreach ($myMenuArr as $myMenu) {
                            $myMenuSub = explode(":", $myMenu);
                            $isThisPage = ($myMenuSub[0] == $menuAction);
                            if ($isThisPage)
                                $isThisPageExist = true;
                            $myMenuSub[0] = h($myMenuSub[0]);
                            // myMenuの表示名は_g()しない。すでに_g()された状態の名称が登録されているため、ここで_g()すると2重になってしまう。
                            // ag.cgi?page=ProjectDocView&pid=1574&did=232309
                            //  そのため登録後のネームスイッチャー設定は反映されない。反映のためには再登録する必要がある。
                            $myMenuSub[1] = h($myMenuSub[1]);
                            // 下記のHTMLを変更するときは、gen_script.js の gen.myMenu.regist() も変更すること
                            $myMenuHtml_noEscape .= "<span id='gen_menu_{$myMenuSub[0]}' class='my_menu' style='margin-right:10px'>";
                            $myMenuHtml_noEscape .= "<a class='my_menu_link" . ($isThisPage ? "_selected" : "") . "' href='index.php?action={$myMenuSub[0]}' tabindex='-1' style='margin-right:10px'>{$myMenuSub[1]}</a>";
                            $myMenuHtml_noEscape .= "<a class='my_menu_link' href=\"javascript:delMyMenu('{$myMenuSub[0]}','{$myMenuSub[1]}')\" tabindex='-1'>☓</a>";
                            $myMenuHtml_noEscape .= "</span>";
                        }
                    }
                }
            }
            $form['gen_myMenuHtml_noEscape'] = $myMenuHtml_noEscape;
            $form['gen_isMyMenuExist'] = $isThisPageExist;

            // メニューバー
            $menu = new Logic_Menu();
            $menuArr = $menu->getMenuBarArray();
            $showMenuArr = array();
            if ($userId != -1) {
                $query = "select class_name from permission_master where user_id = '$userId' and permission in (1,2) and class_name ilike 'menu_%'";
                $topMenuArr = $gen_db->getArray($query);
            }
            foreach($menuArr as $menu) {
                // メニュー非表示カテゴリはスキップ
                if (!$menu[0][4])
                    continue;

                // カテゴリに対するアクセス権をチェック
                $isMenuDisable = false;
                if ($userId != -1 && $menu[0][0] != 'Logout') {
                    $isMenuDisable = true;
                    if ($topMenuArr) {
                        foreach($topMenuArr as $topMenu) {
                            if (strtolower($topMenu['class_name']) == strtolower($menu[0][0])) {
                                $isMenuDisable = false;
                                break;
                            }
                        }
                    }
                }

                $isMenuCurrent = false;
                $showSubMenuArr = null;
                if (!$isMenuDisable) {
                    // 現在のページがこのカテゴリに含まれているかどうか
                    if (count($menu)==1) {
                        // サブメニューなし
                        if ($form['gen_actionForMenu'] == $menu[0][0]) {
                            $isMenuCurrent = true;
                        }
                    } else {
                        // サブメニューあり
                        $showSubMenuArr = array();
                        foreach($menu as $key => $submenu) {
                            if ($key == 0)
                                continue;
                            // メニュー非表示項目はスキップ
                            if (!$submenu[4])
                                continue;
                            $mAct = $submenu[0] . ($submenu[1]=="" ? "" : "_{$submenu[1]}");
                            if ($form['gen_actionForMenu'] == $mAct) {
                                $isMenuCurrent = true;
                            }
                            $showSubMenuArr[] = array(
                                (isset($submenu[5]) && $submenu[5] ? "javascript:gen.modal.open('index.php?action={$mAct}')" : "index.php?action={$mAct}"),    // href
                                $submenu[2],   // title
                            );
                        }
                    }
                }
                // カテゴリメニュー作成
                $showMenuArr[] = array(
                    "index.php?action=" . $menu[0][0],    // href
                    $menu[0][2],   // title
                    $isMenuDisable,
                    $showSubMenuArr,
                    $isMenuCurrent,
                );
            }
            $form['gen_menuArr'] = $showMenuArr;

            if ($bench)
                $timer->setMarker('menubar');
        }

        if ($tpl != "dropdown.tpl" && $tpl != "login.tpl"  && $tpl != "simple.tpl") {
            //------------------------------------------------------
            // チャット
            //------------------------------------------------------

            $res = Gen_Auth::sessionCheck("menu_chat");
            $form['gen_allow_chat'] = ($res == 1 || $res == 2);
            if ($form['gen_allow_chat']) {

                // ダイアログ情報
                $query = "select case when show_chat_dialog then 1 else 0 end as show_chat_dialog, chat_dialog_x, chat_dialog_y, chat_dialog_width, chat_dialog_height, last_chat_header_id from ";
                if ($userId == -1) {
                    $query .= "company_master";
                } else {
                    $query .= "user_master where user_id = '{$userId}'";
                }
                $chatInfo = $gen_db->queryOneRowObject($query);
                if ($chatInfo) {
                    if ($action == "Menu_Chat") {
                        // チャットページではチャットダイアログ表示禁止（エレメントID重複などの問題あり）
                        $form['gen_show_chat_dialog'] = false;
                    } else if ($action == "Menu_Home") {
                        // ホームではチャットダイアログを常に表示
                        $form['gen_show_chat_dialog'] = true;
                    } else {
                        $form['gen_show_chat_dialog'] = $chatInfo->show_chat_dialog;
                    }
                    $form['gen_chat_dialog_x'] = $chatInfo->chat_dialog_x;
                    $form['gen_chat_dialog_y'] = $chatInfo->chat_dialog_y;
                    $form['gen_chat_dialog_width'] = $chatInfo->chat_dialog_width;
                    $form['gen_chat_dialog_height'] = $chatInfo->chat_dialog_height;
                    $form['gen_last_chat_header_id'] = $chatInfo->last_chat_header_id;
                }

                // 未読件数
                //  Config_Setting_AjaxChat の read部分にあるSQLと同じ
                $query = "
                select
                    count(case when not coalesce(chat_header.is_ecom,false) and not coalesce(chat_header.is_system,false) then chat_detail.chat_detail_id end) as unread
                    ,count(case when chat_header.is_ecom then chat_detail.chat_detail_id end) as unread_ecom
                    ,count(case when chat_header.is_system then chat_detail.chat_detail_id end) as unread_system
                from
                    chat_user
                    inner join chat_detail on chat_user.chat_header_id = chat_detail.chat_header_id
                        and coalesce(chat_user.readed_chat_detail_id,-1) < chat_detail.chat_detail_id
                        and chat_detail.user_id <> '{$userId}'
                    inner join chat_header on chat_user.chat_header_id = chat_header.chat_header_id
                where
                    chat_user.user_id = '{$userId}'
                ";

                $chatUnreadObj = $gen_db->queryOneRowObject($query);
                $chatUnreadCount = $chatUnreadObj->unread;
                $chatUnreadCountEcom = $chatUnreadObj->unread_ecom;
                $chatUnreadCountSystem = $chatUnreadObj->unread_system;
                $chatUnreadMsg = array();
                // チャットのスレッド表示時に未読件数バッジが更新されるため（gen_chat.js）、
                // チップヘルプへの件数表示はやめた。
                if ($chatUnreadCountEcom && $chatUnreadCountEcom != "0") {
                    $form['gen_chat_unread_count_ecom'] = $chatUnreadCountEcom;
                    $chatUnreadMsg[] = _g("イー・コモードからのお知らせがあります");
                }
                if ($chatUnreadCountSystem && $chatUnreadCountSystem != "0") {
                    $form['gen_chat_unread_count_system'] = $chatUnreadCountSystem;
                    $chatUnreadMsg[] = _g("通知センターからのメッセージがあります");
                }
                if ($chatUnreadCount && $chatUnreadCount != "0") {
                    $form['gen_chat_unread_count'] = $chatUnreadCount;
                    $chatUnreadMsg[] = _g("未読スレッドがあります");
                }
                $form['gen_chat_unread_count_msg'] = join(_g("。"), $chatUnreadMsg);
            }

            if ($bench)
                $timer->setMarker('chat');
        }

        //------------------------------------------------------
        // Stickynote
        //------------------------------------------------------
        if ($_SESSION["user_customer_id"] == "-1" && is_numeric($userId)) {
            $query = "
            select
                stickynote_info.*
                ,stickynote_info.user_id as author_id
                ,case when stickynote_info.user_id=-1 then '" . ADMIN_NAME . "' else user_name end as author_name
            from
                stickynote_info
                left join user_master on stickynote_info.user_id = user_master.user_id
            where
                (stickynote_info.user_id = '{$userId}' or show_all_user)
                and (action = '{$action}' or show_all_action)
            ";

            $form['gen_stickynote_info'] = $gen_db->getArray($query);
            if (!$form['gen_stickynote_info']) {
                unset($form['gen_stickynote_info']);
            } else {
                foreach ($form['gen_stickynote_info'] as &$stickyinfo) {
                    // DB登録時に「'」が「[gen_quot]」に変換してある（Config_Setting_AjaxStickynote）ので元に戻す
                    $stickyinfo['content'] = str_replace('[gen_quot]', "'", $stickyinfo['content']);
                    // DB登録時に無害化してあるが、念のため表示時にも無害化
                    $stickyinfo['content'] = Gen_String::escapeDangerTags($stickyinfo['content']);
                    // common_header.tpl で書き出すときのために、「"」を「\"」に変換しておく
                    $stickyinfo['content'] = str_replace('"', "\\\"", $stickyinfo['content']);
                }
            }
        }

        if ($bench)
            $timer->setMarker('sticky note');
    }

    //------------------------------------------------------
    // Smartyの設定
    //------------------------------------------------------

    require_once(SMARTY_DIR . 'Smarty.class.php');

    $smarty = new Smarty;
    $smarty->template_dir = SMARTY_TEMPRATE_DIR;
    $smarty->compile_dir = SMARTY_COMPILE_DIR;
    $smarty->config_dir = SMARTY_CONFIG_DIR;
    $smarty->cache_dir = SMARTY_CACHE_DIR;
    // 下の行のコメントアウトをはずすとsmartyキャッシュが有効になるが、
    // ページャーでうまく画面が切り替わらないときがあるなどの問題がある。
    // 静的コンテンツはほとんどないためキャッシュを有効にする意味はあまりないだろう。
    //$smarty->caching = true;
    //
    //$needOfEscape = false;
    ////$needOfEscape = true;
    //foreach ($smarty->default_modifiers as $modifier) {
    //    if (preg_match('|escape|', $modifier)) {
    //        $needOfEscape = false;
    //        break;
    //    }
    //}

    //------------------------------------------------------
    // Smartyへの値の割り当て
    //------------------------------------------------------

    $smarty->assign('form', $form);
    $smarty->assign('background_color', GEN_BACKGROUND_COLOR);

    $errorList = "";
    if (isset($validator)) {
        if ($validator->hasError()) {
            $smarty->assign('errorList', $validator->errorList);
            $errorList = $validator->errorList;
        }
    }

    if ($bench)
        $timer->setMarker('smarty setting');

    unset($form);

    //------------------------------------------------------
    // Smarty実行
    //------------------------------------------------------

    if ($_SERVER["SERVER_NAME"] !== "127.0.0.1") {
        ob_start ("ob_gzhandler");
    }

    $smarty->display($tpl);

    if ($bench)
        $timer->setMarker('smarty display');

    //------------------------------------------------------
    // Log
    //------------------------------------------------------
    $data = array(
        'url' => $gen_db->quoteParam($_SERVER['REQUEST_URI']),
        'action' => $action . ($_SESSION['gen_app'] ? " (app)" : ""),
        'user_name' => (isset($_SESSION['user_name']) ? $_SESSION['user_name'] : ''),
        'access_time' => date('Y-m-d H:i:s'),
        'ip' => Gen_Auth::getRemoteIpAddress(),
    );
    $gen_db->insert('access_log', $data);

    if ($bench)
        $timer->setMarker('access_log');

    //------------------------------------------------------
    // データベースコネクションのクローズ
    //------------------------------------------------------

    $gen_db->close();
    unset($gen_db);

    if ($bench)
        $timer->setMarker('database close');

    //------------------------------------------------------
    // ベンチマーク
    //------------------------------------------------------

    if ($bench) {
        $timer->stop();

        if (@$actionNameSep[0] != "Dropdown" && substr(@$actionNameSep[2], 0, 4) != "Ajax") {
            //var_dump($_SESSION);
            //var_dump($_COOKIE);
            $timer->display();
        }
    }

    // End of Main

    //************************************************
    // utils
    //************************************************

     function _g($str)
     {
         // getText
         $str = _($str);

         // 用語変換
         if (isset($_SESSION['gen_setting_company']->wordconvert)) {
             // admin用の用語変換モード
             $mode = 0;
             if (isset($_SESSION['adminWordConvertMode'])) {
                 switch($_SESSION['adminWordConvertMode']) {
                     case 1: $mode = 1; break;  // 変換された用語に [] をつける
                     case 2: $mode = 2; break;  // 元の用語を表示
                 }
             }
             // 用語変換
             if ($mode != 2) {
                foreach ($_SESSION['gen_setting_company']->wordconvert as $key => $val) {
                    if ($mode == 1) {
                        $val = "[{$val}]";
                    }
                    $str = str_replace($key, $val, $str);
                }
             }
         }
         return $str;
     }

    // var_dump
    function d()
    {
        echo '<pre style="background:#fff;color:#333;border:1px solid #ccc;margin:2px;padding:4px;font-family:monospace;font-size:12px">';
        foreach (func_get_args() as $v)
            var_dump($v);
        echo '</pre>';
    }

    // htmlspecialchars
    function h($str)
    {
        if (is_array($str)) {
            return array_map("h", $str);
        } else {
            return htmlspecialchars($str, ENT_QUOTES);
        }
    }

    // php.iniで使用されるメモリサイズの省略形（1Mなど）をバイトに変換
    function gen_getBytes($size)
    {
        $size = trim($size);
        $last = strtolower($size[strlen($size)-1]);
        switch($last) {
            case 'g':
                $size *= 1024;
            case 'm':
                $size *= 1024;
            case 'k':
                $size *= 1024;
        }
        return $size;
    }

    //************************************************
    // autoload
    //************************************************
    // index.php 内の spl_autoload_register() で登録。
    // require の省略を可能にする。

    function gen_autoload($className)
    {
        $nodes = explode('_', $className);

        switch ($nodes[0]) {
            // rtrimは*_DIRの末尾についている/を削除するためにある。
            // array_sliceはクラスファイル内に、そのクラスを補助・あるいは関連する
            // 小目的クラスを定義できるようにするためにある。
            // (Logic_Receivedと同ファイルにLogic_Received_Subなど))
            case 'Base':
                $nodes[0] = rtrim(BASE_DIR, SEPARATOR);
                $nodes = array_slice($nodes, 0, 2);
                break;
            case 'Gen':
                $nodes[0] = rtrim(COMPONENTS_DIR, SEPARATOR);
                $nodes = array_slice($nodes, 0, 2);
                break;
            case 'Logic':
                $nodes[0] = rtrim(LOGIC_DIR, SEPARATOR);
                $nodes = array_slice($nodes, 0, 2);
                break;
            default :
                // 上記以外のクラス
                return;
        }
        require_once(COMPONENTS_DIR. "File.class.php");
        $fileName = Gen_File::safetyPath(join(SEPARATOR, array_slice($nodes, 0, count($nodes)-1)), end($nodes) . ".class.php");
        require_once($fileName);
    }

    //************************************************
    // エラーハンドラ
    //************************************************
    //
    // デモモード用エラーハンドラ
    function demoErrorHandler($errno, $errmsg, $filename, $linenum, $vars)
    {
        // E_STRICT : PHP5における推奨事項違反警告
        // E_NOTICE : 警告（@つき未定義変数に対しても反応）
        if ($errno == E_STRICT || $errno == E_NOTICE)
            return;

        global $gen_demoErrorNo;
        if (!isset($gen_demoErrorNo) || !is_numeric($gen_demoErrorNo)) {
            $gen_demoErrorNo = 1;
        } else {
            $gen_demoErrorNo++;
        }
        print "<script>function gen_demo_handler{$gen_demoErrorNo}() {var s=document.getElementById('gen_demo_error{$gen_demoErrorNo}').style; if (s.display=='') {s.display='none'} else {s.display=''};}</script>";
        print " <font color='red'><a href=\"javascript:gen_demo_handler{$gen_demoErrorNo}();\">Warning</a></font><br>";
        print "<span id='gen_demo_error{$gen_demoErrorNo}' style='display:none'> {$errmsg}<BR>{$filename}  {$linenum}行<br></span>";
    }
