<?php

class Config_Setting_AjaxChat extends Base_AjaxBase
{

    function _execute(&$form)
    {
        global $gen_db;

        $res = Gen_Auth::sessionCheck("menu_chat");
        if ($res != 1 && $res != 2) {
            return;
        }

        if (!isset($form['op'])) {
            return;
        }

        $gen_db->begin();

        $userId = Gen_Auth::getCurrentUserId();

        switch ($form['op']) {
            case 'show':
            case 'hide':
                $where = ($userId == -1 ? "" : "where user_id = '{$userId}'");
                $table = ($userId == -1 ? "company_master" : "user_master");
                // アトリビュートは更新しない
                $query = "update {$table} set show_chat_dialog = " . ($form['op'] == 'show' ? 'true' : 'false') . " {$where}";
                $gen_db->query($query);
                $resArr = array('status' => 'success');
                break;

            case 'mov':
                if (!is_numeric($form['x_pos']) || !is_numeric($form['y_pos']))
                    return;
                $xPos = (int)$form['x_pos'];
                $yPos = (int)$form['y_pos'];
                if ($xPos < 0) {
                    $xPos = 0;   // ブラウザ外対応
                }
                if ($yPos < 0) {
                    $yPos = 0;   // ブラウザ外対応
                }
                $where = ($userId == -1 ? "" : "where user_id = '{$userId}'");
                $table = ($userId == -1 ? "company_master" : "user_master");
                // アトリビュートは更新しない
                $query = "update {$table} set chat_dialog_x = '{$xPos}', chat_dialog_y = '{$yPos}' {$where}";
                $gen_db->query($query);
                $resArr = array('status' => 'success');
                break;

            case 'resize':
                if (!is_numeric($form['width']) || !is_numeric($form['height']))
                    return;
                $where = ($userId == -1 ? "" : "where user_id = '{$userId}'");
                $table = ($userId == -1 ? "company_master" : "user_master");
                // アトリビュートは更新しない
                $query = "update {$table} set chat_dialog_width = '{$form['width']}', chat_dialog_height = '{$form['height']}' {$where}";
                $gen_db->query($query);
                $resArr = array('status' => 'success');
                break;

            case 'list':
                $isSearch = (isset($form['search']) && $form['search'] != "");
                $isTitleSearch = false;
                if ($isSearch) {
                    if (substr($form['search'], 0, 6) == "title:" && strlen($form['search']) > 6) {
                        $isTitleSearch = true;
                        $form['search'] = substr($form['search'], 6);
                    }
                    $searchStr = str_replace("　", " " , $form['search']);
                    $searchArr = explode(" ", $searchStr);
                }
                $groupId = "";
                if (isset($form['groupId']) && Gen_String::isNumeric($form['groupId'])) {
                    $groupId = $form['groupId'];
                }
                // スレッドピン
                $pinList = isset($_SESSION['gen_setting_user']->chatpins) ? $_SESSION['gen_setting_user']->chatpins : "";
                // カテゴリピン
                $genPins = array();
                $action = "gen_chat";
                $colInfoJson = $gen_db->queryOneValue("select pin_info from page_info where user_id = '{$userId}' and action = '{$action}'");
                // 登録の際に「\」が「￥」に自動変換されているので、ここで元に戻す必要がある。
                if (($colInfoObj = json_decode(str_replace("￥", "\\", $colInfoJson))) != null) {
                    foreach ($colInfoObj as $key => $val) {
                        if ((!isset($form['groupId']) || $form['groupId'] == "undefined") && $key == "gen_chat_group") {
                            $groupId = $val;
                        }
                        $genPins[] = $key;
                    }
                }
                $catPinHtml = Gen_String::makePinControl($genPins, $action, "gen_chat_group");

                $query = "
                    select
                        chat_header.chat_header_id
                        ,chat_header.title
                        /* -1: admin、 -2: 「イーコモードからのお知らせ」スレッド、-3: 通知センター */
                        ,coalesce(case chat_detail.user_id when -1 then '" . ADMIN_NAME . "' when -2 then 'e-commode' when -3 then 'Genesiss' else user_master.user_name end,
                            case chat_header.author_id when -1 then '" . ADMIN_NAME . "' when -2 then 'e-commode' when -3 then 'Genesiss' else t_author.user_name end) as user_name
                        ,to_char(coalesce(chat_detail.chat_time, chat_header.create_time), 'YYYY-MM-DD HH24:MI') as chat_time
                        ,chat_group.group_name
                        ,case when coalesce(chat_user.readed_chat_detail_id,-1) < t_detail_without_me.chat_detail_id then 0 else 1 end as is_readed
                        ,case when is_ecom then 1 else 0 end as is_ecom
                        ,case when is_system then 1 else 0 end as is_system
                        ," . ($pinList == "" ? "0" : "case when chat_header.chat_header_id in ({$pinList}) then 1 else 0 end") . " as is_pin
                        ," . (($isSearch && !$isTitleSearch) || $groupId == "-2" ? "t_search_detail.contents" : "''") . " as detail_search
                    from
                        chat_header
                        -- 権限のあるチャットのみに限定
                        inner join chat_user on chat_header.chat_header_id = chat_user.chat_header_id and chat_user.user_id = '{$userId}'
                        -- 最終書き込み情報
                        left join
                            chat_detail
                            on chat_header.chat_header_id = chat_detail.chat_header_id
                            -- header_id ごとに最後の書き込みだけを取得
                            and chat_detail.chat_detail_id in
                                (select max(chat_detail.chat_detail_id) as chat_detail_id
                                from chat_detail
                                inner join (select chat_header_id, max(chat_time) as last_chat_time from chat_detail group by chat_header_id) as t_last_time
                                on chat_detail.chat_header_id = t_last_time.chat_header_id and chat_detail.chat_time = t_last_time.last_chat_time
                                group by chat_detail.chat_header_id)
                        -- 自分以外の最終書き込み情報（未読確認用）
                        left join
                            (select chat_detail.chat_header_id, max(chat_detail.chat_detail_id) as chat_detail_id
                                from chat_detail
                                where user_id <> '{$userId}'
                                group by chat_detail.chat_header_id
                            ) as t_detail_without_me
                            on chat_header.chat_header_id = t_detail_without_me.chat_header_id
                        left join
                            user_master as t_author on chat_header.author_id = t_author.user_id
                        left join
                            user_master on chat_detail.user_id = user_master.user_id
                        " . (($isSearch && !$isTitleSearch) || $groupId == "-2" ?
                            ($groupId == "-2" ? "inner" : "left") . " join (
                            select chat_detail.chat_header_id, string_agg(cast(chat_detail.chat_detail_id as text) || '[gen_no]' || coalesce(original_file_name, content) || '[gen_sep]', '' order by chat_detail.chat_detail_id) as contents
                            from chat_detail
                            " . ($groupId == "-2" ?
                                "inner join chat_star on chat_detail.chat_detail_id = chat_star.chat_detail_id and chat_star.user_id = '{$userId}'" : "") . "
                             " . ($isSearch ?
                                "where " . self::getMultiWhereStr("content", $searchArr) . "
                                /* 添付ファイル名は検索対象とするが、画像ファイル名は対象としない。ちなみにスタンプ名の排除はこの後のPHP処理で行っている */
                                or (" . self::getMultiWhereStr("original_file_name", $searchArr) . " and image_width is null)"
                                : "") . "
                            group by chat_detail.chat_header_id
                            ) as t_search_detail on chat_header.chat_header_id = t_search_detail.chat_header_id" : "") . "
                        left join
                            chat_group on chat_header.chat_group_id = chat_group.chat_group_id
                    where 1=1
                        " . ($isSearch ? " and (" . self::getMultiWhereStr("chat_header.title", $searchArr) : "") . ($isSearch && !$isTitleSearch ? " or t_search_detail.contents <> ''" : "") . ($isSearch ? ")" : "") . "
                        " . ($groupId == "" || $groupId == "-2" ?
                            ""
                            : " and " . ($groupId == "-1" ?
                                "(coalesce(chat_user.readed_chat_detail_id,-1) < t_detail_without_me.chat_detail_id " . ($pinList == "" ? "" : " or chat_header.chat_header_id in ({$pinList})") . ") "
                                : "chat_header.chat_group_id = '{$groupId}' ")
                            ) . "
                    order by
                        is_pin desc, is_readed, coalesce(chat_detail.chat_time, chat_header.create_time) desc
                    limit 100
                ";
                $dataArr = $gen_db->getArray($query);
                if ($dataArr) {
                    foreach($dataArr as $key => $val) {
                        $dataArr[$key]["title"] = str_replace("[gen_quot]", "'", $val["title"]);
                        // 明細表示（検索結果 or スター検索）
                        if (($isSearch && $val["detail_search"] != "") || $groupId == "-2") {
                            $detailSearch = str_replace("[br]", " ", str_replace("[gen_quot]", "'", $val["detail_search"]));
                            $detailSearchArr = explode("[gen_sep]", $detailSearch);
                            $searchRes = "";
                            if ($isSearch && $val["detail_search"] != "") {
                                // 検索結果。検索語の前後40文字を切り出し
                                $searchWord = "";
                                foreach($searchArr as $search) {
                                    if ($search != "") {
                                        $searchWord = $search;
                                        break;
                                    }
                                }
                                foreach($detailSearchArr as $d) {
                                    if ($d == "") {
                                        continue;
                                    }
                                    $arr = explode("[gen_no]", $d);
                                    $content = $arr[1];
                                    // スタンプ名を検索結果から除外。ちなみに画像名の除外はSQL内で行っている
                                    if (substr($content,0,2) === "[[" && substr($content,-2) === "]]") {
                                        continue;
                                    }
                                    $pos = mb_stripos($content, $searchWord);
                                    $len = mb_strlen($content);
                                    $wordLen = mb_strlen($searchWord);
                                    $startPos = ($pos <= 30 ? 0 : $pos - 30);
                                    $endPos = ($len <= $pos + $wordLen + 30 ? $len : $pos + $wordLen + 30);
                                    $cutLen = $endPos - $startPos + 1;
                                    $searchRes .= $arr[0] . "[gen_no]" . mb_substr($content, $startPos, $cutLen) . "[gen_sep]";
                                }
                            } else if ($groupId == "-2") {
                                // スター付き。先頭から60文字を切り出し
                                foreach($detailSearchArr as $d) {
                                    if ($d == "") {
                                        continue;
                                    }
                                    $arr = explode("[gen_no]", $d);
                                    $content = $arr[1];
                                    $searchRes .= $arr[0] . "[gen_no]" . mb_substr($content, 0, 60) . "[gen_sep]";
                                }
                            }
                            $dataArr[$key]["detail_search"] = $searchRes;
                        }
                    }
                }
                $query = "select chat_group_id, group_name from chat_group order by group_name";
                $arr = $gen_db->getArray($query);
                $groups = array();
                if ($arr) {
                    foreach($arr as $row) {
                        $groups[$row['chat_group_id']] = $row['group_name'];
                    }
                }

                // 結果
                $resArr = array(
                    'desktop' => (isset($_SESSION['gen_setting_user']->desktopNotification_chat)
                            && $_SESSION['gen_setting_user']->desktopNotification_chat),
                    'data' => $dataArr,
                    'groups' => $groups,
                    'groupId' => $groupId,
                    'catPinHtml' => $catPinHtml,
                );
                $unreadArr = Logic_Chat::getUnreadCount($userId);
                $resArr['unreadCount'] = $unreadArr[0];
                $resArr['unreadCountEcom'] = $unreadArr[1];
                $resArr['unreadCountSystem'] = $unreadArr[2];

                if (isset($form['clearLastHeaderId'])) {
                    $where = ($userId == -1 ? "" : "where user_id = '{$userId}'");
                    $table = ($userId == -1 ? "company_master" : "user_master");
                    // アトリビュートは更新しない
                    $query = "update {$table} set last_chat_header_id = null {$where}";
                    $gen_db->query($query);
                }
                break;

            case 'listPin':
                if (!isset($form['headerId']) || !Gen_String::isNumeric($form['headerId']))
                    return;
                if (!isset($form['val']) || ($form['val'] != '0' && $form['val'] != '1'))
                    return;

                $isPin = ($form['val'] == '1');
                $pinArr = array();
                if (isset($_SESSION['gen_setting_user']->chatpins) && $_SESSION['gen_setting_user']->chatpins != "") {
                    $pinArr = explode(",", $_SESSION['gen_setting_user']->chatpins);
                }
                if ($form['val'] == '1') {
                    // trun on
                    if (!in_array($form['headerId'], $pinArr)) {
                        $pinArr[] = $form['headerId'];
                    }
                } else {
                    // turn off
                    foreach($pinArr as $key=>$val) {
                        if ($val == $form['headerId']) {
                            unset($pinArr[$key]);
                        }
                    }
                }
                $_SESSION['gen_setting_user']->chatpins = join(",", $pinArr);
                Gen_Setting::saveSetting();

                $resArr = array(
                    'status' => "success",
                );

                break;

            case 'showConfig':
                $isNew = (!isset($form['headerId']) || !Gen_String::isNumeric($form['headerId']));

                if ($isNew) {
                    $title = "";
                    $groupId = "";
                } else {
                    $query = "select title, author_id, chat_group_id from chat_header where chat_header_id = '{$form['headerId']}'";
                    $obj = $gen_db->queryOneRowObject($query);
//                    if ($obj->author_id != $userId) {
//                        // 編集できるのは自分が所有者のチャットのみ
//                        return;
//                    }
                    $title = $obj->title;
                    $groupId = $obj->chat_group_id;
                }
                $query = "
                    select
                        user_master.user_id
                        ,user_master.user_name
                        ,case when chat_user.user_id is null then 0 else 1 end as member_flag
                        ,'0' || user_name as for_order
                        ,user_master.section_id
                        ,section_name
                    from
                        user_master
                        left join chat_user on user_master.user_id = chat_user.user_id and chat_header_id = '" . ($isNew ? "-1000" : $form['headerId']) . "'
                        left join section_master on user_master.section_id = section_master.section_id
                    ";
                    if ($userId == -1) {
                        // 以前は「e-commode」（admin）をユーザーリストに出していたが、ユーザーが書き込んだメッセージを
                        // e-commodeが見ていると誤解されるおそれがあるため、adminの画面以外には出さないようにした。
                        // ag.cgi?page=ProjectDocView&pid=1574&did=209794
                        $query .= "
                        union
                        select
                            -1 as user_id
                            ,'" . ADMIN_NAME . "' as user_name
                            ,case when chat_user.user_id is null then 0 else 1 end as member_flag
                            ,'1' as for_order
                            ,null as section_id
                            ,null as section_name
                        from
                            user_master
                            left join chat_user on user_master.user_id = chat_user.user_id and chat_header_id = '" . ($isNew ? "-1000" : $form['headerId']) . "'
                        ";
                    }
                    $query .= "
                        order by
                            for_order
                ";
                $arr = $gen_db->getArray($query);
                $members = array();
                $notMembers = array();
                $sections = array();
                $sectionMembers = array();
                if ($arr) {
                    foreach($arr as $row) {
                        // 自分自身は必ず参加ユーザー側に入れる
                        if ($row['member_flag'] == "0" && $row['user_id'] != $userId) {
                            $notMembers[$row['user_id']] = $row['user_name'];
                        } else {
                            $members[$row['user_id']] = $row['user_name'];
                        }
                        if ($row['section_id'] !== null) {
                            $sectionMembers[$row['section_id']][] = $row['user_id'];
                            $sections[$row['section_id']] = $row['section_name'];
                        }
                    }
                }

                $query = "select chat_group_id, group_name from chat_group order by group_name";
                $arr = $gen_db->getArray($query);
                $groups = array();
                if ($arr) {
                    foreach($arr as $row) {
                        $groups[$row['chat_group_id']] = $row['group_name'];
                    }
                }

                $resArr = array(
                    'title' => $title,
                    'members' => $members,
                    'notMembers' => $notMembers,
                    'myId' => $userId,
                    'groupId' => $groupId,
                    'groups' => $groups,
                    'sections' => $sections,
                    'sectionMembers' => $sectionMembers,
                );
                break;

            case 'createGroup':
                $name = $form['name'];
                $query = "select * from chat_group where group_name = '{$name}'";
                if ($gen_db->existRecord($query)) {
                    $resArr["badNameMsg"] = _g("そのカテゴリ名はすでに使用されています。");
                    break;
                }
                $data = array(
                    "group_name" => $gen_db->quoteParam($name),
                );
                $gen_db->insert("chat_group", $data);
                $id = $gen_db->getSequence("chat_group_chat_group_id_seq");
                $resArr = array(
                    'id' => $id,
                );

                break;

            case 'deleteGroup':
                $groupId = $form['groupId'];
                if (!Gen_String::isNumeric($groupId)) {
                    return;
                }
                $gen_db->begin();
                $query = "
                    update chat_header set chat_group_id = null where chat_group_id = '{$groupId}';
                    delete from chat_group where chat_group_id = '{$groupId}';
                ";
                $gen_db->query($query);
                $gen_db->commit();
                $resArr = array(
                );

                break;

            case 'regConfig':
                $isNew = (!isset($form['headerId']) || !Gen_String::isNumeric($form['headerId']));

                // タイトルチェック
                $title = $form['title'];
                if ($title == "") {
                    $resArr["badTitleMsg"] = _g("タイトルを入力してください。");
                    break;
                }
                $query = "select * from chat_header where title = '{$title}'" . ($isNew ? "" : " and chat_header_id <> '{$form['headerId']}'");
                if ($gen_db->existRecord($query)) {
                    $resArr["badTitleMsg"] = _g("そのタイトルはすでに使用されています。");
                    break;
                }

                // 更新時の権限チェック
