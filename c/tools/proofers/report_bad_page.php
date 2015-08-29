<?php

global $projectid;
global $imagefile;
global $submitted;
global $reason;
global $redirect_action;

$page->MarkBad();
divert(url_for_project_level($projectid, 4));

