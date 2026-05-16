<?php
// app/Http/Controllers/NotificationController.php
namespace App\Http\Controllers;

use App\Models\Notification;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $query = Notification::with('member:id,first_name,last_name')
            ->where(fn($q) =>
                $q->where('user_id', $request->user()->id)
                  ->orWhereNull('user_id')
            )
            ->when($request->is_read !== null, fn($q) =>
                $q->where('is_read', filter_var($request->is_read, FILTER_VALIDATE_BOOLEAN))
            )
            ->when($request->type, fn($q, $t) => $q->where('type', $t))
            ->when($request->priority, fn($q, $p) => $q->where('priority', $p))
            ->orderByDesc('created_at');

        return response()->json([
            'data' => $query->paginate(50)->items(),
            'unread_count' => Notification::where('is_read', false)->count(),
        ]);
    }

    public function read(Notification $notification)
    {
        $notification->markAsRead();
        return response()->json(['message' => 'تم تحديده كمقروء.']);
    }

    public function readAll()
    {
        Notification::where('is_read', false)->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
        return response()->json(['message' => 'تم تحديد الكل كمقروء.']);
    }

    public function destroy(Notification $notification)
    {
        $notification->delete();
        return response()->json(['message' => 'تم حذف الإشعار.']);
    }

    public function sendReminder(Request $request, WhatsAppService $wa)
    {
        $data = $request->validate([
            'member_id' => 'required|exists:members,id',
            'channel'   => 'required|in:whatsapp,system',
            'message'   => 'required|string|max:1000',
        ]);

        if ($data['channel'] === 'whatsapp') {
            $wa->sendMessage($data['member_id'], $data['message']);
        }

        Notification::create([
            'member_id' => $data['member_id'],
            'type'      => 'compliance',
            'priority'  => 'normal',
            'title'     => 'تذكير يدوي',
            'body'      => $data['message'],
        ]);

        return response()->json(['message' => 'تم الإرسال بنجاح.']);
    }
}

// ─────────────────────────────────────────────────

// app/Http/Controllers/UserController.php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        return response()->json(User::orderByDesc('created_at')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'email'       => 'required|email|unique:users',
            'phone'       => 'required|string|unique:users',
            'role'        => 'required|in:admin,trainer,reception',
            'governorate' => 'required|string',
            'password'    => 'required|min:8',
        ]);

        $data['password'] = Hash::make($data['password']);
        $user = User::create($data);

        return response()->json([
            'id'      => $user->id,
            'message' => 'تم إضافة المستخدم بنجاح.',
        ], 201);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'        => 'sometimes|string|max:100',
            'phone'       => "sometimes|string|unique:users,phone,{$user->id}",
            'role'        => 'sometimes|in:admin,trainer,reception',
            'governorate' => 'sometimes|string',
        ]);

        $user->update($data);
        return response()->json(['message' => 'تم التحديث.']);
    }

    public function destroy(User $user)
    {
        if ($user->id === request()->user()->id) {
            return response()->json(['message' => 'لا يمكنك حذف حسابك الخاص.'], 422);
        }
        $user->delete();
        return response()->json(['message' => 'تم حذف المستخدم.']);
    }

    public function toggleActive(User $user)
    {
        $user->update(['is_active' => !$user->is_active]);
        $status = $user->is_active ? 'مفعّل' : 'معطّل';
        return response()->json(['message' => "تم تحديث الحساب: {$status}."]);
    }
}

// ─────────────────────────────────────────────────

// app/Http/Controllers/SettingsController.php
namespace App\Http\Controllers;

use App\Models\CenterSetting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        return response()->json(CenterSetting::first());
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'center_name'        => 'sometimes|string|max:200',
            'governorate'        => 'sometimes|string',
            'address'            => 'sometimes|nullable|string',
            'phone'              => 'sometimes|nullable|string',
            'email'              => 'sometimes|nullable|email',
            'primary_color'      => 'sometimes|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'working_hours'      => 'sometimes|array',
            'accept_new_members' => 'sometimes|boolean',
        ]);

        $settings = CenterSetting::firstOrCreate([]);
        $settings->update($data);

        return response()->json(['message' => 'تم حفظ الإعدادات.']);
    }
}
