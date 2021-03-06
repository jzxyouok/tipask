<?php

namespace App\Models;
use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Bican\Roles\Traits\HasRoleAndPermission;
use Bican\Roles\Contracts\HasRoleAndPermission as HasRoleAndPermissionContract;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class User extends Model implements AuthenticatableContract,
    AuthorizableContract,
    CanResetPasswordContract,
    HasRoleAndPermissionContract
{
    use Authenticatable, CanResetPassword,HasRoleAndPermission;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'email', 'password','status'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['password', 'remember_token'];

    public static function getAvatarPath($userId,$size='big',$ext='jpg')
    {
        $avatarDir = self::getAvatarDir($userId);
        $avatarFileName = self::getAvatarFileName($userId,$size);
        return $avatarDir.'/'.$avatarFileName.'.'.$ext;
    }

    /**
     * 获取用户头像存储目录
     * @param $user_id
     * @return string
     */
    public static function getAvatarDir($userId,$rootPath='avatars')
    {
        $userId = sprintf("%09d", $userId);
        return $rootPath.'/'.substr($userId, 0, 3) . '/' . substr($userId, 3, 2) . '/' . substr($userId, 5, 2);
    }


    /**
     * 获取头像文件命名
     * @param string $size
     * @return mixed
     */
    public static function getAvatarFileName($userId,$size='big')
    {
        $avatarNames = [
            'small'=>'user_small_'.$userId,
            'middle'=>'user_middle_'.$userId,
            'big'=>'user_big_'.$userId,
            'origin'=>'user_origin_'.$userId
        ];
       return $avatarNames[$size];
    }


    /**
     * 从缓存中获取用户数据，主要用户问答文章等用户数据显示
     * @param $userId
     * @return mixed
     */
    public static function findFromCache($userId)
    {

        $data = Cache::remember('user_cache_'.$userId,Config::get('tipask.user_cache_time'),function() use($userId) {
            return  self::select('name','title','gender')->find($userId);
        });

        return $data;
    }

    /*搜索*/
    public static function search($word,$size=16)
    {
        $list = self::where('name','like',"$word%")->paginate($size);
        return $list;
    }


    /**
     *获取用户数据
     * @param $userId
     */
    public function userData()
    {
        return $this->hasOne('App\Models\UserData');
    }


    /*用户认证信息*/
    public function authentication()
    {
        return $this->hasOne('App\Models\Authentication');
    }

    /**
     * 获取用户问题
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function questions()
    {
        return $this->hasMany('App\Models\Question');
    }

    /**
     * 获取用户回答
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function answers()
    {
        return $this->hasMany('App\Models\Answer');
    }


    /**
     * 获取用户文章
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function articles()
    {
        return $this->hasMany('App\Models\Article');
    }

    /**
     * 获取用户动态
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function doings()
    {
        return $this->hasMany('App\Models\Doing');
    }


    /*获取用户收藏*/
    public function collections()
    {
        return $this->hasMany('App\Models\Collection');
    }


    /*用户关注*/
    public function attentions()
    {
        return $this->hasMany('App\Models\Attention');
    }

    /*用户粉丝*/
    public function followers()
    {
        return $this->morphToMany('App\Models\UserData', 'source','attentions','source_id','user_id');
    }

    /*邀请的回答*/
    public function questionInvitations()
    {
        return $this->hasMany('App\Models\QuestionInvitation');
    }

    /*我的商品兑换*/
    public function exchanges()
    {
        return $this->hasMany('App\Models\Exchange');
    }


    /*是否回答过问题*/
    public function isAnswered($questionId)
    {
        return boolval($this->answers()->where('question_id','=',$questionId)->count());
    }


    /*是否已经收藏过问题或文章*/
    public function isCollected($source_type,$source_id)
    {
        return $this->collections()->where('source_type','=',$source_type)->where('source_id','=',$source_id)->first();
    }

    /*是否已关注问题、用户*/
    public function isFollowed($source_type,$source_id)
    {
        return boolval($this->attentions()->where('source_type','=',$source_type)->where('source_id','=',$source_id)->count());
    }












}
