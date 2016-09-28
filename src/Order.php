<?php defined('BASEPATH') OR exit('No direct script access allowed');
class Order extends CI_Controller {
	public function __construct()
	{
		parent::__construct();
		//if (!$this->ion_auth->logged_in()) redirect('auth/admin_login');
		$this->load->library('code');
		$this->load->library('pagination');
		$this->load->library('code_function');
		// $this->load->library('Code_email');
		$this->config->load('my_application');
		$this->config->load('pagination',TRUE);

		$this->load->helper('report_helper');
		$this->load->model("Order_model");
		$this->load->model("Status_model");
		$this->load->model("User_model");
		$this->load->model("Tour_model");
		$this->load->model("Transaction_model");
	}
	public function index($sub_order_no)
	{
		/*if(!$param1) $param1 = "ALL";
		if(!$param2) $param2 = 0;
		redirect('/order/flight_trx/'.$param1.'/'.$param2);*/
	}

	public function order($order_no)
	{
		if($order_no){
			$product = $this->Order_model->_get_order_type($order_no);
			redirect("order/".$product."_edit/".$order_no);
		}
	}
	public function sub_order($sub_order_no)
	{
		$product = $this->Order_model->_get_product($sub_order_no);
		if($this->session->userdata('flash_notification')){
			$this->session->set_flashdata('flash_notification',$this->session->userdata('flash_notification'));
		}
		redirect('order/'.$product.'_sub_edit/'.$sub_order_no);
	}

	public function flight_trx($status="ALL",$page=0)
	{
		$filters = array('product'=>'flight');
		$filters_2 = array('category'=>'flight');
		$data = $this->get_by_product('flight');
		$temp_status = "";
		if($status && $status <> "ALL") {
			$filters['status'] = $status;
			$temp_status = $status;
		}
		$data['status_code'] = $status;
		$data['status_info']	= $this->Status_model->get_status_name($temp_status);
		$data['order_list']		= $this->Order_model->list_order($filters,$page);
		$data['count_order'] 	= count($this->Order_model->count_order('flight',$temp_status));
		$this->code->admin_page('flight_trx',$data);
	}
	public function flight_edit($order_no)
	{
		if($this->input->post()){
			if($this->input->post('btn_assign') !== NULL){
				$this->set_user_assigned();
			}
			if($this->input->post('btn_update')){
				echo "#debug";
				return false;
			}
		}
		//echo $order_no;
		$data['ddl_users']		= $this->User_model->list_user();
		$data['order_header'] 	= $this->Order_model->read_order(array('order_no'=>$order_no));
		$data['order_detail'] 	= $this->Order_model->get_traveller($order_no);
		$this->code->admin_page('flight_edit',$data);
	}
	public function flight_sub_edit($sub_order_no=NULL)
	{
		if(!$sub_order_no)show_404();
		if(!$this->Order_model->exists($sub_order_no)){
			show_404();
		}
		if($this->input->post()){
			$data['user_assigned'] = $this->input->post('assign');
			$data['status'] = $this->input->post('state');
			if($this->Order_model->update_sub_order($sub_order_no,$data)){
				redirect("order/flight_sub_edit/".$sub_order_no);
			}
		}
		$data['ddl_users']		= $this->User_model->list_user();
		$data['ddl_states']		= $this->Status_model->list_status(array('category'=>'flight'));
		$data['order_info']		= $this->Order_model->read_order(array('sub_order_no'=>$sub_order_no));
		$data['order_traveller']= $this->Order_model->get_traveller($sub_order_no);
		$order_id 				= $this->Order_model->return_id($sub_order_no);
		$data['flights']		= $this->Order_model->get_flight_detail($sub_order_no);
		$this->code->admin_page('flight_sub_edit',$data);
	}


