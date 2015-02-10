<?php

// 登録時、このサイズ以下になるようリサイズされる。画面トップのバーの縦幅にあわせる
define("GEN_COMPANY_IMAGE_HEIGHT", "40");       // 自社ロゴ。縦幅のみ指定（横幅は縦横比率を保つように自動縮小）
define("GEN_ITEM_IMAGE_WIDTH", "800");          // 品目画像
define("GEN_ITEM_IMAGE_HEIGHT", "1000");
define("GEN_PROFILE_IMAGE_WIDTH", "200");       // プロフィール画像
define("GEN_PROFILE_IMAGE_HEIGHT", "150");

class Config_Setting_FileUpload
{
    // ■ユーザー登録ファイルの集中保存（files_dir方式）
    //
    //	ユーザーがアップロードしたファイル（会社ロゴ・品目画像・帳票テンプレ・プロフィール画像（15i）・
    //	登録ファイル（15i）等）を、サーバー内の特定のディレクトリに集中して保存するようにした。
    //
    //	当初は15i用として開発したが、15i開発の途中で負荷分散によるソースコード同期遅延の問題が持ち上がり、
    //	（ag.cgi?page=MyFolderMessageView&mDBID=1&mDID=12479&mEID=13816）
    //	その対処として一部を13i以前にも移植した。
    //
    //	●旧方式（2013/9月ごろまで）
    //	　・画像系（自社ロゴ・品目画像）
    //	　　　ラージオブジェクトとしてDBに保存していた。
    //	　　　次のような問題があった。
    //	　　　・DBのサイズが膨れ上がるため、バックアップ等がやりにくい。
    //	　　　・リストアの際、しばしばラージオブジェクトの復元に失敗する。
    //	　　　・Workに読み出した画像を、非ログインユーザーでも自由にダウンロードできてしまう。
    //	　・帳票テンプレート
    //	　　　ソース内のReportTemplates ディレクトリに保存していた。
    //	　　　次のような問題があった。
    //	　　　・非ログインユーザーでも自由にダウンロードできてしまう。
    //	　　　・サイトごとに上記ディレクトリをバックアップしておかなければならない。
    //	　　　・新規サイト構築の際、上記ディレクトリのアクセス権設定を忘れ、テンプレートの
    //		　アップロードができないという事態がしばしば発生する。
    //	　　　・サーバー移転の際、上記ディレクトリからユーザーテンプレートを移行する手間がかかる。
    //	　・Workを使用してDLするファイル（自社ロゴ、品目画像、帳票テンプレ、adminバックアップ、グラフデータ）
    //      　上記のファイルは、作成してからいったんWorkに置き、それをユーザーにDLさせていた。
    //	　　　次のような問題があった。
    //	　　　・負荷分散環境では、同期遅延により画像が正しく表示されないことがある。
    //	　　　・非ログインユーザーでも取得できてしまう。
    //    ・Workを使用した処理（バックアップ、エクスポート等）
    //      　Workディレクトリを作業用として使用していた。
    //      　次のような問題があった。
    //	　　　・非ログインユーザーでも取得できてしまう。
    //
    //  ●13i以前のバージョンの改善（DFC版。13i以前すべてに適用）
    //  　負荷分散環境での同期遅延の問題に対処するため、次のような改善を行った。
    //  　（同時にセキュリティ改善にもなっている）
    //
    //	　　・ファイルは gen_server_config.yml の files_dir で指定されたディレクトリに保存される。
    //		(files_dir)
    //			13i_foo		⇒ DB名（※1）
    //				CompanyLogo	自社ロゴ（画像はDBに登録されるが、このディレクトリを読み出し用キャッシュとして使用）
    //				ReportTemplates	帳票テンプレ（ユーザー登録分のみ）
    //				ChartData       グラフデータ（15iではcanvas化したため廃止）
    //				MRPProgress     mrp_progress.dat
    //
    //			※1 サイト名ではないことに注意。サイト名は運用途中で変更の可能性がある。
    //			　　また、将来的に複数のサイトが同一のDBを参照するという形態もありえなくはない。
    //			　　その際、ファイルや画像はサイトごとというよりDBごとに保存される方が自然だろう。
    //
    //　　　・ファイルのDLはWorkを使用せずに行う。
    //          帳票テンプレ        index.php の DLモード
    //          adminバックアップ   直接送信
    //          グラフ関連          index.php の DLモード（15iではcanvas化したため不要）
    //          自社ロゴ            index.php の DLモード
    //          品目画像            index.php の DLモード
    //
    //	●15iでの改善
    //    ・動的ファイル（実行中に登録・生成・更新されるファイル）はすべて files_dir か temp_dirのいずれかに配置するようにした。
    //      　ソース内には動的ファイルが一切存在しないようにした。ロードバランサー使用時のソース同期時差による問題を回避するため。
    //	　・次のようにfiles_dirのカテゴリを変更した。またtemp_dirを追加した。
    //      ※files_dir内のカテゴリは Amazon S3 に保存することもできる。
    //      　各カテゴリがどちらに配置されるかについては、Gen_Storage 冒頭を参照。
    //      　どちらにでも配置できるよう、各カテゴリのデータへのアクセスはすべて Gen_Storage を経由して行う。
    //		(files_dir or Amazon S3)
    //			13i_foo		⇒ DB名（※1）
    //				Files		登録ファイル（15i新機能）
    //				CompanyLogo	自社ロゴ（13i DFC版とは異なり、画像はDBではなくディレクトリに保存する）
    //				ItemImage	品目画像（15iで追加）
    //				JSGetText       JS用getText/wordConvert 変換ファイル（15i新機能）
    //				ProfileImage	プロフィール画像（15i新機能）
    //				ReportTemplates	帳票テンプレ（ユーザー登録分のみ）（13i DFC版と同じ）
    //				MRPProgress     mrp_progress.dat（13i DFC版と同じ）
    //		(temp_dir)
    //			13i_foo		⇒ DB名（※1）
    //				Smarty          Smartyキャッシュ（15iで追加）
    //                              cache
    //                              configs
    //                              templates_c
    //                          Temp            テンポラリファイル（ = GEN_TEMP_DIR）
    //
    //	　・自社ロゴ・品目画像はDBではなく、files_dirに保存するようにした。
    //	　・Smartyキャッシュと一時ファイルを temp_dirに保存するようにした。
    //          13iまでは GEN_TEMP_DIR は共通（gen_server_config.ymlで指定）だったが、
    //          複数ユーザーの一時ファイルが同一ディレクトリに混在することの危険性を考え、
    //          temp_dirに移動した。
    //	　・システム帳票テンプレートは htdocsの外に移動した。
    //
    //	●files_dir と temp_dir の使い分けについて
    //      files_dir:
    //          存在の有無や内容が、アクセスのたびに同じである必要があるファイルを置く。
    //              例： 添付ファイル・バックアップ・画像・テンプレートなど。
    //          NFSに配置することを想定している。（上記の通り、一部は Amazon S3）
    //              ロードバランサー使用時でも常に同じファイルにアクセスできることが保証されるが、I/O性能は悪い。
    //              運用上、バックアップを取ることを想定している。
    //      temp_dir:
    //          存在の有無や内容が、アクセスのたびに変わってもよいファイルを置く。
    //              例： Smartyキャッシュ・テンポラリファイルなど。
    //          サーバーのローカルストレージに配置することを想定している。
    //              I/O性能は高いが、ロードバランサー使用時はアクセスのたびにファイルの有無や内容が変わる可能性がある。
    //              運用上、バックアップを取らないことを想定している。
    //
    //	●files_dir方式のメリット
    //      ・負荷分散（ロードバランサ）環境への対応
    //      ・複数ユーザー（サイト）間でのソースコードの共用が可能に
    //	　　・セキュリティ向上（13i改善）
    //          権限のないユーザーは画像・テンプレートをDLできなくなる。
    //	　　・バックアップは files_dir のみを対象にすればいい（13i改善）
    //          サイトごとに設定しなくてよいため、管理がしやすい。帳票テンプレもバックアップされる。
    //	　　・新サイト構築の際に、ReportTemplatesの権限設定ミスがなくなる。（13i改善）
    //	　　・サーバー移転の際は、files_dirをまるごとコピーすればいい。帳票テンプレの移行忘れがなくなる。（13i改善）
    //	　　・DBサイズが抑制される（15i改善）
    //          バックアップ等がおこないやすい。
    //
    //	●注意点
    //	　　・DBに保存されるわけではないので、DBを復元しても削除されたファイルは元に戻らない。
    //      ・運用の途中でDB名を変更する場合は、先に files_dir のディレクトリ名を変更する必要がある
    //          DB名を先に変更してGenにアクセスすると、新DB名のディレクトリが作成されてしまい、
    //          画像や帳票テンプレが引き継がれなくなる。その場合は手動で修正を行う。
    //
    //	●使い方
    //　　　●ファイルの表示（ダウンロード）
    //      　　次のように行う。
    //		index.php?action=download&cat=files&file=A123.tmp
    //
    //		上記のようにactionを download にすると、通常のindex.phpの処理の大部分がスキップされ、
    //		アクセス権のチェックだけが行われてファイルがダウンロードされる。
    //			cat
    //			--------------
    //			files			actionGroupに対するログインチェック
    //			companylogo		ログインしていれば誰でもOK
    //			itemimage		ログインしていれば誰でもOK
    //			profileimage		ログインしていれば誰でもOK
    //			reporttemplates		ログインしていれば誰でもOK
    //
    //	　　●新しいカテゴリを追加するには
    //
    //		・index.php
    //			ファイルダウンロード部のカテゴリを増やす。（先頭から「ファイルダウンロードモード」で検索）
    //          ・Gen_Storage
    //                  冒頭のカテゴリを増やす。
    //		・Config_Setting_FileUpload（このファイル）
    //			削除・登録それぞれのカテゴリを増やす。
    //		・表示部分
    //			<img src='index.php?action=download&cat=XXX'>
    //			必要に応じてidなどのパラメータを追加。
    //		・登録・削除UI部分
    //			画像登録
    //                      プロフィール画像のようにダイアログを出したいなら
    //				common_header.tpl のプロフィール画像部を参考に。
    //                      品目画像や自社ロゴのようにやりたいなら
    //				Master_Item_Edit を参考に。
    //                  ファイル登録
    //				editmodal.tpl のファイル登録部を参考に。

