<?php

require_once("Model.class.php");

class Config_Schedule_Edit extends Base_EditBase
{

    function setQueryParam(&$form)
    {
        $this->keyColumn = 'schedule_id';
        $this->selectQuery = "
            select
                staff_schedule.*
                ,t_user.user_id
                ,coalesce(record_update_date, record_create_date) as gen_last_update
                ,coalesce(record_updater, record_creator) as gen_last_updater
            from
                staff_schedule
                inner join (select schedule_id as sid, user_id from staff_schedule_user) as t_user on staff_schedule.schedule_id = t_user.sid
            [Where]
            order by
                schedule_id
        ";
    }

    function setViewParam(&$form)
    {
        global $gen_db;

        $this->modelName = "Config_Schedule_Model";

        $form['gen_pageTitle'] = _g('スケジュール');
        $form['gen_entryAction'] = "Config_Schedule_Entry";
        $form['gen_listAction'] = "Config_Schedule_List";
        $form['gen_onLoad_noEscape'] = "onLoad()";
        $form['gen_beforeEntryScript_noEscape'] = "beforeEntry()";

        $vErr = false;
        if (isset($form['users'])) {
            // valid error時
            $vErr = true;
            $userArr = explode(",", $form['users']);
            foreach($userArr as $key => $userId) {
                if (!Gen_String::isNumeric($userId)) {
                    unset($userArr[$key]);
                }
            }
            $userCsv = join(",", $userArr);
        } else {
            $isNew = !isset($form['schedule_id']) || !Gen_String::isNumeric($form['schedule_id']);
            $defaultUserId = (isset($form['user_id']) && Gen_String::isNumeric($form['user_id']) ? $form['user_id'] : Gen_Auth::getCurrentUserId());
            if ($defaultUserId == -1) { // adminはスケジュール登録できない
                $defaultUserId = $gen_db->queryOneValue("select user_id from user_master order by user_id limit 1");
                if (!$defaultUserId) {
                    $defaultUserId = -99999;    // ユーザーが一人も登録されていないとき。dummy
                }
            }
        }
        $query = "
            select 
                user_id
                ,user_name
                ,case when user_id in (" . ($vErr ? ($userCsv == "" ? "-99999" : $userCsv) : ($isNew ? $defaultUserId : "select user_id from staff_schedule_user where schedule_id='{$form['schedule_id']}'")) . ") then 1 else 0 end 
                    as is_member
            from 
                user_master 
            order by user_id
        ";
        $arr = $gen_db->getArray($query);
        
        $members = "";
        $notMembers = "";
        if ($arr) {
            foreach($arr as $row) {
                if ($row['is_member']=='1') {
                    $members .= "\" + userSelectGetLeftBoxLine('" . h($row['user_id']) . "','" . h($row['user_name']) . "') + \"";
                } else {
                    $notMembers .= "\" + userSelectGetRightBoxLine('" . h($row['user_id']) . "','" . h($row['user_name']) . "') + \"";
                }
            }
        }
        
        $form['gen_javascript_noEscape'] = "
            function onLoad() {
                $('#background_color option').each(function(i,val) {
                    $(this).css('background','#'+$(this).val());
                });
                
                var elm = $('#users_area');
                if (elm.length == 0) {
                    var html = \"<div id='user_select_area' style='text-align:left;padding-top:20px'>\"
                        + \"<b><span style='height:20px;font-size:14px;color:blue;border-left:solid 5px blue'>&nbsp;&nbsp;" . _g("参加ユーザー") . "</span></b>\"
                        + \"<table style='padding-top:20px'><tr>\"
                            + \"<td width='200px' valign='top' style='border:1px'>" . _g("参加ユーザー") . "<br>\"
                                + \"<div style='height:220px;overflow-y:auto; border:solid 1px #cccccc'>\"
                                    + \"<table id='user_select_leftBox'>{$members}</table>\"
                                + \"</div>\"
                            + \"</td>\"
                            + \"<td width='10px'></td>\"
                            + \"<td width='200px' valign='top'>" . _g("追加可能なユーザー") . "<br>\"
                                + \"<div style='width:100%; text-align:center'>\"
                                    + \"<input id='user_select_filter' type='text' style='width:120px'>\"
                                    + \"<input type='button' style='width:60px' value='" . _g("絞込み") . "' onclick='javascript:userSelectUserFilter()'>\"
                                + \"</div>\"
                                + \"<div style='height:170px; overflow-y:auto; border:solid 1px #cccccc'>\"
                                    + \"<table id='user_select_rightBox'>{$notMembers}</table>\"
                                + \"</div>\"
                                + \"<div style='height:3px'></div>\"
                                + \"<div style='width:100%; text-align:center'>\"
                                    + \"<input type='button' style='width:100px' value='<< " . _g("追加") . "' onclick='javascript:userSelectAddUser()'>\"
                                    + \"<input id='user_select_alterCheckAll' type='checkbox' onclick='javascript:userSelectAlterCheckAll()'><span style='font-size:11px'>" . _g("全チェック") . "</span>\"
                                + \"</div>\"
                            + \"</td>\"
                        + \"</tr></table>\"
                    +\"</div>\";
                    $('#gen_edit_area').append(html);
                }
                repeatPatternChange();
            }
            
            function userSelectGetLeftBoxLine(userId, userName) {
                var line = \"<tr id='user_select_leftBoxTr_\" + gen.util.escape(userId) + \"' style='height:20px'>\"
                    + \"<td id='user_select_leftBoxTd_\" + gen.util.escape(userId) + \"' style='width:130px; font-size: 12px; overflow:hidden'>\" + gen.util.escape(userName) + \"</td>\"
                    + \"<td><img class='imgContainer sprite-close' src='img/space.gif' style='vertical-align: middle; cursor:pointer;' title='" . _g("削除") . "' onclick='userSelectDeleteUser(\" + gen.util.escape(userId) + \")'></td>\"
                    + \"</tr>\";
                return line;
            }

            function userSelectGetRightBoxLine(userId, userName) {
                var line = \"<tr id='user_select_rightBoxTr_\" + gen.util.escape(userId) + \"' style='height:20px'>\"
                    + \"<td><input id='user_select_rightBoxCheck_\" + gen.util.escape(userId) + \"' type='checkbox'></td>\"
                    + \"<td id='user_select_rightBoxTd_\" + gen.util.escape(userId) + \"' style='width:100px; font-size: 12px; overflow:hidden' onclick='javascript:userSelectAlterCheckUser(\" + gen.util.escape(userId) + \")'>\" + gen.util.escape(userName) + \"</td>\"
                    + \"</tr>\";
                return line;
            }
            
            function userSelectUserFilter() {
                var filter = $('#user_select_filter').val();
                $('[id^=user_select_rightBoxTr_]').each(function(){
                    var userId = this.id.replace('user_select_rightBoxTr_', '');
                    var name = $('#user_select_rightBoxTd_' + userId).html();
                    this.style.display = (name.indexOf(filter) >= 0 ? '' : 'none');
                });
            }

            function userSelectAlterCheckUser(userId) {
                var elm = $('#user_select_rightBoxCheck_' + userId);
                elm.attr('checked', !elm.is(':checked'));
            }

            function userSelectAddUser() {
                $('[id^=user_select_rightBoxCheck_]').each(function(){
                    if (this.checked) {
                        var userId = this.id.replace('user_select_rightBoxCheck_', '');
                        var name = $('#user_select_rightBoxTd_' + userId).html();
                        $('#user_select_leftBox').append(userSelectGetLeftBoxLine(userId, name, true));
                        $('#user_select_rightBoxTr_' + userId).remove();
                    }
                });
            }

            function userSelectAlterCheckAll() {
                var chk = $('#user_select_alterCheckAll').is(':checked');
                $('[id^=user_select_rightBoxTr_]').each(function(){
                    var userId = this.id.replace('user_select_rightBoxTr_', '');
                    if (this.style.display != 'none') {
                        document.getElementById('user_select_rightBoxCheck_' + userId).checked = chk;
                    }
                });
           }

            function userSelectDeleteUser(userId) {
                var name = $('#user_select_leftBoxTd_' + userId).html();
                $('#user_select_rightBox').append(userSelectGetRightBoxLine(userId, name));
                $('#user_select_leftBoxTr_' + userId).remove();
            }
            
            function repeatPatternChange() {
                var val = $('#repeat_pattern').val();
                alterDisabled($('#repeat_weekday'), (val == '' || val == '0' || val == '1' || val == '8'));
                alterDisabled($('#repeat_day'), (val != '8'));
                alterDisabled($('#end_date'), (val == ''));
            }
            
            function alterDisabled(elm, isDisabled) {
                gen.ui.alterDisabled(elm, isDisabled);
                elm.css('background-color', isDisabled ? '#ccc' : '#fff');
            }
            
            // hourが変更されたときのみ
            function setDefaultMinute(name) {
                var hourElm = $('#' + name + '_hour');
                var minElm = $('#' + name + '_minute');
                if (hourElm.val()=='') {
                    minElm.val('');
                } else if (minElm.val()=='') {
                    minElm.val('0');
                }
            }   
            
            // 登録
            function beforeEntry() {
                var users = '';
                $('[id^=user_select_leftBoxTd_]').each(function(){
                    if (users != '')
                        users += ',';
                    users += this.id.replace('user_select_leftBoxTd_', '');
                });
                $('form')
                    .append(\"<input type='hidden' name='users' value='\" + users+ \"'>\")
                    .get(0).submit();
            }
        ";
        
        if (isset($form['schedule_id'])) {
            $form['gen_message_noEscape'] =
                    "<input type=\"button\" class=\"gen-button\" value='" . _g("このスケジュールを削除") . "' onClick='deleteItem();'><br><br>";

            $form['gen_javascript_noEscape'] .= "
                function deleteItem() {
                    if (!confirm('" . _g("このスケジュールを削除してもよろしいですか？") . "')) return;
                    document.body.style.cursor = 'wait';
                    location.href = 'index.php?action=Config_Schedule_Delete&schedule_id=" . h($form['schedule_id']) ."';
                }
            ";
        }

        $query = "select generate_series(1,31), generate_series(1,31)";
        $option_day = $gen_db->getHtmlOptionArray($query, false);

        $form['gen_editControlArray'] = array(
            array(
                'label' => _g('日付'),
                'type' => 'calendar',
                'name' => 'begin_date',
                'value' => (isset($form['begin_date']) ? date("Y-m-d", strtotime($form['begin_date'])) : date("Y-m-d")),
                'size' => '8',
                'require' => true,
                'isCalendar' => true,
            ),
            array(
                'type' => 'literal',
                'denyMove' => true,
            ),            
            // type 'select_hour_minute' は 2009で追加されたタイプ。
            // name/id は、時は name+'_hour'、分が name+'_minute' になる。
            array(
                'label' => _g('開始時刻'),
                'type' => 'select_hour_minute',
                'name' => 'begin_time',
                'hourSelected' => (isset($form['begin_time_hour']) ? $form['begin_time_hour'] : (isset($form['begin_time']) ? date("H", strtotime($form['begin_time'])) : "")),
                'minuteSelected' => (isset($form['begin_time_minute']) ? $form['begin_time_minute'] : (isset($form['begin_time']) ? date("i", strtotime($form['begin_time'])) : "")),
                'onChange_noEscape' => "setDefaultMinute('begin_time')",
                'onChangeHourOnly' => true,     // onChangeが時間セレクタのみに適用される
                'hasNothingRow' => true,
                'size' => '12',
                'require' => true
            ),
            array(
                'label' => _g('終了時刻'),
                'type' => 'select_hour_minute',
                'name' => 'end_time',
                'hourSelected' => (isset($form['end_time_hour']) ? $form['end_time_hour'] : (isset($form['end_time']) ? date("H", strtotime($form['end_time'])) : "")),
                'minuteSelected' => (isset($form['end_time_minute']) ? $form['end_time_minute'] : (isset($form['end_time']) ? date("i", strtotime($form['end_time'])) : "")),
                'onChange_noEscape' => "setDefaultMinute('end_time')",
                'onChangeHourOnly' => true,     // onChangeが時間セレクタのみに適用される
                'hasNothingRow' => true,
                'size' => '12',
                'require' => true
            ),
            array(
                'label' => _g('スケジュール'),
                'type' => 'textarea',
                'name' => 'schedule_text',
                'value' => @$form['schedule_text'],
                'size' => '12',
                'ime' => 'on',
                'require' => true
            ),
            array(
                'label' => _g('メモ'),
                'type' => 'textarea',
                'name' => 'schedule_memo',
                'value' => @$form['schedule_memo'],
                'size' => '12',
                'ime' => 'on',
            ),
            array(
                // 選択肢の背景色はJSでつけている
                'label' => _g('背景色'),
                'type' => 'select',
                'name' => 'background_color',
                'options' => array(""=>_g("なし"), "FFB6C1"=>_g("スケジュール色1"), "00FFFF"=>_g("スケジュール色2"), "FFFF00"=>_g("スケジュール色3"), "99ff99"=>_g("スケジュール色4"), "FFA500"=>_g("スケジュール色5"), "FF6347"=>_g("スケジュール色6"), "8FBC8F"=>_g("スケジュール色7")),
                'selected' => @$form['background_color'],
            ),
            array(
                'label' => _g('非公開'),
                'type' => 'checkbox',
                'name' => 'non_disclosure',
                'onvalue' => 'true', // trueのときの値。デフォルト値ではない
                'value' => @$form['non_disclosure'],
                'helpText_noEscape' => _g('このチェックをオンにすると、参加メンバー以外には非公開となります。')
            ),
            array(
                'type' => 'literal',
                'denyMove' => true,
            ),   
            array(
                'type' => 'literal',
                'denyMove' => true,
            ),   
             array(
                'label' => _g("繰り返し予定"),
                'type' => 'section',
                'denyMove' => true,
            ),
            array(
                'type' => 'literal',
                'denyMove' => true,
            ),   
            array(
                'label' => _g('繰り返し'),
                'type' => 'select',
                'name' => 'repeat_pattern',
                'options' => Gen_Option::getRepeatSchedule('options'),
                'selected' => @$form['repeat_pattern'],
                'onChange_noEscape' => 'repeatPatternChange()'
            ),
            array(
                'label' => _g('曜日'),
                'type' => 'select',
                'name' => 'repeat_weekday',
                'options' => Gen_Option::getWeekdays('options'),
                'selected' => (isset($form['repeat_weekday']) ? $form['repeat_weekday'] : (isset($form['begin_date']) ? date("w", strtotime($form['begin_date'])) : date("w"))),
            ),
            array(
                'label' => _g('日付'),
                'type' => 'select',
                'name' => 'repeat_day',
                'options' => $option_day,
                'selected' => @$form['repeat_day'],
            ),
            array(
                'label' => _g('期限'),
                'type' => 'calendar',
                'name' => 'end_date',
                'value' => (isset($form['end_date']) && $form['end_date'] != "" ? date("Y-m-d", strtotime($form['end_date'])) : ""),
                'size' => '8',
                'isCalendar' => true,
            ),
            
            // トークボードとの間をあける
            array(
                'type' => 'literal',
                'denyMove' => true,
            ),   
            array(
                'type' => 'literal',
                'denyMove' => true,
            ),   
        );
        
        // gen_app ユーザーリスト
        if ($_SESSION['gen_app']) {
            $query = "
                select 
                    user_id
                    ,user_name
                    ,case when user_id in (" . ($isNew ? $defaultUserId : "select user_id from staff_schedule_user where schedule_id='{$form['schedule_id']}'") . ") then 1 else 0 end 
                        as is_member
                from 
                    user_master 
                order by 
                    /* 自分自身を先頭に */
                    case when user_id = '" . ($_SESSION['user_id']) . "' then -999 else user_id end
            ";
            $arr = $gen_db->getArray($query);
            
            $form['gen_editControlArray'][] = 
                array(
                    'label' => _g("参加ユーザー"),
                    'type' => 'section',
                );
            foreach($arr as $row) {
                for ($i=0; $i<=1; $i++) {
                    if (($i==0 && $row['is_member']=='1') || ($i==1 && $row['is_member']!='1')) {
                        $form['gen_editControlArray'][] = 
                            array(
                                'label' => $row['user_name'],
                                'type' => 'checkbox',
                                'name' => 'user_' . $row['user_id'],
                                'value' => $i==0 ? 'true' : 'false',
                            );
                    }
                }
            }
        }
    }

}
