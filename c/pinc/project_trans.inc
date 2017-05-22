<?php
    // $Id: project_trans.inc,v 1.98 2006/08/31 02:53:24 jmdyck Exp $

include_once($relPath.'dpinit.php');
include_once($relPath.'gettext_setup.inc');
include_once($relPath.'Project.inc');
include_once($relPath.'ProjectTransition.inc');

// Move the given project to the given state,
// and perform any attendant processing.
// If there are any problems, return a string containing an error message.
// Otherwise, return an empty string.
//
// This function produces no output except for debugging messages.
function project_transition( $projectid, $new_state, $who, $extras = array() ) {
	$project = new Project($projectid);

	$current_state = $project->state;
    if($new_state == $current_state) {
        return "";
    }
	$transition_str = "$projectid: "._("transition from")." $current_state "
        ._("to")." $new_state";

	// -------------------------------------------------------------------------


    /** @var ProjectTransition $transition */
	$transition = get_transition( $current_state, $new_state );
	if ( is_null($transition) ) {
		$error = "$transition_str "._("is illegal");
		return $error;
	}

	// -------------------------------------------------------------------------

	$error = $transition->do_state_change( $project, $who, $extras );
	if ( $error != '' ) {
		$error = "$transition_str: $error";
	}
	return $error;
}
