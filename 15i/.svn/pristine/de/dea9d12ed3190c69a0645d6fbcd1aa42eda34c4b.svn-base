<?php

class Config_MrpReset_MrpReset
{

    function execute(&$form)
    {
        $mrp = new Logic_Mrp();
        $mrp->deleteProgress();
        $form['gen_done'] = "true";

        return 'action:Menu_Admin';
    }

}