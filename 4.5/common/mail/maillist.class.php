<?php
require_once $emps->common_module('uploads/uploads.class.php');
//require_once "Mail.php";
//require_once "Mail/mime.php";


class EMPS_MailList
{
    public $up;
    public $m;

    public function __construct()
    {
        $this->up = new EMPS_Uploads;
        $this->m = new Mail;
    }

    function prepare_mail($row, $context_id)
    {
        global $emps, $smarty;

        $fctx = $emps->p->get_context(DT_ML_MAIL, 4000, $row['id']);

        $this->up->delete_files_context($fctx);

        $hdrs = array(
            'Subject' => $row['name'],
            'Precedence' => 'bulk'
        );

        $mime = new Mail_mime(array('eol' => "\r\n", 'html_charset' => 'UTF-8', 'text_charset' => 'UTF-8', 'head_charset' => 'UTF-8', 'head_encoding' => 'base64', 'text_encoding' => 'quoted-printable',
            'html_encoding' => 'quoted-printable'));

        $style = "";
        $fname = EMPS_WEBSITE_SCRIPT_PATH . '/css/default.css';
        if (file_exists($fname)) {
            $style = file_get_contents($fname);
        } else {
            $fname = EMPS_SCRIPT_PATH . '/css/default.css';
            if (file_exists($fname)) {
                $style = file_get_contents($fname);
            }
        }
        $smarty->assign("BaseURL", EMPS_SCRIPT_WEB);
        $smarty->assign("default_style", $style);
        $smarty->assign("list_id", $row['list_id']);

        $lst = $this->up->list_files($context_id, 1000);

        /*		while(list($n,$v)=each($lst)){
                    $fname=$this->up->upload_filename($v['id'],DT_FILE);
                    $mime->addAttachment($fname, $v['content_type'], $v['file_name']);
                }*/
        $smarty->assign("files", $lst);

        $body = $smarty->fetch("db:ml/mltexthead") . $this->text_body($row['html']) . $smarty->fetch("db:ml/mltextfoot");

        $mime->setTXTBody($body);

        $body = $smarty->fetch("db:ml/mlhead") . $row['html'] . $smarty->fetch("db:ml/mlfoot");
        $mime->setHTMLBody($body);


        $body = $mime->get();
        $hdrs = $mime->headers($hdrs);

        list(, $hdt) = $this->m->prepareHeaders($hdrs);

        $_REQUEST['md5'] = md5(uniqid());
        $_REQUEST['file_name'] = "msg_body_" . $row['id'] . '_' . time() . '.txt';
        $_REQUEST['context_id'] = $fctx;
        $_REQUEST['content_type'] = "text/plain";
        $_REQUEST['size'] = strlen($body);
        $_REQUEST['user_id'] = $emps->auth->USER_ID;
        $emps->db->sql_insert("files");
        $file_id = $emps->db->last_insert();
        $fname = $this->up->upload_filename($file_id, DT_FILE);
        file_put_contents($fname, $hdt . "\r\n\r\n" . $body);

    }

    function process_email($email)
    {
        return trim(strtolower($email));
    }

    function ensure_address($email)
    {
        global $emps, $SET;

        $email = $emps->db->sql_escape($email);
        $row = $emps->db->get_row("ml_addresses", "email='$email'");
        if ($row) {
            return $row;
        }

        $SET = array();
        $SET['email'] = $email;
        $SET['status'] = 0;
        $emps->db->sql_insert("ml_addresses");
        $id = $emps->db->last_insert();

        $row = $emps->db->get_row("ml_addresses", "id=$id");
        return $row;
    }

    function remove_address($address_id)
    {
        global $emps;
        $emps->db->query("delete from " . TP . "ml_lists_addresses where address_id=$address_id");
        $emps->db->query("delete from " . TP . "ml_sent where address_id=$address_id");
        $emps->db->query("delete from " . TP . "ml_addresses where id=$address_id");
    }

