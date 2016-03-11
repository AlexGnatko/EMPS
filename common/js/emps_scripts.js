// JavaScript Document

function call_emps_scripts(){
	var emps_script;
	while(emps_scripts.length > 0){
		emps_script = emps_scripts.shift();
		emps_script.call(this);
	}
	setTimeout(call_emps_scripts, 500);
}

call_emps_scripts();