//                if (!$isNew) {
//                    $query = "select author_id from chat_header where chat_header_id = '{$form['headerId']}'";
//                    if ($gen_db->queryOneValue($query) != $userId) {
//                        return;
//                    }
//                }

                // $formはindex.phpでDBサニタイジングされており、「'」が「’」に変換されてしまっているので、
                // ここでは特別に $_REQUEST から取得する。
                $dbTitle = str_replace("'", "[gen_quot]", urldecode($_REQUEST['title']));    // 「'」を「[gen_quot]」に。表示時にもとに戻す
                $dbTitle = $gen_db->quoteParam($dbTitle);               // DBサニタイジング

                // カテゴリ
                $groupId = null;
                if ($form['groupId'] == "record") {
                    // レコードと関連付けられたスレッド
                    $query = "select chat_group_id from chat_group where group_name = '" . _g("レコード") . "'";
                    $groupId = $gen_db->queryOneValue($query);
                    if (!$groupId) {
                        $data = array(
                            "group_name" => _g("レコード"),
                        );
                        $gen_db->insert("chat_group", $data);
                        $groupId = $gen_db->getSequence("chat_group_chat_group_id_seq");
                    }
                } else if (Gen_String::isNumeric($form['groupId'])) {
                    $groupId = $form['groupId'];
                }

                $gen_db->begin();

                // ヘッダ登録
                $chatTime = new DateTime();
                $data = array(
                    "title" => $dbTitle,
                    "chat_group_id" => $groupId,
                    "create_time" => $chatTime->format("Y-m-d H:i:s"),
                );
                if ($isNew) {
                    $data["author_id"] = $userId;
                    if (isset($form["actionGroup"])) {
                        $data["action_group"] = $form["actionGroup"];
                    }
                    if (isset($form["recordId"])) {
                        $data["record_id"] = $form["recordId"];
                    }
                    if (isset($form["tempUserId"])) {
                        $data["temp_user_id"] = $form["tempUserId"];
                    }
                    $gen_db->insert("chat_header", $data);
                    $headerId = $gen_db->getSequence("chat_header_chat_header_id_seq");
                } else {
                    $gen_db->update("chat_header", $data, "chat_header_id = '{$form['headerId']}'");
                    $headerId = $form['headerId'];
                }

                // ユーザーリスト登録
                if ($form['users'] == 'all') {
                    $userArr = array();
                    $query = "select user_id from user_master order by user_id";
                    $arr = $gen_db->getArray($query);
                    foreach($arr as $row) {
                        $userArr[] = $row['user_id'];
                    }
                } else {
                    $userArr = explode(",", $form['users']);
                }
                $userArr[] = -1;        // adminは必ずメンバーに入れる（2014/04/28 DS会議での要望。利用状況を見られるようにするため）。ただしadminがメンバーに入っていることは各ユーザーからは見えない
                $userArr[] = $userId;   // 自分自身は必ずメンバーに入れる
                $addedArr[] = array();
                foreach($userArr as $user) {
                    if (Gen_String::isNumeric($user) && !in_array($user, $addedArr)) {
                        $key = array(
                            "chat_header_id" => $headerId,
                            "user_id" =>$user,
                        );
                        $data = array(
                        );
                        $gen_db->updateOrInsert("chat_user", $key, $data);
                        $addedArr[] = $user;
                    }
                }
                // 削除されたユーザー
                if (!$isNew) {
                    $users = $form['users'];
                    if ($users != "") {
                        $users .= ",";
                    }
                    $users .= "-1";
                    $query = "delete from chat_user where chat_header_id = '$headerId' and user_id not in ({$users})";
                    $gen_db->query($query);
                }

                $gen_db->commit();

                $resArr = array(
                    'status' => 'success',
                    'header_id' => $headerId,
                );
                break;

            case 'deleteChat':
                if (!isset($form['headerId']) || !Gen_String::isNumeric($form['headerId']))
                    return;

                $query = "select author_id from chat_header where chat_header_id = '{$form['headerId']}'";
                if ($gen_db->queryOneValue($query) != $userId) {
                    return;
                }

                Logic_Chat::deleteChat($form['headerId']);

                $resArr = array(
                    'status' => 'success',
                );
                break;

            case 'read':
                if (!isset($form['headerId']) || !Gen_String::isNumeric($form['headerId']))
                    return;

                // 権限チェック　兼　既読情報読み出し
                $query = "select coalesce(readed_chat_detail_id, -1) from chat_user where chat_header_id = '{$form['headerId']}' and user_id = '{$userId}'";
                $readedDetailId = $gen_db->queryOneValue($query);
                if (!$readedDetailId) {
                    $resArr = array(
                        'status' => 'permissionError',
                    );
                    break;
                }
                
                $isUnreadOnly = isset($form['isUnreadOnly']) && $form['isUnreadOnly'] == 'true';
                if (!$isUnreadOnly) {
                    // 所有者、カテゴリ
                    $query = "
                        select
                            chat_header.author_id
                            /* -1: admin、 -2: 「イーコモードからのお知らせ」スレッド、-3: 通知センター */
                            ,case chat_header.author_id when -1 then '" . ADMIN_NAME . "' when -2 then 'e-commode' when -3 then 'Genesiss' else user_master.user_name end as author_name
                            ,case when is_ecom then 1 else 0 end as is_ecom
                            ,case when is_system then 1 else 0 end as is_system
                            ,chat_group.group_name
                            ,chat_header.action_group
                            ,chat_header.record_id
                        from
                            chat_header
                            left join user_master on chat_header.author_id = user_master.user_id
                            left join chat_group on chat_header.chat_group_id = chat_group.chat_group_id
                        where
                            chat_header.chat_header_id = '{$form['headerId']}'
                    ";
                    $obj = $gen_db->queryOneRowObject($query);
                    $isMine = ($obj->author_id == $userId);
                    $author = $obj->author_name;
                    $isEcom = ($obj->is_ecom == 1);
                    $isSystem = ($obj->is_system == 1);
                    $group = $obj->group_name;
                    $goEditAction = "";
                    if ($obj->action_group && $obj->record_id) {
                        $arr = Logic_EditGroup::getAttachableGroupList();
                        $actionGroup = $obj->action_group;
                        if (substr($actionGroup,-1) == "_") {
                            $actionGroup = substr($actionGroup, 0, strlen($actionGroup) - 1);
                        }
                        if (isset($arr[$actionGroup])) {
                            $goEditAction = "{$arr[$actionGroup][0]}&{$arr[$actionGroup][1]}={$obj->record_id}";
                        }
                    }

                    // 参加ユーザーリスト
                    $query = "
                        select
                            user_master.user_name
                        from
                            chat_user
                            left join user_master on chat_user.user_id = user_master.user_id
                        where
                            chat_user.chat_header_id = '{$form['headerId']}'
                            /* 参加ユーザーリストにはadminを出さない。
                            　　adminは自動的に全スレッドに参加する（case 'regConfig' 部分を参照）が、ユーザーからは
                                adminが参加していることがわからないようにする。 */
                            and chat_user.user_id <> -1
                        ";
                    $userArr = $gen_db->getArray($query);

                    $query = "select title from chat_header where chat_header_id = '{$form['headerId']}'";
                    $title = str_replace("[gen_quot]", "'", $gen_db->queryOneValue($query));
               }

                // コンテンツ
                $query = "
                    select
                        chat_detail.chat_detail_id as id
                        ,chat_detail.user_id
                        ,chat_detail.chat_time
                        ,chat_detail.content
                        ,chat_detail.file_name
                        ,chat_detail.original_file_name
                        ,chat_detail.file_size
                        ,chat_detail.image_width
                        ,chat_detail.image_height
                        /* -1: admin、 -2: 「イーコモードからのお知らせ」スレッド、-3: 通知センター */
                        ,case chat_detail.user_id when -1 then '" . ADMIN_NAME . "' when -2 then 'e-commode' when -3 then 'Genesiss' else user_master.user_name end as name
                        ,case when chat_star.chat_detail_id is null then 0 else 1 end as is_star
                        ,t1.like as like_count
                        ,case when chat_like.chat_detail_id is null then 0 else 1 end as is_like
                    from
                        chat_detail
                        left join user_master on chat_detail.user_id = user_master.user_id
                        left join chat_star on chat_detail.chat_detail_id = chat_star.chat_detail_id and chat_star.user_id = '{$userId}'
                        left join (select chat_detail_id, count(user_id) as like from chat_like group by chat_detail_id) as t1 on chat_detail.chat_detail_id = t1.chat_detail_id
                        left join chat_like on chat_detail.chat_detail_id = chat_like.chat_detail_id and chat_like.user_id = '{$userId}'
                    where
                        chat_detail.chat_header_id = '{$form['headerId']}'
                        " .($isUnreadOnly ? "and chat_detail.chat_detail_id > '{$readedDetailId}'" : "") . "
                    order by
                        chat_time, chat_detail.chat_detail_id
                    ";

                $arr = $gen_db->getArray($query);
                $maxDetailId = -1;
                if ($arr) {
                    $storage = new Gen_Storage("ChatFiles");
                    foreach($arr as $key => $val) {
                        $arr[$key]["is_star"] = ($arr[$key]["is_star"] == "1");
                        $arr[$key]["is_like"] = ($arr[$key]["is_like"] == "1");

                        // コンテンツのエスケープ
                        //  Ajaxのエスケープはクライアント側でやるのが基本だが（セキュアコーディングガイド参照）、
                        //  チャットコンテンツだけはHTMLタグを許す仕様なので、ここで行う
                        $content = Gen_String::escapeDangerTags($val['content']);   // 危険なタグ・属性をサニタイジング
                        $arr[$key]["content"] = str_replace("[gen_quot]", "'", $content);

                        // 添付ファイルと画像の処理
                        $arr[$key]["time"] = date("Y-m-d H:i", strtotime($val["chat_time"]));
                        unset($arr[$key]["chat_time"]);
                        $arr[$key]["is_mine"] = ($val["user_id"] == $userId);
                        if ($arr[$key]["id"] > $maxDetailId) {
                            $maxDetailId = $arr[$key]["id"];
                        }
                        if ($val['file_name'] != "") {
                            $arr[$key]["file"] = h($val['file_name']);
                            $arr[$key]["org_file"] = h($val['original_file_name']);

                            $size = $arr[$key]["file_size"];
                            if ($size < 1024) {
                                $sizef = $size . "B";
                            } else if ($size < 1024 * 1024) {
                                $sizef = ceil($size / 1024) . "KB";
                            } else {
                                $sizef = Gen_Math::round($size / 1024 / 1024, "ceil", 1) . "MB";
                            }
                            $arr[$key]["file_size"] = $sizef;

                            // 画像のwidth/heightを取得。表示エリアより画像の幅が大きいなら、収まるように調整する。
                            //  htmlのimgタグのstyleで指定するため。
                            //  サイズ未指定であっても表示はできるが、画像ロード完了まで縦幅が確保されないため、自動スクロールが末端までいかない問題がある。
                            //  一方、画像ロード完了までスクロール処理を待つと表示が遅くなってしまう。
                            //  そこで、あえて画像サイズを指定して縦幅を確保している。
                            $arr[$key]["img_w"] = "";
                            $arr[$key]["img_h"] = "";
                            if (is_numeric($arr[$key]["image_width"])) {
                                if ($form['imgMaxWidth'] < $arr[$key]["image_width"]) {
                                    $arr[$key]["image_height"] *= ($form['imgMaxWidth'] / $arr[$key]["image_width"]);
                                    $arr[$key]["image_width"] = $form['imgMaxWidth'];
                                }
                                $arr[$key]["img_w"] = $arr[$key]["image_width"];
                                $arr[$key]["img_h"] = $arr[$key]["image_height"];
                            }
                        }
                        unset($arr[$key]["file_name"]);
                        unset($arr[$key]["original_file_name"]);
                    }
                }

                // 既読情報の更新
                //  「未読のみ」モードで未読がなかった場合は、既読情報を更新しない
                if (!$isUnreadOnly || $maxDetailId > -1) {
                    $data = array("readed_chat_detail_id" => $maxDetailId);
                    $where = "chat_header_id = '{$form['headerId']}' and user_id = {$userId}";
                    $gen_db->update("chat_user", $data, $where);
                }

                // 最終チャット情報の更新
                $where = ($userId == -1 ? "" : "where user_id = '{$userId}'");
                $table = ($userId == -1 ? "company_master" : "user_master");
                // アトリビュートは更新しない
                $query = "update {$table} set last_chat_header_id = '{$form['headerId']}' {$where}";
                $gen_db->query($query);

                if (!$isUnreadOnly) {
                    $resArr['title'] = $title;
                    $resArr['author'] = $author;
                    $resArr['users'] = $userArr;
                    $resArr['is_mine'] = $isMine;
                    $resArr['is_pin'] = isset($_SESSION['gen_setting_user']->chatpins) && in_array($form['headerId'], explode(",", $_SESSION['gen_setting_user']->chatpins));
                    $resArr['group'] = $group;
                    $resArr['edit_action'] = $goEditAction;
                    $resArr['is_ecom'] = $isEcom;
                    $resArr['is_system'] = $isSystem;
                }

                $resArr['contents'] = $arr;
                $resArr['readedId'] = $readedDetailId;
                $unreadArr = Logic_Chat::getUnreadCount($userId);
                $resArr['unreadCount'] = $unreadArr[0];
                $resArr['unreadCountEcom'] = $unreadArr[1];
                $resArr['unreadCountSystem'] = $unreadArr[2];
                break;

            case 'reg':
                if (!isset($form['headerId']) || !Gen_String::isNumeric($form['headerId']))
                    return;

                // ecom/systemスレッドは書き込み禁止
                $query = "select chat_header_id from chat_header where chat_header_id = '{$form['headerId']}' and (is_ecom or is_system)";
                if ($gen_db->existRecord($query)) {
                    return;
                }

                // 権限チェック
                $query = "select chat_header_id from chat_user where chat_header_id = '{$form['headerId']}' and user_id = '{$userId}'";
                if (!$gen_db->existRecord($query)) {
                    return;
                }
                // $formはindex.phpでDBサニタイジングされており、「'」が「’」に変換されてしまっているので、
                // ここでは特別に $_REQUEST から取得する。
                $content = urldecode($_REQUEST['content']);
                $dbContent = str_replace("'", "[gen_quot]", $content);    // 「'」を「[gen_quot]」に。表示時にもとに戻す
                $dbContent = $gen_db->quoteParam($dbContent);               // DBサニタイジング

                $chatTime = new DateTime();
                $data = array(
                    "chat_header_id" =>$form['headerId'],
                    "user_id" =>$userId,
                    "chat_time" => $chatTime->format("Y-m-d H:i:s"),
                    "content" => $dbContent,
                );
                $gen_db->insert("chat_detail", $data);
                $detailId = $gen_db->getSequence("chat_detail_chat_detail_id_seq");

                $resArr = array(
                    'success' => true,  // for fileUpload
                    'status' => 'success',
                    'detail_id' => $detailId,
                    'user_name' => $_SESSION["user_name"],
                    'chat_time' => $chatTime->format("Y-m-d H:i"),
                );
                
                Logic_Chat::pushNotification($form['headerId'], $content);
                
                break;

            case 'delete':
                if (!isset($form['detailId']) || !Gen_String::isNumeric($form['detailId']))
                    return;

                $query = "select user_id ,file_name from chat_detail where chat_detail_id = '{$form['detailId']}'";
                $obj = $gen_db->queryOneRowObject($query);
                if ($obj->user_id != $userId) {
                    return;
                }

                $query = "delete from chat_detail where chat_detail_id = '{$form['detailId']}'";
                $gen_db->query($query);

                if ($obj->file_name != "") {
                    $storage = new Gen_Storage("ChatFiles");
                    $storage->delete($obj->file_name);
                }

                $resArr = array(
                    'status' => 'success',
                );
                break;

            case 'star':
                if (!isset($form['detailId']) || !Gen_String::isNumeric($form['detailId']))
                    return;

                if ($userId == -1) {
                    $resArr = array(
                        'msg' => "adminはこの操作を行えません。",
                    );
                    break;
                }

                $key = array(
                    "chat_detail_id" => $form['detailId'],
                    "user_id" =>$userId,
                );
                $data = array(
                );
                $gen_db->updateOrInsert("chat_star", $key, $data);

                $resArr = array(
                    'status' => 'success',
                );
                break;

            case 'delStar':
                if (!isset($form['detailId']) || !Gen_String::isNumeric($form['detailId']))
                    return;

                if ($userId == -1) {
                    $resArr = array(
                        'msg' => "adminはこの操作を行えません。",
                    );
                    break;
                }

                $query = "delete from chat_star where chat_detail_id = '{$form['detailId']}' and user_id = '{$userId}'";
                $gen_db->query($query);

                $resArr = array(
                    'status' => 'success',
                );
                break;

            case 'like':
                if (!isset($form['detailId']) || !Gen_String::isNumeric($form['detailId']))
                    return;

                if ($userId == -1) {
                    $resArr = array(
                        'msg' => "adminはこの操作を行えません。",
                    );
                    break;
                }

                $key = array(
                    "chat_detail_id" => $form['detailId'],
                    "user_id" =>$userId,
                );
                $data = array(
                );
                $gen_db->updateOrInsert("chat_like", $key, $data);

                // 通知センター
                //  該当スレッドに参加しているユーザーにのみ通知する
                $query = "
                    select
                        max(user_name) as user_name
                        ,max(chat_header.title) as title
                        ,string_agg(chat_user.user_id::text, ',') as users
                    from
                        chat_detail
                        inner join chat_header on chat_detail.chat_header_id = chat_header.chat_header_id
                        inner join user_master on chat_detail.user_id = user_master.user_id
                        inner join chat_user on chat_detail.chat_header_id = chat_user.chat_header_id
                    where
                        chat_detail.chat_detail_id = '{$form['detailId']}'
                    group by
                        chat_detail.chat_detail_id
                ";
                $obj = $gen_db->queryOneRowObject($query);
                if ($obj) {
                    $content = sprintf(_g("%1\$s さんがスレッド「%2\$s」で、 %3\$s さんに「Good！」と言っています。"), $gen_db->quoteParam($_SESSION['user_name']), $obj->title, $obj->user_name);
                    $content .= " [chat-detail-link:{$form['detailId']}:]";
                    Gen_Chat::writeSystemChat($obj->users, $content);
                }

                $resArr = array(
                    'status' => 'success',
                );
                break;

            case 'delLike':
                if (!isset($form['detailId']) || !Gen_String::isNumeric($form['detailId']))
                    return;

                if ($userId == -1) {
                    $resArr = array(
                        'msg' => "adminはこの操作を行えません。",
                    );
                    break;
                }

                $query = "delete from chat_like where chat_detail_id = '{$form['detailId']}' and user_id = '{$userId}'";
                $gen_db->query($query);

                $resArr = array(
                    'status' => 'success',
                );
                break;

            case 'getLikeUsers':
                if (!isset($form['detailId']) || !Gen_String::isNumeric($form['detailId']))
                    return;

                $query = "
                    select
                        user_name
                    from
                        chat_like
                        inner join user_master on chat_like.user_id = user_master.user_id
                    where
                        chat_detail_id = '{$form['detailId']}'
                    order by
                        chat_like.record_create_date
                ";
                $arr = $gen_db->getArray($query);
                $users = array();
                foreach($arr as $row) {
                    $users[] = $row["user_name"];
                }

                $resArr = array(
                    'users' => $users,
                );
                break;

            default:
                return;
        }

        $gen_db->commit();

        return $resArr;
    }

    function getMultiWhereStr($column, $searchArr)
    {
        global $gen_db;

        $res = "(";
        foreach($searchArr as $search) {
            if ($search == "")
                continue;
            if ($res != "(") {
                $res .= " and ";
            }
            $search = $gen_db->quoteParam($search);
            $res .= "{$column} like '%{$search}%'";
        }
        return $res . ")";
    }

}