	/**
	 * HOTEL
	 */
	public function hotel_trx($status="ALL",$page=0)
	{
		$filters['product'] = 'hotel';
		$data = $this->get_by_product('hotel');
		$temp_status = "";
		if($status && $status <> "ALL") {
			$filters['status'] = $status;
			$temp_status = $status;
		}
		$data['status_code'] 	= $status;
		$data['status_info']	= $this->Status_model->get_status_name($temp_status);
		$data['order_list']		= $this->Order_model->list_order($filters,$page);
		$data['count_order'] 	= count($this->Order_model->count_order('hotel',$temp_status));
		$data['ajax_path'] 		= base_url('/order/ajax_hotel_list');
		$this->code->admin_page('hotel_trx',$data);
	}
	public function hotel_edit($order_no)
	{
		$data['ddl_users']		= $this->User_model->list_user();
		$data['order_header'] 	= $this->Order_model->read_order(array('order_no'=>$order_no));
		$data['order_detail'] 	= $this->Order_model->get_traveller($order_no);
		$this->code->admin_page('hotel_edit',$data);
	}
	/*public function hotel_sub_edit($sub_order_no=NULL)
	{
		if(!$sub_order_no)show_404();
		if(!$this->Order_model->exists($sub_order_no)){
			show_404();
		}
		$data['ddl_users']		= $this->User_model->list_user(array('company'=>'oms','active'=>1));
		$data['ddl_states']		= $this->Status_model->list_all_status();

		$data['order_info']		= $this->Order_model->read_order(array('sub_order_no'=>$sub_order_no));
		$data['order_traveller_group'] = $this->Order_model->order_traveler_group($data['order_info'][0]['sub_order_no']);
		$data['order_traveller']= $this->Order_model->order_traveler($sub_order_no);
		$data['passengers'] 	= $this->Order_model->passenger_summary($data['order_info'][0]['sub_order_no']);
		$data['order_trx']		= $this->Transaction_model->by_sub_order($sub_order_no);
		$data['is_auth']		= $this->Order_model->_is_auth_assign($sub_order_no);

		$data['order_comm']		= $this->Order_model->view_comm($sub_order_no);
		$data['order_option']	= $this->Order_model->view_order_option($sub_order_no);
		$data['order_tour'] 	= $this->Order_model->order_tour($data['order_info'][0]['order_no']);
		$tour_id 				= $data['order_tour'][0]['tour_id'];

		$data['all_icon']		= $this->Tour_model->get_tour_with_icon($tour_id);
		$data['trip_icon'] 		= $this->Tour_model->view_icon($tour_id);
		$data['tour_info'] 		= $this->Tour_model->view_tour($data['order_tour'][0]['tour_id']);
		$data['tour_detail'] 	= $this->Tour_model->view_tour_detail(array("tour_id"=>$data['order_tour'][0]['tour_id']));
		$order_id 				= $this->Order_model->return_id($sub_order_no);
		$data['flights']		= $this->Order_model->get_flight_detail($sub_order_no);

    	$tripThemes = $this->Tour_model->view_tour_theme($tour_id);
        $tripThemesDesc = [];
        foreach ($tripThemes as $tripTheme) {
            $tripThemesDesc[] = $tripTheme['theme_desc'];
        }
        $data['tripThemesDesc'] = implode(', ', $tripThemesDesc);

    	$this->code->admin_page('hotel_sub_edit',$data);
	}*/