    function set_list_address_status($list_id, $addr_id, $status)
    {
        global $emps;

        $list_id += 0;
        $addr_id += 0;
        $status += 0;

        $emps->db->query("update " . TP . "ml_lists_addresses set status=$status where list_id=$list_id and address_id=$addr_id");
    }

    // $mode = 0 - keep current maillist status, $mode != 0 - change maillist status to 0 for existing addresses
    function add_to_list($list_id, $email, $mode)
    {
        global $emps, $SET;

        $email = $this->process_email($email);

        $addr = $this->ensure_address($email);

        if (!$addr) {
            return 0;
        }

        $row = $emps->db->get_row("ml_lists_addresses", "list_id=$list_id and address_id=" . $addr['id']);
        if ($row) {
            if ($row['status'] == 0) {
                return 100;
            }
            if (!$mode) {
                if ($row['status'] == 20) {
                    return 250;
                }
            }
            if ($row['status'] == 20) {
                $this->set_list_address_status($list_id, $addr['id'], 0);
                return 210;
            }
        }

        $SET = array();
        $SET['list_id'] = $list_id;
        $SET['address_id'] = $addr['id'];
        $emps->db->sql_insert("ml_lists_addresses");

        return 200;
    }

    function text_body($html)
    {
        global $emps;
        $text = $html;
        require_once $emps->common_module('mail/conversion.php');

        return $out;
    }

    function mail_status($mail_id, $status)
    {
        global $emps;

        $status += 0;
        $mail_id += 0;
        $emps->db->query("update " . TP . "ml_messages set status=$status where id=$mail_id");
    }

    function clear_sentlist($mail_id)
    {
        global $emps;

        $mail_id += 0;
        $emps->db->query("delete from " . TP . "ml_sent where message_id=$mail_id");
    }

    function count_sent($mail_id)
    {
        global $emps;

        $mail_id += 0;
        $r = $emps->db->query("select count(*) from " . TP . "ml_sent where message_id=$mail_id");
        $ra = $emps->db->fetch_row($r);
        if ($ra) {
            return $ra[0];
        }
    }

    function count_sent_ex($mail_id)
    {
        global $emps;

        $mail_id += 0;
        $r = $emps->db->query("select count(*) as ttl,sum(if(status=50,1,0)) as success,sum(if(status=10,1,0)) as errs from " . TP . "ml_sent where message_id=$mail_id group by message_id");
        $ra = $emps->db->fetch_named($r);
        if ($ra) {
            return $ra;
        }
    }

    function count_addresses($list_id)
    {
        global $emps;

        $list_id += 0;
        $r = $emps->db->query("select count(*) from " . TP . "ml_lists_addresses where list_id=$list_id");
        $ra = $emps->db->fetch_row($r);
        if ($ra) {
            return $ra[0];
        }
    }

    function count_websites($list_id)
    {
        global $emps;

        $list_id += 0;
        $r = $emps->db->query("select count(*) from " . TP . "ml_lists_websites where list_id=$list_id");
        $ra = $emps->db->fetch_row($r);
        if ($ra) {
            return $ra[0];
        }
    }

    function count_box_websites($box_id)
    {
        global $emps;

        $box_id += 0;
        $r = $emps->db->query("select count(*) from " . TP . "ml_boxes_websites where box_id=$box_id");
        $ra = $emps->db->fetch_row($r);
        if ($ra) {
            return $ra[0];
        }
    }

