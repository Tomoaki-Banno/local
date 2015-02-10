<?php

class Menu_Home
{

    function execute(&$form)
    {
        global $gen_db;

        $form['gen_pageTitle'] = _g("パティオ");     // タブ表示用

        $userId = Gen_Auth::getCurrentUserId();

        $baseArr = array();     // ディレクトリ内のすべての画像のリスト
        $imageArr = array();    // 表示する画像のリスト（マイセレクト or すべて。ランダムやスライドショーはこの中から選択）

        if (GEN_IS_BACKGROUND) {
            // 画像ディレクトリ設定
            $dirArr = explode(";", BACKGROUND_IMAGE_DIR);
            if (isset($dirArr)) {
                foreach ($dirArr as $value) {
                    $path = BACKGROUND_IMAGE_PATH . $value;
                    // 指定のディレクトリをオープンし内容を取得
                    if (is_dir($path)) {
                        if ($dh = opendir($path)) {
                            while (($file = readdir($dh)) !== false) {
                                // ログイン画像を取得
                                if (!preg_match("/-log.jpg/", $file) && !preg_match("/-thumb.jpg/", $file) && preg_match("/.jpg/", $file)) {
                                    $baseArr[] = BACKGROUND_IMAGE_URL . "{$value}/{$file}";
                                }
                            }
                        }
                    }
                }
            }

            // ユーザー画像設定取得
            $query = "select background_mode, background_image from user_master where user_id = {$userId}";
            $res = $gen_db->queryOneRowObject($query);

            // 画像配列設定
            if (isset($res->background_mode) && $res->background_mode == "1" && isset($res->background_image) && $res->background_image != "") {
                // マイセレクト画像配列設定
                $userArr = explode(";", rtrim($res->background_image, ";"));
                foreach ($userArr as $value) {
                    $url = BACKGROUND_IMAGE_URL . preg_replace('/_/', '/', $value) . ".jpg";
                    if (count($baseArr) > 0 && in_array($url, $baseArr)) {
                        $imageArr[] = $url;
                    }
                }
            }
        }

        // 画像配列補間
        if (count($baseArr) == 0 && count($imageArr) == 0) {
            $imageArr[] = "img/background/default.jpg";     // 緊急時設定
        } elseif (count($baseArr) > 0 && count($imageArr) == 0) {
            $imageArr = $baseArr;
        }

        // 表示する画像を決定
        // （スライドショーありのときは、最初に表示される画像）
        if (isset($form['imageId']) && in_array($form['imageId'], $imageArr)) {
            // ログイン直後のとき。ログイン画面に表示されていた画像を選択
            $form['gen_background_image'] = $form['imageId'];
            $_SESSION['user_background_image'] = $form['imageId'];
        } else {
            // それ以外のとき。画像リスト($imageArr)の中からランダムに選択
            if (count($imageArr) > 1 && isset($_SESSION['user_background_image'])) {
                // 現在表示されている画像は削除（現在以外の画像を表示するため）
                $key = array_search($_SESSION['user_background_image'], $imageArr);
                unset($imageArr[$key]);
            }
            unset($_SESSION['user_background_image']);      // 削除
            // ランダムセレクト
            $randKeys = array_rand($imageArr, 1);
            $form['gen_background_image'] = $imageArr[$randKeys];
            $_SESSION['user_background_image'] = $imageArr[$randKeys];
        }
        
        // slide show
        if (isset($_SESSION['gen_setting_user']->slideshowSpeed) && $_SESSION['gen_setting_user']->slideshowSpeed == "0") {
            $imageArr = array($form['gen_background_image']);
        } else {
            // 表示画像リストの中から、ランダムに10枚を選択する
            //   枚数制限しているのは、大量のトラフィックが発生することを回避するため。
            //   （flexsliderでは、すべての画像を最初にimgタグで読み込んでおく必要がある）
            $max = 10;
            if (count($imageArr) > $max) {
                $keys = array_rand($imageArr, $max);
                $oldImageArr = $imageArr;
                $imageArr = array();
                foreach($keys as $key) {
                    $imageArr[] = $oldImageArr[$key];
                }
            }
            
            // シャッフル
            shuffle($imageArr);
            
            // ログイン画像またはセレクト画像を先頭に移動
            $key = array_search($form['gen_background_image'], $imageArr);
            if ($key !== FALSE) {
                unset($imageArr[$key]);
            }
            array_unshift($imageArr, $form['gen_background_image']);
        }
        $form['gen_background_image_arr'] = $imageArr;

        // Welcome Message
        if (!isset($_SESSION['gen_setting_user']->welcomeMsgCreated) || !$_SESSION['gen_setting_user']->welcomeMsgCreated
                || isset($form['showWelcomeMsg'])) {
            // Welcome Message の作成（ユーザーごとに最初の1回のみ。もしくは再作成指示があったとき）
            $data = array(
                'user_id' => $userId,
                'show_all_user' => 'false', // 自分だけ表示
                'allow_edit_all_user' => 'false', // 自分だけ編集可能
                'show_all_action' => 'false', // この画面のみ表示
                'action' => "Menu_Home",
                'x_pos' => 0,
                'y_pos' => 111,
                'width' => 650,
                'height' => 760,
                'color' => '#FFFFE0',
                'system_note_no' => 1, // Welcome Message
                'content' =>
                "<b><u>" . _g("■ジェネシスへようこそ！") . "</u></b><br><br>" .
                _g("ジェネシスをご利用いただき、誠にありがとうございます。") . "<br><br>" .
                _g("ジェネシスは、多様な業種の様々なニーズにお応えできるよう設計された、生産・販売・在庫管理システムです。") . "<br>" .
                _g("機械製造・金属加工・食品・医療・アパレル・ファブレス・商社・流通・小売・各種サービス業など、さまざまな業種のユーザー様に幅広くご活用いただいています。") .
                _g("企業の基幹システムとして本格的に利用される場合が多いですが、手軽な帳票発行ツールとして使用されることもあります。") .
                _g("どのようなニーズにもマッチする懐の深さが、ジェネシスの特徴です。") . "<br>" .
                _g("御社の業務にもきっと役立てていただけるものと存じます。") . "<br><br>" .
                _g("本システムは、フレンドリーな外観とリーズナブルな価格からは想像できないほど高度で多様な機能を備えています。") . "<br>" .
                _g("生産管理機能をご利用になる場合、所要量計算機能の便利さに驚かれることでしょう。") . "<br>" .
                _g("販売管理機能をご利用になる場合は、柔軟な帳票カスタマイズ機能や高度なデータ分析機能が御社の業務を強力にサポートします。") . "<br>" .
                _g("在庫管理機能をご利用になる場合であれば、在庫推移リストが手放せなくなるに違いありません。") . "<br><br>" .
                _g("多くの機能があるため、最初は操作方法がよくわからなかったり、戸惑うことがあるかもしれません。") . "<br>" .
                _g("しかし、ご安心ください。ジェネシスは一貫した論理的な設計がなされていますので、他のシステムと比べても比較的短期間で習得が可能です。") .
                sprintf(_g("操作でわからないことがあったら、まずは画面右上の「%1\$sヘルプ%2\$s」をクリックしてみてください。キーワードが自動で設定されていますので、「%1\$s検索%2\$s」ボタンをクリックすれば、よくある質問のリストが表示されます。もし、その中に目的の質問がなくても、検索キーワードを変えれば豊富なFAQの中から答えを探し出すことができます。"), "<font color=\"#0000cd\"><b>", "</b></font>") . "<br>" .
                _g("もちろん、オンラインサポートをご利用いただくこともできます。操作方法以外にも運用に関するご相談など、どうぞお気軽にお問い合わせください。") . "<br><br>" .
                sprintf(_g("画面右上の「ヘルプ」内の「%1\$sサポートサイト%2\$s」をクリックすると、サポートサイトが表示されます。サポートサイトにはジェネシスの%1\$sメンテナンス情報%2\$sや、機能追加などの%1\$sリビジョンアップ情報%2\$sが掲載されます。"), "<font color=\"#0000cd\"><b>", "</b></font>") . "<br>" .
                sprintf(_g("%1\$sお問い合わせ%2\$sフォームからお問い合わせいただけば、イー・コモードのコンシェルジェが親身になってご質問にお応えいたします。"), "<font color=\"#0000cd\"><b>", "</b></font>") . "<br>" .
                sprintf(_g("また、ジェネシスをご利用になって、あったらいいな、と思われる機能がありましたら、ぜひ、%1\$s機能提案%2\$sからご意見をお送りください。いただいた貴重なご意見は%1\$s機能提案ステータス%2\$sに掲載され、標準機能として実装できるか検討いたします。ご提案が採用された場合、%1\$sリビジョンアップ情報%2\$sで機能が実装されたことをご報告いたします。"), "<font color=\"#0000cd\"><b>", "</b></font>") . "<br><br>" .
                _g("最初からすべての業務をジェネシスに乗せようとするより、まずは「受注登録をする」「注文書を発行する」など、一部の業務をジェネシスで行ってみるとよいかもしれません。慣れてきたら、徐々に適用範囲を広げることができるでしょう。") . "<br>" .
                sprintf(_g("そして使い込んでいくと、操作を手助けする様々な工夫がシステムの各所に施されていることに気づかれるでしょう。ジェネシスは、%3\$s使えば使うほどあなたの手に馴染むツール%2\$sなのです。"), "<font color=\"#0000cd\"><b>", "</b></font>", "<font color=\"#c71585\"><b>") . "<br><br>" .
                _g("さあ、ジェネシスで業務を効率化しましょう！") . "<br><br>" .
                _g("（このメモパッドは右上の×をクリックすると削除できます。最上部をドラッグで移動、右下をドラッグでサイズ変更できます）") .
                "",
            );
            $gen_db->insert('stickynote_info', $data);

            @$_SESSION['gen_setting_user']->welcomeMsgCreated = true;
            Gen_Setting::saveSetting();
        } else {
            // Welcome Message が削除済みの場合、再表示リンクを作る
            $query = "select * from stickynote_info where system_note_no = 1 and user_id = '{$userId}'";
            if (!$gen_db->existRecord($query)) {
                $form['showWelcomeMsgLink'] = true;
            }
        }

        // 新機能
        if (isset($form['showNewFunction'])) {
            // 新機能メッセージの作成（ユーザーごとに最初の1回のみ。もしくは再作成指示があったとき）
            $data = array(
                'user_id' => $userId,
                'show_all_user' => 'false',          // 自分だけ表示
                'allow_edit_all_user' => 'false',    // 自分だけ編集可能
                'show_all_action' => 'false',        // この画面のみ表示
                'action' => "Menu_Home",
                'x_pos' => 0,
                'y_pos' => 111,
                'width' => 590,
                'height' => 820,
                'color' => '#FFFFE0',
                'system_note_no' => 2,              // New function
                'content' =>
                    // コンテンツの量をこれ以上増やすと、ブラウザによってはメモパッドからはみ出してしまうので注意
                    "<b><u>" . _g("■新しいジェネシスの新機能") . "</u></b><br><br>" .

                    "<b>" . _g("●新しい画面デザイン")."</b><br>".
                    _g("画面のレイアウトを刷新し、操作性向上のための多数の改善を行いました。")."<br>".

                    "<b>" . _g("●クロス集計")."</b><br>".
                    _g("リスト画面でクロス集計を行えるようになりました。ジェネシスの用途を大幅に広げ、高度なデータ分析を可能にする新機能です。")."<br>".

                    "<b>" . _g("●フィールド・クリエイター")."</b><br>".
                    _g("各画面に自由にオリジナルのデータ項目を追加することができるようになりました。")."<br>".

                    "<b>" . _g("●ネーム・スイッチャー")."</b><br>".
                    _g("ジェネシス内に出てくる用語を別の用語に置き換えることができるようになりました。")."<br>".

                    "<b>" . _g("●トークボード")."</b><br>".
                    _g("社内コミュニケーションに役立つトークボード（チャット）機能が搭載されました。")."<br>".

                    "<b>" . _g("●コンパス")."</b><br>".
                    _g("コンパス画面でさまざまな情報を集中的に参照/編集できるようになりました。レイアウトも自由に設定できます。")."<br>".

                    "<b>" . _g("●ロット管理")."</b><br>".
                    _g("ロット管理および消費期限管理の機能が追加されました。とくに食品・医薬品業界において有用です。")."<br>".

                    "<b>" . _g("●ファイル登録")."</b><br>".
                    _g("ほとんどのレコードにファイルを添付できるようになりました。図面やFAX画像、資料などを保存できます。")."<br>".

                    "<b>" . _g("●クイック検索")."</b><br>".
                    _g("リスト画面にクイック検索機能が追加されました。ぜひご活用いただきたい便利な機能です。")."<br>".

                    "<b>" . _g("●表示条件パターン")."</b><br>".
                    _g("リストの表示条件に名前をつけて保存できるようになりました。様々なパターンでデータを表示・分析するのに便利です。")."<br>".

                    "<b>" . _g("●列フィルタ")."</b><br>".
                    _g("リストの列にフィルタをかけることができるようになりました。表示条件にない項目も絞り込みの対象とすることができますし、複雑な条件の設定も可能です。")."<br>".

                    "<b>" . _g("●小計機能")."</b><br>".
                    _g("リスト画面で小計表示ができるようになりました。基準となる項目は自由に設定できます。")."<br>".

                    "<b>" . _g("●在庫評価単価の更新機能の改善")."</b><br>".
                    _g("在庫評価単価の履歴が各画面に反映されるようになりました。更新の取り消しも行えます。")."<br>".

                    "<b>" . _g("●帳票タグの追加")."</b><br>".
                    _g("各種帳票のタグが多数追加されました。")."<br>".

                    "<b>" . _g("●リストの直接編集")."</b><br>".
                    _g("リスト画面からデータを直接編集できるようになりました。（マスタのみ）")."<br>".

                    "<b>" . _g("●取引先元帳")."</b><br>".
                    _g("得意先元帳・仕入先元帳を出力できるようになりました。")."<br>".

                    "<b>" . _g("●改善されたオートコンプリート")."</b><br>".
                    _g("受注・注文・製造指示等の登録画面で、品目や取引先の入力を素早く行えるようになりました。")."<br>".

                    "<b>" . _g("●その他")."</b><br>".
                    _g("上記のほか、100項目以上におよぶ機能追加や改善が行われています。"),

            );
            $gen_db->insert('stickynote_info', $data);

            $_SESSION['gen_setting_user']->welcomeMsgCreated = true;
            Gen_Setting::saveSetting();

        } else {
            // 新機能メッセージが表示されていない場合、表示リンクを作る
            $query = "select * from stickynote_info where system_note_no = 2 and user_id = '{$userId}'";
            if (!$gen_db->existRecord($query)) {
                $form['showNewFunctionLink'] = true;
            }
        }

        // スケジュール
        $form['homeScheduleStyle'] = false;
        $perm = Gen_Auth::sessionCheck("config_schedule");
        if ($perm == 1 || $perm == 2) {
            if (isset($_SESSION['gen_setting_user']->homeScheduleStyle)) {
                $form['homeScheduleStyle'] = $_SESSION['gen_setting_user']->homeScheduleStyle;
            } else {
                $form['homeScheduleStyle'] = 2;     // 1週間
            }
        }
        return 'menu_home.tpl';
    }

}