    public function hotel_sub_edit($sub_order_no = null)
    {
        if(!$sub_order_no)show_404();
        if(!$this->Order_model->exists($sub_order_no)){
			show_404();
		}
        if($this->input->post()){

			if($this->input->post("btn_mail")){
				// $this->code_email->sendEmail('jun@code.id','test','blablabla');
			//}else{
				//echo 'not send email'; return false;
			}
			$data['user_assigned'] = $this->input->post('assign');
			$data['status'] = $this->input->post('state');
			if($this->Order_model->update_sub_order($sub_order_no,$data)){
				redirect("order/hotel_sub_edit/".$sub_order_no);
			}
		}
		$data['ddl_users']		= $this->User_model->list_user();
		//$data['ddl_states']		= $this->Status_model->list_status(array('category'=>'tour'));
		$data['ddl_states']		= $this->Status_model->list_all_status();
		$data['order_info']		= $this->Order_model->read_order(array('sub_order_no'=>$sub_order_no));
		$data['passengers'] 	= $this->Order_model->passenger_summary($data['order_info'][0]['sub_order_no']);
		$data['order_trx']		= $this->Transaction_model->by_sub_order($sub_order_no);
		$data['is_auth']		= $this->Order_model->_is_auth_assign($sub_order_no);


		$data['order_comm']		= $this->Order_model->view_comm($sub_order_no);
		$data['order_option']	= $this->Order_model->view_order_option($sub_order_no);
        $data['hotelGuest']     = $this->Order_model->getDetailHotelOrder($sub_order_no);

    	$this->code->admin_page('hotel_sub_edit', $data);
    }
	public function tour_trx($status="ALL",$page=0)
	{
		$filters['product'] = 'tour';
		$data = $this->get_by_product('tour');
		$temp_status = "";
		if($status && $status <> "ALL") {
			$filters['status'] = $status;
			$temp_status = $status;
		}
		$data['status_code'] 	= $status;
		$data['status_info']	= $this->Status_model->get_status_name($temp_status);
		$data['order_list']		= $this->Order_model->list_order($filters,$page);
		$data['count_order'] 	= count($this->Order_model->count_order('tour',$temp_status));
		$data['ajax_path'] 		= base_url('/order/ajax_tour_list');
		$this->code->admin_page('tour_trx',$data);
	}
	public function tour_edit($order_no)
	{
		$data['ddl_users']		= $this->User_model->list_user();
		$data['order_header'] 	= $this->Order_model->read_order(array('order_no'=>$order_no));
		$data['order_detail'] 	= $this->Order_model->get_traveller($order_no);
		$this->code->admin_page('tour_edit',$data);
	}
	public function tour_sub_edit($sub_order_no=NULL)
	{
		if(!$sub_order_no)show_404();
		if(!$this->Order_model->exists($sub_order_no)){
			show_404();
		}
		if($this->input->post()){

			if($this->input->post("btn_mail")){
				// $this->code_email->sendEmail('jun@code.id','test','blablabla');
			//}else{
				//echo 'not send email'; return false;
				return false;
			}
			$data['user_assigned'] = $this->input->post('assign');
			$data['status'] = $this->input->post('state');
			if($this->Order_model->update_sub_order($sub_order_no,$data)){
				redirect("order/tour_sub_edit/".$sub_order_no);
			}
		}
		//$data['ddl_users']		= $this->User_model->list_user();
		$data['ddl_users']		= $this->User_model->list_user(array('company'=>'oms','active'=>1));
		//$data['ddl_states']		= $this->Status_model->list_status(array('category'=>'tour'));
		$data['ddl_states']		= $this->Status_model->list_all_status();
		$data['order_info']		= $this->Order_model->read_order(array('sub_order_no'=>$sub_order_no));
		$data['order_traveller_group'] = $this->Order_model->order_traveler_group($data['order_info'][0]['sub_order_no']);
		$data['order_traveller']= $this->Order_model->order_traveler($sub_order_no);
		$data['passengers'] 	= $this->Order_model->passenger_summary($data['order_info'][0]['sub_order_no']);
		$data['order_trx']		= $this->Transaction_model->by_sub_order($sub_order_no);
		$data['is_auth']		= $this->Order_model->_is_auth_assign($sub_order_no);


		$data['order_comm']		= $this->Order_model->view_comm($sub_order_no);
		$data['order_option']	= $this->Order_model->view_order_option($sub_order_no);
		$data['order_tour'] 	= $this->Order_model->order_tour($data['order_info'][0]['order_no']);
		$tour_id 				= $data['order_tour'][0]['tour_id'];

		$data['all_icon']		= $this->Tour_model->get_tour_with_icon($tour_id);
		$data['trip_icon'] 		= $this->Tour_model->view_icon($tour_id);
		$data['tour_info'] 		= $this->Tour_model->view_tour($data['order_tour'][0]['tour_id']);
		$data['tour_detail'] 	= $this->Tour_model->view_tour_detail(array("tour_id"=>$data['order_tour'][0]['tour_id']));
		$order_id 				= $this->Order_model->return_id($sub_order_no);
		$data['flights']		= $this->Order_model->get_flight_detail($sub_order_no);

    	$tripThemes = $this->Tour_model->view_tour_theme($tour_id);
        $tripThemesDesc = [];
        foreach ($tripThemes as $tripTheme) {
            $tripThemesDesc[] = $tripTheme['theme_desc'];
        }
        $data['tripThemesDesc'] = implode(', ', $tripThemesDesc);

    	$this->code->admin_page('tour_sub_edit',$data);
	}

