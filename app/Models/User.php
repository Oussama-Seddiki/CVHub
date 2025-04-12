<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Carbon\Carbon;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_subscribed',
        'subscription_ends_at',
        'subscription_status'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_subscribed' => 'boolean',
        'subscription_ends_at' => 'datetime'
    ];

    /**
     * Get the subscriptions for the user.
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get all CVs created by the user.
     */
    public function cvs()
    {
        return $this->hasMany(CV::class);
    }

    /**
     * Get all processed files created by the user.
     */
    public function processedFiles()
    {
        return $this->hasMany(ProcessedFile::class);
    }

    /**
     * Get the user's uploaded documents.
     */
    public function uploadedDocuments()
    {
        return $this->hasMany(Document::class);
    }

    /**
     * Get the user's file processing history.
     */
    public function fileProcessingHistory()
    {
        return $this->hasMany(FileProcessingHistory::class);
    }

    /**
     * Check if the user has an active subscription.
     */
    public function hasActiveSubscription()
    {
        return $this->subscriptions()
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->exists();
    }

    /**
     * Get the user's active subscription.
     */
    public function activeSubscription()
    {
        return $this->subscriptions()
            ->where('status', 'active')
            ->where('expires_at', '>', Carbon::now())
            ->latest()
            ->first();
    }

    /**
     * Get the user's subscription history.
     */
    public function subscriptionHistory()
    {
        return $this->subscriptions()->orderBy('created_at', 'desc');
    }

    /**
     * Get the user's last subscription regardless of status.
     */
    public function lastSubscription()
    {
        return $this->subscriptions()->latest()->first();
    }

    /**
     * Check if the user's subscription is expired.
     */
    public function hasExpiredSubscription()
    {
        return $this->subscriptions()
            ->where('status', 'expired')
            ->orWhere(function($query) {
                $query->where('status', 'active')
                    ->where('expires_at', '<=', now());
            })
            ->exists();
    }

    /**
     * Get days remaining in active subscription.
     */
    public function subscriptionDaysRemaining()
    {
        $activeSubscription = $this->activeSubscription();
        if (!$activeSubscription) {
            return 0;
        }
        return now()->diffInDays($activeSubscription->expires_at, false);
    }

    /**
     * Check if subscription is expiring soon (within 7 days).
     */
    public function isSubscriptionExpiringSoon()
    {
        $daysRemaining = $this->subscriptionDaysRemaining();
        return $daysRemaining > 0 && $daysRemaining <= 7;
    }

    /**
     * Update user's subscription status based on their latest subscription
     */
    public function updateSubscriptionStatus()
    {
        $latestSubscription = $this->subscriptions()->latest()->first();

        if (!$latestSubscription) {
            $this->update([
                'is_subscribed' => false,
                'subscription_status' => 'none',
                'subscription_ends_at' => null
            ]);
            return;
        }

        $this->update([
            'is_subscribed' => $latestSubscription->isActive(),
            'subscription_status' => $latestSubscription->status,
            'subscription_ends_at' => $latestSubscription->expires_at
        ]);
    }

    /**
     * Check if user has an active subscription (using local fields)
     */
    public function isSubscribed()
    {
        return $this->is_subscribed && 
               $this->subscription_status === 'active' && 
               ($this->subscription_ends_at > now());
    }

    /**
     * Get subscription status text
     */
    public function getSubscriptionStatusText()
    {
        return match($this->subscription_status) {
            'none' => 'غير مشترك',
            'active' => 'مشترك',
            'expired' => 'منتهي',
            'cancelled' => 'ملغي',
            default => 'غير معروف'
        };
    }

    /**
     * Get days remaining in subscription (using local fields)
     */
    public function getSubscriptionDaysRemaining()
    {
        if (!$this->isSubscribed()) {
            return 0;
        }
        return now()->diffInDays($this->subscription_ends_at, false);
    }

    /**
     * Sync all user's subscription fields from their subscriptions
     * This should be called via a console command periodically
     */
    public static function syncAllSubscriptions()
    {
        $users = self::all();
        foreach ($users as $user) {
            $user->updateSubscriptionStatus();
        }
    }

    /**
     * Check if user can access a particular feature
     */
    public function canAccessFeature(string $feature): bool
    {
        if (in_array($feature, ['welcome', 'login', 'register', 'dashboard', 'profile', 'subscription', 'plans'])) {
            return true; // These features are always accessible
        }
        
        return $this->isSubscribed();
    }

    /**
     * Check if user can download a document
     */
    public function canDownloadDocument(Document $document): bool
    {
        if (!$document->requires_subscription) {
            return true;
        }
        
        return $this->isSubscribed();
    }

    /**
     * Get the number of documents downloaded by the user in the current month
     */
    public function getMonthlyDownloadsCount(): int
    {
        return $this->fileProcessingHistory()
            ->where('operation_type', 'download')
            ->where('created_at', '>=', now()->startOfMonth())
            ->where('status', 'completed')
            ->count();
    }

    /**
     * Get the number of files processed by the user in the current month
     */
    public function getMonthlyProcessedFilesCount(): int
    {
        return $this->fileProcessingHistory()
            ->whereIn('operation_type', ['compress', 'merge', 'split', 'convert', 'ocr', 'protect'])
            ->where('created_at', '>=', now()->startOfMonth())
            ->where('status', 'completed')
            ->count();
    }

    /**
     * Get the number of CVs created by the user
     */
    public function getCvsCount(): int
    {
        return $this->cvs()->count();
    }
}
