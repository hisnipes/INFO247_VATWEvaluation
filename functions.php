<?php
// load in child and parent theme styles
function theme_enqueue_styles() {

    $parent_style = 'parent-style';

    wp_enqueue_style( $parent_style, get_template_directory_uri() . '/style.css' );
    wp_enqueue_style( 'child-style',
        get_stylesheet_directory_uri() . '/style.css',
        array( $parent_style )
    );
}
add_action( 'wp_enqueue_scripts', 'theme_enqueue_styles' );


// custom functions created for form ids 1 and 2
// form id 1 = identification (student picks their last name)
// form id 2 = survey questions
// gform_pre_render_### | ### is the form id (leave out to apply to all forms)
// gform_validation_### | ### is the form id (leave out to apply to all forms)
// gform_field_value_xxx | xxx is the field parameter name
add_filter('gform_pre_render_1', 'selectstudent');
add_filter('gform_pre_validation', 'selectstudent');
add_filter('gform_admin_pre_render', 'selectstudent');
add_filter('gform_pre_submission_filter', 'selectstudent');

add_filter('gform_validation_1', 'custom_validation');

add_filter('gform_pre_render_2', 'populate_vizimg');

add_filter('gform_field_value_viz1field', 'populate_viz1field');
add_filter('gform_field_value_viz2field', 'populate_viz2field');

add_filter( 'gform_pre_render_2', 'set_custom_conditionals');


// pull the student parameter value passed in the URL as a query string
$studentID = htmlspecialchars($_GET["student"]);

// hook into Google Spreadsheets to pull data
$url = 'http://spreadsheets.google.com/feeds/list/1Hmx1W-VLBiLD6oHVcJMYoN_V-npyVyCeRb8r6JXQwT0/od6/public/values?alt=json';
$file = file_get_contents($url);

$json = json_decode($file);
$rows = $json->{'feed'}->{'entry'};

// initialize arrays
$digits = array();
$students = array();
$firsts = array();
$q1s = array();
$q2s = array();
$h1 = array();
$h2 = array();
$records = array();

// for each row of data in the spreadsheet, separate them out by column name
foreach($rows as $row) {
	$digit = $row->{'gsx$digits'}->{'$t'};
	$student = $row->{'gsx$lastname'}->{'$t'};
	$first = $row->{'gsx$firstname'}->{'$t'};
	$questions1 = $row->{'gsx$questions1'}->{'$t'};
	$questions2 = $row->{'gsx$questions2'}->{'$t'};
	$he1 = $row->{'gsx$he1'}->{'$t'};
	$he2 = $row->{'gsx$he2'}->{'$t'};

	// save each piece of data inside each array
	$digits[] = $digit;
	$students[] = $student;
	$firsts[] = $first;
	$q1s[] = $questions1;
	$q2s[] = $questions2;
	$h1[] = $he1;
	$h2[] = $he2;
};

// create one big array with the Key being the student's last name and
// and the Value as an array of values
$records = array_merge_recursive(
	array_combine($students, $digits), // $value[0]
	array_combine($students, $firsts), // $value[1]
	array_combine($students, $q1s),	   // $value[2]
	array_combine($students, $q2s),    // $value[3]
	array_combine($students, $h1),     // $value[4]
	array_combine($students, $h2)      // $value[5]
);

// function to pre-populate dropdown menu in form id 1
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
			$field->choices = $selectNames; // need to use "choices" for dropdown
		};
	};

	return $form;
}

// validate against last four digits

function custom_validation($validation_result) {
	global $records;

	$form = $validation_result['form'];

	foreach($form['fields'] as &$field){
		if(strpos($field->cssClass,'studentid') === false)
			continue;
		$visitor_name = rgpost("input_{$field['id']}");
	}

	foreach($form['fields'] as &$field){
		if(strpos($field->cssClass,'sid-digits') === false)
			continue;

		$field_value = rgpost("input_{$field['id']}");

		foreach($records as $key => $value){
			if($key == $visitor_name)
				$visitor_digits = $value[0];
		}

		if ( $field_value === $visitor_digits )
			$is_valid = true;
		else
			$is_valid = false;

		if ( !$is_valid ) {
			$validation_result['is_valid'] = false;

			$field->failed_validation = true;
			$field->validation_message = "Sorry, those aren't the right digits. If this is incorrect, please contact your TA.";
		}
	}

	$validation_result['form'] = $form;

	return $validation_result;
}