    function send_ml_message($mail_id, $limit_divisor)
    {
        global $emps, $mail;

        $mail_id += 0;

        $msg = $emps->db->get_row("ml_messages", "id=$mail_id");
        if (!$msg) {
            return false;
        }

        $list_id = $msg['list_id'] + 0;
        $box_id = $msg['box_id'] + 0;

        if (!$list_id || !$box_id) {
            return false;
        }

        $box = $emps->db->get_row("ml_mailboxes", "id=$box_id");
        if (!$box) {
            return false;
        }

        $limit = ceil($box['minute_limit'] / $limit_divisor);

        $r = $emps->db->query("select count(*) from " . TP . "ml_sent where message_id = $mail_id and status=50");
        $ra = $emps->db->fetch_row($r);
        $sent_count = $ra[0];

        if ($sent_count >= $box['total_limit']) {
            $this->mail_status($mail_id, 100);
            return;
        }

        $fctx = $emps->p->get_context(DT_ML_MAIL, 4000, $mail_id);
        $lst = $this->up->list_files($fctx, 1);
        if (!$lst) {
            return false;
        }
        $fname = $this->up->upload_filename($lst[0]['id'], DT_FILE);

        $body = file_get_contents($fname);

        $x = explode("\r\n\r\n", $body, 2);
        $hdrs = $mail->parse_headers($x[0]);
        $body = $x[1];

        $params = array();
        $params['From'] = $mail->encode_string($box['sender'], 'utf-8') . ' <' . $box['email'] . '>';
        if ($box['reply_to']) {
            $params['Reply-To'] = $box['reply_to'];
        }
        $oparams = $params;

        $smtp_data = array();
        $smtp_data['host'] = $box['smtp_server'];
        $smtp_data['port'] = $box['smtp_port'];
        if ($box['smtp_auth']) {
            if (trim($box['smtp_auth']) == 'true') {
                $smtp_data['auth'] = true;
            } else {
                $smtp_data['auth'] = $box['smtp_auth'];
            }
        }
        $smtp_data['username'] = $box['smtp_login'];
        $smtp_data['password'] = $box['smtp_pwd'];

        while ($limit > 0) {
            $query = "select addrs.*,sent.status as sentstatus,emails.status as estatus,sent.failures as sentfailures,sent.dt as sentdt from " . TP . "ml_lists_addresses as addrs
					left join " . TP . "ml_sent as sent
					on sent.message_id=$mail_id
					and sent.address_id=addrs.address_id
					join " . TP . "ml_addresses as emails
					on emails.id=addrs.address_id
					and emails.status=0
					where addrs.list_id=$list_id and addrs.status=0
					having ((sentstatus is null) or (sentstatus=10)) and (sentfailures<10 or sentfailures is null)
					order by sentdt asc, rand() asc
					limit 1";

//echo $query;

            $r = $emps->db->query($query);


            $ra = $emps->db->fetch_named($r);
            if (!$ra) {
                $this->mail_status($mail_id, 100);
                return;
            }

            $email = $emps->db->get_row("ml_addresses", "id=" . $ra['address_id']);

            if ($email) {
                $sulink = EMPS_SCRIPT_WEB . "/mm-unsubscribe/" . $list_id . "/";
                $ulink = $sulink . "?email=" . urlencode($email['email']);

                $hdrs['List-Unsubscribe'] = "<" . $ulink . ">";

                $params = array_merge($oparams, $hdrs);

                $xr = $mail->mail_smtp($email['email'], $hdrs['Subject'], $body, $smtp_data, $params);
                if ($xr) {
                    echo "Sent: " . $email['email'] . " ($mail_id)</br>";
                    $this->add_sent($mail_id, $box_id, $ra['address_id'], 50);
                } else {
                    echo "Delayed: " . $email['email'] . " ($mail_id)</br>";
                    $this->add_sent($mail_id, $box_id, $ra['address_id'], 10);
                    $this->increase_failures($mail_id, $ra['address_id'], 1);
                }
            } else {
                $this->add_sent($mail_id, $box_id, $ra['address_id'], 100);
            }


            $limit--;
        }
    }

    function increase_failures($mail_id, $address_id, $num)
    {
        global $emps, $SET;
        $emps->db->query("update " . TP . "ml_sent set failures=failures+($num) where address_id=$address_id and message_id=$mail_id");
    }

