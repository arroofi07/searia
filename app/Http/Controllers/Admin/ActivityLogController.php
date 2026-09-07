<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', ActivityLog::class);

        $query = ActivityLog::query()->with('user')->latest('id');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('action')) {
            $query->where('action', $request->string('action')->toString());
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->date('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->date('to'));
        }

        if ($request->filled('subject_type') && $request->filled('subject_id')) {
            $type = $request->string('subject_type')->toString();
            $query->where('subject_type', $type)
                ->where('subject_id', $request->integer('subject_id'));
        }

        return view('admin.activity-logs.index', [
            'logs' => $query->paginate(40)->withQueryString(),
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'email']),
            'actions' => ActivityLog::query()->distinct()->orderBy('action')->pluck('action'),
            'filters' => $request->only(['user_id', 'action', 'from', 'to', 'subject_type', 'subject_id']),
        ]);
    }

    public function show(ActivityLog $activityLog): View
    {
        $this->authorize('view', $activityLog);

        $activityLog->load('user');

        return view('admin.activity-logs.show', [
            'log' => $activityLog,
        ]);
    }

    public function forSubject(Request $request, string $subjectType, int $subjectId): View
    {
        $this->authorize('viewAny', ActivityLog::class);

        $class = Relation::getMorphedModel($subjectType) ?? $subjectType;
        if (! class_exists($class)) {
            abort(404);
        }

        $request->merge([
            'subject_type' => $class,
            'subject_id' => $subjectId,
        ]);

        return $this->index($request);
    }
}
