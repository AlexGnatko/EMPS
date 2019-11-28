<?php

/**
 * EMPS_Service Class - handle heartbeat services
 */
class EMPS_Service
{
    public $interval;
    public $last_dt;
    public $service_variable;

    public $common_mode = false;

    public function init($varname, $interval){
        global $emps, $pp;

        $this->service_variable = $varname;
        $this->interval = $interval;

        $emps->no_time_limit();
        $emps->no_smarty = true;

        header("Content-Type: text/plain; charset=utf-8");

        $this->last_dt = $this->get_setting($this->service_variable);
        echo "Service: ".$pp." at ".$emps->form_time(time())."\r\n";
    }

    public function is_runnable(){
        global $emps;
        if($_GET['runnow']){
            return true;
        }
        if($this->last_dt < (time() - $this->interval)){
            $this->save_setting($this->service_variable, time());
            return true;
        }else{
            echo "Time left: ".($this->last_dt - time() + $this->interval)." seconds\r\n";
        }
        return false;
    }

    public function get_setting($var) {
        global $emps;
        if ($this->common_mode) {
            return $emps->get_setting_common($var);
        }
        return $emps->get_setting($var);
    }

    public function save_setting($var, $value) {
        global $emps;
        if ($this->common_mode) {
            return $emps->save_setting_common($var, $value);
        }
        return $emps->save_setting($var, $value);
    }
}