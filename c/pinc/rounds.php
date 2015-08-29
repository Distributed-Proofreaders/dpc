<?php
    function PhasesInOrder() {
        return array(0 => "PREP", 1 => "P1", 2 => "P2", 3 => "P3", 4 => "F1", 5 => "F2",
                6 => "PP", 7 => "PPV", 8 => "POSTED");
    }

    function PhaseForRoundId($roundid) {
        switch($roundid) {
            case "OCR":
                return "PREP";
            case "P1":
            case "P2":
            case "P3":
            case "F1":
            case "F2":
                return $roundid;
            default:
                return null;
        }
    }

    function PhaseForIndex($index) {
        $p = PhasesInOrder();
        return $p[$index];
    }

    function IndexForPhase($phase) {
        $p = PhasesInOrder();
        return array_search($phase, $p);
    }

    function RoundIdsInOrder() {
        return array("P1", "P2", "P3", "F1", "F2");
        // global $dpdb;
        // static $_ids;
        // if(!isset($_ids)) {
            // $sql = "
                // SELECT roundid FROM rounds
                // ORDER BY round_index";
            // $_ids = $dpdb->SqlValues($sql);
        // }
        // return $_ids;
    }

    function RoundCount() {
        return 5;
    }

    function RoundIndexForId($roundid) {
        switch(strtoupper($roundid)) {
            case "OCR":
                return 0;
            case "P1":
                return 1;
            case "P2":
                return 2;
            case "P3":
                return 3;
            case "F1":
                return 4;
            case "F2":
                return 5;
            default:
                return null;
        }
    }

    function RoundIdBefore($roundid) {
        switch($roundid) {
            case "P1":
                return "PREP";
            case "P2":
                return "P1";
            case "P3":
                return "P2";
            case "F1":
                return "P3";
            case "F2":
                return "F1";
            default:
                return null;
        }
        // $index = RoundIndexForId($roundid);
        // if($index == 0)
            // return null;
        // return RoundIdForIndex($index - 1);
    }

    function RoundIdAfter($roundid) {
        switch($roundid) {
            case "OCR":
                return "P1";
            case "P1":
                return "P2";
            case "P2":
                return "P3";
            case "P3":
                return "F1";
            case "F1":
                return "F2";
            case "F2":
                return "proj_post_first_available";
            default:
                return null;
        }
        // $index = RoundIndexForId($roundid);
        // if($index >= 6)
            // return null;
        // return RoundIdForIndex(RoundIndexForId($roundid) + 1);
    }

    function RoundIdForIndex($index) {
        switch($index) {
            case 0:
                return "OCR";
            case 1:
                return "P1";
            case 2:
                return "P2";
            case 3:
                return "P3";
            case 4:
                return "F1";
            case 5:
                return "F2";
            default:
                return null;
                
        }
    }

    function UserFieldForRoundIndex($index) {
        if($index < 1 || $index > 5) {
            return "";
        }
        return sprintf("%s%d%s", "round", $index, "_user");
    }

    function TimeFieldForRoundIndex($index) {
        return sprintf("%s%d%s", "round", $index, "_time");
    }

    function TextFieldForRoundIndex($index) {
        if($index == 0) {
            return "master_text";
        }
        return sprintf("%s%d%s", "round", $index, "_text");
    }


    function TextFieldForPhase($phase) {
        switch($phase) {
            case "PREP":
                return "master_text";
            case "P1":
                return "round1_text";
            case "P2":
                return "round2_text";
            case "P3":
                return "round3_text";
            case "F1":
                return "round4_text";
            case "F2":
                return "round5_text";
            default:
                return "round5_text";
        }
    }

    function TextFieldForRoundId($roundid) {
        switch($roundid) {
            case "OCR":
                return "master_text";
            case "P1":
                return "round1_text";
            case "P2":
                return "round2_text";
            case "P3":
                return "round3_text";
            case "F1":
                return "round4_text";
            case "F2":
                return "round5_text";
            default:
                return "round5_text";
        }
    }

    function PreviousUserFieldForRoundid($roundid) {
        switch($roundid) {
            case "P2":
                return "round1_user";
            case "P3":
                return "round2_user";
            case "F1":
                return "round3_user";
            case "F2":
                return "round4_user";
            default:
                return null;
        }
    }

    function UserFieldForRoundId($roundid) {
        switch($roundid) {
            case "P1":
                return "round1_user";
            case "P2":
                return "round2_user";
            case "P3":
                return "round3_user";
            case "F1":
                return "round4_user";
            case "F2":
                return "round5_user";
            default:
                return null;
        }
    }

    function TimeFieldForRoundId($roundid) {
        return TimeFieldForRoundIndex(RoundIndexForId($roundid));
    }


    function FirstRoundId() {
        return RoundIdForIndex(0);
    }

    function PreviousRoundIdForRoundId($roundid) {
            $idx = RoundIndexForId($roundid) - 1;
            if($idx < 1) {
                return null;
            }
            return RoundIdForIndex($idx);
    }

    function NextRoundIdForRoundId($roundid) {
        $idx = RoundIndexForId($roundid) + 1;
        if($idx > 5 ){
            return null;
        }
        return RoundIdForIndex($idx);
    }

    function RoundUrl($roundid) {
        global $proof_url;
        return "{$proof_url}/round.php?roundid={$roundid}";
    }
