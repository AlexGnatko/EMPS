<?php

/**
 * EMPS_Service Class - handle heartbeat services
 */
class EMPS_Service
{
    public $interval;
    public $last_dt;
    public $service_variable;

    public function init($varname, $interval){
        global $emps, $pp;

        $this->service_variable = $varname;
        $this->interval = $interval;

        $emps->no_time_limit();
        $emps->no_smarty = true;

        header("Content-Type: text/plain; charset=utf-8");

        $this->last_dt = $emps->get_setting($this->service_variable);
        echo "Service: ".$pp." at ".$emps->form_time(time())."\r\n";
    }

    public function is_runnable(){
        global $emps;
        if($_GET['runnow']){
            return true;
        }
        if($this->last_dt < (time() - $this->interval)){
            $emps->save_setting($this->service_variable, time());
            return true;
        }else{
            echo "Time left: ".($this->last_dt - time() + $this->interval)." seconds\r\n";
        }
        return false;
    }
}