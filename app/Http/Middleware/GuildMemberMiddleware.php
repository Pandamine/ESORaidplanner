<?php

namespace App\Http\Middleware;

use App\Guild;
use Auth;
use Closure;

class GuildMemberMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $slug = $request->route('slug');
        /** @var Guild $guild */
        $guild = Guild::query()->where('slug', '=', $slug)->first();

        if (null === $guild) {
            return redirect('/');
        }
        if (0 === $guild->userStatus(Auth::user())) {
            return view('guild.guild_awaiting_confirmation', compact('guild'));
        }
        if (!$guild->isMember(Auth::user())) {
            return view('guild.guild_apply', compact('guild'));
        }

        return $next($request);
    }
}
