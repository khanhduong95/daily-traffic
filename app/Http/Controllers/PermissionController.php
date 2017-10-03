<?php

namespace App\Http\Controllers;

use Exception;
use App\User;
use App\Permission;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
	public function index(Request $request)
	{
		$this->authorize('read', Permission::class);

		$pageSize = $this->getPageSize($request->input('page_size'));
		$res = Permission::orderBy('id', 'desc')->paginate($pageSize);
		return response()->json($res);
	}

	public function indexByUser(Request $request, $id)
	{
		$this->authorize('read', Permission::class);

		$pageSize = $this->getPageSize($request->input('page_size'));
		$res = Permission::orderBy('id', 'desc')->paginate($pageSize);
		return response()->json($res);
	}

	public function addByUser(Request $request, $id)
	{
		$this->authorize('write', Permission::class);
		User::findOrFail($id);

		$tables = [];
		foreach (Permission::$models as $mk){
			$tables[] = $mk::TABLE_NAME;
		}
		$this->validate($request, [
            'table_name' => 'bail|required|in:'implode(',',$tables).'|unique:'.Permission::TABLE_NAME.',table_name,NULL,id,user_id,'.$id,
            'write' => 'bail|required|boolean'
        ]);
		$permission = new Permission;
		$permission->table_name = $request->input('table_name');
		$permission->write = $request->input('write');
		$permission->user_id = $id;
		$permission->save();

		return response(null, 201, ['Location' => '/api/permission/'.$permission->id]);
	}

	public function detail($id)
	{
		$this->authorize('write', Permission::class);
		$permission = Permission::findOrFail($id);
		return response()->json($permission);
	}

	public function update(Request $request, $id)
	{
		$this->authorize('write', Permission::class);
		$permission = Permission::findOrFail($id);

		$this->validate($request, [
            'write' => 'bail|required|boolean'
        ]);

		$permission->write = $request->input('write');
		$permission->save();
		return response(null, 204);
	}

	public function delete(Request $request, $id)
	{
		$this->authorize('write', Permission::class);
		$permission = Permission::findOrFail($id);

		$permission->delete();
		return response(null, 204);
	}
}