    // ----------------------------------------------

    // ■ファイルアップロードのクライアント部分の改善
    //
    //  15iで次のような改善を行った。
    //   ・ファイル選択⇒アップロードボタン ではなく、ファイルを選択しただけで自動的にアップロードされるようにした。
    //   ・アップロード処理が画面遷移なしで行われるようにした。
    //   ・ブラウザ標準のファイルセレクタではなく独自のボタンを使用するようにし、外観を改善した。
    //   ・処理をあるていどFW側で行うようにし、ファイルアップロードのコードを簡潔に書けるようにした。

    // ●使用方法
    // 　※カテゴリについては、上記「ユーザー登録ファイルの集中保存」を参照。
    //
    // 　▲ファイルの場合
    //      ※この方法だとどのファイルでも扱えるし、1レコードに対して複数のファイルを登録することもできる。
    //      　ただし画像登録で、1レコードに1画像に限定されている場合、次の「画像の場合」のほうが簡単。
    //      基本的には、アップロードボタンを配置したい場所に <script>gen.fileUpload.init('(url)','(beforeFunc)','(afterFunc)','(afterFunc param)')</script>
    //      を書き出すか、該当箇所に divを用意しておいて JS内で gen.fileUpload.init2('(div名)','(url)','(beforeFunc)','(afterFunc)','(afterFunc param)')　を
    //      呼び出せばよい。
    //      登録ファイル名の表示や削除処理の記述も必要。
    //      formやfileタグを配置する必要はない。
    //
    //      具体的には・・
    //          ・tpl内で使用したい場合： editmodal.tpl の下部、ファイル登録部分を参照。
    //          ・ダイアログ内で使用したい場合（CSVインポートのように）： gen_script.js の gen.csvImport を参照
    //
    // 　▲画像の場合
    //  　　※レコードに対して一つの画像のみを登録する場合。複数画像を登録する場合は上の「ファイルの場合」を参照。
    //      　「ファイルの場合」との違いは、自動的に登録画像が表示されること、削除機能が自動的につくこと。
    //      基本的には、画像およびアップロードボタンを配置したい場所に <script>gen.imageUpload.init('(既存画像ファイル名)','(カテゴリ)','(レコードid)')</script>
    //      を書き出すか、該当箇所に divを用意しておいて JS内で gen.imageUpload.init2('(div名)','(既存画像ファイル名)','(カテゴリ)','(レコードid)')　を
    //      呼び出せばよい。
    //      formやfileタグを配置する必要はない。
    //
    //      具体的には・・
    //          ・ListやEdit内で使用したい場合： Master_Item_Edit や Master_Company_Edit の画像登録部分を参照
    //          ・ダイアログ内で使用したい場合（プロフィール画像のように）： gen_script.js の gen.profileImage を参照

