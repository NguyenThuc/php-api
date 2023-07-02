<?php 
if ($f == 'get_messages') {
    if (Wo_CheckMainSession($hash_id) === true) {
        $data     = array(
            'status' => 200,
            'html' => ''
        );

        $messages = Wo_GetMessagesUsers($wo['user']['user_id'], '', 5);
        
        $groups_messages = Wo_GetGroupsListAPP(array('limit' => 5));
        if (!empty($messages) || !empty($groups_messages)) {
            $messages_count = 0;
            if (!empty($messages) && !empty($groups_messages) && (count($messages) >= count($groups_messages))) {
                foreach ($messages as $key => $wo['message']) {
                    $message = Wo_GetMessagesHeader(array('user_id' => $wo['message']['user_id']), 1);
                    if (!empty($message)) {
                        foreach ($groups_messages as $group_key => $group_value) {
                            if ($message['time'] < $group_value['time']) {
                                    $wo['group'] = $groups_messages[$group_key];
                                    if (!empty($wo['group']['last_message']) && $messages_count < 5) {
                                        $data['html'] .= Wo_LoadPage('header/group_messages');
                                        $messages_count = $messages_count + 1;
                                        unset($groups_messages[$group_key]);
                                    }
                            }
                        }
                        if ($messages_count < 5 && !empty($message['messageUser'])) {
                            $data['html'] .= Wo_LoadPage('header/messages'); 
                            $messages_count = $messages_count + 1;
                        }
                    }
                }
                if ($messages_count < 5 && !empty($groups_messages)) {
                    foreach ($groups_messages as $group_key => $group_value) {
                        $wo['group'] = $groups_messages[$group_key];
                        if (!empty($wo['group']['last_message']) && $messages_count < 5) {
                            $data['html'] .= Wo_LoadPage('header/group_messages');
                            $messages_count = $messages_count + 1;
                            unset($groups_messages[$group_key]);
                        }
                    }
                }


            }
            elseif (!empty($messages) && !empty($groups_messages) && (count($messages) < count($groups_messages))) {
                foreach ($groups_messages as $key => $wo['group']) {
                    foreach ($messages as $messages_key => $messages_value) {
                        $message = Wo_GetMessagesHeader(array('user_id' => $messages_value['user_id']), 1);
                        if ($message['time'] > $wo['group']['time']) {
                                $wo['message'] = $messages[$messages_key];
                                if ($messages_count < 5 && !empty($message['messageUser'])) {
                                    $data['html'] .= Wo_LoadPage('header/messages'); 
                                    $messages_count = $messages_count + 1;
                                    unset($messages[$messages_key]);
                                }
                        }
                    }
                    if (!empty($wo['group']['last_message']) && $messages_count < 5) {
                        $data['html'] .= Wo_LoadPage('header/group_messages');
                        $messages_count = $messages_count + 1;
                    }
                }
                if ($messages_count < 5 && !empty($messages)) {
                    foreach ($messages as $messages_key => $messages_value) {
                        $message = Wo_GetMessagesHeader(array('user_id' => $messages_value['user_id']), 1);
                        $wo['message'] = $messages[$messages_key];
                        if ($messages_count < 5 && !empty($message['messageUser'])) {
                            $data['html'] .= Wo_LoadPage('header/messages'); 
                            $messages_count = $messages_count + 1;
                            unset($messages[$messages_key]);
                        }
                    }
                }
            }
            elseif (!empty($messages) && empty($groups_messages)) {
                foreach ($messages as $key => $wo['message']) {
                    $message = Wo_GetMessagesHeader(array('user_id' => $wo['message']['user_id']), 1);
                    if (!empty($message['messageUser'])) {
                        $data['html'] .= Wo_LoadPage('header/messages');
                    }
                }
            }
            elseif (empty($messages) && !empty($groups_messages)) {
                foreach ($groups_messages as $key => $wo['group']) {
                    if (!empty($wo['group']['last_message'])) {
                        $data['html'] .= Wo_LoadPage('header/group_messages');
                    }
                }
            }
        }else {
            $data['message'] = $wo['lang']['no_more_message_to_show'];
        }


        // if (count($messages) > 0 || count($groups_messages) > 0) {
        //     if (!empty($messages)) {
        //         foreach ($messages as $key => $wo['message']) {
        //             $message = Wo_GetMessagesHeader(array('user_id' => $wo['message']['user_id']), 1);
        //             if (!empty($groups_messages)) {
        //                 foreach ($groups_messages as $group_key => $group_value) {
        //                     if ($message['time'] < $group_value['time']) {
        //                             $wo['group'] = $groups_messages[$group_key];
        //                             $data['html'] .= Wo_LoadPage('header/group_messages');
        //                             unset($groups_messages[$group_key]);
        //                     }
        //                 }
        //             }
        //             $data['html'] .= Wo_LoadPage('header/messages'); 
        //         }
        //     }
        // } 
        $data['messages_url']  = Wo_SeoLink('index.php?link1=messages');
        $data['messages_text'] = $wo['lang']['see_all'];
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
