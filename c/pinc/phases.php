<?php

    
$DpPhases = array();

$DpPhases["PREP"] = array( "name" => _('Preparation'),
        "description" => _("Images are converted to OCR text,
        and both the text and images are checked for completeness and quality.") );

$DpPhases["P1"] = array( "name" => _('Proofreading Round 1'),
        "description" => _("The text is the output from OCR software.
        Compare it carefully with the image.") );

$DpPhases["P2"] = array( "name" => _('Proofreading Round 2'),
        "description" => _("The texts have been proofread once.
        Now carefully compare them with the image again.") );

$DpPhases["P3"] = array( "name" => _('Proofreading Round 3'),
        "description" => _("The texts have been proofread twice.
		Examine them <b>closely</b> for small errors that may have been missed,
		paying careful attention to the punctuation.") );

$DpPhases["F1"] = array( "name" => _('Formatting Round 1'),
        "description" => _("The texts have been proofread.
        Now they need to be formatted with markup
        (which may in some cases be specific to the project.)") );

$DpPhases["F2"] = array( "name" => _('Formatting Round 2'),
        "description" => _("The rexts need to be carefully checked to complete any remaining formatting.") );

$DpPhases["PP"] = array( "name" => _('Post Processing'),
        "description" => _("The texts have completed the rounds,
         and now are converted to publication-quality texts.") );

$DpPhases["PPV"] = array( "name" => _('PP Verifying'),
        "description" => _("A final check to confirm that the project meets DPC and FadedPage standards.") );

function PhaseCaption($phase) {
    return _("Pages Completed in $phase");
}

function NameForPhase($phase) {
    global $DpPhases;
    return $phase != "" && is_array($DpPhases[$phase])
            ? $DpPhases[$phase]['name']
            : "";
}

function DescriptionForPhase($phase) {
    global $DpPhases;
    return $phase != "" && is_array($DpPhases[$phase])
        ? $DpPhases[$phase]['description']
        : "";
}

