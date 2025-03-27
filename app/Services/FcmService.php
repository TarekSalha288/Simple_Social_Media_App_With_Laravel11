<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Google\Cloud\Firestore\FirestoreClient;
use Kreait\Firebase\Exception\FirebaseException;

class FcmService
{
    protected $messaging;
    protected $firestore;

    public function __construct()
    {
        // Absolute path to the Firebase service account file
        $serviceAccountPath = storage_path('app/firebase/socialmediaapp.json');

        // Initialize Firebase using service account key
        $firebase = (new Factory)
            ->withServiceAccount($serviceAccountPath);

        // Initialize Messaging
        $this->messaging = $firebase->createMessaging();

        // Initialize Firestore using FirestoreClient
        $this->firestore =$firebase->createFirestore();
    }

    // Send push notification via FCM
    public function sendNotification($deviceToken, $title, $body, array $data = [])
    {
        $notification = Notification::create($title, $body);

        $message = CloudMessage::withTarget('token', $deviceToken)
            ->withNotification($notification)
            ->withData($data);

        return $this->messaging->send($message);
    }

    /**
     * Store a message in Firebase Firestore
     *
     * @param array $messageData
     * @return array
     */
    public function sendMessageToFirestore(array $messageData)
    {
        try {
            $messageRef = $this->firestore->database()->collection('message')->add($messageData);
            return [
                'success' => 'Message stored successfully',
                'id' => $messageRef->id()
            ];
        } catch (FirebaseException $e) {
            return ['error' => 'Firebase error: ' . $e->getMessage()];
        } catch (\Exception $e) {
            return ['error' => 'General error: ' . $e->getMessage()];
        }
    }
}