    // ●技術的な解説（gen_script.js gen.fileUpload.init(), gen.imageUpload.init()）
    //
    // 　HTMLでファイルのアップロードを行うには、formタグ内にfileタグを配置してPOSTする。
    // 　ただ、それだとアップロード時に画面遷移してしまうなどの問題がある。
    //
    // 　▲画面遷移を回避する
    // 　ダミーiframeを配置し、そこをtargetにしてPOSTするのが定番。
    // 　その方法であれば、iframeのonloadイベントでアップロード終了を検知できるし、iframe内を調べることでサーバーからの
    // 　レスポンスを取得できる。
    // 　gen.imageUploadでは、このあたりの処理を jquery.upload を使用して行なっている。
    // 　同プラグインはアップロード用のformやダミーiframeの生成を自動で行なってくれる。
    // 　また、サーバーからのレスポンスを簡単に扱える。
    //
    //  ▲ファイル選択時に即時アップロードされるようにする
    //   アップロード開始用のボタンを設けず、ダイアログでファイルを選択したら即時アップロードされるようにするため、
    //   fileタグのonchangeイベントを使用している。
    //
    //　▲他のform内でのアップロードを行えるようにする
    // 　HTMLではformの入れ子ができない。そのため、通常は他のform内にアップロード機能を置くことはできない。
    // 　gen.imageUploadでは、iframeを設置してその中にform & fileタグを配置することで、その制限を回避している。
    //　 （formは jquery.upload によって自動生成される）
    //　 ちなみにFF/CH限定であれば、formの外部に不可視のform & fileタグを置き、それをJSでclickしてダイアログを表示させ、
    //　 fileタグのonchangeでアップロード処理を開始するという方法がある。
    //　 このほうが簡単だが、IE（8以降）ではJSからfileタグを操作するとアップロード時にファイルが空になる
    //　 というセキュリティ上の制限があるため、この方法が使えない。
    //
    //　▲ブラウザ標準のfileタグではなく、ボタンでアップロードする
    //　 ブラウザ標準のfileタグは見栄えがよくないため、代わりにボタンを設置している。
    //　 この実装は FF/Ch 限定であれば比較的カンタンで、fileタグを不可視にしておき、ボタンクリックしたらfileタグ
    //　 のclickイベントを呼べばいい。それでファイル選択ダイアログが開く。あとはfileタグのonchangeで
    //　 アップロード処理すればいい。
    //　 しかしIE（8以上）では、JSからfileタグのclickイベントを呼ぶと、アップロード時にファイルがカラになるという
    //   動作上の制限があり、使えない。
    //　 IEにも対応するため、透明にしたfileタグのボタン部分をアップロードボタンに重ねて表示するという方法を
    //　 とっている。ユーザーはアップロードボタンをクリックしたつもりで、実際にはfileタグのボタンをクリック
    //　 しているという状態になる。

