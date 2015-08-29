<?PHP

// A "themed table" is an HTML table that uses various values
// from the current $theme for some of its presentational aspects.

// ThemedTable is a class for handling the formulaic parts of a themed table.

class ThemedTable
{
    private $column_widths;
    private $column_alignments;
    private $class = array('center');
    private $style = array('border' => '1px solid #111111');

    function ThemedTable( $n_cols, $title, $options=array() ) {
        $this->n_cols = $n_cols;

        $this->rollover_highlight = FALSE;
        $width = null;
        $subtitle = null;
        foreach ( $options as $option_name => $option_value ) {
            switch ( $option_name ) {
                case 'rollover_highlight':
                    $this->rollover_highlight = $option_value;
                    break;
                case 'border':
                    $this->style['border'] =  $option_value;
                    break;
                case 'width':
                    $this->style["width"] = $option_value;
                    break;
                case 'subtitle':
                    $subtitle = $option_value;
                    break;
                case 'class':
                    $this->class = $option_value;
                    break;
                case 'style':
                    $this->style[$option_name] = $option_value;
                    break;
                default:
                    die( "ThemedTable created with invalid option: '$option_name'" );
            }
        }

        echo "\n";
        $sstyle = implode("; ", $this->style);
        $sclass = count($this->class) == 0 ? "" : "class='" . implode(' ', $this->class) . "'";
        echo "<table style='$sstyle' $sclass>";

        $possible_subtitle =
            is_null($subtitle)
            ? ''
            : "<br><font size='1'>$subtitle</font>";

            // $bgcolor    = $theme['color_headerbar_bg'];
            // $font_face  = $theme['font_headerbar'];
            // $font_color = $theme['color_headerbar_font'];

        echo "
            <tr>
            <td colspan='$this->n_cols' class='headerbar center bold'>
                $title
                $possible_subtitle
            </td>
            </tr>\n";
    }

    function set_column_widths()
    // Set explicit column widths.
    // (If you don't call this method, <td> tags
    // will be output without a 'width' attribute.)
    {
        // There should be an arg (width) for each column.
        assert( func_num_args() == $this->n_cols );
        $this->column_widths = func_get_args();

        // They should add up to 100.
        assert( array_sum($this->column_widths) == 100 );
    }

    function set_column_alignments()
    {
        assert( func_num_args() == $this->n_cols );
        $this->column_alignments = func_get_args();

        // Should check that they're sensible.
    }

    function column_headers()
    {
        $args = func_get_args();
        $this->_row( $args, TRUE );
    }

    function row()
    {
        $args = func_get_args();
        $this->_row( $args, FALSE );
    }

    function _row( $cell_texts, $is_header )
    {
        if ( $this->n_cols > 1 && count($cell_texts) == 1 && is_array($cell_texts[0]) )
        {
            $cell_texts = $cell_texts[0];
        }

        // There should be an arg (cell content) for each column.
        assert( count($cell_texts) == $this->n_cols );

//        $bgcolor         = $theme['color_navbar_bg'];
//        $highlight_color = $theme['color_mainbody_bg'];
//        $font_face       = $theme['font_navbar'];
//        $font_color      = $theme['color_navbar_font'];

        // (We don't put the rollover highlight on a header row,
        // though there's no particular reason we couldn't.)
        echo "<tr class='navbar'>";

        for ( $c = 0; $c < $this->n_cols; $c++ ) {
            $class = array();
            if ( isset($this->column_alignments) ) {
                switch($this->column_alignments[$c]) {
                    case 'left':
                        $class[] = 'lfloat';
                        break;
                    case 'right':
                        $class[] = 'rfloat';
                        break;
                    case 'center':
                        $class[] = 'center';
                        break;
                }
            }

            $cell_content = $cell_texts[$c];
            $sclass = count($class) == 0 ? "" : "class='" . implode("; ", $class) . "'";

            if ($is_header) {
                echo "<th $sclass > $cell_content</th>";
            }
            else {
                echo "<td $sclass >$cell_content</td>";
            }
        }

        echo "</tr>\n";
    }

    function end() {
        echo "</table>\n";
    }
}

// vim: sw=4 ts=4 expandtab
