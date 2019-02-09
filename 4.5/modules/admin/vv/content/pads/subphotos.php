<?php

global $context_id, $emps;

$context_id = $emps->p->get_context($this->ref_type, 100, $this->ref_id);
$this->context_id = $context_id;

require_once $emps->page_file_name("_comp/photos", "controller");
