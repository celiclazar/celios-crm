<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('contacts')) {
            Schema::create('contacts', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->nullable()->index();
                $table->string('phone')->nullable();
                $table->string('company')->nullable();
                $table->string('stage')->default('lead')->index(); // lead, contacted, qualified, proposal, won, lost
                $table->string('source')->nullable()->default('manual'); // form_submission, manual, referral, etc.
                $table->decimal('lead_value', 12, 2)->nullable();
                $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('form_submission_id')->nullable()->constrained('form_submissions')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->timestamp('last_contacted_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('contact_notes')) {
            Schema::create('contact_notes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('contact_id')->constrained('contacts')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('type')->default('note'); // note, call, meeting, email
                $table->text('content');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_notes');
        Schema::dropIfExists('contacts');
    }
};
