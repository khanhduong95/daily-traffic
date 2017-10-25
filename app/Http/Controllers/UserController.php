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
            'email' => 'bail|required|email|unique:'.User::TABLE_NAME,
            'password' => 'bail|required|min:6|alpha_dash',
            'app_password' => 'bail|min:6|alpha_dash',
            'birthday' => 'bail|date_format:Y-m-d|before:now',
        ]);

		$user = new User;
		$user->email = $request->input('email');
		$user->name = $request->input('name');
		$user->birthday = $request->input('birthday');
		$user->phone = $request->input('phone');
		$user->password = $this->createPassword($request->input('password'));
		$user->app_password = $request->input('app_password') ? $this->createPassword($request->input('app_password')) : null;
		$user->save();

		return response(null, 201, ['Location' => $request->url().'/'.$user->id]);
	}

	public function getToken(Request $request)
	{		
		$email = $request->input('email');
		$password = $request->input('password');

        if (! $email || ! $password){
            try {
                $header = trim(substr($request->header('authorization'), strlen('Basic')+1));
                $parts = explode(':', base64_decode($header, true));
                $email = $parts[0];
                $password = $parts[1];
            }
            catch (Exception $e){}
        }

        if (! $email || ! $password) throw new UnauthorizedHttpException("Basic realm=\"Please enter your valid email and password.\"");

		$user = User::where('email', $email)->firstOrFail();

        $expires = intval(env('TOKEN_EXPIRE', 900));
        if ($this->checkPassword($password, $user->password)){
            if (! $user->token || ($expires > 0 && intval(hexdec(explode('.', $user->token)[0])) < strtotime('-'.$expires.' seconds'))){
                $user->token = dechex(time()).'.'.str_random().'.'.str_random();
            }
            $returnToken = $user->token;
            $fullPermission = true;
        }
        elseif ($user->app_password && $this->checkPassword($password, $user->app_password)){
            if (! $user->app_token || ($expires > 0 && intval(hexdec(explode('.', $user->app_token)[0])) < strtotime('-'.$expires.' seconds'))){
                $user->app_token = dechex(time()).'.'.str_random();
            }
            $returnToken = $user->app_token;
            $fullPermission = false;
        }
        else {
            throw new UnprocessableEntityHttpException('The password is incorrect.');
        }

        $user->save();

		return response()->json([
            'token' => $returnToken,
            'full_permission' => $fullPermission,
            '_links' => [
                'user' => route(User::TABLE_NAME.'.detail', ['id' => $user->id]),
            ],
        ]);
	}

	public function deleteToken(Request $request)
	{		
		$user = $request->user();
        $tokenDots = substr_count($user->current_token, '.');
        if ($tokenDots > 1)
            $user->token = null;

        $user->app_token = null;

        unset($user->current_token);
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
            'email' => 'bail|required|email|unique:'.User::TABLE_NAME.',email,'.$user->id,
            'birthday' => 'bail|date_format:Y-m-d|before:now',
        ]);

		$user->email = $request->input('email');
		$user->name = $request->input('name');
		$user->birthday = $request->input('birthday');
		$user->phone = $request->input('phone');
		$user->save();
		return response(null, 204);
	}

	public function updatePassword(Request $request)
	{        
		$user = $request->user();
		$this->authorize('write', $user);

		$this->validate($request, [
            'current_password' => 'bail|required',
            'new_password' => 'bail|min:6|alpha_dash',
            'new_app_password' => 'bail|min:6|alpha_dash',
        ]);

        if (! $this->checkPassword($request->input('current_password'), $user->password))
            throw new UnprocessableEntityHttpException('The current password is incorrect.');

        if ($request->has('new_password')){
            $user->password = $this->createPassword($request->input('new_password'));
        }
        elseif ($request->has('new_app_password')) {
            $user->app_password = $this->createPassword($request->input('new_app_password'));
        }
        else {
            $user->app_password = null;
        }

        unset($user->current_token);
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

	private function createPassword($password)
	{
		return app('hash')->make($password);
	}

	private function checkPassword($inputPassword, $correctPassword)
	{
		return app('hash')->check($inputPassword, $correctPassword);
	}
}
