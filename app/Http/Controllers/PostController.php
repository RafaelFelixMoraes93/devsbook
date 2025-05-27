<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostLike;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    private $loggedUser;

    public  function __construct() {
        $this->middleware('auth:api');

        $this->loggedUser = Auth::user();
    }

    public function like($id) {
        $array = ['error' => ''];

        $postExists = Post::find($id);
        if($postExists) {
            $isLiked = PostLike::where('id_post', $id)
            ->where('id_user', $this->loggedUser['id'])
            ->count();
                if($isLiked > 0) {
                    $postLike = PostLike::where('id_post', $id)
                    ->where('is_user', $this->loggedUser['id'])
                    ->first();
                    $postLike->delete();

                    $isLiked = false;
                } else {
                    $newPostLike = new PostLike();
                    $newPostLike->id_post = $id;
                    $newPostLike->id_user = $this->loggedUser['id'];
                    $newPostLike->created_at = date('Y-m-d H-i-s');
                    $newPostLike->save();

                    $isLiked = true;
                }

        } else {
            $array['error'] = 'Post não existe!';
            return $array;
        }

        return $array;
    }
}
