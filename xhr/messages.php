<?php 
if ($f == 'messages') {
    if ($s == 'get_user_messages') {
        if (!empty($_GET['user_id']) AND is_numeric($_GET['user_id']) AND $_GET['user_id'] > 0 && Wo_CheckMainSession($hash_id) === true) {
            $html       = '';
            $user_id    = $_GET['user_id'];
            $can_replay = true;
            $recipient  = Wo_UserData($user_id);
            $messages   = Wo_GetMessages(array(
                'user_id' => $user_id
            ));

            if (!empty($recipient['user_id']) && $recipient['message_privacy'] == 1) {
                if (Wo_IsFollowing($wo['user']['user_id'], $recipient['user_id']) === false) {
                    $can_replay = false;
                }
            }
            elseif (!empty($recipient['user_id']) && $recipient['message_privacy'] == 2) {
                $can_replay = false;
            }
            foreach ($messages as $wo['message']) {
                $wo['message']['color'] = Wo_GetChatColor($wo['user']['user_id'], $recipient['user_id']);
                $html .= Wo_LoadPage('messages/messages-text-list');
            }

            $_SESSION['chat_active_background'] = $recipient['user_id'];
            $wo['chat']['color'] = Wo_GetChatColor($wo['user']['user_id'], $recipient['user_id']);
            $data                = array(
                'status' => 200,
                'html' => $html,
                'can_replay' => $can_replay,
                'view_more_text' => $wo['lang']['view_more_messages'],
                'video_call' => 0,
                'audio_call' => 0,
                'color' => $wo['chat']['color'],
                'block_url' => $recipient['url'] . '?block_user=block&redirect=messages',
                'url' => $recipient['url'],
                'avatar' => $recipient['avatar']
            );
            $data['lastseen'] = '';
            if($wo['config']['user_lastseen'] == 1 && $recipient['showlastseen'] != 0) { 
                $data['lastseen'] = Wo_UserStatus($recipient['user_id'],$recipient['lastseen']);
            }
            if ($wo['config']['video_chat'] == 1) {
                if ($recipient['lastseen'] > time() - 60) {
                    $data['video_call'] = 200;
                }
            }
            if ($wo['config']['audio_chat'] == 1) {
                if ($recipient['lastseen'] > time() - 60) {
                    $data['audio_call'] = 200;
                }
            }
            $attachments = Wo_GetLastAttachments($user_id);
            $attachments_html = '';
            if (!empty($attachments)) {
                foreach ($attachments as $key => $value) {
                    $attachments_html  .= '<li data-href="'.$value.'" onclick="Wo_OpenLighteBox(this,event);"><span><img src="'.$value.'"></span></li>';
                }
            }
            $data['attachments_html'] = $attachments_html;
            $data['messages_count'] = Wo_CountMessages(array('new' => false,'user_id' => $user_id));
            $data['posts_count'] = $recipient['details']['post_count'];
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'get_group_messages' && isset($_GET['group_id']) && is_numeric($_GET['group_id']) && $_GET['group_id'] > 0 && Wo_CheckMainSession($hash_id)) {
        $html     = '';
        $group_id = $_GET['group_id'];
        $messages = Wo_GetGroupMessages(array(
            'group_id' => $group_id
        ));
        @Wo_UpdateGChatLastSeen($group_id);
        foreach ($messages as $wo['message']) {
            $html .= Wo_LoadPage('messages/group-text-list');
        }
        $data = array(
            'status' => 200,
            'html' => $html,
            'view_more_text' => $wo['lang']['view_more_messages']
        );
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'send_message') {
        if (isset($_POST['user_id']) && is_numeric($_POST['user_id']) && $_POST['user_id'] > 0 && Wo_CheckMainSession($hash_id) === true) {
            $html          = '';
            $media         = '';
            $mediaFilename = '';
            $mediaName     = '';
            $invalid_file  = 0;
            if (isset($_FILES['sendMessageFile']['name'])) {
                if ($_FILES['sendMessageFile']['size'] > $wo['config']['maxUpload']) {
                    $invalid_file = 1;
                } else if (!in_array($_FILES["sendMessageFile"]["type"], explode(',', $wo['config']['mime_types']))) {
                    $invalid_file = 2;
                } else {
                    $fileInfo      = array(
                        'file' => $_FILES["sendMessageFile"]["tmp_name"],
                        'name' => $_FILES['sendMessageFile']['name'],
                        'size' => $_FILES["sendMessageFile"]["size"],
                        'type' => $_FILES["sendMessageFile"]["type"]
                    );
                    $media         = Wo_ShareFile($fileInfo);
                    $mediaFilename = $media['filename'];
                    $mediaName     = $media['name'];
                }
            } else if (!empty($_POST['record-file']) && !empty($_POST['record-name'])) {
                $mediaFilename = $_POST['record-file'];
                $mediaName     = $_POST['record-name'];
            }
            $sticker = '';
            if (isset($_POST['chatSticker']) && Wo_IsUrl($_POST['chatSticker']) && !$mediaFilename && !$mediaName) {
                $sticker = (isset($_POST['chatSticker']) && Wo_IsUrl($_POST['chatSticker'])) ? $_POST['chatSticker'] : '';
            }
            if (empty($_POST['textSendMessage']) && empty($mediaFilename) && empty($sticker)) {
                exit();
            }
            $user_data    = Wo_UserData($_POST['user_id']);
            if (!empty($user_data) && $user_data['message_privacy'] == 2) {
                exit();
            }
            if (!empty($user_data) && $user_data['message_privacy'] == 1 && Wo_IsFollowing($wo['user']['user_id'], $_POST['user_id']) === false) {
                exit();
            }
            $messages = Wo_RegisterMessage(array(
                'from_id' => Wo_Secure($wo['user']['user_id']),
                'to_id' => Wo_Secure($_POST['user_id']),
                'text' => Wo_Secure($_POST['textSendMessage']),
                'media' => Wo_Secure($mediaFilename),
                'mediaFileName' => Wo_Secure($mediaName),
                'time' => time(),
                'stickers' => $sticker
            ));
            if ($messages > 0) {
                $messages = Wo_GetMessages(array(
                    'message_id' => $messages,
                    'user_id' => $_POST['user_id']
                ));
                foreach ($messages as $wo['message']) {
                    $wo['message']['color'] = Wo_GetChatColor($wo['user']['user_id'], $_POST['user_id']);
                    $html .= Wo_LoadPage('messages/messages-text-list');
                }
                $data = array(
                    'status' => 200,
                    'html' => $html,
                    'invalid_file' => $invalid_file
                );
                $to_id        = $_POST['user_id'];
                $recipient    = Wo_UserData($to_id);
                $data['messages_count'] = Wo_CountMessages(array('new' => false,'user_id' => $_POST['user_id']));
                $data['posts_count'] = $recipient['details']['post_count'];
                if ($wo['config']['emailNotification'] == 1) {
                    $send_notif   = array();
                    $send_notif[] = (!empty($recipient) && ($recipient['lastseen'] < (time() - 120)));
                    $send_notif[] = ($recipient['e_last_notif'] < time() && $recipient['e_sentme_msg'] == 1);
                    if (!in_array(false, $send_notif)) {
                        $db->where("user_id", $to_id)->update(T_USERS, array(
                            'e_last_notif' => (time() + 3600)
                        ));
                        $wo['emailNotification']['notifier'] = $wo['user'];
                        $wo['emailNotification']['type']     = 'sent_message';
                        $wo['emailNotification']['url']      = $recipient['url'];
                        $wo['emailNotification']['msg_text'] = Wo_Secure($_POST['textSendMessage']);
                        $send_message_data                   = array(
                            'from_email' => $wo['config']['siteEmail'],
                            'from_name' => $wo['config']['siteName'],
                            'to_email' => $recipient['email'],
                            'to_name' => $recipient['name'],
                            'subject' => 'New notification',
                            'charSet' => 'utf-8',
                            'message_body' => Wo_LoadPage('emails/notifiction-email'),
                            'is_html' => true
                        );
                        if ($wo['config']['smtp_or_mail'] == 'smtp') {
                            $send_message_data['insert_database'] = 1;
                        }
                        Wo_SendMessage($send_message_data);
                    }
                }
            }
            if ($invalid_file > 0 && empty($messages)) {
                $data = array(
                    'status' => 500,
                    'invalid_file' => $invalid_file
                );
            }
        } else if (isset($_POST['group_id']) && is_numeric($_POST['group_id']) && $_POST['group_id'] > 0 && Wo_CheckMainSession($hash_id) === true) {
            $html          = '';
            $media         = '';
            $mediaFilename = '';
            $mediaName     = '';
            $invalid_file  = 0;
            if (isset($_FILES['sendMessageFile']['name'])) {
                if ($_FILES['sendMessageFile']['size'] > $wo['config']['maxUpload']) {
                    $invalid_file = 1;
                } else if (!in_array($_FILES["sendMessageFile"]["type"], explode(',', $wo['config']['mime_types']))) {
                    $invalid_file = 2;
                } else {
                    $fileInfo      = array(
                        'file' => $_FILES["sendMessageFile"]["tmp_name"],
                        'name' => $_FILES['sendMessageFile']['name'],
                        'size' => $_FILES["sendMessageFile"]["size"],
                        'type' => $_FILES["sendMessageFile"]["type"]
                    );
                    $media         = Wo_ShareFile($fileInfo);
                    $mediaFilename = $media['filename'];
                    $mediaName     = $media['name'];
                }
            } else if (!empty($_POST['record-file']) && !empty($_POST['record-name'])) {
                $mediaFilename = $_POST['record-file'];
                $mediaName     = $_POST['record-name'];
            }
            $message_id = Wo_RegisterGroupMessage(array(
                'from_id' => Wo_Secure($wo['user']['user_id']),
                'group_id' => Wo_Secure($_POST['group_id']),
                'text' => Wo_Secure($_POST['textSendMessage']),
                'media' => Wo_Secure($mediaFilename),
                'mediaFileName' => Wo_Secure($mediaName),
                'time' => time()
            ));
            if ($message_id > 0) {
                @Wo_UpdateGChat(Wo_Secure($_POST['group_id']), array(
                    "time" => time()
                    ));
                $message = Wo_GetGroupMessages(array(
                    'id' => $message_id,
                    'group_id' => $_POST['group_id']
                ));
                foreach ($message as $wo['message']) {
                    $html .= Wo_LoadPage('messages/group-text-list');
                }
                $data = array(
                    'status' => 200,
                    'html' => $html,
                    'invalid_file' => $invalid_file
                );
            }
            if ($invalid_file > 0 && empty($message_id)) {
                $data = array(
                    'status' => 500,
                    'invalid_file' => $invalid_file
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'register_message_record') {
        if (isset($_POST['audio-filename']) && isset($_FILES['audio-blob']['name'])) {
            $fileInfo       = array(
                'file' => $_FILES["audio-blob"]["tmp_name"],
                'name' => $_FILES['audio-blob']['name'],
                'size' => $_FILES["audio-blob"]["size"],
                'type' => $_FILES["audio-blob"]["type"]
            );
            $media          = Wo_ShareFile($fileInfo);
            $data['url']    = $media['filename'];
            $data['status'] = 200;
            $data['name']   = $media['name'];
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'upload_record') {
        if (isset($_POST['audio-filename']) && isset($_FILES['audio-blob']['name'])) {
            $fileInfo       = array(
                'file' => $_FILES["audio-blob"]["tmp_name"],
                'name' => $_FILES['audio-blob']['name'],
                'size' => $_FILES["audio-blob"]["size"],
                'type' => $_FILES["audio-blob"]["type"]
            );
            $media          = Wo_ShareFile($fileInfo);
            $data['status'] = 200;
            $data['url']    = $media['filename'];
            $data['name']   = $media['name'];
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'load_previous_messages') {
        $html = '';
        if (!empty($_GET['user_id']) && is_numeric($_GET['user_id']) && $_GET['user_id'] > 0 && !empty($_GET['before_message_id'])) {
            $user_id           = Wo_Secure($_GET['user_id']);
            $before_message_id = Wo_Secure($_GET['before_message_id']);
            $messages          = Wo_GetMessages(array(
                'user_id' => $user_id,
                'before_message_id' => $before_message_id
            ));
            if ($messages > 0) {
                foreach ($messages as $wo['message']) {
                    $html .= Wo_LoadPage('messages/messages-text-list');
                }
                $data = array(
                    'status' => 200,
                    'html' => $html
                );
            }
        } else if (!empty($_GET['group_id']) && is_numeric($_GET['group_id']) && $_GET['group_id'] > 0 && !empty($_GET['before_message_id'])) {
            $group_id          = Wo_Secure($_GET['group_id']);
            $before_message_id = Wo_Secure($_GET['before_message_id']);
            $messages          = Wo_GetGroupMessages(array(
                'group_id' => $group_id,
                'offset' => $before_message_id,
                'old' => true
            ));
            if ($messages > 0) {
                foreach ($messages as $wo['message']) {
                    $html .= Wo_LoadPage('messages/group-text-list');
                }
                $data = array(
                    'status' => 200,
                    'html' => $html
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'update_recipients') {
        $html  = '';
        $users = Wo_GetMessagesUsers($wo['user']['user_id']);
        $data  = array(
            'status' => 404
        );
        if (count($users) > 0) {
            foreach ($users as $wo['recipient']) {
                $wo['session_active_background'] = (!empty($_SESSION['chat_active_background'])) ? $_SESSION['chat_active_background'] : 0;
                $html .= Wo_LoadPage('messages/messages-recipients-list');
            }
            $data = array(
                'status' => 200,
                'html' => $html
            );
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'get_new_messages') {
        $html                        = '';
        $data['update_group_status'] = Wo_CheckLastGroupAction();
        if (isset($_GET['user_id']) && is_numeric($_GET['user_id']) && $_GET['user_id'] > 0 && Wo_CheckMainSession($hash_id) === true) {
            $user_id = Wo_Secure($_GET['user_id']);
            if (!empty($user_id)) {
                $user_id  = $_GET['user_id'];
                $messages = Wo_GetMessages(array(
                    'after_message_id' => $_GET['message_id'],
                    'user_id' => $user_id
                ));
                if (count($messages) > 0) {
                    foreach ($messages as $wo['message']) {
                        $html .= Wo_LoadPage('messages/messages-text-list');
                    }
                    $data = array(
                        'status' => 200,
                        'html' => $html,
                        'sender' => $wo['user']['user_id']
                    );
                    $recipient    = Wo_UserData($user_id);
                    $data['messages_count'] = Wo_CountMessages(array('new' => false,'user_id' => $user_id));
                    $data['posts_count'] = $recipient['details']['post_count'];
                }
            }
        } else if (isset($_GET['group_id']) && is_numeric($_GET['group_id']) && $_GET['group_id'] > 0 && Wo_CheckMainSession($hash_id) === true) {
            $group_id = Wo_Secure($_GET['group_id']);
            if (!empty($group_id)) {
                $group_id = $group_id;
                $messages = Wo_GetGroupMessages(array(
                    'offset' => $_GET['message_id'],
                    'group_id' => $group_id,
                    'new' => true
                ));
                if (count($messages) > 0) {
                    foreach ($messages as $wo['message']) {
                        $html .= Wo_LoadPage('messages/group-text-list');
                    }
                    $data = array(
                        'status' => 200,
                        'html' => $html
                    );
                    @Wo_UpdateGChatLastSeen($group_id);
                }
            }
        }
        if (!empty($user_id)) {
            $data['color'] = Wo_GetChatColor($wo['user']['user_id'], $user_id);
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'delete_message') {
        if (isset($_GET['message_id']) && Wo_CheckMainSession($hash_id) === true) {
            $message_id = Wo_Secure($_GET['message_id']);
            $message = $db->where('id',$message_id)->getOne(T_MESSAGES);
            if (!empty($message_id) || is_numeric($message_id) || $message_id > 0) {
                
                if (Wo_DeleteMessage($message_id) === true) {
                    $data['status'] = 200;
                    if (!empty($message)) {
                        $user_id = $message->to_id;
                        if ($message->to_id == $wo['user']['id']) {
                            $user_id = $message->from_id;
                        }
                        $recipient    = Wo_UserData($user_id);
                        $data['messages_count'] = Wo_CountMessages(array('new' => false,'user_id' => $user_id));
                        $data['posts_count'] = $recipient['details']['post_count'];
                    }
                    
                }
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'delete_conversation') {
        if (isset($_GET['user_id']) && Wo_CheckMainSession($hash_id) === true) {
            $user_id = Wo_Secure($_GET['user_id']);
            if (!empty($user_id) || is_numeric($user_id) || $user_id > 0) {
                if (Wo_DeleteConversation($user_id) === true) {
                    $data = array(
                        'status' => 200,
                        'message' => $wo['lang']['conver_deleted']
                    );
                }
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'clear_group_chat') {
        if (isset($_GET['id']) && Wo_CheckMainSession($hash_id) === true) {
            $id = Wo_Secure($_GET['id']);
            if (!empty($id) || is_numeric($id) || $id > 0) {
                if (Wo_DeleteConversation($user_id) === true) {
                    $data = array(
                        'status' => 200,
                        'message' => $wo['lang']['no_messages_here_yet']
                    );
                }
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'get_last_message_seen_status') {
        if (isset($_GET['last_id'])) {
            $message_id = Wo_Secure($_GET['last_id']);
            if (!empty($message_id) || is_numeric($message_id) || $message_id > 0) {
                $seen = Wo_SeenMessage($message_id);
                if ($seen > 0) {
                    $data = array(
                        'status' => 200,
                        'time' => $seen['time'],
                        'seen' => $seen['seen']
                    );
                }
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
}
