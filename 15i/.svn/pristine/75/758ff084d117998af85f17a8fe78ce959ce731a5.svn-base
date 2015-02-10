<?php

class Menu_Home2
{

    function execute(&$form)
    {
        global $gen_db;
        
        $form['gen_pageTitle'] = _g("コンパス");     // タブ表示用
        $form['gen_listAction'] = "Menu_Home2";
        
        // Widget Data の取得
        $widgetDataArr = self::_getWidgetData(); 
        
        // Widget のリセット
        if (isset($form['widgetReset'])) {
            unset($_SESSION['gen_setting_user']->dashboardWidgetIds);
        }

        // Widget の並び順と開閉状態の取得
        if (isset($_SESSION['gen_setting_user']->dashboardWidgetIds)) {
        } else {
            $_SESSION['gen_setting_user']->dashboardWidgetIds = "0,1,2,3,0,4,5,0,6,7,8,9";  // デフォルト。0は列頭
        }
        $widgetSortArr = explode(",", $_SESSION['gen_setting_user']->dashboardWidgetIds);
        
        // パーツ選択ダイアログ表示用
        $widgetArrForDialog = array();
        foreach($widgetDataArr as $id => $widget) {
            if (isset($widget['title'])) {
                $hide = true;
                foreach($widgetSortArr as $sortId) {
                    if (substr($sortId, -1) == 'c') {
                        $sortId = substr($sortId, 0, strlen($sortId)-1);
                    }
                    if ($id == $sortId) {
                        $hide = false;
                        break;
                    }
                }
                $widgetArrForDialog[] = array(
                    "id" => $id,
                    "title" => $widget['title'],
                    "hide" => $hide,
                );
            }
        }
        $form['widgetArrForDialog'] = $widgetArrForDialog;
        
        // パーツ表示用
        $widgetArr = array();
        foreach($widgetSortArr as $sortId) {
            $isClose = false;
            if (substr($sortId, -1) == 'c') {
                $isClose = true;
                $sortId = substr($sortId, 0, strlen($sortId)-1);
            }
            if (isset($widgetDataArr[$sortId])) {
                if ($_SESSION['gen_app'] &&!isset($widgetDataArr[$sortId]['chart'])) {
                    continue;
                }
                
                $widgetDataArr[$sortId]['id'] = $sortId;
                if ($isClose) {
                    $widgetDataArr[$sortId]['close'] = true;
                }
                // パーミッション
                $perm = 0;
                if (isset($widgetDataArr[$sortId]['permissionClass'])) {
                    $perm = Gen_Auth::sessionCheck(strtolower($widgetDataArr[$sortId]['permissionClass']));
                }
                if ($perm == 1 || $perm == 2) {
                    if (isset($widgetDataArr[$sortId]['query'])) {
                        // SQLの実行
                        $widgetDataArr[$sortId]['data'] = $gen_db->getArray($widgetDataArr[$sortId]['query']);
                    }
                } else {
                    $widgetDataArr[$sortId]['permissionError'] = true;
                }
                unset($widgetDataArr[$sortId]['query']);
                $widgetArr[] = $widgetDataArr[$sortId];
            }
        }
        if ($_SESSION['gen_app']) {
            $form['response_noEscape'] = json_encode($widgetArr);
            return 'simple.tpl';
        }
            
        $form['widgetArr'] = $widgetArr;
        
        return 'menu_home2.tpl';
    }
    
