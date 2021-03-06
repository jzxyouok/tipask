<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Services\Registrar;
use Bican\Roles\Models\Role;
use Illuminate\Http\Request;

use App\Http\Requests;
use Illuminate\Support\Facades\Config;

class UserController extends AdminController
{
    /*权限验证规则*/
    protected $validateRules = [
        'name' => 'required|max:100',
        'email' => 'required|email|max:255|unique:users',
        'password' => 'required|min:6|max:20',
    ];
    /**
     * 用户管理首页
     */
    public function index(Request $request)
    {
        $filter =  $request->all();

        $query = User::query();

        /*关键词过滤*/
        if( isset($filter['word']) && $filter['word'] ){
            $query->where(function($subQuery) use ($filter) {
                return $subQuery->where('name','like',$filter['word'].'%')
                         ->orWhere('email','like',$filter['word'].'%')
                         ->orWhere('mobile','like',$filter['word'].'%');
            });
        }

        /*注册时间过滤*/
        if( isset($filter['date_range']) && $filter['date_range'] ){
            $query->whereBetween('created_at',explode(" - ",$filter['date_range']));
        }

        /*状态过滤*/
        if( isset($filter['status']) && $filter['status'] > -1 ){
            $query->where('status','=',$filter['status']);
        }

        $users = $query->orderBy('updated_at','desc')->paginate(Config::get('tipask.admin.page_size'));
        return view('admin.user.index')->with('users',$users)->with('filter',$filter);
    }

    /**
     * 显示用户添加页面
     */
    public function create()
    {
        $roles = Role::orderby('name','asc')->get();

        return view('admin.user.create')->with(compact('roles'));
    }

    /**
     * 保存创建用户信息
     */
    public function store(Request $request,Registrar $registrar)
    {

        $request->flash();
        $this->validate($request,$this->validateRules);

        $formData = $request->all();
        $formData['status'] = 1;
        $formData['visit_ip'] = $request->getClientIp();

        $user = $registrar->create($formData);
        $user->attachRole($request->input('role_id'));
        return $this->success(route('admin.user.index'),'用户添加成功！');

    }


    /**
     * 显示用户编辑页面
     */
    public function edit($id)
    {
        $user = User::find($id);
        $roles = Role::orderby('name','asc')->get();
        return view('admin.user.edit')->with('user',$user)->with('roles',$roles);
    }

    /**
     * 保存用户修改
     */
    public function update(Request $request, $id)
    {
        $request->flash();
        $user = User::find($id);
        if(!$user){
            abort(404);
        }
        $this->validateRules['name'] = 'required|email|max:255|unique:users,name,'.$user->id;
        $this->validateRules['email'] = 'required|email|max:255|unique:users,email,'.$user->id;
        $this->validateRules['password'] = 'sometimes|min:6';
        $password = $request->input('password');
        if($password)
        {
            $user->password = bcrypt($password);
        }
        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $user->save();
        $user->detachAllRoles();
        $user->attachRole($request->input('role_id'));
        return $this->success(route('admin.user.index'),'用户修改成功');
    }

    /*用户审核*/
    public function verify(Request $request)
    {
        $userIds = $request->input('id');
        User::whereIn('id',$userIds)->update(['status'=>1]);
        return $this->success(route('admin.user.index').'?status=0','用户审核成功');

    }

    /**
     * 删除用户
     */
    public function destroy(Request $request)
    {
        $userIds = $request->input('id');
        User::destroy($userIds);
        return $this->success(route('admin.user.index'),'用户删除成功');

    }
}
