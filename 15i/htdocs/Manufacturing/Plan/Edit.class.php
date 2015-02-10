<?php

require_once("Model.class.php");

class Manufacturing_Plan_Edit extends Base_EditBase
{

    function convert($converter, &$form)
    {
        $converter->nullBlankToValue('plan_year', date("Y"));
        $converter->nullBlankToValue('plan_month', date("m"));

        $monthLastDay = date('d', mktime(0, 0, 0, $form['plan_month'] + 1, 0, $form['plan_year']));
        for ($i = 1; $i <= $monthLastDay; $i++) {
            $converter->nullBlankToValue("day$i", 0);
        }
    }

    function setQueryParam(&$form)
    {
        $this->keyColumn = 'plan_id';
        $this->selectQuery = "
            select
                *
                ,coalesce(record_update_date, record_create_date) as gen_last_update
                ,coalesce(record_updater, record_creator) as gen_last_updater
            from
                plan
            [Where]
        ";
    }

    // 画面表示のための設定
    function setViewParam(&$form)
    {
        $this->modelName = "Manufacturing_Plan_Model";

        $form['gen_pageTitle'] = _g("計画登録");
        $form['gen_entryAction'] = "Manufacturing_Plan_Entry&plan_year={$form['plan_year']}&plan_month={$form['plan_month']}";
        $form['gen_listAction'] = "Manufacturing_Plan_List";
        $form['gen_pageHelp'] = _g("計画登録");

        $form['gen_editControlArray'] = array(
            array(
                'label' => _g('品目'),
                'type' => 'dropdown',
                'name' => 'item_id',
                'value' => @$form['item_id'],
                'dropdownCategory' => 'item_plan',
                'dropdownParam' => $form['plan_year'] . ";" . $form['plan_month'],
                'require' => true,
                'size' => '12',
                'subSize' => '20',
                'colspan' => '3',
                'tabindex' => 1,
                'readonly' => isset($form['plan_id']),
                'denyMove' => true,
                'helpText_noEscape' => _g('計画登録する品目を選択してください。MRP品目のみが対象です。また、すでに登録済みの品目はここには表示されません。')
            ),
            array(
                'type' => 'literal',
                'denyMove' => true,
            ),
        );

        $year = $form['plan_year'];
        $month = $form['plan_month'];
        $monthLastDay = date('d', mktime(0, 0, 0, $month + 1, 0, $year));

        for ($i = 1; $i <= 16; $i++) {
            // 左の列
            $day = $i;
            if ($day <= $monthLastDay) {
                $dateStr = "{$year}/{$month}/{$day}";
                $form['gen_editControlArray'][] = array(
                    'label' => $dateStr . "(" . Gen_String::weekdayStr($dateStr) . ")",
                    'type' => 'data',
                    'name' => "day{$day}",
                    'value' => @$form["day{$day}"],
                    'size' => '5',
                    'ime' => 'off',
                    'tabindex' => $day + 100,
                    'hidePin' => true,
                    'denyMove' => true,
                );
            } else {
                $form['gen_editControlArray'][] = array(
                    'type' => 'literal',
                    'denyMove' => true,
                );
            }

            // 右の列
            $day = $i + 16;
            if ($day <= $monthLastDay) {
                $dateStr = "{$year}/{$month}/{$day}";
                $form['gen_editControlArray'][] = array(
                    'label' => $dateStr . "(" . Gen_String::weekdayStr($dateStr) . ")",
                    'type' => 'data',
                    'name' => "day{$day}",
                    'value' => @$form["day{$day}"],
                    'size' => '5',
                    'tabindex' => $day + 100,
                    'hidePin' => true,
                    'denyMove' => true,
                );
            } else {
                $form['gen_editControlArray'][] = array(
                    'type' => 'literal',
                    'denyMove' => true,
                );
            }
        }
    }

}
