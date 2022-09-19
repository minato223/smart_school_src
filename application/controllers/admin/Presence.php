<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Presence extends Admin_Controller
{

    function __construct()
    {

        parent::__construct();
        $this->load->helper('file');

        $this->config->load("mailsms");
        $this->config->load("payroll");
        $this->load->library('mailsmsconf');
        $this->config_attendance = $this->config->item('attendence');
        $this->staff_attendance = $this->config->item('staffattendance');
        $this->load->model("presencemodel");
        $this->load->model("staff_model");
        $this->load->model("payroll_model");
    }

    function index()
    {

        if (!($this->rbac->hasPrivilege('staff_attendance', 'can_view'))) {
            access_denied();
        }
        $this->session->set_userdata('top_menu', 'HR');
        $this->session->set_userdata('sub_menu', 'admin/presence');
        $data['title'] = 'Liste de présence';
        $data['title_list'] = 'Liste de présence';
        $user_type = $this->staff_model->getStaffRole();
        $data['classlist'] = $user_type;
        $data['class_id'] = "";
        $data['section_id'] = "";
        $data['date'] = "";
        $user_type_id = $this->input->post('user_id');
        $data["user_type_id"] = $user_type_id;
        $data["presences"] = $this->presencemodel->getAttendance();
        if (isset($_POST) && !empty($_POST["date"])) {
            $post_date = $_POST["date"];
            try {
                $new_date = new DateTimeImmutable($post_date);
            } catch (\Throwable $th) {
                $new_date = new DateTimeImmutable();
            }
            $formated_date = $new_date->format("Y-m-d");
            $data["presences"] = $this->presencemodel->getAttendance(null, $formated_date);
        }
        $this->load->view('layout/header', $data);
        $this->load->view('admin/presence/listepresence', $data);
        $this->load->view('layout/footer', $data);

        return;
        if (!(isset($user_type_id))) {
            $this->load->view('layout/header', $data);
            $this->load->view('admin/staffattendance/staffattendancelist', $data);
            $this->load->view('layout/footer', $data);
        } else {

            $user_type = $this->input->post('user_id');
            $date = $this->input->post('date');
            $user_list = $this->staffattendancemodel->get();
            $data['userlist'] = $user_list;
            $data['class_id'] = $user_list;
            $data['user_type_id'] = $user_type_id;
            $data['section_id'] = "";
            $data['date'] = $date;
            $search = $this->input->post('search');
            $holiday = $this->input->post('holiday');
            $this->session->set_flashdata('msg', '');
            if ($search == "saveattendence") {
                $user_type_ary = $this->input->post('student_session');
                $absent_student_list = array();
                foreach ($user_type_ary as $key => $value) {
                    $checkForUpdate = $this->input->post('attendendence_id' . $value);
                    if ($checkForUpdate != 0) {
                        if (isset($holiday)) {
                            $arr = array(
                                'id' => $checkForUpdate,
                                'staff_id' => $value,
                                'staff_attendance_type_id' => 5,
                                'remark' => $this->input->post("remark" . $value),
                                'date' => date('Y-m-d', $this->customlib->datetostrtotime($date))
                            );
                        } else {
                            $arr = array(
                                'id' => $checkForUpdate,
                                'staff_id' => $value,
                                'staff_attendance_type_id' => $this->input->post('attendencetype' . $value),
                                'remark' => $this->input->post("remark" . $value),
                                'date' => date('Y-m-d', $this->customlib->datetostrtotime($date))
                            );
                        }

                        $insert_id = $this->staffattendancemodel->add($arr);
                    } else {
                        if (isset($holiday)) {
                            $arr = array(
                                'staff_id' => $value,
                                'staff_attendance_type_id' => 5,
                                'date' => date('Y-m-d', $this->customlib->datetostrtotime($date)),
                                'remark' => ''
                            );
                        } else {
                            $arr = array(
                                'staff_id' => $value,
                                'staff_attendance_type_id' => $this->input->post('attendencetype' . $value),
                                'date' => date('Y-m-d', $this->customlib->datetostrtotime($date)),
                                'remark' => $this->input->post("remark" . $value),
                            );
                        }
                        $insert_id = $this->staffattendancemodel->add($arr);
                        $absent_config = $this->config_attendance['absent'];
                        if ($arr['staff_attendance_type_id'] == $absent_config) {
                            $absent_student_list[] = $value;
                        }
                    }
                }

                $absent_config = $this->config_attendance['absent'];
                if (!empty($absent_student_list)) {

                    $this->mailsmsconf->mailsms('absent_attendence', $absent_student_list, $date);
                }
                $this->session->set_flashdata('msg', '<div class="alert alert-success text-left">' . $this->lang->line('success_message') . '</div>');
                redirect('admin/staffattendance/index');
            }

            $attendencetypes = $this->attendencetype_model->getStaffAttendanceType();
            $data['attendencetypeslist'] = $attendencetypes;
            $resultlist = $this->staffattendancemodel->searchAttendenceUserType($user_type, date('Y-m-d', $this->customlib->datetostrtotime($date)));
            $data['resultlist'] = $resultlist;
            $this->load->view('layout/header', $data);
            $this->load->view('admin/staffattendance/staffattendancelist', $data);
            $this->load->view('layout/footer', $data);
        }
    }
}
