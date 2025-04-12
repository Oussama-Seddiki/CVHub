<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class SubscriptionController extends Controller
{
    /**
     * عرض صفحة الاشتراكات
     */
    public function index()
    {
        $user = Auth::user();
        $activeSubscription = $user->activeSubscription();
        
        // سعر الاشتراك السنوي بالدينار الجزائري
        $subscriptionCost = 2000.00;
        
        return Inertia::render('Subscription/Index', [
            'activeSubscription' => $activeSubscription,
            'subscriptionCost' => $subscriptionCost
        ]);
    }

    /**
     * إنشاء اشتراك جديد
     */
    public function create(Request $request)
    {
        $user = Auth::user();
        
        // إنشاء اشتراك جديد بحالة معلقة
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'amount' => 2000.00,
            'status' => 'pending',
        ]);
        
        return redirect()->route('subscription.payment', $subscription);
    }

    /**
     * عرض صفحة الدفع
     */
    public function showPayment(Subscription $subscription)
    {
        // التحقق من أن الاشتراك ينتمي للمستخدم الحالي
        if ($subscription->user_id !== Auth::id()) {
            abort(403);
        }
        
        return Inertia::render('Subscription/Payment', [
            'subscription' => $subscription
        ]);
    }

    /**
     * محاكاة الدفع (للتطوير فقط)
     */
    public function simulatePayment(Subscription $subscription)
    {
        // التحقق من أن الاشتراك ينتمي للمستخدم الحالي
        if ($subscription->user_id !== Auth::id()) {
            abort(403);
        }
        
        return Inertia::render('Subscription/SimulatePayment', [
            'subscription' => $subscription
        ]);
    }

    /**
     * تأكيد الدفع وتفعيل الاشتراك
     */
    public function confirmPayment(Subscription $subscription)
    {
        // التحقق من أن الاشتراك ينتمي للمستخدم الحالي
        if ($subscription->user_id !== Auth::id()) {
            abort(403);
        }
        
        // تحديث حالة الاشتراك إلى نشط وتعيين تاريخ انتهاء الصلاحية بعد سنة
        $subscription->transaction_id = 'sim_' . time();
        $subscription->status = 'active';
        $subscription->expires_at = Carbon::now()->addYear();
        $subscription->save();
        
        // تحديث حالة اشتراك المستخدم
        $user = Auth::user();
        $user->updateSubscriptionStatus();
        
        // إعادة توجيه المستخدم إلى صفحة الاشتراكات مع رسالة نجاح
        return redirect()->route('subscription')->with('success', 'تم تفعيل اشتراكك بنجاح!');
    }

    /**
     * التحقق من حالة الاشتراك
     */
    public function checkStatus()
    {
        $user = Auth::user();
        $activeSubscription = $user->activeSubscription();
        
        return response()->json([
            'hasActiveSubscription' => !is_null($activeSubscription),
            'subscription' => $activeSubscription,
        ]);
    }
}
