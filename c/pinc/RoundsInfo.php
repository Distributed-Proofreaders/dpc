<?php

// require_once "rounds.php";
    
$DpRounds = array();

$DpRounds["PREP"] = array( "name" => _('Preparation'),
        "description" => _("Check the text and images for completeness and quality.") );

$DpRounds["P1"] = array( "name" => _('Proofreading Round 1'),
        "description" => _("Examine the text and make it match the image (without formatting).") );

$DpRounds["P2"] = array( "name" => _('Proofreading Round 2'),
        "description" => _("The text has been proofread once. Ensure the text matches the image.") );

$DpRounds["P3"] = array( "name" => _('Proofreading Round 3'),
        "description" => _("Match the text to the image.") );

$DpRounds["F1"] = array( "name" => _('Formatting Round 1'),
        "description" => _("Add formatting markup.") );

$DpRounds["F2"] = array( "name" => _('Formatting Round 2'),
        "description" => _("Check formatting markup.") );

$DpRounds["PP"] = array( "name" => _('Post Processing'),
        "description" => _("Post Processing.") );

$DpRounds["PPV"] = array( "name" => _('PP Verifying'),
     "description" => _("Confirm the project meets DPC and FadedPage standards."));

$DpRounds["POSTED"] = array( "name" => _('Posted'),
        "description" => _("The project has been published.") );

//$DpRounds["proj_post_first_checked_out"] = array( "name" => _('Post Processing'),
//	"description" => _("The text is now converted to publication-quality texts.") );

//$DpRounds["proj_post_first_available"] = array( "name" => _('Post Processing'),
	//	"description" => _("The text is now converted to publication-quality texts.") );

function RoundIdName($roundid) {
    global $DpRounds;
    return $roundid == ""
            ? ""
            : $DpRounds[$roundid]['name'];
}

function RoundIdDescription($roundid) {
    global $DpRounds;
    return $roundid == ""
        ? ""
        : $DpRounds[$roundid]['description'];
}

