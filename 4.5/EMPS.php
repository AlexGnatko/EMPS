<?php
/**
 * EMPS MULTI-WEBSITE ENGINE
 *
 * Version 4.5 / MySQL-based
 */

require_once EMPS_COMMON_PATH_PREFIX . "/EMPS.php";

/**
 * EMPS Class - Version 4.5 / MySQL-based
 */
class EMPS extends EMPS_Common
{
    public $db;
    public $cas;

    public $settings_cache, $content_cache;

    public $period_size = 60 * 60 * 24 * 7;

    public function __destruct()
    {
        unset($this->db);
        unset($this->p);
        ob_end_flush();
    }

    public function early_init()
    {
        $this->db = new EMPS_DB;
        if (isset($GLOBALS['emps_cassandra_config'])) {
            $this->cas = new EMPS_Cassandra;
        }
        $this->p = new EMPS_Properties;
        if (!$this->fast) {
            $this->auth = new EMPS_Auth;
        }

        $this->p->db = $this->db;

        $this->db->query("SET SESSION sql_mode=''");
    }

    public function section_menu_ex($code, $parent, $default_parent)
    {
        // Load the menu "grp=$code" and return it as a nested array (if subenus are present)
        $menu = array();

        $use_context = $this->website_ctx;

        $query = 'select * from ' . TP . "e_menu where parent=$parent and context_id=" . $use_context . " and grp='$code' order by ord asc";
        $r = $this->db->query($query);

        $mlst = array();
        while ($ra = $this->db->fetch_named($r)) {
            $mlst[] = $ra;
        }

        if ($parent == 0 || $default_parent) {
            $use_parent = $parent;
            if ($default_parent) {
                $use_parent = $default_parent;
            }
            $q = 'select * from ' . TP . "e_menu where parent=$use_parent and context_id=" . $this->default_ctx . " and grp='$code' order by ord asc";

            $r = $this->db->query($q);
            $dlst = array();
            while ($ra = $this->db->fetch_named($r)) {
                $ra['default_id'] = $ra['id'];
                $dlst[] = $ra;
            }
            $ndlst = array();
            foreach($dlst as $v) {
                reset($mlst);
                $add = true;
                foreach($mlst as $nn => $vv){
                    if ($vv['uri'] == $v['uri'] && $vv['grp'] == $v['grp']) {
                        $mlst[$nn]['default_id'] = $v['id'];
                        $add = false;
                    }
                }
                if ($add) {
                    $ndlst[] = $v;
                }
            }
            if ($ndlst) {
                reset($ndlst);
                foreach($ndlst as $vv){
                    $mlst[] = $vv;
                }

                uasort($mlst, array($this, 'sort_menu'));
            }
        }
        reset($mlst);
        foreach($mlst as $ra) {
            if(!$ra['enabled']){
                continue;
            }
            $md = $this->get_menu_data($ra);

            $ra['link'] = $ra['uri'];

            $ra['splink'] = $md['splink'];
            if (!$ra['splink']) {
                $ra['splink'] = $ra['link'];
            }

            if (!$md['name']) {
                $use_name = $ra['uri'];
            } else {
                if ($md['name$' . $this->lang]) {
                    $use_name = $md['name$' . $this->lang];
                } else {
                    $use_name = $md['name'];
                }
            }

            $ra['dname'] = $use_name;

            if ($md['width']) {
                $ra['width'] = $md['width'];
            }

            if (!$md['regex']) {
                if ($ra['uri'] == $this->menu_URI) {
                    $ra['sel'] = 1;
                } else {
                    if ($ra['uri']) {
                        $x = explode($ra['uri'], $this->menu_URI);
                        if ($x[0] == '' && $x[1] != '') {
                            $ra['sel'] = 1;
                        }
                    }
                }
            }

            if ($md['regex']) {
                if (preg_match('/' . $md['regex'] . '/', $this->menu_URI)) {
                    $ra['sel'] = 1;
                }
            }

            if ($md['grant']) {
                if (!$this->auth->credentials($md['grant'])) continue;
            }

            if ($md['hide']) {
                if ($this->auth->credentials($md['hide'])) continue;
            }

            if ($md['nouser']) {
                if ($this->auth->USER_ID) continue;
            }

            $smenu = $this->section_menu_ex($code, $ra['id'], $ra['default_id']);

            $ra['sub'] = $smenu;
            $ra['md'] = $md;
            $menu[] = $ra;
        }
        return $menu;

    }

    public function save_setting($code, $value)
    {
        $x = explode(':', $code);
        $name = $x[0];
        if (!isset($x[1])) {
            $code = $name . ":t";
        }
        $a = array($name => $value);
        $this->p->save_properties($a, $this->website_ctx, $code);
    }