    // ----------------------------------------------
    //
    // ■ファイルダウンロード部分についての解説：
    //  index.php 「ファイルダウンロードモード」のコードとコメントを参照。

    function execute(&$form)
    {
        global $gen_db;

        // トークンの確認（CSRF対策）
        //　　Ajax用のものを流用。トークンについての詳細はAjaxBaseのコメントを参照。
        if (!isset($form['gen_ajax_token']) || $_SESSION['gen_ajax_token'] != $form['gen_ajax_token']) {
            $form['response_noEscape'] = json_encode(array("status" => "tokenError", "success" => false, "msg" => ""));
            return 'simple.tpl';
        }

        // 実行時間制限を変更
        // PHPがセーフモードの場合は効かない。
        // このスクリプトの中だけで有効。
        // 無制限にするのはやめたほうがいい。
        set_time_limit(600);

        $cat = @$form['cat'];

        if (isset($form['delete'])) {
            // -----------------------------------
            //  削除モード
            // -----------------------------------
            if (!isset($form['file'])) {
                $form['response_noEscape'] = json_encode(array("status" => "deleteFileError", "success" => false, "msg" => ""));
                return 'simple.tpl';
            }
            $originalFileName = "";
            // アクセス権チェック
            switch($cat) {
                case "companylogo":
                    $actionGroup = "master_company";
                    $allowReadOnlyUser = true;  // 読み取りアクセス権でもOK
                    break;
                case "itemimage":
                    $actionGroup = "master_item";
                    $allowReadOnlyUser = false;  // 読み取りアクセス権ではNG
                    break;
                case "profileimage":
                    $actionGroup = "";
                    $allowReadOnlyUser = true;  // 読み取りアクセス権でもOK
                    break;
                default:    // files
                    $query = "select action_group, original_file_name, file_name from upload_file_info where file_name = '{$form['file']}'";
                    $obj = $gen_db->queryOneRowObject($query);
                    if (!$obj) {
                        $form['response_noEscape'] = json_encode(array("status" => "deleteFileError", "success" => false, "msg" => ""));
                        return 'simple.tpl';
                    }
                    $actionGroup = $obj->action_group;
                    $originalFileName = $obj->original_file_name;
                    $allowReadOnlyUser = false;  // 読み取りアクセス権ではNG
            }
            if (!self::_checkPermission($actionGroup, $allowReadOnlyUser)) {
                $form['response_noEscape'] = json_encode(array("status" => "permissionError", "success" => false, "msg" => ""));
                return 'simple.tpl';
            }

            // 削除
            switch($cat) {
                case "companylogo":
                    $query = "select image_file_name from company_master";
                    $imageFileName = $gen_db->queryOneValue($query);
                    $storage = new Gen_Storage("CompanyLogo");
                    $storage->delete($imageFileName);
                    $data = array(
                        "image_file_name" => "",
                        "original_image_file_name" => ""
                    );
                    $gen_db->update("company_master", $data, "1=1");
                    unset($_SESSION['gen_setting_company']->companyLogoFile);
                    unset($_SESSION['gen_setting_company']->companyLogoFileLastMod);
                    Gen_Setting::saveSetting();
                    $clientMsg = _g("自社ロゴを削除しました。画面を更新すると反映されます。");
                    $catName = _g("自社ロゴ");
                    break;
                case "itemimage":
                    if ($form['file'] == "") {
                        $form['response_noEscape'] = json_encode(array("status" => "deleteImageError", "success" => false, "msg" => ""));
                        return 'simple.tpl';
                    }
                    $storage = new Gen_Storage("ItemImage");
                    $storage->delete($form['file']);
                    $query = "select item_id from item_master where image_file_name = '{$form['file']}'";
                    $itemId = $gen_db->queryOneValue($query);
                    if ($itemId) {
                        $data = array(
                            "image_file_name" => "",
                            "original_image_file_name" => ""
                        );
                        $gen_db->update("item_master", $data, "item_id = '{$itemId}'");
                    }
                    $clientMsg = "";
                    $catName = _g("品目画像");
                    break;
                case "profileimage":
                    $userId = Gen_Auth::getCurrentUserId();
                    if ($userId == -1) {
                        $form['response_noEscape'] = json_encode(array("status" => "adminImageError", "success" => false, "msg" => _g("管理者はプロフィール画像の操作を行えません。"),));
                        return 'simple.tpl';
                    }
                    $query = "select image_file_name from user_master where user_id = '{$userId}'";
                    $imageFileName = $gen_db->queryOneValue($query);
                    $storage = new Gen_Storage("ProfileImage");
                    $storage->delete($imageFileName);
                    $data = array(
                        "image_file_name" => "",
                        "original_image_file_name" => ""
                    );
                    $gen_db->update("user_master", $data, "user_id = '{$userId}'");
                    unset($_SESSION['gen_setting_user']->profileImage);
                    unset($_SESSION['gen_setting_user']->profileImageLastMod);
                    Gen_Setting::saveSetting();
                    $clientMsg = _g("プロフィール画像を削除しました。画面を更新すると反映されます。");
                    $catName = _g("プロフィール画像");
                    break;
                default:    // files
                    $query = "delete from upload_file_info where file_name = '{$form['file']}'";
                    $gen_db->query($query);
                    $storage = new Gen_Storage("Files");
                    $storage->delete($form['file']);
                    $clientMsg = "";
                    $catName = _g("ファイル登録");
            }

            $success = true;
            $menu = new Logic_Menu();
            $pageTitle = $menu->actionGroupToName($actionGroup);
            Gen_Log::dataAccessLog($catName, _g("削除"), "{$pageTitle}  [" . _g("ファイル名") . "] " . $gen_db->quoteParam($originalFileName));

        } else if (is_uploaded_file(@$_FILES['uploadFile']['tmp_name'])
                && @$_FILES['uploadFile']['size'] > 0) {

            // -----------------------------------
            //  ファイルがアップロードされたとき
            // -----------------------------------

            // アクセス権チェック　兼　ファイル保管ディレクトリ決定
            switch($cat) {
                case "companylogo":
                    $actionGroup = "master_company";
                    $storageCat = "CompanyLogo";
                    $allowReadOnlyUser = true;  // 読み取りアクセス権でもOK
                    break;
                case "itemimage":
                    if (!Gen_String::isNumeric($form['id'])) {
                        $form['response_noEscape'] = json_encode(array("status" => "idError", "success" => false, "msg" => ""));
                        return 'simple.tpl';
                    }
                    $actionGroup = "master_item";
                    $storageCat = "ItemImage";
                    $allowReadOnlyUser = false;  // 読み取りアクセス権ではNG
                    break;
                case "profileimage":
                    $actionGroup = "";
                    $storageCat = "ProfileImage";
                    $allowReadOnlyUser = true;  // 読み取りアクセス権でもOK
                    break;
                case "chatfile":
                    if (!isset($form['headerId']) || !Gen_String::isNumeric($form['headerId'])) {
                        $form['response_noEscape'] = json_encode(array("status" => "idError", "success" => false, "msg" => ""));
                        return 'simple.tpl';
                    }
                    $userId = Gen_Auth::getCurrentUserId();
                    $query = "select chat_header_id from chat_user where chat_header_id = '{$form['headerId']}' and user_id = '{$userId}'";
                    if (!$gen_db->existRecord($query)) {
                        $form['response_noEscape'] = json_encode(array("status" => "chatError", "success" => false, "msg" => ""));
                        return 'simple.tpl';
                    }
                    $actionGroup = "Menu_Chat";
                    $storageCat = "ChatFiles";
                    $allowReadOnlyUser = true;  // 読み取りアクセス権でもOK

                    // 画像ファイルの処理
                    $clientFileName = Gen_File::path2FileName($_FILES['uploadFile']['name']);
                    $pinfo = pathinfo($clientFileName);
                    $extension = "." . $pinfo['extension'];
                    $chatImgWidthHeight = false;
                    if (Gen_Image::getImageType($clientFileName . $extension) != "") {
                        // 画像サイズを保存
                        $chatImgWidthHeight = getimagesize($_FILES['uploadFile']['tmp_name']);
                    }
                    break;
                default:    // files
                    if (!isset($form['actionGroup']) || !Gen_String::isNumeric($form['id'])) {
                        $form['response_noEscape'] = json_encode(array("status" => "idError", "success" => false, "msg" => ""));
                        return 'simple.tpl';
                    }
                    $fileSize = filesize($_FILES['uploadFile']['tmp_name']);
                    $actionGroup = $form['actionGroup'];
                    $storageCat = "Files";
                    $allowReadOnlyUser = false;  // 読み取りアクセス権ではNG
            }
            if (!self::_checkPermission($actionGroup, $allowReadOnlyUser)) {
                $form['response_noEscape'] = json_encode(array("status" => "permissionError", "success" => false, "msg" => ""));
                return 'simple.tpl';
            }

            // 画像の場合、最大サイズを決める
            $maxWidth = "";
            $maxHeight = "";
            switch($cat) {
                case "companylogo":
                    $maxWidth = "";    //  縦幅基準で、縦横比を維持したサイズ。
                    $maxHeight = GEN_COMPANY_IMAGE_HEIGHT;
                    break;
                case "itemimage":
                    $maxWidth = GEN_ITEM_IMAGE_WIDTH;
                    $maxHeight = GEN_ITEM_IMAGE_HEIGHT;
                    break;
                case "profileimage":
                    $maxWidth = GEN_PROFILE_IMAGE_WIDTH;
                    $maxHeight = GEN_PROFILE_IMAGE_HEIGHT;
                    break;
            }

            // ファイルの保存
            //  画像に関してはリサイズや回転等の処理も行う
            $isCheckStorageSize = ($cat != "companylogo" && $cat != "profileimage");
            $res = Gen_File::saveUploadFile($_FILES['uploadFile'], $storageCat, $isCheckStorageSize, $maxWidth, $maxHeight);
            if ($res == "storageError") {
                $form['response_noEscape'] = json_encode(array("status" => "storageError", "success" => false, "msg" => ""));
                return 'simple.tpl';
            } else if ($res == "imageTypeError") {
                $form['response_noEscape'] = json_encode(array("status" => "fileTypeError", "success" => false, "msg" => _g("ファイル形式が正しくありません。")));
                return 'simple.tpl';
            }
            list($clientFileName, $fileName, $fileSize) = $res;

            // データベースの書き換え
            //  更新の場合は旧ファイルの削除も行う
            switch($cat) {
                case "companylogo":
                    $query = "select image_file_name from company_master";
                    $imageFileName = $gen_db->queryOneValue($query);
                    if ($imageFileName != "") {
                        $storage = new Gen_Storage("CompanyLogo");
                        $storage->delete($imageFileName);
                    }
                    $data = array(
                        "image_file_name" => $fileName,
                        "original_image_file_name" => $gen_db->quoteParam($clientFileName)
                    );
                    $gen_db->update("company_master", $data, "1=1");
                    // 他ユーザーの gen_setting_company は再ログインまで更新されないが、ファイルがunlinkされているため
                    // 画像DL時（index.php）にDBの値から再更新される。
                    $_SESSION['gen_setting_company']->companyLogoFile = $fileName;
                    $_SESSION['gen_setting_company']->companyLogoFileLastMod = date('Y-m-d H:i:s');
                    Gen_Setting::saveSetting();
                    $clientMsg = _g("自社ロゴを登録しました。画面を更新すると反映されます。");
                    $catName = _g("自社ロゴ");
                    break;
                case "itemimage":
                    $query = "select image_file_name from item_master where item_id = '{$form['id']}'";
                    $imageFileName = $gen_db->queryOneValue($query);
                    if ($imageFileName != "") {
                        $storage = new Gen_Storage("ItemImage");
                        $storage->delete($imageFileName);
                    }
                    $data = array(
                        "image_file_name" => $fileName,
                        "original_image_file_name" => $gen_db->quoteParam($clientFileName)
                    );
                    $gen_db->update("item_master", $data, "item_id = '{$form['id']}'");
                    $clientMsg = "";
                    $catName = _g("品目画像");
                    break;
                case "profileimage":
                    $userId = Gen_Auth::getCurrentUserId();
                    if ($userId == -1) {
                        $form['response_noEscape'] = json_encode(array("status" => "adminImageError", "success" => false, "msg" => _g("管理者はプロフィール画像の操作を行えません。")));
                        return 'simple.tpl';
                    }
                    $query = "select image_file_name from user_master where user_id = '{$userId}'";
                    $imageFileName = $gen_db->queryOneValue($query);
                    if ($imageFileName != "") {
                        $storage = new Gen_Storage("ProfileImage");
                        $storage->delete($imageFileName);
                    }
                    $data = array(
                        "image_file_name" => $fileName,
                        "original_image_file_name" => $gen_db->quoteParam($clientFileName)
                    );
                    $gen_db->update("user_master", $data, "user_id = '{$userId}'");
                    $_SESSION['gen_setting_user']->profileImage = $fileName;
                    $_SESSION['gen_setting_user']->profileImageLastMod = date('Y-m-d H:i:s');
                    Gen_Setting::saveSetting();
                    $clientMsg = _g("プロフィール画像を登録しました。画面を更新すると反映されます。");
                    $catName = _g("プロフィール画像");
                    break;
                case "chatfile":
                    $chatTime = new DateTime();
                    $data = array(
                        "chat_header_id" =>$form['headerId'],
                        "user_id" => $userId,
                        "chat_time" => $chatTime->format("Y-m-d H:i:s"),
                        "content" => "",
                        "file_name" => $fileName,
                        "original_file_name" => $clientFileName,
                        "file_size" => $fileSize,
                    );
                    if ($chatImgWidthHeight) {
                        $data["image_width"] = $chatImgWidthHeight[0];
                        $data["image_height"] = $chatImgWidthHeight[1];
                    }
                    $gen_db->insert("chat_detail", $data);
                    $clientMsg = "";
                    $catName = _g("トークボード添付ファイル");
                    
                    Logic_Chat::pushNotification($form['headerId'], $clientFileName);
                    
                    break;
                default:    // files
                    $data = array(
                        "action_group" => $form['actionGroup'],
                        "record_id" => $form['id'],
                        "file_name" => $fileName,
                        "original_file_name" => $gen_db->quoteParam($clientFileName),
                        "file_size" => $fileSize,
                    );
                    // 仮登録の場合は仮登録ユーザーIDを記録
                    if ($form['id'] == '-999') {
                        $userId = Gen_Auth::getCurrentUserId();
                        $data['temp_upload_user_id'] = $userId;
                    }
                    $gen_db->insert("upload_file_info", $data);
                    $clientMsg = "";
                    $catName = _g("ファイル登録");
            }

            $success = true;

            // データアクセスログ
            $menu = new Logic_Menu();
            $pageTitle = $menu->actionGroupToName($actionGroup);
            Gen_Log::dataAccessLog($catName, _g("登録"), "{$pageTitle}  [" . _g("ファイル名") . "] " . $gen_db->quoteParam($clientFileName));

        } else {
            // -----------------------------------
            //  ファイルサイズ不正か、ファイルがアップロードされていないとき
            // -----------------------------------

            $clientMsg = _g("登録に失敗しました。ファイルサイズが大きすぎます。");
            $success = false;
        }

        $obj = array(
            'msg' => $clientMsg,
            'success' => $success,
        );
        if (isset($fileName)) {
            $obj['fileName'] = $fileName;
            $obj['originalFileName'] = $clientFileName;
        }

        $form['response_noEscape'] = json_encode($obj);
        return 'simple.tpl';
    }

    // $actionGroup が空文字の場合はセッションの有無をチェック。
    // $actionGroup が指定されている場合はアクセス権の有無をチェック。
    //  読み取りアクセス権の場合、第2引数がtrueならOKだが、falseにするとNGとなる。
    private function _checkPermission($actionGroup, $allowReadOnlyUser) {
        $actionGroup = strtolower($actionGroup);
        if (substr($actionGroup, -1) == '_') {
            $actionGroup = substr($actionGroup, 0, strlen($actionGroup) - 1);
        }
        $sessionRes = Gen_Auth::sessionCheck($actionGroup);
        if ($actionGroup == "") {   // セッションだけをチェックする場合
            return ($sessionRes != -1 && $sessionRes != -2 && $sessionRes != -3);
        }
        return ($sessionRes == 2 || ($sessionRes == 1 && $allowReadOnlyUser));
    }

}
