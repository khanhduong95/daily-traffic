<?php

namespace App\Http\Controllers;

use Exception;
use App\User;
use App\Exceptions\IncorrectPasswordException;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserController extends Controller
{
	public function index(Request $request)
	{
		$this->authorize('readList', User::class);

		$pageSize = $this->getPageSize($request->input('page_size'));
		$res = User::orderBy('id', 'desc')->paginate($pageSize);
		return response()->json($res);
	}

	public function add(Request $request)
	{
		$this->validate($request, [
            'email' => 'bail|required|email|unique:'.User::TABLE_NAME,
            'password' => 'bail|required|min:6|alpha_dash',
            'application_password' => 'bail|min:6|alpha_dash',
            'birthday' => 'bail|date_format:d-m-Y|before:'.date('d-m-Y'),
        ]);

		$user = new User;
		$user->email = $request->input('email');
		$user->name = $request->input('name');
		$user->birthday = $request->input('birthday');
		$user->phone = $request->input('phone');
		$user->password = app('hash')->make($request->input('password'));
		$user->application_password = $request->input('application_password') ? app('hash')->make($request->input('application_password')) : null;
		$user->save();

		return response(null, 201, ['Location' => '/api/user/'.$user->id]);
	}

	public function current(Request $request)
	{
		return response()->json($request->user());
	}

	public function getToken(Request $request)
	{		
		$email = $request->header('email');
		$password = $request->header('password');

        if (! $email || ! $password){
            $email = $request->input('email');
            $password = $request->input('password');
        }
		$user = User::where('email', $email)->firstOrFail();

		$this->checkPassword($password, $user->password);

        $user->api_token = dechex(time()).str_random(11);
        $user->save();

		return response()->json(['token' => $user->api_token]);
	}

	public function deleteToken(Request $request)
	{		
		$user = $request->user();
        $user->api_token = null;
        $user->save();

		return response(null, 204);
	}

	public function detail($id)
	{
		$user = User::findOrFail($id);
		return $this->renderJson($user);
	}

	public function updateInfo(Request $request, $id)
	{
		$user = User::findOrFail($id);
		$this->authorize('write', $user);

		$this->validate($request, [
            'email' => 'bail|required|email|unique:'.User::TABLE_NAME.',email,'.$user->id,
            'birthday' => 'bail|date_format:d-m-Y|before:'.date('d-m-Y')
        ]);

		$user->email = $request->input('email');
		$user->name = $request->input('name');
		$user->birthday = $request->input('birthday');
		$user->phone = $request->input('phone');
		$user->save();
		return response(null, 204);
	}

	public function updatePassword(Request $request, $id)
	{        
		$email = $request->header('email');
		$password = $request->header('password');

        if (! $email || ! $password){
            $email = $request->input('email');
            $password = $request->input('password');
        }
		$currentUser = User::where('email', $email)->firstOrFail();

		$this->checkPassword($password, $currentUser->password);

		$user = User::findOrFail($id);
		$this->authorizeForUser($currentUser, 'write', $user);

		$this->validate($request, [
            'new_password' => 'bail|required|min:6|alpha_dash',
            'new_application_password' => 'bail|min:6|alpha_dash',
        ]);

		$user->password = app('hash')->make($request->input('new_password'));
		$user->application_password = $request->input('application_password') ? app('hash')->make($request->input('application_password')) : null;
		$user->save();
		return response(null, 204);
	}

	public function delete(Request $request, $id)
	{
		$email = $request->header('email');
		$password = $request->header('password');

        if (! $email || ! $password){
            $email = $request->input('email');
            $password = $request->input('password');
        }
		$currentUser = User::where('email', $email)->firstOrFail();

		$this->checkPassword($password, $currentUser->password);

		$user = User::findOrFail($id);
		$this->authorizeForUser($currentUser, 'write', $user);

		$user->delete();
		return response(null, 204);
	}

	private function checkPassword($inputPassword, $correctPassword)
	{
		if (! app('hash')->check($inputPassword, $correctPassword)) 
			throw new IncorrectPasswordException;		
	}
}