    public function get_setting($code)
    {
        // Get a fine-tuning setting
        if (!is_array($this->settings_cache)) {
            $default_settings = $this->p->read_properties(array(), $this->default_ctx);
            if (!$default_settings) {
                $default_settings = array();
            }
            $website_settings = $this->p->read_properties(array(), $this->website_ctx);
            if (!$website_settings) {
                $website_settings = array();
            }
            if (!$default_settings['_full']) {
                $default_settings['_full'] = array();
            }
            if (!$website_settings['_full']) {
                $website_settings['_full'] = array();
            }
            $website_settings['_full'] = array_merge($default_settings['_full'], $website_settings['_full']);
            $this->settings_cache = array_merge($default_settings, $website_settings);
//			dump($this->settings_cache);
        }
        $rv = $this->settings_cache[$code];
        if(isset($rv)){
            if(intval($this->settings_cache['_full'][$code]['id']) > 0){
                return $rv;
            }
        }
        return false;
    }

    public function website_by_host($hostname)
    {
        $website = $this->db->get_row("e_websites", "'" . $this->db->sql_escape($hostname) . "' regexp hostname_filter or hostname = '" . $this->db->sql_escape($hostname) . "'");
        if ($website) {
//			dump($website);
            $this->current_website = $website;
            if ($website['lang']) {
                $this->lang = $website['lang'];
            }
            return $website['id'];
        }
        return 0;
    }

    public function select_website()
    {
        // URL parser to decide which website is active
        $hostname = $_SERVER['SERVER_NAME'];
        $this->default_ctx = $this->p->get_context(1, 1, 0);
        $website_id = $this->website_by_host($hostname);
        $this->website_id = 0;
        if ($website_id) {
            $this->website_id = $website_id;
            if ($this->current_website['status'] == 100) {
                $this->website_ctx = $this->default_ctx;
            } else {
                $this->website_ctx = $this->p->get_context(DT_WEBSITE, 1, $website_id);
            }
        } else {
            $this->website_ctx = $this->default_ctx;
        }
//		echo "ctx: ".$this->website_ctx;
    }

    public function base_url_by_ctx($website_ctx)
    {
        $ctx = $this->db->get_row("e_contexts", "id = " . $website_ctx);
        if ($ctx) {
            if ($ctx['ref_type'] == DT_WEBSITE) {
                $website = $this->db->get_row("e_websites", "id=" . $ctx['ref_id']);
                if ($website) {
                    return "http://" . $website['hostname'];
                }
            }
        }
        return EMPS_SCRIPT_WEB;
    }

    public function display_log()
    {
        global $smarty;
        $smarty->assign("ShowTiming", EMPS_SHOW_TIMING);
        $smarty->assign("ShowErrors", EMPS_SHOW_SQL_ERRORS);
        $end_time = emps_microtime_float(microtime(true));

        $span = $end_time - $this->start_time;

        $smarty->assign("timespan", sprintf("%02d", $span * 1000));
        $smarty->assign("errors", $this->db->sql_errors);
        if ($_GET['sql_profile']) {
            $smarty->assign("SqlProfile", 1);
            $smarty->assign("timing", $this->db->sql_timing);
        }

        return $smarty->fetch("db:page/foottimer");
    }

    function get_full_id($id, $table, $pf, $vf)
    {
        global $emps;
        $row = $emps->db->get_row($table, "id = {$id}");
        if (!$row) {
            return "";
        }

        if ($row[$pf]) {
            $full_id = $this->get_full_id($row[$pf], $table, $pf, $vf);
        } else {
            $full_id = "";
        }

        $value = "";
        $vle = $row[$vf];
        $id = -$vle + 0;
        for ($i = 0; $i < 4; $i++) {
            $cur = ($id >> ((3 - $i) * 8)) & 255;
            $value .= chr($cur);
        }
        return $full_id . $value;
    }

    public function not_default_website()
    {
        global $smarty;
        if ($this->current_website['status'] == 100) {
            if ($this->website_ctx == $this->default_ctx) {
                $this->deny_access('WebsiteNeeded');

                $r = $this->db->query("select * from " . TP . "e_websites where status = 50 and pub = 10 and parent = " . $this->current_website['id'] . " order by hostname asc");
                $lst = array();
                while ($ra = $this->db->fetch_named($r)) {
                    $lst[] = $ra;
                }

                $smarty->assign("wlst", $lst);
                $smarty->assign("current_url", $_SERVER['REQUEST_URI']);

                return false;
            }
        }
        return true;
    }

    public function handle_redirect($uri)
    {
        $ouri = $this->db->sql_escape(urldecode($uri));
        $row = $this->db->get_row("e_redirect", "'$ouri' regexp olduri");
        if ($row) {
            // redirect if there is an entry in the e_redirect table
            header("HTTP/1.1 301 Moved Permanently");
            $this->redirect_page($row['newuri']);
            exit();
        }
    }

