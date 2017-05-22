<?PHP

global $userP;
$interface_type = $userP['i_type'];
switch ($interface_type) {
    case 0:
        // standard interface
        include('proof_frame_std.inc');
        break;

    default:
        include('proof_frame_enh.inc');
        break;

}

// vim: sw=4 ts=4 expandtab
