<?php

use Razorpay\Api\Api;
use Razorpay\Api\Errors;
use Razorpay\SubscriptionButton\Errors as BtnErrors;

require_once __DIR__ . '/../includes/rzp-subscription-buttons.php';
require_once __DIR__.'/../razorpay-sdk/Razorpay.php';
require_once __DIR__ . '/../includes/errors/payment-button-error-code.php';

class RZP_View_Subscription_Button_Templates
{
    public function __construct()
    {
        $this->razorpay = new RZP_Subscription_Button_Loader(false);

        $this->api = $this->razorpay->get_razorpay_api_instance();
    }

    /**
     * Generates admin page options using Settings API
    **/
    function razorpay_view_button()
    {
        if(empty(sanitize_text_field($_REQUEST['btn'])) or null === (sanitize_text_field($_REQUEST['btn'])))
        {
            wp_die("This page consist some request parameters to view response");
        }

        $pagenum = $_REQUEST['paged'];
        $previous_page_url = admin_url('admin.php?page=razorpay_Subscription_button&paged='.$pagenum);
        $button_detail = $this->fetch_button_detail(sanitize_text_field($_REQUEST['btn']));
        
        $show = "jQuery('.overlay').show()";
        $hide = "jQuery('.overlay').hide()";
        echo '<div class="wrap">
            <div class="content-header">
                <a href="'.$previous_page_url.'">
                    <span class="dashicons rzp-dashicons dashicons-arrow-left-alt"></span> Button List
                </a>
                <span class="dashicons rzp-dashicons dashicons-arrow-right-alt2"></span>'.$button_detail['title'].'
            </div>
            <div class="container rzp-container">
                <div class="row panel-heading">
                    <div class="text">'.$button_detail['title'].'</div>
                </div>
                <div class="row panel-body">
                    <div class="col-md-5 panel-body-left">
                        <div class="row">
                            <div class="col-sm-4 panel-label">Button ID</div>
                            <div class="col-sm-8 panel-value">'.$button_detail["id"].'</div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4 panel-label">Button Status</div>
                            <div class="col-sm-8 panel-value">
                                <span class="status-label">'.$button_detail['status'].'</span>
                                <button onclick="'.$show.'" class="status-button">'.$button_detail['btn_pointer_status'].'</button>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4 panel-label">Total Quantity Sold</div>
                            <div class="col-sm-8 panel-value">'.$button_detail['total_item_sold'].'</div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4 panel-label">Created on</div>
                            <div class="col-sm-8 panel-value">'.$button_detail['created_at'].'</div>
                        </div>
                    </div>
                    <div class="col-md-7"><b>Subscription Plans</b>'.$button_detail['html_content_item'].'</div>
                </div>          
            </div>
                  
        </div>';

        $modal = '<div class="overlay"><div class="modal">
  <form class="modal-content" action="'.esc_url(admin_url('admin-post.php')).'" method="POST">
    <div class="container">
        <div class="modal-header">
            <h3 class="modal-title">'.$button_detail["modal_title_content"].'</h3>
        </div>  
        <div class="modal-body">
            <div class="text-semi-muted">
                <p>'.$button_detail["modal_body_content"].'</p>
            </div>
            <div class="Modal__actions">
                <button type="button" onclick="'.$hide.'" class="btn btn-default">No, don`t!</button>
                <button type="submit" onclick="'.$hide.'" name="btn_action" value="'.$button_detail['btn_pointer_status'].'" class="btn btn-primary">Yes, '.$button_detail['btn_pointer_status'].'</button>
                <input type="hidden" name="btn_id" value="'.$button_detail['id'].'">
                <input type="hidden" name="paged" value="'.$pagenum.'">
                <input type="hidden" name="action" value="rzp_sub_btn_action">
            </div>
        </div>
    </div>
  </form>
</div>
</div>
<script type="text/javascript">
    jQuery("'.'.overlay'.'").on("'.'click'.'", function(e) {
      if (e.target !== this) {
        return;
      }
      jQuery("'.'.overlay'.'").hide();
    });
</script>
';
echo $modal;
    }

    public function fetch_button_detail($btn_id) 
    {
        try
        {
            $button_detail = $this->api->paymentPage->fetch($btn_id);
        }
        catch (Exception $e)
        {
            $message = $e->getMessage();

            throw new Exception("RAZORPAY ERROR: Fetch payment button detail failed with the following message: '$message'");
        }

        $modal_title = 'Deactivate Payment Button?';
        $modal_body = 'Once you deactivate the payment button, you will not be able to accept payments till you activate it again.';
        $btn_pointer_status = 'deactivate';

        if($button_detail['status'] === 'inactive')
        {
            $btn_pointer_status = 'activate';
            $modal_title = 'Activate Payment Button?';
            $modal_body = 'Once you activate the payment button, you will be able to accept payments.';
        }

        $total_item_sold = 0;
        $html_content_item = '';

        foreach ((array) $button_detail['payment_page_items'] as $payment_item) 
        {
            $total_item_sold = $payment_item['quantity_sold'] + $total_item_sold;
            $interval = $payment_item['product_config']['plan_details']['interval'];
            $period = $payment_item['product_config']['plan_details']['period'];
            $interval = ($interval == 1)? '': $interval;
            $period = ($interval > 1)? ucfirst(rtrim($period, "ly").'s'): ucfirst(rtrim($period, "ly"));

            $content = '<div class="button-items-detail">
                            <div class="row">
                                <div class="col-sm-3">'.$payment_item['item']['name'].'</div>
                                <div class="col-sm-3 panel-label">Plan Amount</div>
                                <div class="col-sm-3 panel-label">Billing Frequency</div>
                                <div class="col-sm-3 panel-label">Billing Cycles</div>
                            </div>
                            <div class="row">
                                <div class="col-sm-3"></div>
                                <div class="col-sm-3"><span class="rzp-currency">₹ </span>'.(int) round($payment_item['item']['amount'] / 100).'</div>
                                <div class="col-sm-3">Every '.$interval.' '.$period.'</div>
                                <div class="col-sm-3">'.$payment_item['product_config']['subscription_details']['total_count'].'</div>
                            </div>
                        </div>';
            $html_content_item = $html_content_item.$content;
        }
        
        return array(
            'id' => $button_detail['id'],
            'title' => $button_detail['title'],
            'status' => $button_detail['status'],
            'btn_pointer_status' => $btn_pointer_status,
            'total_item_sold'     => $total_item_sold,
            'html_content_item' => $html_content_item,
            'modal_title_content' => $modal_title,
            'modal_body_content' => $modal_body,
            'created_at' => date("d F Y", $button_detail['created_at']),
        );
    }
}
