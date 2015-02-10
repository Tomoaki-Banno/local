<?php

class Config_Report_AjaxTemplateInfo extends Base_AjaxBase
{

    function _execute(&$form)
    {
        // レポートクラスから帳票情報を取得
        require_once(Gen_File::safetyPathForAction($form['reportAction']));
        $obj = new $form['reportAction'];
        $param =$obj->getReportParam($form);
        
        $report = $param['report'];
        $reportTitle = $param['reportTitle'];
        $tagList = $param['tagList'];
        
        $info = Gen_PDF::getTemplateInfo($param['report']);
        $templateFileInfo = $info[2];
        $selectedNo = $info[3];
        
        $permission = Gen_Auth::sessionCheck("config_report");

        return
            array(
                'template_file_info' => $templateFileInfo,
                'selected_no' => $selectedNo,
                'report' => $report,
                'report_title' => $reportTitle,
                'tag_list' => $tagList,
                'permission' => $permission,
            );
    }

}