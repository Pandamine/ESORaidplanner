<?php

/**
 * This file is part of the ESO Raidplanner project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 3
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/ESORaidplanner/ESORaidplanner
 */

namespace App;

use DateTime;
use DateTimeZone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Class Event.
 */
class Event extends Model
{
    const STATUS_LOCKED   = 1;
    const STATUS_UNLOCKED = 0;

    /**
     * @return int
     */
    public function getTotalSignups(): int
    {
        return DB::table('signups')
            ->where('event_id', $this->id)
            ->count();
    }

    /**
     * @return string
     */
    public function getNiceDate(): string
    {
        $date = new DateTime($this->start_date);

        $date->setTimezone(new DateTimeZone(Auth::user()->timezone));

        if (12 === Auth::user()->clock) {
            return $date->format('F jS g:i a');
        }

        return $date->format('F jS H:i');
    }

    /**
     * @return string
     */
    public function getTypeName(): string
    {
        if (1 === $this->type) {
            return 'Trials';
        } elseif (2 === $this->type) {
            return 'Dungeons';
        } elseif (3 === $this->type) {
            return 'PvP';
        } elseif (4 === $this->type) {
            return 'Guild Meeting';
        } elseif (999 === $this->type) {
            return 'Other';
        }

        return 'Unknown';
    }

    /**
     * @return string
     */
    public function getTypeImage()
    {
        if (1 === $this->type) {
            return asset('img/Header_Event_Trials.jpg');
        } elseif (2 === $this->type) {
            return 'Dungeons';
        } elseif (3 === $this->type) {
            return 'PvP';
        } elseif (4 === $this->type) {
            return 'Guild Meeting';
        } elseif (999 === $this->type) {
            return 'Other';
        }

        return 'Unknown';
    }

    /**
     * @return bool
     */
    public function userIsSignedUp(int $user_id = null): bool
    {
        $result = Signup::query()
            ->where('event_id', $this->id)
            ->where('user_id', '=', $user_id ?? Auth::id())
            ->count();

        if (1 === $result) {
            return true;
        }

        return false;
    }

    /**
     * @param string $type
     *
     * @return mixed
     */
    public function getUserSignup(string $type)
    {
        $result = Signup::query()
            ->where('event_id', $this->id)
            ->where('user_id', '=', Auth::id())
            ->first();

        return $result->$type;
    }

    /**
     * @return Guild
     */
    public function getGuild(): Guild
    {
        return Guild::query()->find($this->guild_id);
    }

    /**
     * @return array
     */
    public function getSignups(): array
    {
        return DB::table('signups')
            ->where('event_id', $this->id);
    }

    public function getSignupsByRole(int $role_id): array
    {
        return Signup::query()
            ->where('event_id', '=', $this->id)
            ->where('role_id', '=', $role_id)
            ->orderBy('created_at', 'asc')
            ->get()->all() ?? [];
    }

    public function getComments(): array
    {
        return Comment::query()
            ->where('event_id', '=', $this->id)
            ->orderBy('created_at', 'desc')
            ->get()->all() ?? [];
    }

    /**
     * @return array
     */
    public function getSignUpIds(): array
    {
        $signups = Signup::query()->where('event_id', '=', $this->id)->get();

        $arr = [];

        foreach ($signups as $signup) {
            $arr[] = $signup->user_id;
        }

        return $arr;
    }

    /**
     * @param User           $user
     * @param int|null       $role_id
     * @param int|null       $class_id
     * @param array          $sets
     * @param Character|null $character
     */
    public function signup(User $user, int $role_id = null, int $class_id = null, array $sets = [], Character $character = null)
    {
        Signup::query()->where('event_id', '=', $this->id)
            ->where('user_id', '=', $user->id)
            ->delete();

        $sign           = new Signup();
        $sign->user_id  = $user->id;
        $sign->event_id = $this->id;

        if (null !== $character) {
            $sign->class_id     = $character->class;
            $sign->role_id      = $character->role;
            $sign->sets         = $character->sets;
            $sign->character_id = $character->id;
        } else {
            $sign->class_id = $class_id;
            $sign->role_id  = $role_id;

            if (count($sets) > 0) {
                $sign->sets = implode(', ', $sets);
            } else {
                $sign->sets = '';
            }
        }

        $sign->save();

        $log = new LogEntry();
        $log->create($this->guild_id, $user->name.' signed up for <a href="/g/'.$this->getGuildSlug().'/event/'.$this->id.'">'.$this->name.'</a>.');
    }

