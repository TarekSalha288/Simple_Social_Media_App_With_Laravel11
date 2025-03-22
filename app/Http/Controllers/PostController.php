<?php

namespace App\Http\Controllers;

use App\Events\PostCreated;
use App\Models\Post;
use App\Models\User;
use App\Models\Comment;
use Illuminate\Support\Facades\File;
use App\Models\Like;
use App\UploadImageTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class PostController extends Controller
{
    use UploadImageTrait;
    public function create_post(Request $request)
    {
        $request->validate([
            'body' => 'required',
        ]);
        $user = User::find(Auth::user()->id);
        $post = new Post;
        $post->body = $request->body;
        $post->user_id = $user->id;
        if ($request->hasFile('image')) {
            $path = $this->uploadImage($request, 'posts', $user->user_name);
            $post->path = $path;
        }
        $post->save();
     broadcast(new PostCreated($post,$user->followers))->toOthers();

        return response()->json('Post Created ');
    }
    public function update_post(Request $request, $id)
    {
        $post = Post::find($id);
        if (Auth::user()->id == $post->user_id) {
            $request->validate([
                'body' => 'required',
                'image' => 'image|mimes:jpeg,png,jpg',
            ]);
            $post->body = $request->body;
            if ($request->hasFile('image')) {
                $destination = public_path('imgs/' . $post->path);
                if (File::exists($destination)) {
                    File::delete($destination);
                }
                $user = User::find(Auth::user()->id);
                $path = $this->uploadImage($request, 'posts', $user->user_name);
                $post->path = $path;
            }
            $post->save();
            return response()->json(
                'Updated Done'
            );
        }

        return response()->json('You Can\'t Update This Post');
    }


    public function delete_post($id)
    {
        $user = User::find(Auth::user()->id);
        $post = Post::where('id', $id)->first();
        if (Auth::user() == $post->user) {
            if (file_exists('public/imgs/posts/' . $user->user_name . '/' . $post->path)) {
                File::delete('public/imgs/posts/' . $user->user_name . '/' . $post->path);
            }
            Post::destroy($id);
            return response()->json('Deleted Done');
        }
        return response()->json('You Can\'t Delete This Post');
    }



    public function show_followings_posts()
    {
        $user = Auth::user();
        $followings = $user->followings;
        $allPosts = collect();

        foreach ($followings as $following) {
            $posts = $following->posts()->orderBy('created_at', 'desc')->get();

            foreach ($posts as $post) {
                $num_likes = $post->likes()->count();
                $num_comments = $post->comments()->count();
                $num_fusers = $post->fusers()->count();
                $likedByUser = $post->likes()->where('user_id', $user->id)->exists();

                $allPosts[] = [
                    'post' => [
                        'id' => $post->id,
                        'body' => $post->body,
                        'path' => $post->path,
                        'user' => $post->user,
                        'created_at' => $post->created_at,
                        'updated_at' => $post->updated_at,
                        'likesNum' => $num_likes,
                        'commentNum' => $num_comments,
                        'favouritesNum' => $num_fusers,
                        'isLiked' => $likedByUser
                    ]
                ];
            }
        }

        if ($allPosts->isEmpty()) {
            return response()->json(['message' => 'No Followings Posts Yet'], 400);
        }

        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 2;
        $currentPageItems = $allPosts->slice(($currentPage - 1) * $perPage, $perPage);
        $paginatedPosts = new LengthAwarePaginator(
            $currentPageItems,
            $allPosts->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url()]
        );

        return response()->json(['posts' => $paginatedPosts], 200);
    }

    public function showTrendPosts()
    {
        $user = Auth::user();
        $posts = Post::with(['likes', 'comments', 'user'])->get();
        $trends = collect();

        foreach ($posts as $post) {
            $num_likes = $post->likes()->count();
            $num_comments = $post->comments()->count();
            $likedByUser = $post->likes()->where('user_id', $user->id)->exists();

            $trends[] = [
                'post' => [
                    'id' => $post->id,
                    'body' => $post->body,
                    'path' => $post->path,
                    'user' => $post->user,
                    'created_at' => $post->created_at,
                    'updated_at' => $post->updated_at,
                    'likesNum' => $num_likes,
                    'commentNum' => $num_comments,
                    'isLiked' => $likedByUser
                ]
            ];
        }

        if ($trends->isEmpty()) {
            return response()->json(['message' => 'No Trend Posts Yet'], 400);
        }

        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 2;
        $currentPageItems = $trends->slice(($currentPage - 1) * $perPage, $perPage);
        $paginatedTrends = new LengthAwarePaginator(
            $currentPageItems,
            $trends->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url()]
        );

        return response()->json(['trend posts' => $paginatedTrends], 200);
    }

    public function post($id)
    {
        $post = Post::where('id', $id)->first();
        $user = Post::find($id)->user;
        $likes = Post::find($id)->Likes;
        $flag = false;
        $num_likes = $post->Likes()->count();
        $num_comments = $post->comments()->count();
        $num_fusers = $post->fusers()->count();
        foreach ($likes as $like) {
            if ($like->user_id == Auth::user()->id) {
                $flag = true;
            }
        }
        return response()->json([
            'post' => $post,
            'user' => $user,
            'Likes' => $num_likes,
            'Comments' => $num_comments,
            'Favourites' => $num_fusers,
            'like' => $flag
        ]);
    }
    public function addComment(Request $request, $id)
    {
        $comment=Comment::create(['post_id' => $id, 'user_id' => Auth::user()->id, 'body' => $request->body, 'created_at' => now(), 'updated_at' => now()]);
   $comment->user;
        return response()->json($comment);
    }

    public function addLike($id)
    {
        $like = Like::where('user_id', Auth::user()->id)->where('post_id', $id)->first();
        if ($like) {
            return response()->json('You Have Already Liked This Post');
        }
        Like::create(['post_id' => $id, 'user_id' => Auth::user()->id, 'active' => 1]);
        return response()->json('Liked Added');
    }

    public function showComments($id)
    {
        $comments = Post::find($id)->comments;
        $allComments=[];
        if($comments){
        foreach ($comments as $comment) {
            $likedByUser = $comment->Likes->contains('user_id', Auth::user()->id);
            $tool=false;
            if($comment->user==Auth::user()){
             $tool=true;
            }
            $comment->user;
            $comment->replays;
            $num_of_likes=$comment->likes()->count();
            $num_of_replays=$comment->replays()->count();
            $comment->toArray;
            $allComments[]=[
                          'comment'=>$comment,
                          'Likes'=>$num_of_likes,
                          'num_Replays'=>$num_of_replays,
                          'isLike'=>$likedByUser,
                          'tool'=>$tool
            ];
        }
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 5;
        $allComments=collect($allComments);
        $currentPageItems = $allComments->forPage($currentPage, $perPage);
        $paginatedComments = new LengthAwarePaginator($currentPageItems, $allComments->count(), $perPage, $currentPage,[
            'path' => request()->url()]);
        return response()->json($paginatedComments);}

        return response()->json('No comment for this post');
    }
    public function showLikes($id)
    {
        $likes = Post::find($id)->likes;
        foreach ($likes as $like) {
            $users[] = User::where('id', $like->user_id)->get();
        }
        return response()->json(['likes' => $likes, 'users' => $users]);
    }
    public function addFavourite($id)
    {
        $flag = DB::table('user_favourite')->where('user_id', Auth::user()->id)->where('post_id', $id)->get();
        if ($flag->count() == 0) {
            DB::table('user_favourite')->insert([
                'user_id' => Auth::user()->id,
                'post_id' => $id
            ]);
            return response()->json('Add To Favourite');
        }
        return response()->json("You Can't Add To favourite again");
    }
    public function ShowFavouritePosts()
    {
        $posts = Auth::user()->fposts;
        $users = $posts->pluck('user');
        return response()->json(['favpost' => $posts]);
    }
    public function deleteFavouritePost($id)
    {
        DB::table('user_favourite')->where('user_id', Auth::user()->id)->where('post_id', $id)->delete();
        return response()->json('Deleted Favourite');
    }
    public function dislikePost($id)
    {
        $like = Like::where('user_id', Auth::user()->id)->where('post_id', $id)->first();
        if ($like) {
            Like::where('user_id', Auth::user()->id)->where('post_id', $id)->delete();
            return response()->json('Dislike Done');
        }
        return response()->json('You Don\'t Liked This Post');
    }
}