<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use App\Models\UserRelation;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\ImageManagerStatic as Image;
use Intervention\Image\ImageManager;



class UserController extends Controller
{
    private $loggedUser;

    public  function __construct() {
        $this->middleware('auth:api');

        $this->loggedUser = Auth::user();
    }

    public function update(Request $request) {
        $array = ['error' => ''];

        $name = $request->input('name');
        $email = $request->input('email');
        $birthdate = $request->input('birthdate');
        $city = $request->input('city');
        $work = $request->input('work');
        $password = $request->input('password');
        $password_confirm = $request->input('password_confirm');
        
        $user = User::find($this->loggedUser['id']);

        if($name) {
            $user->name = $name;
        }

        if($email) {
            if($email != $user->email) {
                $emailExists = User::where('email', $email)->count();
                if($emailExists === 0) {
                    $user->email = $email;
                } else {
                    $array['error'] = 'E-mail já existe!';
                    return $array;
                }
            }
        }

        if($birthdate) {
            if(strtotime($birthdate) === false) {
                $array['error'] = 'Data de nascimento inválida!';
                return $array;
            }
            $user->birthdate = $birthdate;
        }

        if($city) {
            $user->city = $city;
        }

        if($work) {
            $user->work = $work;
        }

        if($password && $password_confirm) {
            if($password === $password_confirm) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $user->password = $hash;
            } else {
                $array['error'] = 'As senhas não batem!';
            }
        }

        $user->save();

        return $array;
    }

    public function updateAvatar(Request $request) {
        $array = ['error' => ''];
        $allowedTypes = ['image/jpg', 'image/jpeg', 'image/png'];

        $image = $request->file('avatar');

        if($image) {
            if(in_array($image->getClientMimeType(), $allowedTypes)) {

                $filename = md5(time().rand(0, 9999)).'jpg';

                $destinyPath = public_path('/media/avatars');

                if (!file_exists($destinyPath)) {
                    mkdir($destinyPath, 0755, true);
                }

                $manager = ImageManager::gd();

                $img = $manager->read($image->path())
                    ->cover(200, 200)
                    ->save($destinyPath . '/' . $filename);

                $user = User::find($this->loggedUser['id']);
                $user->avatar = $filename;
                $user->save();

                $array['url'] = url('/media/avatars/'.$filename);
            } else {
                $array['error'] = 'Arquivo não suportado!';
                return $array;
            }

        } else {
            $array['error'] = 'Arquivo não enviado!';
            return $array;
        }

        return $array;
    }

    public function updateCover(Request $request) {
        $array = ['error' => ''];
        $allowedTypes = ['image/jpg', 'image/jpeg', 'image/png'];

        $image = $request->file('cover');

        if($image) {
            if(in_array($image->getClientMimeType(), $allowedTypes)) {

                $filename = md5(time().rand(0, 9999)).'jpg';

                $destinyPath = public_path('/media/covers');

                if (!file_exists($destinyPath)) {
                    mkdir($destinyPath, 0755, true);
                }

                $manager = ImageManager::gd();

                $img = $manager->read($image->path())
                    ->cover(850, 310)
                    ->save($destinyPath . '/' . $filename);

                $user = User::find($this->loggedUser['id']);
                $user->cover = $filename;
                $user->save();

                $array['url'] = url('/media/covers/'.$filename);
            } else {
                $array['error'] = 'Arquivo não suportado!';
                return $array;
            }

        } else {
            $array['error'] = 'Arquivo não enviado!';
            return $array;
        }

        return $array;
    }

    public function read($id = false) {
        $array = ['error' => ''];

        if($id) {
            $info = User::find($id);
            if(!$info) {
                $array['error'] = 'Usuário inexistente!';
                return $array;
            }
        } else {
            $info = $this->loggedUser;
        }

        $info['avatar'] = url('media/avatars/'.$info['avatar']);
        $info['cover'] = url('media/covers/'.$info['cover']);

        $info['me'] = ($info['id'] == $this->loggedUser['id']) ? true : false;

        $datefrom = new \DateTime($info['birthdate']);
        $dateTo = new \DateTime('today');
        $info['age'] = $datefrom->diff($dateTo)->y;

        $info['followers'] = UserRelation::where('user_to', $info['id'])->count();
        $info['following'] = UserRelation::where('user_from', $info['id'])->count();

        $info['photoCount'] = Post::where('id_user', $info['id'])
        ->where('type', 'photo')
        ->count();

        $hasRelation = UserRelation::where('user_from', $this->loggedUser['id'])
        ->where('user_to', $info['id'])
        ->count();
        $info['isFollowing'] = ($hasRelation > 0) ? true : false;

        $array['data'] = $info;

        return $array;
    }
}
