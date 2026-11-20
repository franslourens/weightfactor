<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminController extends Controller
{
    /**
     * List every account and which ones still have a live login.
     */
    public function index(): View
    {
        abort_unless(auth()->user()?->isOwner(), 403);

        $cutoff = now()->subMinutes((int) config('session.lifetime'))->getTimestamp();
        $activity = DB::table(config('session.table', 'sessions'))
            ->select('user_id', DB::raw('max(last_activity) as last_activity'))
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->get()
            ->mapWithKeys(fn ($row) => [(int) $row->user_id => (int) $row->last_activity]);

        $rows = User::query()
            ->orderBy('id')
            ->get()
            ->map(function (User $user) use ($activity, $cutoff) {
                $last = $activity->get($user->id);

                return [
                    'user' => $user,
                    'online' => $last !== null && $last >= $cutoff,
                    'lastActive' => $last === null ? null : Carbon::createFromTimestamp($last),
                ];
            })
            ->sortBy([
                fn (array $row) => $row['online'] ? 0 : 1,
                fn (array $row) => mb_strtolower($row['user']->name),
            ])
            ->values();

        return view('admin', [
            'rows' => $rows,
            'online' => $rows->where('online', true)->count(),
        ]);
    }
}
