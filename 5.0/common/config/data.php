<?php

function emps_define_constant($name, $value){
	if(!defined($name)){
		define($name, $value);
	}
}

// Define Data Types
emps_define_constant('DT_WEBSITE',	'emps_websites');
emps_define_constant('DT_USER',		'emps_users');
emps_define_constant('DT_CONTENT',	'emps_content');
emps_define_constant('DT_MENU',		'emps_menu');
emps_define_constant('DT_FILE',		'emps_file');
emps_define_constant('DT_IMAGE',		'emps_image');
emps_define_constant('DT_VIDEO',	'emps_video');
emps_define_constant('DT_SHADOW',	'emps_shadow');

