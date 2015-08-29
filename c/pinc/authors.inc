<?PHP
function get_biography($id) {
    global $dpdb;
    $sql = "SELECT bio FROM biographies
            WHERE bio_id = $id";
    $bio = $dpdb->SqlOneValue($sql);
    if(! $bio) {
        return _("<p>An error has occurred somewhere. The project manager of
        this project has requested that a biography be automatically inserted
        here, but no biography is known by the id provided:</p> <p>You may wish
        to contact the project manager so that the problem can be
        resolved.</p>"); 
    }
    else {
        return "
            <!-- 
            begin bio retrieved from database: id=$id -->
            $bio
            <!-- end bio retrieved from database: id=$id -->\n";
    }
}