    private function _getWidgetData()
    {
        global $gen_db;
        
        $date1 = new DateTime('-1 year +1 month');
        $before1year = $date1->format('Y-m-1'); 
        $now = new DateTime();
        $thisMonthEnd = $now->format('Y-m-t');
        $userId = Gen_Auth::getCurrentUserId();
        $today = date('Y-m-d');
        
        // スケジュール用
        $from = date('Y-m-d');
        $to = date('Y-m-d', strtotime(date('Y-m-d') . " +4 day"));
        Logic_Schedule::createTempScheduleTable(
                $from, 
                $to, 
                Gen_Auth::getCurrentUserId(), 
                null,     // search
                false,    // isShowNewButton
                false,    // isLinkEnable
                false     // isCrossCount
            );
        // 休業日
        $query = "select holiday from holiday_master where holiday between '{$from}' and '{$to}'";
        $res = $gen_db->getArray($query);
        $holidayArr = array();
        if ($res) {
            foreach ($res as $row) {
                $holidayArr[] = strtotime($row['holiday']);
            }
        }
        $scdCols = array();
        for($day = strtotime($from); $day <= strtotime($to); $day += 86400) {
            $prop = "day" . date('Ymd', $day);
            $scdCols[] = array(
                'label' => date('m-d', $day) . "(" . Gen_String::weekdayStr(date('Y-m-d', $day)) . ")",
                'field' => $prop,
                'width' => 110,
                'style' => 'vertical-align:top' . (in_array($day, $holidayArr) ? ';background-color:#FFD5D5' : ''),
            );
        }

        $widgetArr = array(
            // ●列開始
            array(
                'data' => 'ul',
            ),
            
            // ●スケジュール
            array(
                'title' => _g('スケジュール'),
                'permissionClass' => "Config_Schedule",
                'noth' => true, // 1列目をth（見出し列）にしない
                'query' => "
                    select * from temp_schedule
                ",
                'cols' => $scdCols,
                'buttons' => array(
                    array(
                        'label' => _g('新規登録'),
                        'window' => 'dialog',
                        'action' => 'Config_Schedule_Edit'
                    ),
                    array(
                        'label' => _g('スケジュール画面'),
                        'window' => 'new',
                        'action' => 'Config_Schedule_List',
                    )
                )
            ),
                                
           // ---------- 受注系 ----------
                                
            // ●受注納期遅延リスト
            array(
                'title' => _g('受注納期遅延リスト'),
                'permissionClass' => "Manufacturing_Received",
                'query' => "
                    select 
                        received_header.received_header_id
                        ,received_number
                        ,customer_name
                        ,line_no
                        ,item_name
                        ,received_quantity
                        ,received_date
                        ,dead_line
                        ,received_header.record_create_date
                    from 
                        received_header
                        inner join received_detail on received_header.received_header_id = received_detail.received_header_id
                        left join item_master on received_detail.item_id = item_master.item_id
                        left join customer_master on received_header.customer_id = customer_master.customer_id
                    where
                        received_detail.dead_line <= '" . date('Y-m-d') . "'
                            and not delivery_completed
                    order by
                        received_detail.dead_line, received_number, line_no
                    limit 
                        30
                ",
                'cols' => array(
                    array(
                        'label' => _g('受注番号'),
                        'field' => 'received_number',
                        'align' => 'center',
                        'width' => 100,
                        'sameCellJoin' => true,
                        'link_noEscape' => "javascript:gen.modal.open('index.php?action=Manufacturing_Received_Edit&received_header_id=[received_header_id]')", 
                    ),
                    array(
                        'label' => _g('得意先名'),
                        'field' => 'customer_name',
                        'align' => 'left',
                        'width' => 100,
                        'sameCellJoin' => true,
                        'parentColumn' => 'received_number',
                    ),
                    array(
                        'label' => _g('品目名'),
                        'field' => 'item_name',
                        'align' => 'left',
                        'width' => 100,
                    ),
                    array(
                        'label' => _g('受注数'),
                        'field' => 'received_quantity',
                        'align' => 'right',
                        'numberFormat' => true,
                        'width' => 70,
                    ),
                    array(
                        'label' => _g('受注日'),
                        'field' => 'received_date',
                        'align' => 'center',
                        'width' => 70,
                    ),
                    array(
                        'label' => _g('受注納期'),
                        'field' => 'dead_line',
                        'align' => 'center',
                        'width' => 70,
                    ),
                ),
                'buttons' => array(
                    array(
                        'label' => _g('受注リスト画面'),
                        'window' => 'new',
                        'action' => 'Manufacturing_Received_List',
                    )
                )
            ),
                                
            // ●最近の受注
            array(
                'title' => _g('最近の受注'),
                'permissionClass' => "Manufacturing_Received",
                'query' => "
                    select 
                        received_header.received_header_id
                        ,received_number
                        ,customer_name
                        ,line_no
                        ,item_name
                        ,received_quantity
                        ,received_date
                        ,dead_line
                        ,received_header.record_create_date
                    from 
                        received_header
                        inner join received_detail on received_header.received_header_id = received_detail.received_header_id
                        left join item_master on received_detail.item_id = item_master.item_id
                        left join customer_master on received_header.customer_id = customer_master.customer_id
                    order by
                        received_header.record_create_date desc, line_no
                    limit 
                        10
                ",
                'cols' => array(
                    array(
                        'label' => _g('受注番号'),
                        'field' => 'received_number',
                        'align' => 'center',
                        'width' => 100,
                        'sameCellJoin' => true,
                        'link_noEscape' => "javascript:gen.modal.open('index.php?action=Manufacturing_Received_Edit&received_header_id=[received_header_id]')", 
                    ),
                    array(
                        'label' => _g('得意先名'),
                        'field' => 'customer_name',
                        'align' => 'left',
                        'width' => 100,
                        'sameCellJoin' => true,
                        'parentColumn' => 'received_number',
                    ),
                    array(
                        'label' => _g('品目名'),
                        'field' => 'item_name',
                        'align' => 'left',
                        'width' => 100,
                    ),
                    array(
                        'label' => _g('受注数'),
                        'field' => 'received_quantity',
                        'align' => 'right',
                        'numberFormat' => true,
                        'width' => 70,
                    ),
                    array(
                        'label' => _g('受注日'),
                        'field' => 'received_date',
                        'align' => 'center',
                        'width' => 70,
                    ),
                    array(
                        'label' => _g('受注納期'),
                        'field' => 'dead_line',
                        'align' => 'center',
                        'width' => 70,
                    ),
                ),
                'buttons' => array(
                    array(
                        'label' => _g('新規登録'),
                        'window' => 'dialog',
                        'action' => 'Manufacturing_Received_Edit'
                    ),
                    array(
                        'label' => _g('受注リスト画面'),
                        'window' => 'new',
                        'action' => 'Manufacturing_Received_List',
                    )
                )
            ),
            
            // ●過去1年の顧客TOP10（受注）
            array(
                'title' => _g('顧客TOP10（受注額、過去1年）'),
                'permissionClass' => "Manufacturing_Received",
                'query' => "
                    select 
                        max(customer_name) as customer_name
                        ,round(sum(received_quantity * product_price)) as received_amount
                    from 
                        received_header
                        inner join received_detail on received_header.received_header_id = received_detail.received_header_id
                        inner join customer_master on received_header.customer_id = customer_master.customer_id
                    where
                        received_date between '{$before1year}' and '{$thisMonthEnd}'
                    group by
                        received_header.customer_id
                    order by
                        received_amount desc
                    limit 
                        10
                ",
                'chart' => 'pie',
                'cols' => array(
                    array(
                        'label' => _g('得意先名'),
                        'field' => 'customer_name',
                        'align' => 'left',
                    ),
                    array(
                        'label' => _g('受注額'),
                        'field' => 'received_amount',
                        'align' => 'right',
                        'numberFormat' => true,
                    ),
                ),
            ),
                                
            // ●過去1年の受注額推移
            array(
                'title' => _g('受注額推移（過去1年）'),
                'permissionClass' => "Manufacturing_Received",
                'query' => "
                    select 
                        to_char(date_trunc('month',received_date),'YYYY-MM') as received_ym
                        ,round(sum(received_quantity * product_price)) as received_amount
                    from 
                        received_header
                        inner join received_detail on received_header.received_header_id = received_detail.received_header_id
                        inner join customer_master on received_header.customer_id = customer_master.customer_id
                    where
                        received_date between '{$before1year}' and '{$thisMonthEnd}'
                    group by
                        to_char(date_trunc('month',received_date),'YYYY-MM')
                    order by
                        received_ym
                ",
                'chart' => 'area',
                'appendkey' => false,
                'cols' => array(
                    array(
                        'label' => _g('受注年月'),
                        'field' => 'received_ym',
                        'align' => 'center',
                    ),
                    array(
                        'label' => _g('受注額'),
                        'field' => 'received_amount',
                        'align' => 'right',
                        'numberFormat' => true,
                    ),
                ),
            ),
                                
            // ●過去1年の商品TOP10（受注）
            array(
                'title' => _g('商品TOP10（受注額、過去1年）'),
                'permissionClass' => "Manufacturing_Received",
                'query' => "
                    select 
                        max(item_name) as item_name
                        ,round(sum(received_quantity * product_price)) as received_amount
                    from 
                        received_header
                        inner join received_detail on received_header.received_header_id = received_detail.received_header_id
                        inner join item_master on received_detail.item_id = item_master.item_id
                    where
                        received_date between '{$before1year}' and '{$thisMonthEnd}'
                    group by
                        received_detail.item_id
                    order by
                        received_amount desc
                    limit 
                        10
                ",
                'chart' => 'bar',
                'appendkey' => false,
                'cols' => array(
                    array(
                        'label' => _g('品目名'),
                        'field' => 'item_name',
                        'align' => 'left',
                    ),
                    array(
                        'label' => _g('受注額'),
                        'field' => 'received_amount',
                        'align' => 'right',
                        'numberFormat' => true,
                    ),
                ),
            ),
                                
           // ---------- 注文系 ----------
                                
            // ●最近の注文
            array(
                'title' => _g('最近の注文'),
                'permissionClass' => "Partner_Order",
                'query' => "
                    select 
                        order_header.order_header_id
                        ,order_id_for_user
                        ,line_no
                        ,customer_name
                        ,item_name
                        ,order_detail_quantity
                        ,order_date
                        ,order_detail_dead_line
                        ,order_header.record_create_date
                    from 
                        order_header
                        inner join order_detail on order_header.order_header_id = order_detail.order_header_id
                        left join customer_master on order_header.partner_id = customer_master.customer_id
                    where
                        order_header.classification = '1' 
                    order by
                        order_header.record_create_date desc, line_no
                    limit 
                        10
                ",
                'cols' => array(
                    array(
                        'label' => _g('注文書番号'),
                        'field' => 'order_id_for_user',
                        'align' => 'center',
                        'width' => 100,
                        'sameCellJoin' => true,
                        'link_noEscape' => "javascript:gen.modal.open('index.php?action=Partner_Order_Edit&order_header_id=[order_header_id]')", 
                    ),
                    array(
                        'label' => _g('発注先名'),
                        'field' => 'customer_name',
                        'align' => 'left',
                        'width' => 100,
                        'sameCellJoin' => true,
                        'parentColumn' => 'order_id_for_user',
                    ),
                    array(
                        'label' => _g('品目名'),
                        'field' => 'item_name',
                        'align' => 'left',
                        'width' => 100,
                    ),
                    array(
                        'label' => _g('発注数'),
                        'field' => 'order_detail_quantity',
                        'align' => 'right',
                        'numberFormat' => true,
                        'width' => 70,
                    ),
                    array(
                        'label' => _g('注文日'),
                        'field' => 'order_date',
                        'align' => 'center',
                        'width' => 70,
                    ),
                    array(
                        'label' => _g('受注納期'),
                        'field' => 'order_detail_dead_line',
                        'align' => 'center',
                        'width' => 70,
                    ),
                ),
                'buttons' => array(
                    array(
                        'label' => _g('新規登録'),
                        'window' => 'dialog',
                        'action' => 'Partner_Order_Edit'
                    ),
                    array(
                        'label' => _g('注文リスト画面'),
                        'window' => 'new',
                        'action' => 'Partner_Order_List',
                    )
                )
            ),
                                
            // ●過去1年の購買額推移
            array(
                'title' => _g('発注額推移（過去1年）'),
                'permissionClass' => "Partner_Order",
                'query' => "
                    select 
                        to_char(date_trunc('month',order_date),'YYYY-MM') as order_ym
                        ,round(sum(order_amount)) as order_amount
                    from 
                        order_header
                        inner join order_detail on order_header.order_header_id = order_detail.order_header_id
                        inner join customer_master on order_header.partner_id = customer_master.customer_id
                    where
                        order_date between '{$before1year}' and '{$thisMonthEnd}'
                    group by
                        to_char(date_trunc('month',order_date),'YYYY-MM')
                    order by
                        order_ym
                ",
                'chart' => 'bar',
                'appendkey' => false,
                'cols' => array(
                    array(
                        'label' => _g('発注年月'),
                        'field' => 'order_ym',
                        'align' => 'center',
                    ),
                    array(
                        'label' => _g('発注額'),
                        'field' => 'order_amount',
                        'align' => 'right',
                        'numberFormat' => true,
                    ),
                ),
            ),
                                
            // ●過去1年の発注先TOP10
            array(
                'title' => _g('発注先TOP10（過去1年）'),
                'permissionClass' => "Partner_Order",
                'query' => "
                    select 
                        max(customer_name) as customer_name
                        ,round(sum(order_amount)) as order_amount
                    from 
                        order_header
                        inner join order_detail on order_header.order_header_id = order_detail.order_header_id
                        inner join customer_master on order_header.partner_id = customer_master.customer_id
                    where
                        order_date between '{$before1year}' and '{$thisMonthEnd}'
                    group by
                        order_header.partner_id
                    order by
                        order_amount desc
                    limit 
                        10
                ",
                'chart' => 'pie',
                'cols' => array(
                    array(
                        'label' => _g('発注先名'),
                        'field' => 'customer_name',
                        'align' => 'left',
                    ),
                    array(
                        'label' => _g('発注額'),
                        'field' => 'order_amount',
                        'align' => 'right',
                        'numberFormat' => true,
                    ),
                ),
            ),
        );
        
        return $widgetArr;
    }




}
