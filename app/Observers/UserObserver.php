<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class UserObserver
{
    /**
     * Handle the User "creating" event.
     */
    public function creating(User $user): void
    {
        // Ensure new users are not admin by default
        if (!isset($user->is_admin)) {
            $user->is_admin = false;
        }
        
        // Ensure new users are active by default
        if (!isset($user->is_active)) {
            $user->is_active = true;
        }
        
        // Reset security fields
        $user->failed_login_attempts = 0;
        $user->locked_until = null;
        
        Log::info('New user account created', [
            'email' => $user->email,
            'name' => $user->name,
            'is_admin' => $user->is_admin,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        Log::info('User account successfully created', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);
    }

    /**
     * Handle the User "updating" event.
     */
    public function updating(User $user): void
    {
        // Log admin status changes
        if ($user->isDirty('is_admin')) {
            Log::warning('User admin status changed', [
                'user_id' => $user->id,
                'email' => $user->email,
                'old_admin_status' => $user->getOriginal('is_admin'),
                'new_admin_status' => $user->is_admin,
                'changed_by' => auth()->id(),
                'ip' => request()->ip(),
            ]);
        }
        
        // Log active status changes
        if ($user->isDirty('is_active')) {
            Log::info('User active status changed', [
                'user_id' => $user->id,
                'email' => $user->email,
                'old_active_status' => $user->getOriginal('is_active'),
                'new_active_status' => $user->is_active,
                'changed_by' => auth()->id(),
            ]);
        }
        
        // Log email changes
        if ($user->isDirty('email')) {
            Log::warning('User email changed', [
                'user_id' => $user->id,
                'old_email' => $user->getOriginal('email'),
                'new_email' => $user->email,
                'changed_by' => auth()->id(),
                'ip' => request()->ip(),
            ]);
            
            // Reset email verification when email changes
            $user->email_verified_at = null;
        }
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        Log::info('User account updated', [
            'user_id' => $user->id,
            'email' => $user->email,
            'updated_by' => auth()->id(),
        ]);
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        Log::warning('User account deleted', [
            'user_id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'deleted_by' => auth()->id(),
            'ip' => request()->ip(),
        ]);
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        Log::info('User account restored', [
            'user_id' => $user->id,
            'email' => $user->email,
            'restored_by' => auth()->id(),
        ]);
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        Log::critical('User account permanently deleted', [
            'user_id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'deleted_by' => auth()->id(),
            'ip' => request()->ip(),
        ]);
    }
}