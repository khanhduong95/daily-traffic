<?php

namespace App\Http\Controllers;

use Exception;
use App\User;
use App\Exceptions\IncorrectPasswordException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Rych\Random\Random;

class UserController extends Controller
{

	public function register(Request $request)
	{
		$this->validate($request, [
					   'email' => 'bail|required|email|unique:'.User::TABLE_NAME,
					   'password' => 'bail|required|confirmed|min:6'
					   ]);

		$user = new User;
		$user->email = $request->input('email');
		$user->name = $request->input('name');
		$user->birthday = $request->input('birthday');
		$user->phone = $request->input('phone');
		$user->password = Hash::make($request->input('password'));
		$user->save();

		return $this->renderJson('');
	}

	public function login(Request $request)
	{		
		$email = $request->header('email');
		$password = $request->header('password');

		if (! $email || !$password){
			$this->validate($request, [
						   'email' => 'bail|required|exists:'.User::TABLE_NAME,
						   'password' => 'bail|required|'
						   ]);
		}

		$user = User::where('email', $email)->first();
		if (! $user) throw new Exception('User not found.');

		if (! Hash::check($password, $user->password)) throw new IncorrectPasswordException;
		if (! $user->api_token) 
			$user->api_token = dechex(time()).(new Random)->getRandomString(16);

		$user->save();
		$user->token = $user->api_token;
		return $this->renderJson($user);
	}

	public function detail($id)
	{
		$user = User::find($id);
		if (! $user) throw new Exception('User not found.');
		return $this->renderJson($user);
	}

	public function updateInfo(Request $request)
	{
		$user = $request->user();

		$this->validate($request, [
					   'email' => 'bail|required|email|unique:'.User::TABLE_NAME.',email,'.$user->id,
					   'name' => 'bail',
					   'phone' => 'bail|numeric|digits_between:8,15',
					   'birthday' => 'bail|date_format:d-m-Y|before:'.date('d-m-Y')
					   ]);

		$user->email = $request->input('email');
		$user->name = $request->input('name');
		$user->birthday = $request->input('birthday');
		$user->phone = $request->input('phone');

		return $this->renderJson($user);
	}

	public function updatePassword(Request $request)
	{
		$user = $request->user();
		$this->validate($request, [
					   'current_password' => 'bail|required|min:6',
					   'new_password' => 'bail|required|confirmed|min:6'
					   ]);
		if (! Hash::check($request->input('current_password'), $user->password)) throw new IncorrectPasswordException;

		$user->password = Hash::make($request->input('new_password'));
		$user->save();
		return $this->renderJson('');
	}

	public function logout(Request $request)
	{
		$user = $request->user();
		$user->api_token = null;
		$user->save();
		return $this->renderJson('');
	}

	public function delete(Request $request)
	{
		$user = $request->user();
		$user->delete();
		return $this->renderJson('');
	}

}
