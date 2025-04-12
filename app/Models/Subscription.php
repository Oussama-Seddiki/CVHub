<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Subscription extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'transaction_id',
        'payment_method',
        'amount',
        'status',
        'expires_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'expires_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    /**
     * Get the user that owns the subscription.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the subscription is active.
     */
    public function isActive()
    {
        return $this->status === 'active' && $this->expires_at > Carbon::now();
    }

    /**
     * Activate the subscription.
     */
    public function activate()
    {
        $this->update([
            'status' => 'active',
            'expires_at' => Carbon::now()->addYear(),
        ]);
        
        $this->user->updateSubscriptionStatus();
    }

    /**
     * Deactivate the subscription.
     */
    public function deactivate()
    {
        $this->update([
            'status' => 'expired',
        ]);
        
        $this->user->updateSubscriptionStatus();
    }

    /**
     * Get the days remaining in the subscription.
     */
    public function daysRemaining()
    {
        if (!$this->isActive()) {
            return 0;
        }
        return now()->diffInDays($this->expires_at, false);
    }

    /**
     * Check if the subscription is expired.
     */
    public function isExpired()
    {
        return $this->status === 'expired' || $this->expires_at <= Carbon::now();
    }

    /**
     * Check if the subscription is pending.
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the subscription is expiring soon (within 7 days).
     */
    public function isExpiringSoon()
    {
        if (!$this->isActive()) {
            return false;
        }
        return $this->daysRemaining() <= 7;
    }

    /**
     * Renew the subscription for another year.
     */
    public function renew()
    {
        $this->update([
            'status' => 'active',
            'expires_at' => Carbon::parse($this->expires_at)->addYear(),
        ]);
        
        $this->user->updateSubscriptionStatus();
    }

    /**
     * Cancel the subscription.
     */
    public function cancel()
    {
        $this->update([
            'status' => 'cancelled',
        ]);
        
        $this->user->updateSubscriptionStatus();
    }

    /**
     * Scope a query to only include active subscriptions.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('expires_at', '>', Carbon::now());
    }

    /**
     * Scope a query to only include expired subscriptions.
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'expired')
            ->orWhere(function($query) {
                $query->where('status', 'active')
                    ->where('expires_at', '<=', Carbon::now());
            });
    }

    protected static function booted()
    {
        // Update user subscription status after subscription is saved
        static::saved(function ($subscription) {
            $subscription->user->updateSubscriptionStatus();
        });

        // Update user subscription status after subscription is deleted
        static::deleted(function ($subscription) {
            $subscription->user->updateSubscriptionStatus();
        });
    }
}
