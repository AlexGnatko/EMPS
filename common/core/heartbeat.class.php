<?php

/**
 * EMPS_Heartbeat Class - handle multiple cURL requests for heartbeat operations
 */
class EMPS_Heartbeat {
    public $queue = [];
    public $ch = [];

    public function add_url($url){
        $this->queue[] = EMPS_SCRIPT_WEB.$url;
    }

    public function add_full_url($url){
        $this->queue[] = $url;
    }

    public function execute(){
//        set_time_limit(60);

        error_log("CURL Execute");
        foreach($this->queue as $url){
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);

            error_log("Heartbeat: ".$url);

            $this->ch[] = $ch;
        }

        $mh = curl_multi_init();

        foreach($this->ch as $ch) {
            error_log("Adding handle");
            curl_multi_add_handle($mh, $ch);
        }
/*
        $active = null;
        do {
            $mrc = curl_multi_exec($mh, $active);
            sleep(1);
            error_log("mrc: ".$mrc);
            break;
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);

        while ($active && $mrc == CURLM_OK) {
            if (curl_multi_select($mh) != -1) {
                do {
                    $mrc = curl_multi_exec($mh, $active);
                    error_log("mrc: ".$mrc);
                    sleep(1);
                    break;
                } while ($mrc == CURLM_CALL_MULTI_PERFORM);
            }
            break;
        }*/

/*
        $running = 0;

        do {
            curl_multi_exec($mh, $running);
            curl_multi_select($mh);
        } while ($running > 0);*/

        foreach($this->ch as $ch){
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }

        curl_multi_close($mh);
    }
}