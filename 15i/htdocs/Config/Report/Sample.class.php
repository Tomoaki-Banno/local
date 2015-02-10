<?php

class Config_Report_Sample
{

    protected $_reportTitle;
    protected $_report;
    protected $_tagList;
    protected $_query;
    protected $_pageKeyColumn;

    function execute(&$form)
    {
        // レポートクラスから帳票情報を取得
        require_once(Gen_File::safetyPathForAction($form['reportAction']));
        $obj = new $form['reportAction'];
        $param =$obj->getReportParam($form);

        $query = "select 1 as sample_id";
        foreach ($param['tagList'] as $tag) {
            if (count($tag) > 1) {    // タグカテゴリ見出しは除く
                $query .= ",'" . $tag[2] . "' as " . $tag[0];
            }
        }
        if ($param['pageKeyColumn'] != "")
            $query .= ",1 as " . $param['pageKeyColumn'];

        // PDF発行
        $pdf = new Gen_PDF();
        $pdf->createPDFFromExcel(
                $param['report']
                , $form['gen_action_group'] . ".pdf"
                , $query
                , $param['pageKeyColumn']
                , @$param['pageCountKeyColumn']
                , $form['gen_template']
        );

        return "simple.tpl";
    }
}