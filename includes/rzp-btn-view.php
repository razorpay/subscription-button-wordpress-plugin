<?php

require_once __DIR__.'/../templates/razorpay-button-view-templates.php';

class RZP_Subscription_View_Button
{
    public function __construct()
    {
        $this->view_template = new RZP_View_Subscription_Button_Templates();
    }

    /**
     * Generates admin page options using Settings API
    **/
    function razorpay_view_button()
    {
        $this->view_template->razorpay_view_button();
    }
}