    function add_sent($mail_id, $box_id, $address_id, $status)
    {
        global $emps, $SET;

        $row = $emps->db->get_row("ml_sent", "address_id=$address_id and box_id=$box_id and message_id=$mail_id");

        if ($row) {
            $dt = time();
            $emps->db->query("update " . TP . "ml_sent set status=$status, dt=$dt where address_id=$address_id and box_id=$box_id and message_id=$mail_id");
        } else {

            $SET = array(
                'message_id' => $mail_id,
                'address_id' => $address_id,
                'box_id' => $box_id,
                'status' => $status,
                'dt' => time()
            );

            $emps->db->sql_insert("ml_sent");
        }
    }

    function resign_from_list($list_id, $email)
    {
        global $emps;

        $email = $this->process_email($email);

        $addr = $this->ensure_address($email);

        if (!$addr) {
            return false;
        }

        $row = $emps->db->get_row("ml_lists_addresses", "list_id=$list_id and address_id=" . $addr['id']);
        if ($row) {
            $md5 = md5(uniqid(time()));
            $emps->db->query("update " . TP . "ml_lists_addresses set md5='$md5' where list_id=$list_id and address_id=" . $addr['id']);
            $row['md5'] = $md5;
            return $row;
        }
    }

    function confirm_resign($md5)
    {
        global $emps;

        $row = $emps->db->get_row("ml_lists_addresses", "md5='$md5'");
        if (!$row) {
            return false;
        }

        $emps->db->query("update " . TP . "ml_lists_addresses set status=20 where md5='$md5'");
        $row['status'] = 20;
        return $row;
    }

    function hard_resign($list_id, $email)
    {
        global $emps;

        $list_id += 0;

        $email = $this->process_email($email);

        $addr = $this->ensure_address($email);

        if (!$addr) {
            return false;
        }

        $row = $emps->db->get_row("ml_lists_addresses", "list_id=$list_id and address_id=" . $addr['id']);
        if ($row) {
            if ($row['status'] != 20) {
                $emps->db->query("update " . TP . "ml_lists_addresses set status=20 where list_id=$list_id and address_id=" . $addr['id']);
            }
        }
    }

    function rotate_mail_logs($count)
    {
        for ($i = $count; $i > 1; $i--) {
            $n = $i - 1;
            $fn = EMPS_SCRIPT_PATH . '/maillog/' . $i . '.txt';
            $nfn = EMPS_SCRIPT_PATH . '/maillog/' . $n . '.txt';
            if (file_exists($nfn)) {
                rename($nfn, $fn);
            }
        }
    }

    function save_log()
    {
        $log = trim(ob_get_flush());
        if (!$log) {
            return;
        }
        $this->rotate_mail_logs(50);
        $fn = EMPS_SCRIPT_PATH . '/maillog/1.txt';
        file_put_contents($fn, $log);
    }

    function list_in_website($list_id, $website_id)
    {
        global $emps;
        $list_id += 0;
        $website_id += 0;
        $row = $emps->db->get_row("ml_lists_websites", "list_id=$list_id and website_id=$website_id");
        if (!$row) {
            return false;
        }
        return true;
    }

    function add_list_to_website($list_id, $website_id)
    {
        global $emps;
        if (!$this->list_in_website($list_id, $website_id)) {
            $list_id += 0;
            $website_id += 0;
            $emps->db->query("insert into " . TP . "ml_lists_websites (list_id,website_id) values ($list_id,$website_id)");
        }
    }

    function remove_list_from_website($list_id, $website_id)
    {
        global $emps;
        $emps->db->query("delete from " . TP . "ml_lists_websites where list_id=$list_id and website_id=$website_id");
    }