    public function get_db_content_item($uri)
    {
        // Return the e_content item by URI, cache the response

        if (isset($this->content_cache[$uri])) return $this->content_cache[$uri];

        $euri = $this->db->sql_escape($uri);

        $q = "select * from " . TP . "e_content where uri='$euri' and context_id = " . $this->website_ctx;
        $r = $this->db->query($q);
        $ra = $this->db->fetch_named($r);
        if (!$ra) {
            $q = "select * from " . TP . "e_content where uri='$euri' and context_id = " . $this->default_ctx;
            $r = $this->db->query($q);
            $ra = $this->db->fetch_named($r);
        }
        $content_cache[$uri] = $ra;
        return $ra;
    }

    public function get_db_cache($code) {
        $result = $this->p->read_cache($this->website_ctx, $code);
        if ($result) {
            return $result['data'];
        }
        return "";
    }

    public function get_content_data($page)
    {
        // Read the properties of a content item (effectively page_properties)
        $context_id = $this->p->get_context(DT_CONTENT, 1, $page['id']);
        $ra = $this->p->read_properties(array(), $context_id);
        $ra['context_id'] = $context_id;
        $ra['page_context_id'] = $context_id;
        return $ra;
    }

    public function get_menu_data($item)
    {
        // Read the properties of a menu item
        $ra = $this->p->read_properties(array(), $this->p->get_context(DT_MENU, 1, $item['id']));
        return $ra;
    }

    public function get_setting_time($code)
    {
        // Get the timestamp of a fine-tuning setting
        $ra = $this->get_setting($code);
        if ($ra) {
//			echo "has setting $code: ".$this->settings_cache['_full'][$code]['dt'].", ";
            return $this->settings_cache['_full'][$code]['dt'] + 0;
        } else {
            return false;
        }
    }

    public function print_pages_found()
    {
        $found = $this->db->found_rows();
        return $this->print_pages($found);
    }

    public function redirect_elink()
    {
        if (count($this->db->sql_errors) > 0) {
//			dump($this->db->sql_errors);
            return false;
        }
        $this->redirect_page($this->elink());
    }

    public function reset_antibot()
    {
        // Discard a used antibot key
        $pk = $_SESSION['antibot_pin'];
        $this->db->query('delete from ' . TP . 'e_pincode where pincode=' . ($pk['pin'] + 0) . ' and access=' . ($pk['sid'] + 0));
        unset($_SESSION['antibot_pin']);
    }

    public function uses_antibot()
    {
        // Prepare the current script for using the anti-bot feature
        global $emps, $smarty, $pk;
        $ip = $this->auth->get_num_ip($_SERVER['REMOTE_ADDR']);
        $pk = $_SESSION['antibot_pin'];

        if (!$pk) {
            $dt = time();
            $dt <<= 16;
            $ip &= 0xFFFF;
            $sid = ($ip | $dt) & (0x7FFFFFFF);

            mt_srand($sid);
            $pin = mt_rand(1114122, 9912988);

            $pk['pin'] = $pin;
            $pk['sid'] = $sid;
            $_SESSION['antibot_pin'] = $pk;

            $dt = time();
            $this->db->query('insert into ' . TP . "e_pincode values ($pin,$dt,$sid)");
            $dt = time() - 60 * 60 * 24 * 7;
            $this->db->query('delete from ' . TP . 'e_pincode where dt < ' . $dt);
        }

        $smarty->assign("pk", $pk);
    }

    public function all_post_required()
    {
        reset($_POST);
        foreach($_POST as $n => $v){
            if (!$v) {
                $this->db->sql_null[$n] = true;
            }
        }
    }

    public function is_empty_database()
    {
        $r = $this->db->query("show tables");
        $lst = array();
        while ($ra = $this->db->fetch_row($r)) {
            $lst[] = $ra;
        }
        if (count($lst) == 0) {
            return true;
        }
        return false;
    }

    public function shadow_properties_link($link)
    {
        $link = $this->db->sql_escape($link);

        $shadow = $this->db->get_row("e_shadows", "url='" . $link . "' and website_ctx = " . $this->website_ctx);
        if (!$shadow) {
            $shadow = $this->db->get_row("e_shadows", "url='" . $link . "' and website_ctx = " . $this->default_ctx);
            if (!$shadow) {
                return false;
            }
        }
        $context_id = $this->p->get_context(DT_SHADOW, 1, $shadow['id']);
        $props = $this->p->read_properties(array(), $context_id);
        $this->page_properties = array_merge($this->page_properties, $props);
    }

    public function shadow_properties($vars)
    {
        $link = $this->raw_elink($vars);

        return $this->shadow_properties_link($link);
    }

