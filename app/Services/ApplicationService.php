<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Enums\Role;
use App\Enums\StoreStatus;
use App\Models\SellerApplication;
use App\Models\Store;
use App\Models\User;
use App\Models\UserRole;
use App\Notifications\ApplicationReviewed;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ApplicationService
{
    /**
     * Store an application with its categories and files (PRD BR-16, FR-APP-2).
     *
     * @param  array{full_name: string, nis: string, whatsapp: string, store_name: string, description: string, categories: array<int, int>}  $data
     * @param  array<int, UploadedFile>  $productPhotos
     */
    public function submit(User $user, array $data, array $productPhotos, UploadedFile $studentCard): SellerApplication
    {
        if ($user->store()->exists()) {
            throw ValidationException::withMessages([
                'store_name' => 'Kamu sudah punya lapak.',
            ]);
        }

        if ($user->applications()->where('status', ApplicationStatus::Pending->value)->exists()) {
            throw ValidationException::withMessages([
                'store_name' => 'Pendaftaranmu masih ditinjau admin.',
            ]);
        }

        return DB::transaction(function () use ($user, $data, $productPhotos, $studentCard): SellerApplication {
            $application = $user->applications()->create([
                'full_name' => $data['full_name'],
                'nis' => $data['nis'],
                'whatsapp' => $data['whatsapp'],
                'store_name' => $data['store_name'],
                'description' => $data['description'],
                'status' => ApplicationStatus::Pending,
                'agreed_terms_at' => now(),
            ]);

            $application->categories()->sync($data['categories']);

            foreach ($productPhotos as $photo) {
                $application->files()->create([
                    'type' => 'product_photo',
                    'path' => $photo->store("applications/{$application->id}/photos", 'public'),
                ]);
            }

            $application->files()->create([
                'type' => 'student_card',
                'path' => $studentCard->store('applications', 'local'),
            ]);

            return $application;
        });
    }

    /**
     * Approve an application, grant the seller role and create the closed store (PRD BR-17).
     */
    public function approve(SellerApplication $application, User $admin): void
    {
        $this->guardPending($application);

        DB::transaction(function () use ($application, $admin): void {
            $application->update([
                'status' => ApplicationStatus::Approved,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            UserRole::firstOrCreate([
                'user_id' => $application->user_id,
                'role' => Role::Seller->value,
            ]);

            Store::create([
                'user_id' => $application->user_id,
                'name' => $application->store_name,
                'slug' => $this->uniqueSlug($application->store_name),
                'description' => $application->description,
                'whatsapp' => $application->whatsapp,
                'status' => StoreStatus::Closed,
            ]);
        });

        $application->user->notify(new ApplicationReviewed($application));
    }

    /**
     * Reject an application with a note for the applicant.
     */
    public function reject(SellerApplication $application, User $admin, string $note): void
    {
        $this->guardPending($application);

        $application->update([
            'status' => ApplicationStatus::Rejected,
            'reject_note' => $note,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);

        $application->user->notify(new ApplicationReviewed($application));
    }

    private function guardPending(SellerApplication $application): void
    {
        if ($application->status !== ApplicationStatus::Pending) {
            throw ValidationException::withMessages([
                'status' => 'Pendaftaran ini sudah ditinjau.',
            ]);
        }
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'lapak';
        $slug = $base;
        $suffix = 1;

        while (Store::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.++$suffix;
        }

        return $slug;
    }
}
