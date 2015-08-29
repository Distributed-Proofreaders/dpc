 <?php
// Load the main class
require_once 'HTML/QuickForm2.php';

// Instantiate the HTML_QuickForm2 object
$form = new HTML_QuickForm2('tutorial');

// Set defaults for the form elements
$form->addDataSource(new HTML_QuickForm2_DataSource_Array(array(
    'name' => 'Joe User'
)));

// Add some elements to the form
$fieldset = $form->addElement('fieldset')->setLabel('QuickForm2 tutorial example');
$name = $fieldset->addElement('text', 'name', array('size' => 50, 'maxlength' => 255))
                 ->setLabel('Enter your name:');
$fieldset->addElement('submit', null, array('value' => 'Send!'));

// Define filters and validation rules
$name->addFilter('trim');
$name->addRule('required', 'Please enter your name');

// Try to validate a form
if ($form->validate()) {
    echo '<h1>Hello, ' . htmlspecialchars($name->getValue()) . '!</h1>';
    exit;
}

// Output the form
echo $form;
?> 

