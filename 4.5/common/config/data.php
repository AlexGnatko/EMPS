<?php

function emps_define_constant($name, $value)
{
    if (!defined($name)) {
        define($name, $value);
    }
}

// Define Data Types
emps_define_constant('DT_WEBSITE', 1);
emps_define_constant('DT_USER', 5);
emps_define_constant('DT_CONTENT', 10);
emps_define_constant('DT_FOLDER', 12);
emps_define_constant('DT_ALBUM', 14);
emps_define_constant('DT_MENU', 20);
emps_define_constant('DT_FILE', 100);
emps_define_constant('DT_IMAGE', 110);
emps_define_constant('DT_IMAGEWM', 111);
emps_define_constant('DT_STORAGE', 120);
emps_define_constant('DT_VIDEO', 130);
emps_define_constant('DT_SHADOW', 210);

// Define Property Lists
emps_define_constant('P_WEBSITE', "");
emps_define_constant('P_MENU', "name:t,regex:t,grant:t");
emps_define_constant('P_CONTENT', "title:t,descr:t,html:t,keywords:t");
emps_define_constant('EXTRA_P_USER', '');
emps_define_constant('P_USER', "phone:c,fax:c,lastname:c,firstname:c,newusername:c,sentnew:c,setpwd:c,position:c,company:c,email:c,street:t,http:c,twitter:c,facebook:c,vk:c,profile_image:c,display_name:c,about:t,gender:i,bdt:i" . EXTRA_P_USER);
emps_define_constant('P_CLIENT', 'http:c');
emps_define_constant('P_PHOTOSET', "html:t");
emps_define_constant('P_VIDEO', "flash:t,3gp:t,dflash:i,d3gp:i,vslink:t,width:i,height:i,embed_url:c");
emps_define_constant('P_SHADOW', "title:t,descr:t,keywords:t");

