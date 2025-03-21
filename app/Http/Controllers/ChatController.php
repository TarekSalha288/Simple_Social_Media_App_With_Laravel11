<?php

namespace App\Http\Controllers;
use App\Events\MessageSent;
use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\FcmService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ChatController extends Controller
{
    protected $fcmService;

    public function __construct(FcmService $fcmService)
    {
        $this->fcmService = $fcmService;
    }
    public function index($chatId)
    {
        $messages = Chat::find($chatId)->messages;
if($messages->isEmpty())
return response()->json(["message"=>"No Messages Yet"]);
        return response()->json(['data'=>$messages],200);
    }
    public function store(Request $request, $id)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $sender = auth()->user();
        if (!$sender) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $receiver = User::find($id);
        if (!$receiver) {
            return response()->json(['error' => 'Receiver not found'], 404);
        }
        $chat = Chat::where(function($query) use ($sender, $id) {
            $query->where('sender_id', $sender->id)
                  ->where('receiver_id', $id);
        })->orWhere(function($query) use ($sender, $id) {
            $query->where('sender_id', $id)
                  ->where('receiver_id', $sender->id);
        })->first();

        if (!$chat) {
            $chat = Chat::create([
                'sender_id' => $sender->id,
                'receiver_id' => $id
            ]);
        }

        // Create the message
        $message = Message::create([
            'sender_id' => $sender->id,
            'receiver_id' => $id,
            'message' => $request->message,
            'chat_id' => $chat->id,
            'created_at' => now()->toDateTimeString(),
        ]);

        $messageData = [
            'sender_email' => auth()->user()->email,
            'receiver_email' => $receiver->email, // Corrected from $id to $receiver->email
            'message' => $message->message,
            'created_at' => now()->toDateTimeString(),
        ];

        // Trigger the MessageSent event
        broadcast(new MessageSent($message, $chat->id))->toOthers();

        // Send FCM notification if the receiver has an FCM token
        if ($receiver->fcm_token) {
        //     $this->fcmService->sendNotification(
        //         $receiver->fcm_token,
        //         'New message From ' . $receiver->email,
        //         $message->message
        //     );
            return response()->json([$messageData], 201);
        }

        return response()->json(["message" => "Fcm Token Not Found"], 400);
    }
    public function getUsers()
    {
        $user = auth()->user();
        $mutualFollowers = $user->mutualFollowers()->paginate(3);
        if ($mutualFollowers->isEmpty()) {
            return response()->json(['message' => "You can't talk to anyone"], 400);
        }
        return response()->json(['data' => $mutualFollowers], 200);
    }
    public function chats()
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $chats = $user->chats()->with(['receiver', 'sender'])->get();
        if ($chats->isEmpty()) {
            return response()->json(['message' => 'No chats found'], 404);
        }
        $formattedChats = $chats->map(function ($chat) use ($user) {
            // Determine if the authenticated user is the sender or receiver
            $receiver = $chat->sender_id === $user->id ? $chat->receiver : $chat->sender;
            if (!$receiver) {
                return null;
            }

            return [
                'chat_id' => $chat->id,
                'receiver_id' => $receiver->id,
                'receiver_name' => $receiver->name,
                'receiver_email' => $receiver->email,
                'last_message' => $chat->messages->last() ? $chat->messages->last()->message : null,
                'receiver_image'=>$receiver->image_path,
                'created_at' => $chat->created_at,
                'updated_at' => $chat->updated_at,
            ];
        })->filter();
        return response()->json(['data' => $formattedChats], 200);
    }
    public function updatePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'password' => 'required|min:8',
            'confirmation_password' => 'required|min:8',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }
        if (Hash::check($request->current_password, auth()->user()->password)) {
            if ($request->password == $request->confirmation_password) {
                $password = Hash::make($request->password);
                User::where('id', auth()->user()->id)->update([
                    'password' => $password,
                ]);
                return response()->json(['message' => 'Password Changed Sucssfully']);
            }
            return response()->json(['message' => 'Password And Confirmation_Password Not The Same'], 400);
        }
        return response()->json(['message' => 'Old Password Not Correct'], 400);
    }
    public function updateInfo(Request $request)
    {
        $user = User::find(auth()->user()->id);
        $validator = Validator::make(request()->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . Auth::id(),
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }
        $user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);
        return response()->json(['message' => 'Info Updated Succseflly'], 200);
    }
}