<?php

//Chat.php

namespace MyApp;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
require dirname(__DIR__) . "/database/ChatUser.php";
require dirname(__DIR__) . "/database/ChatRooms.php";
require dirname(__DIR__) . "/database/PrivateChat.php";


class Chat implements MessageComponentInterface {
    protected $clients;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        echo 'Server Started';
    }

    public function onOpen(ConnectionInterface $conn) {

             // Store the new connection to send messages to later
              echo 'Server Started';

            $this->clients->attach($conn);

            // Parse the query parameters from the URL
            $queryString = $conn->httpRequest->getUri()->getQuery();
            parse_str($queryString, $queryParameters);
            
            if (isset($queryParameters['token']) && isset($queryParameters['user_id'])) {
                
                $token = $queryParameters['token'];
                $user_id = $queryParameters['user_id'];
                
                $user_object = new \ChatUser;
    
                $user_object->setUserToken($token);
    
                $user_object->setUserConnectionId($conn->resourceId);
    
                $user_object->update_user_connection_id();
    
                $user_data = $user_object->get_user_id_from_token();

                $user_id = $user_data['user_id'];
                
                // Send the 'Online' status message to all existing users

                foreach ($this->clients as $client) {
                    $receiverUserId = $user_object->getUserIdByConnectionId($client->resourceId);
                    // Avoid sending status message to the user who just connected
                    $user_object->user_login($user_id);

                    if ($receiverUserId !== $user_id) {
                        $data['status_type'] = 'Online';
                        $data['user_id_status'] = $user_id;
                        $client->send(json_encode($data));
                    }
                }

            }
    
            echo "New connection! ({$conn->resourceId})\n";
        }

    public function onMessage(ConnectionInterface $from, $msg) {
        $numRecv = count($this->clients) - 1;
        echo sprintf('Connection %d sending message "%s" to %d other connection%s' . "\n"
            , $from->resourceId, $msg, $numRecv, $numRecv == 1 ? '' : 's');

        $data = json_decode($msg, true);
        if ($data['command'] == 'typing') {
            // Handle typing indicator
            $userObject = new \ChatUser();

            $fromUserId = $userObject->getUserIdByConnectionId($from->resourceId); // Get user ID by connection ID
           
            foreach ($this->clients as $client) {
                $receiverUserId = $userObject->getUserIdByConnectionId($client->resourceId);
            
                if ($fromUserId != $receiverUserId) {
                    // Sender's user ID matches the current client
                    $senderUserData = $userObject->get_user_data_by_id_new($fromUserId);
                    $data['to'] = $senderUserData['user_id'];
                    $data['from'] = $receiverUserId;
                    $client->send(json_encode($data));
                }
            }
            
          
        }elseif($data['command'] == 'private'){
            //private chat
            $private_chat_object = new \PrivateChat;

            $private_chat_object->setToUserId($data['receiver_id']);

            $private_chat_object->setFromUserId($data['user_id']);

            $private_chat_object->setChatMessage($data['msg']);

            $private_chat_object->setChatToken($data['token']);

            $timestamp = date('Y-m-d H:i:s', strtotime('now', strtotime('Asia/Kolkata')));

            $private_chat_object->setTimestamp($timestamp);

            $private_chat_object->setStatus('1');

            $chat_message_id = $private_chat_object->save_chat();

            $user_object = new \ChatUser;

            $user_object->setUserId($data['user_id']);

            $sender_user_data = $user_object->get_user_data_by_id();

            $user_object->setUserId($data['receiver_id']);

            $receiver_user_data = $user_object->get_user_data_by_id();

            $sender_user_name = $sender_user_data['user_name'];

            $data['datetime'] = $timestamp;
            
            $receiver_user_connection_id = $receiver_user_data['user_connection_id'];

            foreach($this->clients as $client){
                if($from == $client){
                    $data['from'] = 'Me';
                }else{
                    $data['from'] = $sender_user_name;
                }

                if($client->resourceId == $receiver_user_connection_id || $from == $client){   
                    $client->send(json_encode($data));
                }else{
                    $private_chat_object->setStatus('No');
                    $private_chat_object->setChatMessageId($chat_message_id);

                    $private_chat_object->update_chat_status();
                }
            }
        }elseif($data['command'] == 'msg_seen'){

            $userObject = new \ChatUser();
            $fromUserId = $userObject->getUserIdByConnectionId($from->resourceId);
            
            foreach ($this->clients as $client) {
                $receiverUserId = $userObject->getUserIdByConnectionId($client->resourceId);
            
               if ($fromUserId != $receiverUserId) {
                    // Sender's user ID matches the current client
                    $senderUserData = $userObject->get_user_data_by_id_new($fromUserId);

                    $data['to'] = $senderUserData['user_id'];
                    $data['from'] = $receiverUserId;
                    $client->send(json_encode($data));
               }
            }

        }else{
            //group chat

            $chat_object = new \ChatRooms;

            $chat_object->setUserId($data['user_id']);

            $chat_object->setMessage($data['msg']);

            $chat_object->setCreatedOn(date("Y-m-d h:i:s"));

            $chat_object->save_chat();

            $user_object = new \ChatUser;

            $user_object->setUserId($data['user_id']);

            $user_data = $user_object->get_user_data_by_id();

            $user_name = $user_data['user_name'];

            $data['dt'] = date("d-m-Y h:i:s");


            foreach ($this->clients as $client) {
                /*if ($from !== $client) {
                    // The sender is not the receiver, send to each client connected
                    $client->send($msg);
                }*/

                if($from == $client)
                {
                    $data['from'] = 'Me';
                }
                else
                {
                    $data['from'] = $user_name;
                }

                $client->send(json_encode($data));
            }
        }
    }


    public function onClose(ConnectionInterface $conn) {

        $querystring = $conn->httpRequest->getUri()->getQuery();

        parse_str($querystring, $queryarray);

        if(isset($queryarray['token'])){

            $user_object = new \ChatUser;

            $user_object->setUserToken($queryarray['token']);

            $user_data = $user_object->get_user_id_from_token();

            $user_object->user_logout($user_data['user_id']);

            $user_id = $user_data['user_id'];

            $data['status_type'] = 'Offline';

            $data['user_id_status'] = $user_id;

            foreach($this->clients as $client)
            {
                $client->send(json_encode($data));
            }
        }
        // The connection is closed, remove it, as we can no longer send it messages
        $this->clients->detach($conn);

        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }

}



?>