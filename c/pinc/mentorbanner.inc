<?PHP
function mentor_banner() {
    global $dpdb;

    $oldest = $dpdb->SqlOneValue("
        SELECT
            MAX(DATEDIFF(CURRENT_DATE(), FROM_UNIXTIME(modifieddate))) days_ago
        FROM projects
        WHERE difficulty = 'beginner'
            AND LANGUAGE = 'English'
            AND state = 'P2.proj_avail'");

    switch ($oldest) {
        case 0:
            return;
        case 1:
        case 2:
            $font_boost = "1.1em";
            $font_col = '#339933';
            break;
        case 3:
        case 4:
            $font_boost = "1.2em";
            $font_col = "#FF6600";
        break;
        default:
            $font_boost = "1.3em";
            $font_col = "#FF0000";
            break;
    }

    echo "<br><div class='center bold' style='color: $font_col; font_size: $font_boost'>"
    . _("Oldest English MENTORS ONLY book in P2 is $oldest days old.")
    . "</div>
    <br/>\n";
}
