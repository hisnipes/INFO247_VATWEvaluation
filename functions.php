<?php
function theme_enqueue_styles() {

    $parent_style = 'parent-style';

    wp_enqueue_style( $parent_style, get_template_directory_uri() . '/style.css' );
    wp_enqueue_style( 'child-style',
        get_stylesheet_directory_uri() . '/style.css',
        array( $parent_style )
    );
}
add_action( 'wp_enqueue_scripts', 'theme_enqueue_styles' );


// Gravity Form Hook to SQL Database for Today's Dishes //
// the ### in gform_pre_render_### locates the id of your form //


add_filter('gform_pre_render_1', 'selectstudent');
add_filter( 'gform_pre_validation', 'selectstudent' );
add_filter( 'gform_admin_pre_render', 'selectstudent' );
add_filter( 'gform_pre_submission_filter', 'selectstudent' );

add_filter('gform_pre_render_2', 'populate_vizimg');

add_filter('gform_field_value_viz1field', 'populate_viz1field');
add_filter('gform_field_value_viz2field', 'populate_viz2field');

add_filter( 'gform_pre_render_2', 'set_custom_conditionals');


// Get Student ID
$studentID = htmlspecialchars($_GET["student"]);

// Hook into Google Spreadsheets
$url = 'http://spreadsheets.google.com/feeds/list/1Hmx1W-VLBiLD6oHVcJMYoN_V-npyVyCeRb8r6JXQwT0/od6/public/values?alt=json';
$file = file_get_contents($url);

$json = json_decode($file);
$rows = $json->{'feed'}->{'entry'};

$students = array();
$firsts = array();
$q1s = array();
$q2s = array();
$h1 = array();
$h2 = array();
$records = array();

foreach($rows as $row) {
	$student = $row->{'gsx$lastname'}->{'$t'};
	$first = $row->{'gsx$firstname'}->{'$t'};
	$questions1 = $row->{'gsx$questions1'}->{'$t'};
	$questions2 = $row->{'gsx$questions2'}->{'$t'};
	$he1 = $row->{'gsx$he1'}->{'$t'};
	$he2 = $row->{'gsx$he2'}->{'$t'};

	$students[] = $student;
	$firsts[] = $first;
	$q1s[] = $questions1;
	$q2s[] = $questions2;
	$h1[] = $he1;
	$h2[] = $he2;
};

$records = array_merge_recursive(
	array_combine($students, $firsts),
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

		// viz 1 with questions
		if($field->id == 6) {
    		foreach($records as $key => $value) {
     			if($key == $studentID)
    	 			$field->content = '<label class="gfield_label">Visualization #1</label><br/><img src="'.get_stylesheet_directory_uri()."/viz/".$value[1].'.jpg"><br/><br/>';
    	 	};
     	};

     	// viz 2 with he
     	if($field->id == 33) {
    		foreach($records as $key => $value) {
     			if($key == $studentID)
    	 			$field->content = '<label class="gfield_label">Visualization #2</label><br/><img src="'.get_stylesheet_directory_uri()."/viz/".$value[3].'.jpg"><br/><br/>';
    	 	};
     	};

     	// viz 3 with questions
		if($field->id == 7) {
    		foreach($records as $key => $value) {
     			if($key == $studentID)
    	 			$field->content = '<label class="gfield_label">Visualization #3</label><br/><img src="'.get_stylesheet_directory_uri()."/viz/".$value[2].'.jpg"><br/><br/>';
    	 	};
     	};

     	// viz 4 with he
     	if($field->id == 34) {
    		foreach($records as $key => $value) {
     			if($key == $studentID)
    	 			$field->content = '<label class="gfield_label">Visualization #4</label><br/><img src="'.get_stylesheet_directory_uri()."/viz/".$value[4].'.jpg"><br/><br/>';
    	 	};
     	};

    };

    return $form;
};


function populate_viz1field(){
	global $studentID;
	global $records;

	foreach($records as $key => $value){
		if($key == $studentID)
			return $value[1];
	};
}


function populate_viz2field(){
	global $studentID;
	global $records;

	foreach($records as $key => $value){
		if($key == $studentID)
			return $value[2];
	};
}


function set_custom_conditionals($form) {

	global $studentID;
	global $records;

	$welcomename = null;
	$debugStudent = array();
	$debugStudent = array_slice($records[$studentID],1);

	foreach($records as $key => $value){
		if($key == $studentID)
			$welcomename = $value[0]." ".$key;
	};

	echo "<h3><strong>Survey for:</strong> ".$welcomename."</h3>";

	$current_page = rgpost('gform_source_page_number_' . $_POST['gform_submit']) ? rgpost('gform_target_page_number_' . $_POST['gform_submit']) : 1;

	if($current_page == 1)
		$conditional_value = substr(populate_viz1field(),0,1); // get only first character
	elseif($current_page == 3)
		$conditional_value = substr(populate_viz2field(),0,1);
	else
		$conditional_value = "N/A, no conditionals used here";

	echo "For Debugging<br/>";
	echo "Current page number: ".$current_page."<br/>";
	echo "Conditional value: ".$conditional_value."<br/>";
	echo "Viz values: ".implode(", ",$debugStudent);

    foreach ( $form['fields'] as &$field ) {
    	if ( $conditional_value == 'a' )
	        if ( $field->cssClass == 'datasetb' || $field->cssClass == 'datasetc' ) {
	            $field->cssClass = "hidden";
	        }
	    if ( $conditional_value == 'b' )
	    	if ( $field->cssClass == 'dataseta' || $field->cssClass == 'datasetc' ) {
	    		$field->cssClass = "hidden";
	    	}
	    if ( $conditional_value == 'c' )
	    	if ( $field->cssClass == 'dataseta' || $field->cssClass == 'datasetb' ) {
	    		$field->cssClass = "hidden";
	    	}
    }
    return $form;
}