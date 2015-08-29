<?PHP

error_reporting(E_ALL);
// $Id: DpForm.class.php,v 1.1 2009/07/28 17:15:54 dkretz Exp $

/**
 * Sort stuff doesn't do anything. It needs to either affect the row generation
 * or js with onload.
 */

/**
         js sorting
         note that <thead loads js/sort.js.
 */

/**
 * DpForm Class.
 *
 *
 *  <p>Initialize with a result-set-type array.</p>
 * </p>Use the AddColumn method to accumulate column information.
 *  <p>Sorting. We can either sort in the query, or sort the array.
    Sorting in the query might be quicker, but it means tweaking the
    SQL, which increases coupling, complicates the query (bad), and
    is less general and reusable (bad). So we'll sort in the array.
 */

/**
 * Magic CSS classes
 *   left, right, pager
 *   sortkey='fieldname'
 *   if table has class 'no_tr_for_th' then suppress <tr> for <th>
 *   <tr> for column headings has class 'colhead'
 *    (i.e. table.dptable.no_tr_for_th tr.colhead { display: none; }
 *
 */

class DpForm
{
    private $_id        = "";
    private $_class     = "";
    private $_rows      = array();
//    private $_fields    = array();
//    private $_captions  = array();
//    private $_values    = array();
//    private $_controls  = array();

    public function __construct($id = "", $class="dpform") {
        $this->_id = $id;
        $this->_class = $class;
    }

    public function AddRow($field, $caption, $value, $control) {
        $this->_rows[] = new FormRow($caption, $field, $value, $control);
    }

    public function EchoForm() {
        echo "<table id='$this->_id' class='dpform'>\n";
        /** @var FormRow $row */
        foreach($this->_rows as $row) {
            $row->EchoRow();
        }
        $this->EchoSubmit();
        echo "</table>\n";
    }

    public function Controls() {
        $ary = array();
        foreach($this->_rows as $row) {
            /** @var FormRow $row */
            $ary[] = $row->Control();
        }
        return $ary;
    }

    public function EchoSubmit() {
        echo "<tr><td></td><td class='right'>
            <input type='submit' name='submit_image_source' value='Submit' /></td></tr>\n";
    }
}

class FormRow
{
    private $_control;
    private $_caption;
    private $_field;
    private $_value;

    public function __construct($caption, $field, $value, $control) {
        $this->_caption = $caption;
        $this->_field = $field;
        $this->_value = $value;
        $this->_control = $control;
    }

    public function EchoRow() {
        $this->EchoCaption();
        $this->EchoControl();
    }

    public function Control() {
        return $this->_control;
    }

    private function EchoCaption() {
        echo "<tr><td class='left'>{$this->_caption}</td>";
    }

    private function EchoControl() {
        switch($this->_control) {
            case "display":
                // echo "<td class='right'><input type='text' name='{$this->_fields[$i]}' value='{$this->_values[$i]}' /></td></tr>\n";
                echo "<td class='right'>{$this->_value}</td></tr>\n";
                break;
            case "text":
                echo "<td class='right'><input type='text' name='{$this->_field}' value='{$this->_value}' /></td></tr>\n";
                break;
            case "textarea":
                echo "<td class='right'><textarea name='{$this->_field}'>{$this->_value}</textarea></td></tr>\n";
                break;
            case "checkbox":
                echo "<td class='right'><input type='checkbox' name={$this->_field}";
                if($this->_value) {
                    echo " checked='CHECKED'";
                }
                echo " /></td></tr>\n";
                break;
            default:
                assert(false);
                break;
        }
    }
}
