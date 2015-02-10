<?php

class Config_Schedule_Model extends Base_ModelBase
{

    protected function _getKeyColumn()
    {
        return 'schedule_id';
    }

    protected function _setDefault(&$param, $entryMode)
    {
    }

    protected function _getColumns()
    {
        $columns = array(
            array(
                "column" => "schedule_id",
                "pattern" => "id",
            ),
            array(
                "column" => "users",
                "validate" => array(
                    array(
                        // カンマ区切りの数値
                        "cat" => "csvNum",
                        "msg" => _g('ユーザーが正しくありません。'),
                    ),
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('ユーザーがマスタに登録されていません。'),
                        "skipHasError" => true,
                        // カンマ区切りのuser_idの中で、user_masterに含まれていないものをselectする。
                        // 普通に existRecord で select user_id from user_master where user_id in ($1) とやればいいように思えるが、
                        // FWで$1の両側にシングルコーテーションが付与されるのでうまくいかない。
                        "param" => "select * from regexp_split_to_table($1, ',') where regexp_split_to_table not in (select user_id::text from user_master)"
                    ),
                ),
            ),
            array(
                "column" => "begin_date",
                "validate" => array(
                    array(
                        "cat" => "systemDateOrLater",
                        "msg" => _g('日付')
                    ),
                ),
            ),
            array(
                "column" => "begin_time",
                "convert" => array(
                    array(
                        "cat" => "blankToNull",
                    ),
                ),
                "skipValidatePHP" => "$1===null",
                "skipValidateJS" => "true", // 時分セレクタなのでクライアントバリデーションは行えない。
                "validate" => array(
                    array(
                        "cat" => "timeString",
                        "msg" => _g('開始時刻が正しくありません。')
                    ),
                    array(
                        "cat" => "eval",
                        "msg" => _g('開始時刻を指定してください。'),
                        "skipHasError" => true,
                        "evalPHP" => "\$res=($1!=''||[[end_time]]=='')"
                    ),
                ),
            ),
            array(
                "column" => "end_time",
                "convert" => array(
                    array(
                        "cat" => "blankToNull",
                    ),
                ),
                "skipValidatePHP" => "$1===null",
                "skipValidateJS" => "true", // 時分セレクタなのでクライアントバリデーションは行えない。
                "validate" => array(
                    array(
                        "cat" => "timeString",
                        "msg" => _g('終了時刻が正しくありません。')
                    ),
                    array(
                        "cat" => "eval",
                        "msg" => _g("終了時刻が開始時刻より早くなっています。"),
                        "skipHasError" => true,
                        "evalPHP" => "\$val1=[[begin_time]];\$val2=$1;if(\$val1==''||\$val2==''){\$res=true;}else{\$res=(strtotime(date('2000/1/1 ').\$val1)<=strtotime(date('2000/1/1 '.\$val2)));};",
                    ),
                ),
            ),
            array(
                "column" => "schedule_text",
                "validate" => array(
                    array(
                        "cat" => "required",
                        "msg" => _g('スケジュールを入力してください。')
                    ),
                ),
            ),
            array(
                "column" => "non_disclosure",
                "pattern" => "bool",
            ),
           
            // 繰り返し予定
            array(
                "column" => "repeat_pattern",
                "skipValidatePHP" => "$1==''",
                "skipValidateJS" => "$1==''",
                "validate" => array(
                    array(
                        "cat" => "range",
                        "msg" => _g("繰り返し指定が正しくありません。"),
                        "param" => array(0,8)
                    ),
                ),
            ),
            array(
                "column" => "repeat_weekday",
                "skipValidatePHP" => "$1==''",
                "skipValidateJS" => "$1==''",
                "validate" => array(
                    array(
                        "cat" => "range",
                        "msg" => _g("繰り返しの曜日が正しくありません。"),
                        "param" => array(0,6)
                    ),
                ),
            ),
            array(
                "column" => "repeat_day",
                "skipValidatePHP" => "$1==''",
                "skipValidateJS" => "$1==''",
                "validate" => array(
                    array(
                        "cat" => "range",
                        "msg" => _g("繰り返しの日付が正しくありません。"),
                        "param" => array(1,31)
                    ),
                ),
            ),
            array(
                "column" => "end_date",
                "skipValidatePHP" => "$1==''",
                "skipValidateJS" => "$1==''",
                "validate" => array(
                    array(
                        "cat" => "systemDateOrLater",
                        "msg" => _g('日付')
                    ),
                ),
            ),
        );

        return $columns;
    }

    protected function _regist(&$param, $isFirstRegist)
    {
        global $gen_db;

        if (isset($param['schedule_id'])) {
            $key = array("schedule_id" => $param['schedule_id']);
            
            // 更新の場合、ユーザーリストはいったん削除
            $query = "delete from staff_schedule_user where schedule_id = '{$param['schedule_id']}'";
            $gen_db->query($query);
        } else {
            $key = null;
        }
        $data = array(
            'begin_date' => $param['begin_date'],
            'begin_time' => $param['begin_time'],
            'end_time' => $param['end_time'],
            'schedule_text' => $param['schedule_text'],
            'schedule_memo' => $param['schedule_memo'],
            'background_color' => $param['background_color'],
            'non_disclosure' => $param['non_disclosure'],

            'repeat_pattern' => $param['repeat_pattern'] === "" ? null : $param['repeat_pattern'],
            // 以下は disabled の場合はPOSTされない
            'repeat_weekday' => isset($param['repeat_weekday']) && $param['repeat_weekday'] !== "" ? $param['repeat_weekday'] : null,
            'repeat_day' => isset($param['repeat_day']) && $param['repeat_day'] !== "" ? $param['repeat_day'] : null,
            'end_date' => isset($param['end_date']) && $param['end_date'] !== "" ? $param['end_date'] : null,
        );
        $gen_db->updateOrInsert('staff_schedule', $key, $data);

        // id(keyColumnの値)を戻す。keyColumnがないModelではfalseを戻す。
        if (isset($key)) {
            $key = $param['schedule_id'];
        } else {
            $key = $gen_db->getSequence("staff_schedule_schedule_id_seq");
        }
        
        // ユーザーリスト
        $arr = explode(",", $param['users']);
        foreach($arr as $userId) {
            $data = array(
                'schedule_id' => $key, 
                'user_id' => $userId,
            );
            $gen_db->insert('staff_schedule_user', $data);
        }
        
        // 通知センター
        //  他ユーザーのスケジュールを登録/更新した場合、そのユーザーに通知する
        $newArr = array();
        $currentUserId = Gen_Auth::getCurrentUserId();
        foreach($arr as $userId) {      // 自分自身には通知しない
            if ($userId != $currentUserId) {
                $newArr[] = $userId;
            }
        }
        if (count($newArr) > 0) {
            $userIds = join(",", $newArr);
            $content = sprintf(isset($param['schedule_id']) ? _g("%1\$s さんがあなたのスケジュールを更新しました。") : _g("%1\$s さんがあなたのスケジュールを新規登録しました。"), $gen_db->quoteParam($_SESSION['user_name']));
            $content .= " ({$param['begin_date']} " . h(mb_substr($param['schedule_text'],0,50)) . ")";
            $content .= " [list-link:Config_Schedule_List&hilight_schedule_id={$key}:]";
            // editダイアログで表示するときは、上のlist-linkをやめてこちらにする
            //$content .= " [record-link:Config_Schedule_Edit&schedule_id={$key}:]";
            Gen_Chat::writeSystemChat($userIds, $content);
        }
        
        
        return $key;
    }

}
