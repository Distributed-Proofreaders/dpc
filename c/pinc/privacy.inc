<?PHP
include_once($relPath.'prefs_options.inc'); // PRIVACY_*

function can_reveal_details_about( $username, $user_privacy) {
    global $User;

    switch ($user_privacy) {
        case PRIVACY_PUBLIC:
            // Details are visible to all.
            return TRUE;

        case PRIVACY_PRIVATE:
            // Details are visible to anyone logged in.
            return $User->IsLoggedIn();

        case PRIVACY_ANONYMOUS:
            // Details are visible to the user him/herself and to Site Admins.
            return $username == $User->Username() || $User->IsSiteManager();

        default:
            return false;
    }
}

// vim: sw=4 ts=4 expandtab
?>
