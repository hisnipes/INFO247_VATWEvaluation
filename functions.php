<?php

add_action( 'wp_enqueue_scripts', 'enqueue_parent_styles' );

function enqueue_parent_styles() {
    wp_enqueue_style( 'parent-style', get_template_directory_uri().'/style.css' );
}

// Gravity Form Hook to SQL Database for Today's Dishes //
// the ### in gform_pre_render_### locates the id of your form //


add_filter('gform_pre_render_1', 'selectstudent');
add_filter( 'gform_pre_validation', 'selectstudent' );
add_filter( 'gform_admin_pre_render', 'selectstudent' );
add_filter( 'gform_pre_submission_filter', 'selectstudent' );

add_filter('gform_pre_render_2', 'populate_vizimg');

add_filter('gform_field_value_viz1field', 'populate_viz1field');
add_filter('gform_field_value_viz2field', 'populate_viz2field');

add_filter( 'gform_pre_render_2', 'set_conditional_logic' );
add_filter( 'gform_pre_validation_2', 'set_conditional_logic' );
add_filter( 'gform_pre_submission_filter_2', 'set_conditional_logic' );
add_filter( 'gform_admin_pre_render_2', 'set_conditional_logic' );

// Get Student ID
$studentID = htmlspecialchars($_GET["student"]);

// Hook into Google Spreadsheets
$url = 'http://spreadsheets.google.com/feeds/list/1Hmx1W-VLBiLD6oHVcJMYoN_V-npyVyCeRb8r6JXQwT0/od6/public/values?alt=json';
$file = file_get_contents($url);

$json = json_decode($file);
$rows = $json->{'feed'}->{'entry'};

$students = array();
$q1s = array();
$q2s = array();
$h1 = array();
$h2 = array();
$records = array();

foreach($rows as $row) {
	$student = $row->{'gsx$lastname'}->{'$t'};
	$questions1 = $row->{'gsx$questions1'}->{'$t'};
	$questions2 = $row->{'gsx$questions2'}->{'$t'};
	$he1 = $row->{'gsx$he1'}->{'$t'};
	$he2 = $row->{'gsx$he2'}->{'$t'};

	$students[] = $student;
	$q1s[] = $questions1;
	$q2s[] = $questions2;
	$h1[] = $he1;
	$h2[] = $he2;
}

$records = array_merge_recursive(
	array_combine($students, $q1s),
	array_combine($students, $q2s),
	array_combine($students, $h1),
	array_combine($students, $h2)
);


function selectstudent($form, $records){
	global $studentID;
	global $records;

	if($form['id'] != 1) {
		return $form;
	}

	$selectNames = array();
	$selectNames[] = array('text' => '', 'value' => '');
	
	foreach($records as $key => $value){
		$selectNames[] = array('value' => $key, 'text' => $key);
	};

	foreach($form['fields'] as &$field){
		if($field->id == 1) {
			$field->choices = $selectNames;
		};
	};

	return $form;
}


function populate_vizimg($form){
	global $studentID;
	global $records;

	foreach($form['fields'] as &$field){
		if($field->id == 6) {
    		foreach($records as $key => $value) {
     			if($key == $studentID)
    	 			$field->content = '<img src="'.get_stylesheet_directory_uri()."/viz/".$value[0].'.jpg">';
    	 	};
     	};

		if($field->id == 7) {
    		foreach($records as $key => $value) {
     			if($key == $studentID)
    	 			$field->content = '<img src="'.get_stylesheet_directory_uri()."/viz/".$value[1].'.jpg">';
    	 	};
     	};

    };
    return $form;
};


function populate_viz1field(){
	global $studentID;
	global $records;

	$test = null;

	foreach($records as $key => $value){
		if($key == $studentID)
			$test = $value[0];
			continue;
	};

	return $test; // c4

}


function populate_viz2field(){
	global $studentID;
	global $records;

	foreach($records as $key => $value){
		if($key == $studentID)
			return $value[1];
	};
}


function set_conditional_logic( $form ) {

	$current_page = rgpost('gform_source_page_number_' . $_POST['gform_submit']) ? rgpost('gform_target_page_number_' . $_POST['gform_submit']) : 1;
	echo "Current page number is ". $current_page;

	if($current_page == 1)
		$conditional_value = substr(populate_viz1field(),0,1); // get only first character
	else
		$conditional_value = substr(populate_viz2field(),0,1);

    foreach ( $form['fields'] as &$field ) {
    	if($conditional_value == 'c')
	        if ( $field->cssClass != 'datasetc' && $field->type != 'html' ) {
	            $field->cssClass = "gf_invisible";
	        }
    }
    return $form;
}