<?php

namespace App\Http\Controllers;

use Exception;
use App\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $pageSize = $this->getPageSize($request->input('per_page'));
        $res = User::orderBy('id', 'desc')->paginate($pageSize);
        return response()->json($res);
    }

    public function add(Request $request)
    {
        $this->validate($request, [
            'email' => 'bail|required|email|unique:users',
            'password' => 'bail|required|min:6|alpha_dash',
            'birthday' => 'bail|date_format:Y-m-d|before:now',
        ]);

        $user = new User($request->only('email', 'name', 'birthday', 'phone'));
        $user->password = app('hash')->make($request->input('password'));
        
        $user->save();

        return response(null, 201, ['Location' => $request->url().'/'.$user->id]);
    }

    public function getToken(Request $request)
    {
        $email = $request->input('email');
        $password = $request->input('password');

        if (! $email || ! $password) {
            try {
                $header = trim(substr($request->header('authorization'), strlen('Basic')+1));
                $parts = explode(':', base64_decode($header, true));
                $email = $parts[0];
                $password = $parts[1];
            } catch (Exception $e) {
                throw new UnauthorizedHttpException('Basic realm="Please enter your valid email and password."');
            }
        }

        if (! $email || ! $password) {
            throw new UnauthorizedHttpException('Basic realm="Please enter your valid email and password."');
        }
        
        $user = User::where('email', $email)->firstOrFail();

        $expires = intval(env('TOKEN_EXPIRE', 900));
        if (app('hash')->check($password, $user->password)) {
            if (! $user->token ||
                ($expires > 0 &&
                 intval(explode('.', $user->token)[0]) < strtotime('-'.$expires.' seconds'))) {
                $user->token = time().'.'.dechex(time()).'.'.str_random();
            }
            
            $returnToken = $user->token;
        } else {
            throw new UnprocessableEntityHttpException('The password is incorrect.');
        }

        $user->save();

        return response()->json([
            'token' => $returnToken,
            '_links' => [
                'user' => route('users.detail', ['id' => $user->id]),
            ],
        ]);
    }

    public function deleteToken(Request $request)
    {
        $user = $request->user();
        $user->token = null;

        $user->save();
        return response(null, 204);
    }

    public function detail(Request $request, $id)
    {
        $user = User::findOrFail($id);
        return response()->json($user);
    }

    public function updateInfo(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $this->authorize('write', $user);

        $this->validate($request, [
            'email' => 'bail|required|email|unique:users,email,'.$user->id,
            'birthday' => 'bail|date_format:Y-m-d|before:now',
        ]);

        $user->fill($request->only('email', 'name', 'birthday', 'phone'));
        $user->save();
        return response(null, 204);
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();
        $this->authorize('write', $user);

        $this->validate($request, [
            'current_password' => 'bail|required',
            'new_password' => 'bail|required|min:6|alpha_dash',
        ]);

        if (! app('hash')->check($request->input('current_password'), $user->password)) {
            throw new UnprocessableEntityHttpException('The current password is incorrect.');
        }
        
        $user->password = app('hash')->make($request->input('new_password'));

        $user->save();
        return response(null, 204);
    }

    public function delete(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $this->authorize('write', $user);

        $user->delete();
        return response(null, 204);
    }
}