    public function signupOther(User $user, User $admin = null, int $role_id = null, int $class_id = null, array $sets = [], Character $character = null)
    {
        $admin = $admin ?? Auth::user();

        Signup::query()->where('event_id', '=', $this->id)
            ->where('user_id', '=', $user->id)
            ->delete();

        $sign           = new Signup();
        $sign->user_id  = $user->id;
        $sign->event_id = $this->id;

        if (null !== $character) {
            $sign->class_id     = $character->class;
            $sign->role_id      = $character->role;
            $sign->sets         = $character->sets;
            $sign->character_id = $character->id;
        } else {
            $sign->class_id = $class_id;
            $sign->role_id  = $role_id;

            if (count($sets) > 0) {
                $sign->sets = implode(', ', $sets);
            } else {
                $sign->sets = '';
            }
        }

        $sign->save();

        $log = new LogEntry();
        $log->create($this->guild_id,
            $admin->name.' signed up '.$user->name.' for <a href="/g/'.$this->getGuildSlug().'/event/'.$this->id.'">'.$this->name.'</a>.');
    }

    /**
     * @param User $user
     */
    public function signoff(User $user)
    {
        Signup::query()->where('event_id', '=', $this->id)
            ->where('user_id', '=', $user->id)
            ->delete();

        $log = new LogEntry();
        $log->create($this->guild_id, $user->name.' signed off for <a href="/g/'.$this->getGuildSlug().'/event/'.$this->id.'">'.$this->name.'</a>.');
    }

    /**
     * @param User      $user
     * @param User|null $admin
     */
    public function signoffOther(User $user, User $admin = null)
    {
        $admin = $admin ?? Auth::user();

        Signup::query()->where('event_id', '=', $this->id)
            ->where('user_id', '=', $user->id)
            ->delete();

        $log = new LogEntry();
        $log->create($this->guild_id, $admin->name.' signed off '.$user->name.' for <a href="/g/'.$this->getGuildSlug().'/event/'.$this->id.'">'.$this->name.'</a>.');
    }

    /**
     * @param User           $user
     * @param int|null       $role_id
     * @param int|null       $class_id
     * @param array          $sets
     * @param Character|null $character
     */
    public function editSignup(User $user, int $role_id = null, int $class_id = null, array $sets = [], Character $character = null)
    {
        $sign = Signup::query()->where('event_id', '=', $this->id)
            ->where('user_id', '=', $user->id)
            ->first();

        if (null !== $character) {
            $sign->class_id     = $character->class;
            $sign->role_id      = $character->role;
            $sign->sets         = $character->sets;
            $sign->character_id = $character->id;
        } else {
            $sign->class_id = $class_id;
            $sign->role_id  = $role_id;

            if (count($sets) > 0) {
                $sign->sets = implode(', ', $sets);
            } else {
                $sign->sets = '';
            }
            $sign->character_id = null;
        }

        $sign->save();
    }

    public function callEventCreationHooks()
    {
        $hooks = Hook::query()->where('call_type', '=', 1)->where('guild_id', '=', $this->guild_id)->get()->all();

        foreach ($hooks as $hook) {
            if ($hook->matchesEventTags($this)) {
                $hook->call($this);
            }
        }
    }

    /**
     * @param int $signup_id
     * @param int $status
     */
    public function setSignupStatus(int $signup_id, int $status)
    {
        $signup         = Signup::query()->find($signup_id);
        $signup->status = $status;
        $signup->save();
    }

    public function lock()
    {
        $this->locked = self::STATUS_LOCKED;
        $this->save();
    }

    public function unlock()
    {
        $this->locked = self::STATUS_UNLOCKED;
        $this->save();
    }

    /**
     * @return string
     */
    private function getGuildSlug(): string
    {
        return Guild::query()->find($this->guild_id)->slug;
    }
}