	public function get_trx($trx_id)
	{
		$this->db->where("id",$trx_id);
		return $this->db->get("t_transaction")->row_array();
	}
	public function refund_done($sub_order_no)
	{
		if($this->Order_model->_get_status($sub_order_no) != "OCLP"){
			$this->session->set_flashdata('flash_notification','This Order is not in Refund process mode.');
            redirect($_SERVER['HTTP_REFERER']);
			return false;
		}
		$refund_trx = $this->Transaction_model->find_refund_trx($sub_order_no);
		$this->Transaction_model->set_status($refund_trx['id'],"ORFD");
		$this->Order_model->set_status($sub_order_no,"OCLX");
		$this->session->set_flashdata('flash_notification','Refund process is done successfully.');
        redirect($_SERVER['HTTP_REFERER']);
	}
	public function set_cancel($sub_order_no)
	{
		if($this->Order_model->_get_status($sub_order_no) != "OCLQ"){
			$this->session->set_flashdata('flash_notification','This Order is not in Cancel Request mode.');
            //redirect($_SERVER['HTTP_REFERER']);
            redirect('order/sub_order/'.$sub_order_no);
			return false;
		}
		if(!$this->Order_model->check_order_valid_add_trx($sub_order_no)){
			$this->session->set_flashdata('flash_notification','There is a pending Transaction.');
            //redirect($_SERVER['HTTP_REFERER']);
            redirect('order/sub_order/'.$sub_order_no);
			return false;
		}
		if($this->input->post()){
			$id_trx = $this->Transaction_model->insert_by_remark($sub_order_no,$this->input->post('remark'));
			$this->Transaction_model->set_cancel($id_trx);
			if($this->Order_model->set_charge($id_trx,$this->input->post())){
				$this->Transaction_model->set_status($id_trx,"OCLI");
				$this->Order_model->set_status($sub_order_no,"OCLI");
				$this->session->set_flashdata('flash_notification','Charges are set.');
                //redirect($_SERVER['HTTP_REFERER']);
                redirect('order/sub_order/'.$sub_order_no);
			}
		}
		$data['order_info']		= $this->Order_model->read_order(array('sub_order_no'=>$sub_order_no));
		$this->code->admin_page('set_cancel',$data);
	}
	public function set_charge($sub_order_no)
	{
		if($this->Order_model->_get_status($sub_order_no) != "OCGQ"){
			$this->session->set_flashdata('flash_notification','This Order is not in Change Request mode.');
            redirect($_SERVER['HTTP_REFERER']);
			return false;
		}

		if(!$this->Order_model->check_order_valid_add_trx($sub_order_no)){
			$this->session->set_flashdata('flash_notification','There is a pending Transaction.');
            redirect($_SERVER['HTTP_REFERER']);
			return false;
		}
		if($this->input->post()){
			$id_trx = $this->Transaction_model->insert_by_remark($sub_order_no,$this->input->post('remark'));
			if($this->Order_model->set_charge($id_trx,$this->input->post())){
				$this->Order_model->set_status($sub_order_no,"OCGI");
				$this->session->set_flashdata('flash_notification','Charges are set.');
                redirect($_SERVER['HTTP_REFERER']);
			}
		}
		$data['order_info']		= $this->Order_model->read_order(array('sub_order_no'=>$sub_order_no));
		$this->code->admin_page('set_charge',$data);
	}
	public function set_confirm($trx_id)
	{
		//$this->Transaction_model->set_status($trx_id,"TPYF");
		$sub_order_no = $this->Order_model->_get_trx_sub_order_no($trx_id);
		//$this->Order_model->set_status($sub_order_no,"TCGP");
		$this->Transaction_model->confirm($trx_id);

		//if this trx_id is_cancel == 1, calculate total paid for this transaction.
		$trx = $this->Transaction_model->view_trx($trx_id);
		if($trx['is_cancel'] == '1'){
			$this->Transaction_model->set_paid_trx($trx_id,$trx['amount_total']);
			$total_paid = $this->Transaction_model->calc_total_paid($sub_order_no);
			$total_charges = $this->Transaction_model->calc_cancel_charges($sub_order_no);
			//var_dump($total_paid);
			//var_dump($total_charges);
			$total_refund = $total_paid - $total_charges;
			//var_dump($total_refund); return false;
			$this->Transaction_model->set_status($trx_id,"OING");
			$refund_trx = $this->Transaction_model->insert_refund($sub_order_no,-$total_refund);
			$this->Transaction_model->set_status($refund_trx,"OCLP");
			$this->Order_model->set_status($sub_order_no,"OCLP");
		}else{
			$this->Order_model->set_status($sub_order_no,"OCGI");
		}
		$this->session->set_flashdata('flash_notification','Charges are confirmed.');
		redirect($_SERVER['HTTP_REFERER']);
	}
	public function set_tour_traveler($sub_order_no)
	{
		$accepted_status = array("OCGQ","OCGP","OCGI");
		if(!is_numeric(array_search($this->Order_model->_get_status($sub_order_no),$accepted_status))){
			$this->session->set_flashdata('flash_notification','This Order is not in Change Request mode.');
			redirect("order/tour_sub_edit/".$sub_order_no);
			return false;
		}
		if($this->input->post()){
			//var_dump($this->input->post()); return false;
			$this->Order_model->set_order_traveler_tour($sub_order_no,$this->input->post());
			redirect("order/tour_sub_edit/".$sub_order_no);
			return false;
		}
		$data['order_info']		= $this->Order_model->read_order(array('sub_order_no'=>$sub_order_no));
		$data['order_traveller']= $this->Order_model->order_traveler($sub_order_no);
		$this->code->admin_page('set_tour_traveler',$data);
	}
	public function set_tour_date($sub_order_no)
	{
		if($this->input->post()){
			$this->Order_model->set_order_tour_date($sub_order_no,$this->input->post('change_date'));
			redirect("order/tour_sub_edit/".$sub_order_no);
			return false;
		}
		//$data['curr_date'] 		= $this->Order_model->read_order(array("sub_order_no"=>$sub_order_no));
		$data['order_info']		= $this->Order_model->read_order(array('sub_order_no'=>$sub_order_no));
		$this->code->admin_page('set_tour_date',$data);
	}
	public function edit_trx($trx_id)
	{
		if($this->input->post()){
			$data['remark'] = $this->input->post('remark');
			$data['charge_bank'] = $this->input->post('charge_bank');
			$data['charge_vendor'] = $this->input->post('charge_vendor');
			$data['charge_misc'] = $this->input->post('charge_misc');

			$sub_order_no = $this->Order_model->_get_trx_sub_order_no($trx_id);
			if($this->Order_model->edit_charge($trx_id,$data)){
				$this->session->set_flashdata('flash_notification','Charges are updated.');
                redirect($_SERVER['HTTP_REFERER']);
			}
		}
		$data['trx'] = $this->get_trx($trx_id);
		$this->code->admin_page('edit_charge',$data);
	}
	public function delete_trx($trx_id)
	{
		$sub_order_no = $this->Order_model->_get_trx_sub_order_no($trx_id);
		if($this->Order_model->delete_charge($trx_id)){
			$this->session->set_flashdata('flash_notification','Charges are deleted.');
            redirect($_SERVER['HTTP_REFERER']);
		}
	}
	public function add_comm()
	{
		$this->Order_model->add_comm(
			$this->input->post('hid_sub_order_no'),
			$this->input->post('new_remark'));
		redirect($_SERVER['HTTP_REFERER']);
	}

