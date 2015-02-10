<?php

class Logic_Schedule
{
    // $fromStr             開始日
    // $toStr               終了日
    // $userId              対象ユーザーID（nullなら全ユーザーのデータを取得）
    // $search              検索するワード
    // $isShowNewButton     新規ボタンを表示するか
    // $isLinkEnable        スケジュールをリンクにするか
    // $isCrossCount        クロス集計でデータの数をcountしたとき true
    // $hilightId           ハイライト表示するスケジュールID（$isLinkEnable = trueのときのみ有効）
    static function createTempScheduleTable($fromStr, $toStr, $userId, $search, $isShowNewButton, $isLinkEnable, $isCrossCount, $hilightId = false, $appMode = false)
    {
        global $gen_db;

        $from = strtotime($fromStr);
        $to = strtotime($toStr);
        $currentUserId = Gen_Auth::getCurrentUserId();

        // 重複予定検出
        $dupQuery = "
            select
                t1.schedule_id,
                t1u.user_id
            from
                staff_schedule as t1 cross join
                staff_schedule as t2
                left join
                    staff_schedule_user as t1u
                on  t1.schedule_id = t1u.schedule_id
                left join
                    staff_schedule_user as t2u
                on  t2.schedule_id = t2u.schedule_id
            where
                (
                    t1.begin_time is not null
                or  t1.end_time is not null
                    )
                and t1.schedule_id <> t2.schedule_id
                and t1u.user_id = t2u.user_id
                and t1.begin_date = t2.begin_date
                and (
                        (
                            t1.begin_time >= t2.begin_time
                        and t1.begin_time < t2.end_time
                        )
                    or  (
                            t2.begin_time >= t1.begin_time
                        and t2.begin_time < t1.end_time
                        )
                    )
             group by t1.schedule_id, t1u.user_id
            ";
        $gen_db->createTempTable("temp_duplicate", $dupQuery, true);

        // セル内のテキストはstring_aggを使って組み立てているが、ひとつのSQL内ではstring_aggの並び順を指定するのが難しいため、
        // いったんテンポラリテーブルを作っている。
        $tempQuery = "
            select
                user_master.user_id,
                case when user_master.user_id = {$currentUserId} then '1111111' else user_name end as for_order,
                case when t0.schedule_id = -1 then 1 else 0 end as for_order2,
                user_name,
                t0.schedule_id,
                begin_date,
                end_date,
                begin_time,
                end_time,
                schedule_text,
                schedule_memo,
                repeat_pattern,
                repeat_weekday,
                repeat_day,
                background_color,
                section_id,
                non_disclosure,
                case when t3.user_id is not null then true else false end as is_dup 
                " . ($isLinkEnable ? ",user_list,user_count" : "") . "
            from
                user_master
                left join (
                    select schedule_id, user_id from staff_schedule_user
                    /* Newボタン表示のためのダミー行 */
                    union all select -1 as schedule_id, user_id from user_master
                    ) as t0
                    on user_master.user_id = t0.user_id
                left join (
                    select
                        staff_schedule.schedule_id
                        ,begin_date
                        ,end_date
                        ,begin_time
                        ,end_time
                        ,schedule_text
                        ,schedule_memo
                        ,repeat_pattern
                        ,repeat_weekday
                        ,repeat_day
                        ,background_color
                        ,non_disclosure
                    from
                        staff_schedule
                    left join (
                        select
                            schedule_id
                        from
                            staff_schedule_user
                        where
                            user_id = '{$currentUserId}'
                        group by
                            schedule_id
                        ) as t_user
                        on staff_schedule.schedule_id = t_user.schedule_id
                     where
                        ((repeat_pattern is null and begin_date between '" . date('Y-m-d', $from) . "' and '" . date('Y-m-d', $to) . "')
                        or (repeat_pattern is not null and begin_date <= '" . date('Y-m-d', $to) . "' and coalesce(end_date, '2037-12-31'::date) >= '" . date('Y-m-d', $from) . "'))
                        and (not staff_schedule.non_disclosure or t_user.schedule_id is not null) 
                        " . ($search === null || $search == "" ? "" : " and schedule_text ilike '%{$search}%'") . "
                    " . ($isShowNewButton ? "
                        /* Newボタン表示のためのダミー行 */
                        union all
                            select
                                -1 as schedule_id, '2037-12-31' as begin_date, null as end_date, null as begin_time, null as end_time, '' as schedule_text, '' as schedule_memo
                                ,null as repeat_pattern ,null as repeat_weekday ,null as repeat_day, null as background_color, false as non_disclosure
                    " : "") . "
                    ) as t1
                    on t0.schedule_id = t1.schedule_id
                    " . ($isLinkEnable ?
                        "left join (
                             select
                                staff_schedule_user.schedule_id
                                ,string_agg(user_name, ', ') as user_list
                                ,count(user_name) as user_count
                             from
                                staff_schedule_user
                                inner join user_master on staff_schedule_user.user_id = user_master.user_id
                             group by
                                staff_schedule_user.schedule_id
                            ) as t2
                            on t0.schedule_id = t2.schedule_id
                         "
                    :
                        ""
                    ) . "
                left join temp_duplicate as t3 on t0.user_id = t3.user_id and t0.schedule_id = t3.schedule_id
                " . ($userId === null ? "" : "where user_master.user_id = '{$userId}'") . "
        ";
        $gen_db->createTempTable("temp_schedule_pre", $tempQuery, true);

        // データ取得
        $query = "
             select
                 user_id
                 ," . ($isCrossCount ? "" : "max") . "(for_order) as for_order
                 ," . ($isCrossCount ? "" : "max") . "(user_name) as user_name
                 ," . ($isCrossCount ? "" : "max") . "(section_id) as section_id 
            ";
            for ($day = $from; $day <= $to; $day += 86400) {        // 86400sec = 1day

                $query .= ",";
                if (!$isCrossCount) {
                    $query .= "string_agg(";
                }
                $weekNum = floor((int)date('d', $day) / 7 - 0.1) + 1;   // 月の第何週目か

                $query .= "
                    case when schedule_id=-1 then
                        /* 新規ボタン */
                        " . ($isCrossCount || !$isShowNewButton ?
                         "null"
                         : "'[[schedule_replace_new1]]' || user_id  || '[[schedule_replace_new2]]' || '" . date('Y-m-d', $day) . " " . date('H:0', strtotime("+1 hour")) . "' || '[[schedule_replace_new3]]'") . "
                    else
                        /* スケジュール表示 */
                        case when
                            /* 繰り返しなし */
                            (repeat_pattern is null and begin_date = '" . date('Y-m-d', $day) . "')
                            /* 繰り返しあり */
                            or ((begin_date <= '" . date('Y-m-d', $day) . "' and coalesce(end_date, '2037-12-31'::date) >= '" . date('Y-m-d', $day) . "')
                                and (
                                    /* 毎日 */
                                    (repeat_pattern = 0)
                                    /* 休業日以外 */
                                    or (repeat_pattern = 1 and (select holiday from holiday_master where holiday = '" . date('Y-m-d', $day) . "') is null)
                                    /* 毎週 x曜日 */
                                    or (repeat_pattern = 2 and repeat_weekday = '" . date('w', $day) . "')
                                    /* 毎月第1-4 x曜日 */
                                    " . ($weekNum == 1 ? "or (repeat_pattern = 3 and repeat_weekday = '" . date('w', $day) . "')" : "") . "
                                    " . ($weekNum == 2 ? "or (repeat_pattern = 4 and repeat_weekday = '" . date('w', $day) . "')" : "") . "
                                    " . ($weekNum == 3 ? "or (repeat_pattern = 5 and repeat_weekday = '" . date('w', $day) . "')" : "") . "
                                    " . ($weekNum == 4 ? "or (repeat_pattern = 6 and repeat_weekday = '" . date('w', $day) . "')" : "") . "
                                    /* 毎月最終 x曜日 */
                                    " . ((int)date('t', $day) - (int)date('d', $day) < 7 ? "or (repeat_pattern = 7 and repeat_weekday = '" . date('w', $day) . "')" : "") . "
                                    /* 毎月 x日 */
                                    or (repeat_pattern = 8 and repeat_day = '" . date('d', $day) . "')
                             )) then
                            " . ($isLinkEnable ?
                                "/* スケジュール画面のリストの各セルの表示内容 */
                                 case when coalesce(schedule_memo,'')='' and user_count <= 1 and repeat_pattern is null then
                                    '[[schedule_replace_edit_no_memo1]]'
                                 else
                                    /* マウスオーバー表示内容 */
                                    '[[schedule_replace_edit_memo1]]' || schedule_id
                                    || '[[schedule_replace_edit_memo2]]' || user_list
                                    || case when
                                        repeat_pattern is null then
                                            ''
                                        else
                                            '[[schedule_replace_edit_memo_repeat]]' || case repeat_pattern when null then '' " . Gen_Option::getRepeatSchedule("list-query") . " else '' end
                                            || case when repeat_pattern >= 2 and repeat_pattern <= 7 then case repeat_weekday " . Gen_Option::getWeekdays("list-query") . " end else '' end
                                            || case when repeat_pattern = 8 then repeat_day::text else '' end
                                            || ' (' || '" . _g("期限") . " : ' || coalesce(end_date::text,'" . _g("なし") . "') || ')'
                                        end
                                    || '[[schedule_replace_edit_memo3]]' || coalesce(schedule_memo ,'')
                                    || '[[schedule_replace_edit_memo4]]' || schedule_id
                                    || '[[schedule_replace_edit_memo5]]'
                                        /* マウスオーバータイトル（スケジュールの内容を表示） */
                                        || schedule_text
                                 end
                                 || '[[schedule_replace_edit_foot1]]' || schedule_id  || '[[schedule_replace_edit_foot2]]'
                                    " . (Gen_String::isNumeric($hilightId) ?
                                        "|| case when schedule_id = '{$hilightId}' then '[[schedule_replace_edit_hilight1]]8DC4FC[[schedule_replace_edit_hilight2]]' else '' end" :
                                        "|| case when background_color is not null and background_color <> '' then '[[schedule_replace_edit_hilight1]]' || background_color || '[[schedule_replace_edit_hilight2]]' else '' end") . "
                                    /* スケジュール表示内容 */
                                    || case when begin_time is null then '' else to_char(begin_time,'HH24:MI ') end
                                    || case when end_time is null then '' else '- ' || to_char(end_time,'HH24:MI ') end
                                    || schedule_text
                                    " . (Gen_String::isNumeric($hilightId) ?
                                        "|| case when schedule_id = '{$hilightId}' then '[[schedule_replace_edit_hilight3]]' else '' end" :
                                        "|| case when background_color is not null and background_color <> '' then '[[schedule_replace_edit_hilight3]]' else '' end") . "
                                 /* スケジュールの後ろに表示されるアイコン群 */
                                 || case when non_disclosure then '[[schedule_replace_edit_nondisicon]]' else '' end
                                 || case when repeat_pattern is not null then '[[schedule_replace_edit_repeaticon]]' else '' end
                                 || case when user_count > 1 then '[[schedule_replace_edit_shareicon]]' else '' end
                                 || case when coalesce(schedule_memo,'')<>'' then '[[schedule_replace_edit_memoicon]]' else '' end
                                 || case when is_dup then '[[schedule_replace_edit_dupicon]]' else '' end
                                 || '[[schedule_replace_edit_foot3]]'
                                 "
                            :
                                ($appMode ? "
                                    '[[%' || schedule_id || '%]]'
                                    || case when background_color is not null and background_color <> '' then '[[#' || background_color || '#]]' else '' end ||
                                    " : "") . 
                                "case when begin_time is null then '' else to_char(begin_time,'HH24:MI ') end
                                 || case when end_time is null then '' else '- ' || to_char(end_time,'HH24:MI ') end 
                                 || schedule_text" .
                                ($appMode ? "
                                    || case when coalesce(schedule_memo,'')<>'' then ' [M]' else '' end
                                    " : "") .
                                "|| '<br>'"
                            ) . "
                       else null end end
                ";
                if (!$isCrossCount) {
                    // begin_dateは含めなくていい。繰り返しスケジュールがあるので、含めるとうまくいかない
                    $query .= ",'' order by for_order2, begin_time, end_time)";
                }
                $query .= " as day" . date('Ymd', $day);
            }
            $query .= "
             from
                 temp_schedule_pre
             " . ($isCrossCount ? "" : "group by user_id") . "
             order by
                for_order
        ";
        $gen_db->createTempTable("temp_schedule", $query, true);
    }

    static function replaceTagToHTML($value, $isHome = false) {
        $repArr = array(
            // 新規ボタン表示
            "[[schedule_replace_new1]]" => "<a href=\"javascript:scheduleNewEdit("
            ,"[[schedule_replace_new2]]" => ",'"
            ,"[[schedule_replace_new3]]" => "');\"><img src='img/add.png' border='0'/></a><br>"

            // スケジュール表示（マウスオーバーなし）
            ,"[[schedule_replace_edit_no_memo1]]" => "・<a href='#"

            // スケジュール表示（マウスオーバーあり）
            ,"[[schedule_replace_edit_memo1]]" => "<p class='helptext_"
            ,"[[schedule_replace_edit_memo2]]" => "' style='display:none;'><b>" . _g("参加ユーザー") . "</b>: "
            ,"[[schedule_replace_edit_memo_repeat]]" => "<br><br><b>" . _g("繰り返し") . "</b>: "
            ,"[[schedule_replace_edit_memo3]]" => "<br><br>"
            ,"[[schedule_replace_edit_memo4]]" => "</p>・<a class='gen_chiphelp' href='#' rel='p.helptext_"
            ,"[[schedule_replace_edit_memo5]]" => "' title='"

            // スケジュール表示（マウスオーバーあり・なし共通）
            ,"[[schedule_replace_edit_foot1]]" => "' style='color:" . ($isHome ? "black;text-decoration:none" : "blue") . "' onclick=\"javascript:scheduleGoEdit("
            ,"[[schedule_replace_edit_foot2]]" => ");\">"
            ,"[[schedule_replace_edit_foot3]]" => "</a><div style='height:2px'></div>"
            ,"[[schedule_replace_edit_hilight1]]" => "<span style='background:#"
            ,"[[schedule_replace_edit_hilight2]]" => "'>"
            ,"[[schedule_replace_edit_hilight3]]" => "</span>"
            ,"[[schedule_replace_edit_memoicon]]" => "<img src='img/memo.png' style='padding-left:2px' border='0'/>"
            ,"[[schedule_replace_edit_repeaticon]]" => "<img src='img/loop.png' style='padding-left:2px' border='0'/>"
            ,"[[schedule_replace_edit_shareicon]]" => "<img src='img/users.png' style='padding-left:2px' border='0'/>"
            ,"[[schedule_replace_edit_nondisicon]]" => "<img src='img/lock.png' style='width:15px;height:14px;padding-left:2px' border='0'/>"
            ,"[[schedule_replace_edit_dupicon]]" => "</a><span style='font-weight:bold;color:red'>&nbsp;[!!]</span><a>"

// Google Calendar風
//            // スケジュール表示（マウスオーバーなし）
//            ,"[[schedule_replace_edit_no_memo1]]" => "<div style='background:#9A9CFF;color:black;border:1px solid #373AD7'><a href='#"
//
//            // スケジュール表示（マウスオーバーあり）
//            ,"[[schedule_replace_edit_memo1]]" => "<div style='background:#9A9CFF;color:black;border:1px solid #373AD7'><p class='helptext_"
//            ,"[[schedule_replace_edit_memo2]]" => "' style='display:none;'><b>" . _g("参加ユーザー") . "</b>: "
//            ,"[[schedule_replace_edit_memo_repeat]]" => "<br><br><b>" . _g("繰り返し") . "</b>: "
//            ,"[[schedule_replace_edit_memo3]]" => "<br><br>"
//            ,"[[schedule_replace_edit_memo4]]" => "</p><a class='gen_chiphelp' href='#' rel='p.helptext_"
//            ,"[[schedule_replace_edit_memo5]]" => "' title='"
//
//            // スケジュール表示（マウスオーバーあり・なし共通）
//            ,"[[schedule_replace_edit_foot1]]" => "' style='color:black;text-decoration:none' onclick=\"javascript:scheduleGoEdit("
//            ,"[[schedule_replace_edit_foot2]]" => ");\">"
//            ,"[[schedule_replace_edit_foot3]]" => "</a></div><div style='height:2px'></div>"
//            ,"[[schedule_replace_edit_memoicon]]" => "<img src='img/memo.png' style='padding-left:2px' border='0'/>"
//            ,"[[schedule_replace_edit_repeaticon]]" => "<img src='img/loop.png' style='padding-left:2px' border='0'/>"
//            ,"[[schedule_replace_edit_shareicon]]" => "<img src='img/users.png' style='padding-left:2px' border='0'/>"

            // 共通
            ,"\r\n" => "<br>"
        );
        $srcArr = array();
        $desArr = array();
        foreach($repArr as $src => $des) {
            $srcArr[] = $src;
            $desArr[] = $des;
        }

        return str_replace($srcArr, $desArr, $value);
    }
}