    public function ensure_browser($name)
    {
        global $SET;
        if (isset($this->db)) {
            $row = $this->db->get_row("e_browsers", "name = '" . $this->db->sql_escape($name) . "'");
            if ($row) {
                return $row['id'];
            } else {
                $SET = array();
                $SET['name'] = $name;
                $this->db->sql_insert("e_browsers");
                $id = $this->db->last_insert();
                return $id;
            }
        } else {
            return -1;
        }
    }

    /**
     * Add the current remote IP address to the black list (or update the timestamps if it already exists)
     *
     *
     */
    public function add_to_blacklist()
    {
        $term = 180 * 24 * 60 * 60;
        $ip = $_SERVER['REMOTE_ADDR'];
        $row = $this->db->get_row("e_blacklist", "ip = '" . $ip . "'");

        $ur = array();
        $ur['edt'] = time() + $term;
        $ur['adt'] = time();

        if ($row) {
            $update = ['SET' => $ur];
            $this->db->sql_update_row("e_blacklist", $update, "id = " . $row['id']);
        } else {
            $ur['ip'] = $ip;
            $update = ['SET' => $ur];
            $this->db->sql_insert_row("e_blacklist", $update);
        }

        $this->service_blacklist();
    }

    /**
     * Add the current remote IP address to the black list (or update the timestamps if it already exists)
     *
     *
     */
    public function add_to_blacklist_term($term)
    {
        $ip = $_SERVER['REMOTE_ADDR'];
        $row = $this->db->get_row("e_blacklist", "ip = '" . $ip . "'");

        $ur = array();
        $ur['edt'] = time() + $term;
        $ur['adt'] = time();

        if ($row) {
            $update = ['SET' => $ur];
            $this->db->sql_update_row("e_blacklist", $update, "id = " . $row['id']);
        } else {
            $ur['ip'] = $ip;
            $update = ['SET' => $ur];
            $this->db->sql_insert_row("e_blacklist", $update);
        }

        $this->service_blacklist();
    }

    /**
     * Check if the current remote IP address is blacklisted
     *
     *
     */
    public function is_blacklisted()
    {
        $ip = $_SERVER['REMOTE_ADDR'];

        $row = $this->db->get_row("e_blacklist", "ip = '" . $ip . "'");
        if ($row) {
            return true;
        }

        return false;
    }

    /**
     * Delete expired items from the black list
     *
     *
     */
    public function service_blacklist()
    {
        $this->db->query("delete from " . TP . "e_blacklist where edt < " . time());
    }

    public function failed_antibot()
    {
        global $emps;

        $ip = $_SERVER['REMOTE_ADDR'];
        error_log("Failed antibot: " . $ip);

        $row = $this->db->get_row("e_watchlist", "ip = '" . $ip . "'");
        if ($row) {
            $cnt = $row['cnt'] + 1;

            $update = array();
            $update['SET'] = array('cnt' => $cnt);
            $emps->db->sql_update_row("e_watchlist", $update, "id = ".$row['id']);

        }else{
            $cnt = 1;
            $ur = array();
            $ur['ip'] = $ip;
            $ur['cnt'] = $cnt;
            $update = array();
            $update['SET'] = $ur;
            $emps->db->sql_insert_row("e_watchlist", $update);
        }

        if($cnt > 5){
            $this->add_to_blacklist_term(30 * 60);
        }
        if($cnt > 10){
            $this->add_to_blacklist_term(6 * 60 * 60);
        }
        if($cnt > 20){
            $this->add_to_blacklist_term(24 * 60 * 60);
        }
    }

    public function passed_antibot()
    {
        global $emps;
        $ip = $_SERVER['REMOTE_ADDR'];

        $row = $this->db->get_row("e_watchlist", "ip = '" . $ip . "'");
        if ($row) {
            $cnt = $row['cnt'] + 1;

            $update = array();
            $update['SET'] = array('cnt' => 0);
            $emps->db->sql_update_row("e_watchlist", $update, "id = ".$row['id']);
        }
    }

    public function page_properties_from_settings($list){
        $x = explode(",", $list);
        foreach($x as $v){
            $v = trim($v);
            $value = $this->get_setting($v);
            if(!$value) {
                continue;
            }
            $this->page_property($v, $value);
        }
    }

    public function add_stat($metric, $value) {
        $period = floor(time() / ($this->period_size));

        $context_id = $this->website_ctx;

        $nr = [];
        $nr['code'] = $metric;
        $nr['context_id'] = $context_id;
        $nr['per'] = $period;
        $nr['dt'] = time();
        $nr['value'] = $value;
        $this->db->query("lock tables ".TP."e_counter");
        $row = $this->db->get_row("e_counter", "code = '{$metric}' and context_id = {$context_id} and per = {$period}");
        if ($row) {
            $SET['vle'] = $row['vle'] + $value;
            $this->db->sql_update("e_counter", "id = " . $row['id']);
        } else {
            $this->db->sql_insert("e_counter");
        }
        $this->db->query("unlock tables");

    }
}