	public function get_data_order($product,$order_no)
	{
		//$data['order'] = $this->Order_model->view_order($order_no);
        $data['order_no'] = $order_no;
        $data['order_info'] = $this->Order_model->read_order(array('order_no'=>$order_no));
        switch ($product) {
            case 'tour':
                $sub_order_no = $data['order']['sub_order_no'];
                $data['order_tour'] = $this->Order_model->order_tour($order_no);
                $data['tour_id'] = $data['order_tour'][0]['tour_id'];
                $data['tour_info'] = $this->Tour_model->view_tour($data['tour_id']);
               	$data['tour_detail'] = $this->Tour_model->view_tour_detail(array('tour_id'=>$data['tour_id']));
                $data['tour_icon'] = $this->Tour_model->view_icon($data['tour_id']);
                $data['order_trx']	= $this->Transaction_model->by_sub_order($sub_order_no);
                $data['orig_trx'] = $this->Order_model->get_orig_trx($sub_order_no);
                $data['voucher_given'] = $this->Voucher_model->trx_entitled($data['orig_trx']['id']);
                $data['order_comm']		= $this->Order_model->view_comm($sub_order_no);
                $data['order_option'] = $this->Order_model->view_order_option($sub_order_no);
                $data['order_traveller_group'] = $this->Order_model->order_traveler_group($sub_order_no);
                $data['order_traveller'] = $this->Order_model->order_traveler($sub_order_no);
                $tripThemes = $this->Tour_model->view_tour_theme($data['tour_id']);
                $tripThemesDesc = [];
                foreach ($tripThemes as $tripTheme) {
                    $tripThemesDesc[] = $tripTheme['theme_desc'];
                }
                $data['tripThemesDesc'] = implode(', ', $tripThemesDesc);
                //$this->code->load_page('view_order',$data);
                break;

            case 'hotel':
                $this->load->model(array('Hotel_model'));
                $orderHotel = $this->Order_model->order_hotel($order_no);
                $perRoomGuest        = $this->Hotel_model->getPerRoomGuest($order_no);
                $data['order_hotel'] = $this->Order_model->order_hotel($order_no);
                $data['order_trx'] = $this->Transaction_model->by_order_no($order_no);
                $data['perRoomGuest']        = $perRoomGuest;

                $transactionList = $this->Transaction_model->by_order_no($order_no);
                $lastTransaction = end($transactionList);

                $data['hotelId'] = $data['order_hotel'][0]['hotel_id'];
                $data['voucherData'] = $lastTransaction;
                $data['isHaveVoucher'] = (is_null($lastTransaction['voucher_code'])) ? false : true;

                // get tour list
                $data['cityName'] = $data['order']['sector'];
                $filter['tour_city'] = $data['order']['sector'];
                $data['trips'] = $this->Tour_model->list_tour($filter);
        		foreach ($data['trips'] as $t => $trp) {
        			$data['trips'][$t]['icons'] = $this->Tour_model->get_tour_with_icon($trp['tour_id']);
        		}

                //$this->code->load_page('hotel/order', $data);
                break;
            default:

                break;
        }
        return $data;
	}

