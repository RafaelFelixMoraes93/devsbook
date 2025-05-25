<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Post;
use App\Models\PostLike;
use App\Models\PostComment;
use App\Models\User;
use App\Models\UserRelation;
use Intervention\Image\ImageManager;

class FeedController extends Controller
{
    private $loggedUser;

    public  function __construct() {
        $this->middleware('auth:api');

        $this->loggedUser = Auth::user();
    }

    public function create(Request $request) {
        $array = ['error' => ''];
        $allowedTypes = ['image/jpg', 'image/jpeg', 'image/png'];        

        $type = $request->input('type');
        $body = $request->input('body');
        $photo = $request->file('photo');

        if($type) {
            switch($type) {
                case 'text':
                    if(!$body) {
                        $array['error'] = 'Texto não enviado!';
                        return $array;
                    }
                break;
                case 'photo':
                    if($photo) {
                        if(in_array($photo->getClientMimeType(), $allowedTypes)) {
                            $filename = md5(time().rand(0, 9999)).'jpg';

                $destinyPath = public_path('/media/uploads');

                if (!file_exists($destinyPath)) {
                    mkdir($destinyPath, 0755, true);
                }

                $manager = ImageManager::gd();

                $img = $manager->read($photo->path())
                    ->resize(800, null)
                    ->save($destinyPath . '/' . $filename);

                    $body = $filename;
                         }
                    } else {
                        $array['error'] = 'Arquivo não enviado';
                        return $array;
                    }
                break;
                default:
                    $array['error'] = 'Tipo de postagem inexistente!';
                    return $array;
                break;
            }

            if($body) {
                $newPost = new Post();
                $newPost->id_user = $this->loggedUser['id'];
                $newPost->type = $type;
                $newPost->created_at = date('Y-m-d H:i:s');
                $newPost->body = $body;
                $newPost->save();
            }
        } else {
            $array['error'] = 'Dados não enviados!';            
        }
        
        return $array;
    }

    public function read(Request $request) {
        $array = ['error' => ''];

        $page = intval($request->input('page', 0));
        $perpage = 2;

        $users = [];
        $userList = UserRelation::where('user_from', $this->loggedUser['id'])->get();
        foreach($userList as $userItem) {
            $users[] = $userItem['user_to'];
        }

        $users[] = $this->loggedUser['id'];

        $postList = Post::whereIn('id_user', $users)
        ->orderBy('created_at', 'desc')
        ->offset($page * $perpage)
        ->limit($perpage)
        ->get();

        $total = Post::whereIn('id_user', $users)->count();
        $pageCount = ($total / $perpage);

        $posts = $this->_postListToObjects($postList, $this->loggedUser['id']);

        $array['posts'] = $posts;
        $array['pageCount'] = $pageCount;
        $array['currentPage'] = $page;

        return $array;
    }

    private function _postListToObjects($postList, $loggedId) {
    foreach ($postList as $postKey => $postItem) {
        // Verificar se o post é meu
        $postList[$postKey]['mine'] = ($postItem['id_user'] == $loggedId);

        // Preencher informações do usuário
        $userInfo = User::find($postItem['id_user']);
        $userInfo['avatar'] = url('media/avatars/' . $userInfo['avatar']);
        $userInfo['cover'] = url('media/covers/' . $userInfo['cover']);
        $postList[$postKey]['user'] = $userInfo;

        // Preencher informações de like
        $likes = PostLike::where('id_post', $postItem['id'])->count();
        $postList[$postKey]['likeCount'] = $likes;

        $isLiked = PostLike::where('id_post', $postItem['id'])
            ->where('id_user', $loggedId) // <- corrigido aqui
            ->count();
        $postList[$postKey]['liked'] = ($isLiked > 0);

        // Preencher informações de comentários
        $comments = PostComment::where('id_post', $postItem['id'])->get();
        foreach ($comments as $commentKey => $comment) {
            $user = User::find($comment['id_user']);
            $user['avatar'] = url('media/avatars/' . $user['avatar']);
            $user['cover'] = url('media/covers/' . $user['cover']);
            $comments[$commentKey]['user'] = $user;
        }
        $postList[$postKey]['comments'] = $comments;
    }

    return $postList;
    }

    public function userFeed(Request $request, $id = false) {
        $array = ['error' => ''];

        if($id == false) {
            $id = $this->loggedUser['id'];
        }

        $page = intval($request->input('page', 0));
        $perpage = 2;

        //pegar os posts do usuário ordenados por data
        $postList = Post::where('id)user', $id)
        ->orderBy('created_at', 'desc')
        ->offset($page * $perpage)
        ->limit($perpage)
        ->get();

        $total = Post::where('id_user', $id)->count();
        $pageCount = ($total / $perpage);

        //preencher as informações adicionais
        $posts = $this->_postListToObjects($postList, $this->loggedUser['id']);

        $array['posts'] = $posts;
        $array['pageCount'] = $pageCount;
        $array['currentPage'] = $page;

        return $array;
    }
}
