<?php

class EMPS_Uploads
{
    public $bucket;

    public function __construct()
    {
        global $emps;

        $this->bucket = $emps->db->gridfs_bucket();

        $emps->p->register_cleanup(array($this, 'delete_files_context'));
    }

    public function current_folder()
    {
        $dt = time();
        $folder = "up" . floor($dt / (60 * 60 * 24 * 7));

        return $folder;
    }

    public function update_file($file_id, $data){
        global $emps;

        $file_id = $emps->db->oid($file_id);

        $params = array();
        $params['query'] = array("_id" => $file_id);
        $params['update'] = array('$set' => $data);
        $this->last_update = $emps->db->update_one("empsfs.files", $params);
        return $this->last_update;
    }

    public function new_file($filename, $data){

        $file = fopen($filename, 'rb');
        $file_id = $this->bucket->uploadFromStream($data['filename'], $file);

        $this->update_file($file_id, $data);

        return $file_id;
    }

    public function file_info($file_id){
        $lst = $this->list_files_ex(['_id' => $file_id, 'ut' => 'f'], ['limit' => 1, 'sort' => ['_id' => -1]]);
        if(count($lst) > 0){
            return $lst[0];
        }
        return false;
    }

    public function list_files($context_id, $ut, $limit)
    {
        return $this->list_files_ex(['context_id' => $context_id, 'ut' => $ut], ['limit' => $limit, 'sort' => ['ord' => 1, '_id' => 1]]);
    }

    public function list_files_ex($query, $opts){
        global $emps;

        $cursor = $this->bucket->find($query, $opts);

        $lst = array();

        foreach($cursor as $ra){
            $ra = $emps->db->safe_array($ra);
            $lst[] = $ra;
        }

        return $lst;
    }

    public function delete_file($file_id)
    {
        global $emps;

        $file_id = $emps->db->oid($file_id);
        $this->bucket->delete($file_id);
    }

    public function delete_files_context($context_id)
    {
        global $emps;


        $lst = $this->list_files($context_id, 'f', 0);
        foreach($lst as $file){
            $this->delete_file($emps->db->oid($file['_id']));
        }
    }

    public function file_extension($ra)
    {

        $x = explode(".", $ra['orig_filename']);
        $ra['ext'] = $x[count($x) - 1];

        return $ra;
    }

    public function download_file($context_id, $url, $filename)
    {
        global $emps, $SET;

        $ord = 10;
        $lst = $this->list_files_ex(['context_id' => $context_id, 'ut' => 'f'], ['limit' => 1, 'sort' => ['ord' => -1]]);

        foreach($lst as $f){
            $ord = $f['ord'] + 10;
        }

        if (!$filename) {
            $a = parse_url($url);
            $path = $a['path'];
            $x = explode("/", $path);
            $filename = urldecode($x[count($x) - 1]);
        }


        $headers = get_headers($url, 1);

        $type = $headers['Content-Type'];
        if (!$type) {
            $type = "application/octet-stream";
        }

        $data = [];
        $data['ut'] = 'f';
        $data['uniq_md5'] = md5(uniqid(time().$filename));
        $data['filename'] = $data['uniq_md5']."-".$data['ut'];
        $data['orig_filename'] = $filename;
        $data = $this->file_extension($data);
        $data['context_id'] = $emps->db->oid($context_id);
        $data['content_type'] = $type;
        $data['user_id'] = $emps->auth->USER_ID;
        $data['ord'] = $ord;

        $file_id = $this->new_file($url, $data);
        return $file_id;
    }


}
