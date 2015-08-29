<?PHP

global $userP;
$interface_type = $userP['i_type'];
switch ($interface_type) {
    case 0:
        // standard interface
        include('proof_frame_std.inc');
        break;

    case 1:
        // enhanced interface
        include('proof_frame_enh.inc');
        break;

    case 2:
        // super interface
        include('proof_frame_super.inc');
        break;

    default:
        die( "unknown i_type: '$interface_type'" );
}

// vim: sw=4 ts=4 expandtab