	public function send_order_detail_to_email()
	{
		$order_no = $this->input->post('order_no');
		$email = $this->input->post('mail_to');

		$product = $this->Order_model->_get_product($order_no."_1");

		$data = $this->get_data_order($product,$order_no);

		/*$html  = $this-> load -> view ('report/print_header',$data, true);
		$html .= $this-> load -> view ('home/view_order',$data, true);
		$html .= $this-> load -> view ('report/print_footer',$data, true);*/
		
		$html = $this->code->admin_page('z_test',$data, true);
		
		$this->output->enable_profiler(true);

		$this -> load -> helper (array('dompdf','file'));
		$timestamp = date('Ymd',time());
		pdf_create($html, "{$timestamp}");

		echo 'asefasfease';


		//return false;
		$order_no = $this->input->post('order_no');
		$this->load->library('code_email');
		$this->code_email->sendTourBookedNotification_to_email($order_no,$email,$pdf);
		$this->session->set_flashdata('flash_notification','Email is sent to '.$email);
		redirect($_SERVER['HTTP_REFERER']);
	}
	public function send_order_detail_trx_to_email($trx_id)
	{
		$trx_id = $this->input->post('trx_id');
		$this->load->library('code_email');
		$this->code_email->sendTourBookedNotification_trx_to_email($trx_id);
		$this->session->set_flashdata('flash_notification','Email is sent to '.$email);
		redirect($_SERVER['HTTP_REFERER']);
	}
	public function assigned($type="hotel",$page=0)
	{
		$status = "ALL"; 
		if($this->session->userdata('user_id') === NULL){
			$this->session->set_flashdata('flash_notification','Your session has expired.');
			redirect('auth/admin_login');
		}
		$data = $this->get_by_product($type);
		$temp_status = "";
		if($status && $status <> "ALL") {
			$filters['status'] = $status;
			$temp_status = $status;
		}
		$data['user_id'] 		= $this->session->userdata('user_id');
		$data['type']			= $type;
		$data['status_code'] 	= $status;
		$data['status_info']	= $this->Status_model->get_status_name($temp_status);
		$data['count_order'] 	= count($this->Order_model->count_order('tour',$temp_status));
		$data['order_list']		= $this->Order_model->get_order_by_assigned($this->session->userdata('user_id'),$type);
		$data['ajax_path'] 		= base_url('/order/ajax_assigned_list');
		
		$this->code->admin_page('trx',$data);
	}

