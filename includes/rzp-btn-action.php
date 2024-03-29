<?php

use Razorpay\Api\Api;
use Razorpay\Api\Errors;
use Razorpay\SubscriptionButton\Errors as BtnErrors;

require_once __DIR__ . '/../includes/rzp-subscription-buttons.php';

class RZP_Subscription_Button_Action
{
    public function __construct()
    {
        $this->razorpay = new RZP_Subscription_Button_Loader(false);

        $this->api = $this->razorpay->get_razorpay_api_instance();
    }

    /**
     * Generates admin page options using Settings API
    **/
    function process() 
    {
        $btn_id = sanitize_text_field($_POST['btn_id']);
        $action = sanitize_text_field($_POST['btn_action']);
        $page_url = admin_url('admin.php?page=rzp_subscription_button_view&btn='.$btn_id);

        try
        {
            $this->api->paymentPage->$action($btn_id);
        }
        catch (Exception $e)
        {
            $message = $e->getMessage();

            throw new Errors\Error(
                $message,
                BtnErrors\Payment_Button_Subscription_Error_Code::API_SUBSCRIPTION_BUTTON_ACTION_FAILED,
                400
            );
        }
        wp_redirect( $page_url );
    }
}
