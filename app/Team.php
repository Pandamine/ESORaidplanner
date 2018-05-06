<?php
/**
 * Created by PhpStorm.
 * User: woeler
 * Date: 06.05.18
 * Time: 15:30
 */

namespace App;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Team extends Model
{
    protected $fillable = ['guild_id', 'name'];

    public function getMembers(): array
    {
        $members = DB::table('teams_users')
            ->select(['teams_users.*', 'users.name'])
            ->join('users', 'teams_users.user_id', '=', 'users.id')
            ->where('team_id', '=', $this->id)
            ->orderBy('users.name')
            ->get()->all();

        return $members ?? [];
    }

    public function addMember(int $user_id, int $class_id, int $role_id, array $sets = [])
    {
        $count = DB::table('teams_users')
            ->where('team_id', '=', $this->id)
            ->where('user_id', '=', $user_id)
            ->count();

        if (count($sets) > 0) {
            $sets_s = implode(', ', $sets);
        } else {
            $sets_s = '';
        }

        if ($count === 0) {
            DB::table('teams_users')->insert([
                'user_id' => $user_id,
                'class_id' => $class_id,
                'role_id' => $role_id,
                'sets' => $sets_s,
            ]);
        } else {
            DB::table('teams_users')
                ->where('team_id', '=', $this->id)
                ->where('user_id', '=', $user_id)
                ->update([
                'user_id' => $user_id,
                'class_id' => $class_id,
                'role_id' => $role_id,
                'sets' => $sets_s,
            ]);
        }
    }

    public function removeMember(int $user_id)
    {
        DB::table('teams_users')
            ->where('team_id', '=', $this->id)
            ->where('user_id', '=', $user_id)
            ->delete();
    }
}