	public function set_user_assigned()
	{
		if($this->input->post()){
			$order_no = $this->input->post('order_no');
			$sub_order_no = $this->input->post('sub_order_no');
			$user_id = $this->input->post('assign');
			if($this->Order_model->set_user_assigned($sub_order_no,$user_id)){
				//var_dump($this->Order_model->set_user_assigned($order_no,$user_id));
                redirect($_SERVER['HTTP_REFERER']);
			}else{
				show_error("Traveller Data Updating Failed.");
			}
		}
	}

    public function cancel_hotel_order()
    {
        if ($this->input->post()) {
            $sub_order_no = $this->input->post('sub_order_no');
            $orderHeader = $this->Order_model->getDetailHotelOrder($sub_order_no);

            $this->load->library('Code');
            if ($result = $this->code->cancelHotelOrder($orderHeader[0]['booking_no'], $orderHeader[0]['confirmation_no'], $orderHeader[0]['contact_email'])) {
                $this->session->set_flashdata('flash_notification', 'Hotel has been cancelled by supplier');
            } else {
                $this->session->set_flashdata('flash_notification', 'Error request cancel hotel to supplier');
            }
        }
        redirect($_SERVER['HTTP_REFERER']);
    }

	public function set_order_status()
	{
		if($this->input->post()){
			$order_no = $this->input->post('order_no');
			$sub_order_no = $this->input->post('sub_order_no');
			$new_status	= $this->input->post('new_status');
			$old_status	= $this->input->post('old_status');
			if($this->Order_model->set_new_status($order_no,$new_status)){
				redirect('tour/flight_sub_edit/'.$sub_order_no);
			}else{
				show_error("Traveller Data Updating Failed.");
			}
		}
	}


	public function get_by_product($type)
	{
		//$tmp['status_list']		= $this->Status_model->list_status(array('category'=>$type));
		$tmp['status_list']		= $this->Status_model->list_all_status();
		$tmp['status_group']	= $this->Order_model->list_order_group_by(array('product'=>$type));
		$tmp['count_all_order'] = count($this->Order_model->count_order($type));
		return $tmp;
	}

	/*public function order_add()
	{
		# code.. //$this->code->load_page('select');
	}*/

	public function order_view($where)
	{
		# code...
	}

	public function order_edit($where,$data)
	{
		# code...
	}

	public function order_delete($where)
	{
		# code...
	}

	public function order_req_cancel($where,$data)
	{
		# code... later using API
	}

	public function order_req_update($where,$data)
	{
		# code... later using API
	}


