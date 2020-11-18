<?php

/**
 * EMPS_Sitemap Class - sitemap utilities
 */
class EMPS_Sitemap
{
    public $menu_items = [];
    public $main_pages = [];
    public $sitemaps = [];
    public $lists = [];

    public $perpage = 1000;

    public function add_menus($list){
        global $emps;

        $x = explode(",", $list);
        foreach($x as $menu_code){
            $menu_code = trim($menu_code);
            $menu = $emps->section_menu_ex($menu_code, 0, 0);
            $this->add_menu_items($menu);
        }
    }

    public function add_menu_items($menu){
        global $emps;

        foreach($menu as $item){
            $code = $item['link'];
            if(count($item['sub']) > 0){
                $this->add_menu_items($item['sub']);
            }
            if(mb_substr($code, 0, 1) == "#"){
                continue;
            }
            if(mb_substr($code, 0, 1) == "-"){
                continue;
            }
            if($item['name'] == "-"){
                continue;
            }
            $x = explode("#", $code);
            $code = $x[0];

            if(isset($this->menu_items[$code])){
                continue;
            }

            $content_item = $emps->get_db_content_item($item['link']);
            if($content_item){
                $item['content'] = $content_item;
            }
            $this->menu_items[$code] = $item;
        }
    }

    public function form_date($dt){
        return date("c", $dt);
    }

    public function add_sitemap($name){
        $sitemap = ['name' => $name, 'time' => $this->form_date(time())];
        $this->sitemaps[] = $sitemap;
    }

    public function list_index($code){
        return ['total' => 0, 'dt' => time()];
    }

    public function add_list($code){
        global $key;

        $this->lists[$code] = true;

        if($key == "index.xml"){
            $index = $this->list_index($code);
            $total = $index['total'];
            $dt = $index['dt'];
            $pages = ceil($total / $this->perpage);
            for ($i = 0; $i < $pages; $i++) {
                $start = $i * $this->perpage;

                $time = date("c", $dt);
                $a['name'] = $code."-" . $start . ".xml";
                $a['time'] = $time;

                $this->sitemaps[] = $a;
            }
        }
    }

    public function handle_index(){
        global $smarty;

        $smarty->assign("lst", $this->sitemaps);

        $smarty->display("db:sitemap/index");
    }

    public function handle_menus(){
        global $smarty;

        $lst = [];
        foreach($this->menu_items as $item){
            $a = [];
            $a['name'] = $item['dname'];
            $a['link'] = $item['link'];
            $dt = time();
            if($item['content']){
                $dt = $item['content']['dt'];
            }
            $a['lastmod'] = $this->form_date($dt);
            $a['freq'] = $this->freq_grade($dt);
            $lst[] = $a;
        }
        $smarty->assign("lst", $lst);
        $smarty->display("db:sitemap/list");
    }

    public function add_page($name, $link, $dt) {
        $this->main_pages[] = ['dname' => $name, 'link' => $link, 'dt' => $dt];
    }

    public function handle_main(){
        global $smarty;

        $lst = [];
        foreach($this->main_pages as $item){
            $a = [];
            $a['name'] = $item['dname'];
            $a['link'] = $item['link'];
            $dt = $item['dt'];
            $a['lastmod'] = $this->form_date($dt);
            $a['freq'] = $this->freq_grade($dt);
            $lst[] = $a;
        }
        $smarty->assign("lst", $lst);
        $smarty->display("db:sitemap/list");
    }

    public function list_page($code, $page_start){
        return [];
    }

    public function handle_list($code, $page_start){
        global $smarty;

        $lst = $this->list_page($code, $page_start);

        $smarty->assign("lst", $lst);
        $smarty->display("db:sitemap/list");

    }

    public function handle_request(){
        global $key, $emps, $smarty;

        $smarty->assign("BaseURL", EMPS_SCRIPT_WEB);

        $emps->no_smarty = true;
        header("Content-Type: text/xml; charset=utf-8");
        //header("Content-Type: text/plain; charset=utf-8");

        if($key == "index.xml"){
            $this->handle_index();
            return;
        }

        if($key == "menus.xml"){
            $this->handle_menus();
            return;
        }

        if($key == "main.xml"){
            $this->handle_main();
            return;
        }

        $x = explode(".", $key);
        if($x[1] == 'xml'){
            $xx = explode("-", $x[0]);
            $code = $xx[0];
            $page_start = $xx[1];
            if($this->lists[$code]){
                $this->handle_list($code, $page_start);
            }
        }
    }

    function freq_grade($dt)
    {
        $diff = floor((time() - $dt) / (60 * 60));
        if ($diff < 12) {
            return "hourly";
        }
        if ($diff < 24) {
            return "daily";
        }
        return "weekly";
    }

    function days($number, $dt) {
        $z = $dt / ($number * 24 * 60 * 60);
        $z = floor($z);
        $rdt = $z * ($number * 24 * 60 * 60);
        return $rdt;
    }

    public function pics_to_images($default_name, $context_id) {
        global $emps;

        $r = $emps->db->query("select * from ".TP."e_uploads where context_id = {$context_id} order by ord asc, id asc");
        $lst = [];
        while ($ra = $emps->db->fetch_named($r)) {
            $a = [];
            $a['descr'] = $ra['descr'];
            if (!$a['descr']) {
                $a['descr'] = $default_name;
            }
            $a['url'] = EMPS_SCRIPT_WEB."/pic/".$ra['md5']."/".$ra['filename'];
            $lst[] = $a;
        }
        return $lst;
    }
}