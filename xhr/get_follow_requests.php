<?php 
if ($f == 'get_follow_requests') {
    $data     = array(
        'status' => 200,
        'html' => ''
    );
    $requests = Wo_GetFollowRequests();
    if (count($requests) > 0) {
        foreach ($requests as $wo['request']) {
            $data['html'] .= Wo_LoadPage('header/follow-requests');
        }
    } else {
        $data['message'] = $wo['lang']['no_new_requests'];
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
