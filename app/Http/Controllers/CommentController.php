<?php

namespace App\Http\Controllers;

use App\Events\MyComment;
use App\Notifications\MyCommentNot;
use Illuminate\Http\Request;
use App\Models\Comment;
use App\Models\LikeComment;
use App\Models\Post;
use App\Models\ReplayComment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class CommentController extends Controller


{
public function edit($id){
    $comment = Comment::where('id',$id)->get();
return response()->json($comment);
}
public function update(Request $request, $id){
    $comment=Comment::where('user_id',Auth::user()->id)->where('id',$id)->first();

    if($comment ){
        $owner=$comment->user;
        if($owner){
$request->validate(['body'=>'required']);
Comment::where('id',$id)->update(['body'=> $request->body]);
return response()->json(['message'=> 'Updated Comment']);
        }
}

    return response()->json(['message'=> 'You Can\'t Update This Comment']);

}
public function delete($id){
    $comment=Comment::where('user_id',Auth::user()->id)->where('id',$id)->first();
    if($comment){
        $owner=$comment->user;
        $post_owner=$comment->post->user;
        if($post_owner || $owner){
    Comment::where('id',$id)->delete();
    return response()->json(['message'=> 'Deleted Comment Done']);}}

        return response()->json(['message'=> 'You Can\'t Delete This Comment']);

}
public function replay(Request $request, $id){
    $user= Post::find($id)->user;
   $replay= ReplayComment::create([
        'body'=> $request->body,
        'user_id'=>Auth::user()->id,
        'comment_id'=>$id,
    ]);
broadcast(new MyComment(Auth::user()->user_name." Replay To Your Comment",$id,$user->id))->toOthers();
Notification::send($user,new MyCommentNot(Auth::user()->user_name." Replay To Your Comment",$id,$user->id));

    return response()->json(['comment'=>$replay,'message'=> 'Updated Replay Comment']);
}
public function like($id){
    $user= Post::find($id)->user;
LikeComment::create([
    'user_id'=>Auth::user()->id,
    'comment_id'=>$id,
    'active'=>1,
]);
broadcast(new MyComment(Auth::user()->user_name." Like Your Comment",$id,$user->id))->toOthers();
Notification::send($user,new MyCommentNot(Auth::user()->user_name." Like Your Comment",$id,$user->id));
return response()->json(['message'=> 'Liked Comment Done']);
}
public function dislike($id){
LikeComment::where('id',$id)->delete();
}
public function showReplays($id){
    $replays[]=Comment::findOrFail($id)->replays;
foreach($replays as $replay){
$replay->pluck('user');
}
    return response()->json(['replays'=>$replays]);
}
public function showLikes($id){
    $likes[]=Comment::findOrFail($id)->likes;
    foreach($likes as $like){
    $like->pluck('user');
    }
        return response()->json(['likes'=>$likes]);
    }

}