	public function trx($status='')
	{
		$data = $this->get_by_product('tour');
		$temp_status = "";
		if($status && $status <> "ALL") {
			$filters['status'] = $status;
			$temp_status = $status;
		}
		$data['status_code'] 	= $status;
		$data['status_info']	= $this->Status_model->get_status_name($temp_status);
		//$data['order_list']		= $this->Order_model->list_order($filters,$page);
		$data['count_order'] 	= count($this->Order_model->count_order('tour',$temp_status));
		$data['ajax_path'] 		= base_url('/order/ajax_tour_change');
		$this->code->admin_page('trx',$data);
	}
	public function change_trx($product="hotel")
	{
		/*$data = $this->get_by_product('tour');
		$status = "";
		$temp_status = "";

		$filters['product'] = 'tour';
		$filters['order_status'] = 'CHANGE_REQUEST';

		//var_dump($filters);
		$data['status_code'] 	= $status;
		$data['status_info']	= $this->Status_model->get_status_name($temp_status);
		$data['order_list']		= $this->Order_model->list_order($filters);
		//var_dump($data['order_list']);
		$data['count_order'] 	= count($this->Order_model->count_order('tour',$temp_status));
		$this->code->admin_page('tour_trx',$data);*/

		$filters['product'] = $product;
		$data = $this->get_by_product($product);
		$temp_status = "";
		/*if($status && $status <> "ALL") {
			$filters['status'] = $status;
			$temp_status = $status;
		}*/
		$data['status_code'] 	= "OCGQ";//$status;
		$data['status_info']	= "Change Requested";//$this->Status_model->get_status_name($temp_status);
		//$data['order_list']		= $this->Order_model->list_order($filters,$page);
		//$data['count_order'] 	= count($this->Order_model->count_order('tour',$temp_status));

		//$data['ajax_path'] 		= base_url('/order/ajax_tour_change');
		$data['ajax_path'] 		= base_url('/order/ajax_trx');
		$this->code->admin_page('tour_trx',$data);
	}
	public function cancel_trx($status="OCLQ",$page=0)
	{
		if($status && $status <> "ALL") {
			$filters['status'] = $status;
			$temp_status = $status;
		}
		$data['status_list']	= $this->Status_model->list_all_status();
		$data['status_group']	= $this->Order_model->list_order_group_by(array('product'=>$type));

		$data['status_code'] 	= $status;
		$data['status_info']	= $this->Status_model->get_status_name($temp_status);
		$data['order_list']		= $this->Order_model->list_order($filters,$page);
		$data['count_order'] 	= count($this->Order_model->count_order('tour',$temp_status));
		$data['ajax_path'] 		= base_url('/order/ajax_trx_cancel');
		$this->code->admin_page('tour_trx',$data);
	}




	/***********************************************************
	 * A J A X
	 ***********************************************************/
	public function ajax_flight_list($return="json")
	{
		if($return == 'json') echo json_encode($this->Order_model->ajax_flight_list($_GET['status']));
	}
	public function ajax_hotel_list($return="json")
	{
		if($return == 'json') echo json_encode($this->Order_model->ajax_hotel_list($_GET['status']));
	}
	public function ajax_tour_list($return="json")
	{
		if($return == 'json') echo json_encode($this->Order_model->ajax_tour_list($_GET['status']));
	}
	public function ajax_tour_change($return="json")
	{
		if($return == 'json') echo json_encode($this->Order_model->ajax_tour_change());
	}
	public function ajax_trx_cancel($return='json')
	{
		if($return == 'json') echo json_encode($this->Order_model->ajax_trx_cancel());
	}
	public function ajax_trx($return="json")
	{
		$prod = $this->input->get("product");
		$stat = $this->input->get("status");
		//var_dump($stat);
		if($return == 'json') echo json_encode($this->Order_model->ajax_trx($prod,$stat));
	}

	public function ajax_assigned_list($return="json")
	{	//var_dump($this->input->get());
		if($return == 'json') echo json_encode($this->Order_model->ajax_assigned_list($_GET['user_id'],$_GET['type_order']));
	}
}
