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
		$this->authorize('readList', Permission::class);

		$pageSize = $this->getPageSize($request->input('per_page'));
		$res = Permission::orderBy('id', 'desc')->paginate($pageSize);
		return response()->json($res);
	}

	public function indexByUser(Request $request, $id)
	{
        $user = User::findOrFail($id);
        if ($request->user()->id != $id)
            $this->authorize('readList', Permission::class);

		$pageSize = $this->getPageSize($request->input('per_page'));
		$res = Permission::orderBy('id', 'desc')
             ->where('user_id', $id)
             ->paginate($pageSize);
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
            'table_name' => 'bail|required|in:'.implode(',', $tables).'|unique:'.Permission::TABLE_NAME.',table_name,NULL,id,user_id,'.$id,
            'write' => 'bail|boolean',
        ]);
		$permission = new Permission;
		$permission->table_name = $request->input('table_name');

        if ($request->has('write'))
            $permission->write = $request->input('write');
        else
            $permission->write = false;            
        
		$permission->user_id = $id;
		$permission->save();

		return response(null, 201, ['Location' => $request->url().'/'.$permission->id]);
	}

	public function detail($id)
	{
		$permission = Permission::findOrFail($id);
		$this->authorize('read', $permission);
		return response()->json($permission);
	}

	public function update(Request $request, $id)
	{
		$this->authorize('write', Permission::class);
		$permission = Permission::findOrFail($id);

		$this->validate($request, [
            'write' => 'bail|boolean',
        ]);

        if ($request->has('write'))
            $permission->write = $request->input('write');
        else
            $permission->write = false;            

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
