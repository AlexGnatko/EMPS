<?php

$this->handle_view_row();

require_once $emps->common_module('uniqtxt/unitqtxt.class.php');

$utxt = new EMPS_UniqueTexts;
$utxt->handle_request($this->context_id, $this->table_name, $this->row);