    function lists_in_website_by_ctx($website_ctx)
    {
        global $emps;
        $website_ctx += 0;
        $ctx = $emps->db->get_row("e_contexts", "id=$website_ctx");
        if ($ctx) {
            if ($ctx['ref_id'] == 0) {
                return false;
            }
            if ($ctx['ref_type'] != DT_WEBSITE) {
                return false;
            }
            $lst = "";
            $r = $emps->db->query("select * from " . TP . "ml_lists_websites where website_id=" . $ctx['ref_id']);
            while ($ra = $emps->db->fetch_named($r)) {
                if ($lst) {
                    $lst .= ',';
                }
                $lst .= $ra['list_id'];
            }
            return $lst;
        }
    }

    function box_in_website($box_id, $website_id)
    {
        global $emps;
        $box_id += 0;
        $website_id += 0;
        $row = $emps->db->get_row("ml_boxes_websites", "box_id=$box_id and website_id=$website_id");
        if (!$row) {
            return false;
        }
        return true;
    }

    function add_box_to_website($box_id, $website_id)
    {
        global $emps;
        if (!$this->box_in_website($box_id, $website_id)) {
            $box_id += 0;
            $website_id += 0;
            $emps->db->query("insert into " . TP . "ml_boxes_websites (box_id,website_id) values ($box_id,$website_id)");
        }
    }

    function remove_box_from_website($box_id, $website_id)
    {
        global $emps;
        $emps->db->query("delete from " . TP . "ml_boxes_websites where box_id=$box_id and website_id=$website_id");
    }

    function boxes_in_website_by_ctx($website_ctx)
    {
        global $emps;
        $website_ctx += 0;
        $ctx = $emps->db->get_row("e_contexts", "id=$website_ctx");
        if ($ctx) {
            if ($ctx['ref_id'] == 0) {
                return false;
            }
            if ($ctx['ref_type'] != DT_WEBSITE) {
                return false;
            }
            $lst = "";
            $r = $emps->db->query("select * from " . TP . "ml_boxes_websites where website_id=" . $ctx['ref_id']);
            while ($ra = $emps->db->fetch_named($r)) {
                if ($lst) {
                    $lst .= ',';
                }
                $lst .= $ra['box_id'];
            }
            return $lst;
        }
    }

    function box_blocking($box_id)
    {
        global $emps;
        $box_id += 0;
        $box = $emps->db->get_row("ml_mailboxes", "id=$box_id");
        if ($box) {
            if ($box['blocking']) {
                $blocking_limit = $emps->get_setting("blocking_limit");
                if (!$blocking_limit || $blocking_limit == -1) {
                    $blocking_limit = 60;
                }
                $dt = time() - ($blocking_limit * 60);
                if ($box['blocking'] < $dt) {
                    $this->unblock_box($box_id);
                }
                return true;
            }
        }
        return false;
    }

    function block_box($box_id)
    {
        global $emps;
        $dt = time();
        $box_id += 0;
        $emps->db->query("update " . TP . "ml_mailboxes set blocking=$dt where id=$box_id");
    }

    function unblock_box($box_id)
    {
        global $emps;
        $box_id += 0;
        $emps->db->query("update " . TP . "ml_mailboxes set blocking=0 where id=$box_id");
    }

    function ensure_address_status($address_id, $status)
    {
        global $emps;

        $address = $emps->db->get_row("ml_addresses", "id=$address_id");
        if ($address) {
            $status += 0;
            $emps->db->query("update " . TP . "ml_addresses set status=$status where id=$address_id");
        }
    }

    function disable_address($address_id)
    {
        global $emps, $SET;

        $address_id += 0;
        $erow = $emps->db->get_row("ml_errors", "address_id=$address_id");
        if (!$erow) {
            $SET = array();
            $SET['address_id'] = $address_id;
            $emps->db->sql_insert("ml_errors");
        }

        $this->ensure_address_status($address_id, 40);
        return;
    }

    function reenable_address($address_id)
    {
        global $emps;

        $address_id += 0;
        $emps->db->query("delete from " . TP . "ml_errors where address_id=" . $address_id);
        $this->ensure_address_status($address_id, 0);
    }
}

