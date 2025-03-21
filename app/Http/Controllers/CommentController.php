<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Comment;
use App\Models\LikeComment;
use App\Models\ReplayComment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
   $replay= ReplayComment::create([
        'body'=> $request->body,
        'user_id'=>Auth::user()->id,
        'comment_id'=>$id,
    ]);
    return response()->json(['comment'=>$replay,'message'=> 'Updated Replay Comment']);
}
public function like($id){
LikeComment::create([
    'user_id'=>Auth::user()->id,
    'comment_id'=>$id,
    'active'=>1,
]);
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