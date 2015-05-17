// JavaScript Document

function call_emps_scripts(){
	var emps_script;
	while(emps_scripts.length > 0){
		emps_script = emps_scripts.shift();
		emps_script.call(this);
	}
}

call_emps_scripts();
