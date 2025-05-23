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
            ->when($request->created_at_from, fn($q) =>
                $q->whereDate('created_at', '>=', $request->created_at_from)
            )
            ->when($request->created_at_to, fn($q) =>
                $q->whereDate('created_at', '<=', $request->created_at_to)
            )
            ->when($request->search_field, function ($q) use ($request) {
                $searchField = $request->search_field;
                $searchValue = $request->search_value;
                // dd($searchField, $searchValue);
                if (in_array($searchField, ['log_name', 'description'])) {
                    $q->where($searchField,  'like', '%' . $searchValue . '%');
                } else {
                    $q->whereHas('causer', function ($query) use ($searchField, $searchValue) {
                        $query->where($searchField, 'like', '%' . $searchValue . '%');
                    });
                }
            })

            ->latest();

        return response()->json($query->paginate($request->get('per_page', 20)));
    }
}