// function to pre-populate viz imgs in the custom html fields
// this code is kind of spaghetti-ish, def. could be improved later
function populate_vizimg($form){
	global $studentID;
	global $records;

	foreach($form['fields'] as &$field){

		// viz 1 - quiz questions
		if($field->id == 6) {
    		foreach($records as $key => $value) {
     			if($key == $studentID)
    	 			$field->content = '<label class="gfield_label">Visualization #1</label><br/><img src="'.get_stylesheet_directory_uri()."/viz/".$value[2].'.jpg"><br/><br/>';
    	 	};
     	};

     	// viz 2 - heuristic evaluation
     	if($field->id == 33) {
    		foreach($records as $key => $value) {
     			if($key == $studentID)
    	 			$field->content = '<label class="gfield_label">Visualization #2</label><br/><img src="'.get_stylesheet_directory_uri()."/viz/".$value[4].'.jpg"><br/><br/>';
    	 	};
     	};

     	// viz 3 - quiz questions
		if($field->id == 7) {
    		foreach($records as $key => $value) {
     			if($key == $studentID)
    	 			$field->content = '<label class="gfield_label">Visualization #3</label><br/><img src="'.get_stylesheet_directory_uri()."/viz/".$value[3].'.jpg"><br/><br/>';
    	 	};
     	};

     	// viz 4 - heuristic evaluation
     	if($field->id == 34) {
    		foreach($records as $key => $value) {
     			if($key == $studentID)
    	 			$field->content = '<label class="gfield_label">Visualization #4</label><br/><img src="'.get_stylesheet_directory_uri()."/viz/".$value[5].'.jpg"><br/><br/>';
    	 	};
     	};

    };

    return $form;
};

// function to populate a text field for viz #1
// this is to later help with the custom conditionals
// we could probably merge this and take out this function completely though
function populate_viz1field(){
	global $studentID;
	global $records;

	foreach($records as $key => $value){
		if($key == $studentID)
			return $value[2];
	};
}

// do the same with this function for viz #2 and the conditionals
function populate_viz2field(){
	global $studentID;
	global $records;

	foreach($records as $key => $value){
		if($key == $studentID)
			return $value[3];
	};
}

// function to show/hide sets of questions depending on the viz image shown
function set_custom_conditionals($form) {

	global $studentID;
	global $records;

	// improve the UX a little by adding a "welcome" statement at the top
	// so the student knows this form is specifically for him/her
	$welcomename = null;
	$debugStudent = array();
	$debugStudent = array_slice($records[$studentID],1);

	foreach($records as $key => $value){
		if($key == $studentID)
			$welcomename = $value[1]." ".$key;
	};

	echo "<h3><strong>Survey for:</strong> ".$welcomename."</h3>";

	// get the current page number
	$current_page = rgpost('gform_source_page_number_' . $_POST['gform_submit']) ? rgpost('gform_target_page_number_' . $_POST['gform_submit']) : 1;

	if($current_page == 1)
		$conditional_value = substr(populate_viz1field(),0,1); // get only first character
	elseif($current_page == 3)
		$conditional_value = substr(populate_viz2field(),0,1);
	else
		$conditional_value = "N/A, no conditionals used here";

	// this is for debugging and demoing; comment out for production
	echo "For Debugging<br/>";
	echo "Current page number: ".$current_page."<br/>";
	echo "Conditional value: ".$conditional_value."<br/>";
	echo "Viz values: ".implode(", ",$debugStudent);

	// conditional statements to show/hide question sets depending on viz image
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