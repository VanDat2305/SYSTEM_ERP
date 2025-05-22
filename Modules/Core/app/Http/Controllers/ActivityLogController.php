<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::guard('sanctum')->user();   
        $query = Activity::query()
            ->with(['causer']) // eager load người tạo nếu có

            // Giới hạn truy vấn nếu không phải superadmin
            ->when(!$user->hasRole('superadmin'), fn($q) =>
                $q->where('causer_id', $user->id)
            )

            // Nếu là superadmin, có thể lọc theo causer_id thủ công
            ->when($user->hasRole('superadmin') && $request->filled('causer_id'), fn($q) =>
                $q->where('causer_id', $request->causer_id)
            )

            // lọc theo log_name (module: users.update, files.upload, etc.)
            ->when($request->log_name, fn($q) =>
                $q->where('log_name', $request->log_name)
            )

            // lọc theo keyword trong description
            ->when($request->search, fn($q) =>
                $q->where('description', 'like', '%' . $request->search . '%')
            )

            // lọc theo khoảng thời gian
            ->when($request->date_from, fn($q) =>
                $q->whereDate('created_at', '>=', $request->date_from)
            )
            ->when($request->date_to, fn($q) =>
                $q->whereDate('created_at', '<=', $request->date_to)
            )

            ->latest();

        return response()->json($query->paginate($request->get('per_page', 20)));
    }
}
