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

class LogEntry extends Model
{
    protected $table = 'guildlogs';

    /**
     * @param int    $guild_id
     * @param string $message
     */
    public function create(int $guild_id, string $message)
    {
        $this->guild_id = $guild_id;
        $this->message  = $message;
        $this->save();
    }

    /**
     * @return string
     */
    public function getNiceDate(): string
    {
        $date = new DateTime($this->created_at);

        $date->setTimezone(new DateTimeZone(Auth::user()->timezone));

        if (12 === Auth::user()->clock) {
            return $date->format('F jS g:i a');
        }

        return $date->format('F jS H:i');
    }
}
