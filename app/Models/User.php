<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'phone',
        'website',
        'bio',
        'email_verified_at',
        'payment_details',
        'promoted_to_author_at',
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
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'payment_details' => 'array',
            'promoted_to_author_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    // Relationships
    public function books()
    {
        return $this->hasMany(Book::class);
    }

    public function payouts()
    {
        return $this->hasMany(Payout::class);
    }

    public function walletTransactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }

    // Helper methods
    public function isAuthor()
    {
        return $this->hasRole('author') || $this->hasRole('admin');
    }

    public function isAdmin()
    {
        return $this->hasRole('admin');
    }

    public function getWalletBalance()
    {
        return $this->walletTransactions()
            ->where('type', '!=', 'payout')
            ->sum('amount');
    }
}