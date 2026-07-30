<?php

use App\Http\Controllers\Api\V1\AnnouncementController;
use App\Http\Controllers\Api\V1\AttendanceController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BranchController;
use App\Http\Controllers\Api\V1\CoachAttendanceController;
use App\Http\Controllers\Api\V1\CoachController;
use App\Http\Controllers\Api\V1\CourtController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\EmergencyContactController;
use App\Http\Controllers\Api\V1\EvaluationController;
use App\Http\Controllers\Api\V1\GalleryController;
use App\Http\Controllers\Api\V1\GuardianController;
use App\Http\Controllers\Api\V1\InjuryController;
use App\Http\Controllers\Api\V1\InventoryItemController;
use App\Http\Controllers\Api\V1\InventoryTransactionController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PackageController;
use App\Http\Controllers\Api\V1\ParticipantController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\PaymentGatewayWebhookController;
use App\Http\Controllers\Api\V1\ProgramController;
use App\Http\Controllers\Api\V1\PublicController;
use App\Http\Controllers\Api\V1\ReferralController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\TrainingClassController;
use App\Http\Controllers\Api\V1\TrainingScheduleController;
use App\Http\Controllers\Api\V1\TrialClassController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\VoucherController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:auth');
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:auth');

    // Public registration: works for both guests (with guardian payload) and
    // authenticated adult participants (self-registration) — see StoreParticipantRequest.
    Route::post('/participants', [ParticipantController::class, 'store'])->middleware('throttle:auth');

    // Called by the external payment gateway, not a logged-in user — protected
    // by HMAC signature verification inside the controller instead of auth:sanctum.
    Route::post('/webhooks/payment-gateway', [PaymentGatewayWebhookController::class, 'handle'])->middleware('throttle:webhook');

    // Thin, unauthenticated read-only listings for the public marketing site
    // and the registration form's branch picker.
    Route::get('/public/branches', [BranchController::class, 'publicIndex']);
    Route::get('/public/programs', [PublicController::class, 'programs']);
    Route::get('/public/packages', [PublicController::class, 'packages']);
    Route::get('/public/coaches', [PublicController::class, 'coaches']);
    Route::get('/public/courts', [PublicController::class, 'courts']);
    Route::get('/public/schedules', [PublicController::class, 'schedules']);
    Route::get('/public/galleries', [PublicController::class, 'galleries']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        Route::middleware('permission:branches.view')->group(function () {
            Route::get('/branches', [BranchController::class, 'index']);
            Route::get('/branches/{branch}', [BranchController::class, 'show']);
        });

        Route::middleware('permission:branches.manage')->group(function () {
            Route::post('/branches', [BranchController::class, 'store']);
            Route::put('/branches/{branch}', [BranchController::class, 'update']);
            Route::patch('/branches/{branch}', [BranchController::class, 'update']);
            Route::delete('/branches/{branch}', [BranchController::class, 'destroy']);
        });

        Route::middleware('permission:users.manage')->group(function () {
            Route::get('/users', [UserController::class, 'index']);
            Route::post('/users', [UserController::class, 'store']);
            Route::get('/users/{user:uuid}', [UserController::class, 'show']);
            Route::put('/users/{user:uuid}', [UserController::class, 'update']);
            Route::patch('/users/{user:uuid}', [UserController::class, 'update']);
            Route::delete('/users/{user:uuid}', [UserController::class, 'destroy']);
        });

        // Participants: visibility is role-scoped inside the controller (staff see
        // all, participant/guardian see only their own/linked records).
        Route::get('/participants', [ParticipantController::class, 'index']);
        Route::get('/participants/{participant:uuid}', [ParticipantController::class, 'show']);
        Route::patch('/participants/{participant:uuid}', [ParticipantController::class, 'update']);
        Route::post('/participants/{participant:uuid}/verify', [ParticipantController::class, 'verify']);
        Route::post('/participants/{participant:uuid}/guardians', [GuardianController::class, 'link']);
        Route::get('/participants/{participant:uuid}/emergency-contacts', [EmergencyContactController::class, 'index']);
        Route::post('/participants/{participant:uuid}/emergency-contacts', [EmergencyContactController::class, 'store']);
        Route::delete('/participants/{participant:uuid}/emergency-contacts/{emergencyContact}', [EmergencyContactController::class, 'destroy']);
        Route::get('/participants/{participant:uuid}/injuries', [InjuryController::class, 'index']);
        Route::post('/participants/{participant:uuid}/injuries', [InjuryController::class, 'store']);
        Route::get('/participants/{participant:uuid}/packages', [ParticipantController::class, 'packages']);

        Route::get('/guardians', [GuardianController::class, 'index']);
        Route::get('/guardians/{guardian}/participants', [GuardianController::class, 'participants']);

        Route::middleware('permission:coaches.view,coaches.manage')->group(function () {
            Route::get('/coaches', [CoachController::class, 'index']);
            Route::get('/coaches/{coach}', [CoachController::class, 'show']);
        });
        // Create/delete require staff roles; update also allows a coach editing their
        // own profile — all enforced in the controller (coaches.manage is too coarse
        // to express the "own record only" scope for the coach role itself).
        Route::post('/coaches', [CoachController::class, 'store']);
        Route::put('/coaches/{coach}', [CoachController::class, 'update']);
        Route::patch('/coaches/{coach}', [CoachController::class, 'update']);
        Route::delete('/coaches/{coach}', [CoachController::class, 'destroy']);

        Route::middleware('permission:courts-inventory.view,courts-inventory.manage')->group(function () {
            Route::get('/courts', [CourtController::class, 'index']);
        });
        // Show (not index) is also reachable by participant/guardian — see
        // CourtController::show()'s own authorization — so a schedule's
        // court can be displayed to the participant checking in for it
        // without handing them list access or the shared inventory-items
        // permission this module's .view would otherwise unlock.
        Route::get('/courts/{court}', [CourtController::class, 'show']);
        Route::middleware('permission:courts-inventory.manage')->group(function () {
            Route::post('/courts', [CourtController::class, 'store']);
            Route::put('/courts/{court}', [CourtController::class, 'update']);
            Route::patch('/courts/{court}', [CourtController::class, 'update']);
            Route::delete('/courts/{court}', [CourtController::class, 'destroy']);
        });

        // Inventory shares the courts-inventory permission group per the seeded
        // access matrix ("Lapangan & inventaris" is one combined row).
        Route::middleware('permission:courts-inventory.view,courts-inventory.manage')->group(function () {
            Route::get('/inventory-items', [InventoryItemController::class, 'index']);
            Route::get('/inventory-items/{inventoryItem}', [InventoryItemController::class, 'show']);
            Route::get('/inventory-items/{inventoryItem}/transactions', [InventoryTransactionController::class, 'index']);
        });
        Route::middleware('permission:courts-inventory.manage')->group(function () {
            Route::post('/inventory-items', [InventoryItemController::class, 'store']);
            Route::put('/inventory-items/{inventoryItem}', [InventoryItemController::class, 'update']);
            Route::patch('/inventory-items/{inventoryItem}', [InventoryItemController::class, 'update']);
            Route::delete('/inventory-items/{inventoryItem}', [InventoryItemController::class, 'destroy']);
            Route::post('/inventory-items/{inventoryItem}/transactions', [InventoryTransactionController::class, 'store']);
        });

        Route::middleware('permission:programs-schedules.view,programs-schedules.manage')->group(function () {
            Route::get('/programs', [ProgramController::class, 'index']);
            Route::get('/programs/{program}', [ProgramController::class, 'show']);
            Route::get('/classes', [TrainingClassController::class, 'index']);
            Route::get('/classes/{trainingClass}', [TrainingClassController::class, 'show']);
            Route::get('/classes/{trainingClass}/members', [TrainingClassController::class, 'members']);
            Route::get('/classes/{trainingClass}/waiting-list', [TrainingClassController::class, 'waitingList']);
            Route::get('/classes/{trainingClass}/trials', [TrialClassController::class, 'index']);
            Route::get('/schedules', [TrainingScheduleController::class, 'index']);
            Route::get('/schedules/{schedule}', [TrainingScheduleController::class, 'show']);
        });
        Route::middleware('permission:programs-schedules.manage')->group(function () {
            Route::post('/programs', [ProgramController::class, 'store']);
            Route::put('/programs/{program}', [ProgramController::class, 'update']);
            Route::patch('/programs/{program}', [ProgramController::class, 'update']);
            Route::delete('/programs/{program}', [ProgramController::class, 'destroy']);

            Route::post('/classes', [TrainingClassController::class, 'store']);
            Route::put('/classes/{trainingClass}', [TrainingClassController::class, 'update']);
            Route::patch('/classes/{trainingClass}', [TrainingClassController::class, 'update']);
            Route::delete('/classes/{trainingClass}', [TrainingClassController::class, 'destroy']);
            Route::post('/classes/{trainingClass}/members', [TrainingClassController::class, 'enroll']);
            Route::delete('/classes/{trainingClass}/members/{participant}', [TrainingClassController::class, 'removeMember']);
            Route::post('/classes/{trainingClass}/trial', [TrialClassController::class, 'store']);
            Route::post('/trial-classes/{trialClass}/convert', [TrialClassController::class, 'convert']);

            Route::post('/schedules', [TrainingScheduleController::class, 'store']);
            Route::post('/schedules/{schedule}/cancel', [TrainingScheduleController::class, 'cancel']);
            Route::post('/schedules/{schedule}/reschedule', [TrainingScheduleController::class, 'reschedule']);
        });

        // Attendance: authorization is scoped per-action inside the controllers
        // (participant self check-in only, coach verifies own class, admin
        // verifies coach honor records) — a single permission slug can't express
        // that per the RBAC seeder's own design (see EnsurePermission usage above).
        Route::get('/schedules/{schedule}/attendance', [AttendanceController::class, 'index']);
        Route::post('/schedules/{schedule}/check-in', [AttendanceController::class, 'checkIn']);
        Route::post('/schedules/{schedule}/attendance', [AttendanceController::class, 'bulkVerify']);
        Route::get('/participants/{participant:uuid}/attendance-stats', [AttendanceController::class, 'stats']);
        Route::post('/schedules/{schedule}/coach-attendance', [CoachAttendanceController::class, 'record']);
        Route::post('/schedules/{schedule}/coach-attendance/verify', [CoachAttendanceController::class, 'verify']);

        Route::get('/galleries', [GalleryController::class, 'index']);
        Route::post('/galleries', [GalleryController::class, 'store']);
        Route::get('/galleries/{gallery}', [GalleryController::class, 'show']);
        Route::post('/galleries/{gallery}/media', [GalleryController::class, 'uploadMedia']);
        Route::post('/galleries/{gallery}/publish', [GalleryController::class, 'publish']);
        Route::delete('/galleries/{gallery}', [GalleryController::class, 'destroy']);

        Route::get('/dashboard', [DashboardController::class, 'index']);

        Route::middleware('permission:packages.view,packages.manage')->group(function () {
            Route::get('/packages', [PackageController::class, 'index']);
            Route::get('/packages/{package}', [PackageController::class, 'show']);
        });
        Route::middleware('permission:packages.manage')->group(function () {
            Route::post('/packages', [PackageController::class, 'store']);
            Route::put('/packages/{package}', [PackageController::class, 'update']);
            Route::patch('/packages/{package}', [PackageController::class, 'update']);
            Route::delete('/packages/{package}', [PackageController::class, 'destroy']);
        });

        // Invoices: index/show visibility is further scoped per-participant inside
        // the controller (finance/admin see all, participant/guardian see own).
        Route::middleware('permission:invoices.view,invoices.manage')->group(function () {
            Route::get('/invoices', [InvoiceController::class, 'index']);
            Route::get('/invoices/{invoice}', [InvoiceController::class, 'show']);
        });
        Route::middleware('permission:invoices.manage')->group(function () {
            Route::post('/invoices', [InvoiceController::class, 'store']);
        });

        // Payments: store visibility (own invoice or staff) enforced in the
        // controller via InvoiceController::authorizeView.
        Route::middleware('permission:invoices.view,invoices.manage')->group(function () {
            Route::get('/invoices/{invoice}/payments', [PaymentController::class, 'index']);
        });
        Route::middleware('permission:payments.manage')->group(function () {
            Route::post('/invoices/{invoice}/payments', [PaymentController::class, 'store']);
        });
        Route::middleware('permission:payments.verify')->group(function () {
            Route::post('/payments/{payment}/verify', [PaymentController::class, 'verify']);
        });
        Route::get('/invoices/{invoice}/receipt', [PaymentController::class, 'receipt']);

        Route::middleware('permission:evaluations.manage')->group(function () {
            Route::post('/evaluations', [EvaluationController::class, 'store']);
        });
        Route::middleware('permission:evaluations.view,evaluations.manage')->group(function () {
            Route::get('/evaluations', [EvaluationController::class, 'index']);
            Route::get('/evaluations/{evaluation}', [EvaluationController::class, 'show']);
            Route::get('/participants/{participant:uuid}/evaluations', [EvaluationController::class, 'forParticipant']);
        });

        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead']);

        Route::middleware('permission:reports.view,reports.manage')->group(function () {
            Route::get('/reports/attendance', [ReportController::class, 'attendance']);
            Route::get('/reports/revenue', [ReportController::class, 'revenue']);
            Route::get('/reports/{type}/export', [ReportController::class, 'export']);
        });

        Route::middleware('permission:vouchers-refunds.view,vouchers-refunds.manage')->group(function () {
            Route::get('/vouchers', [VoucherController::class, 'index']);
        });
        Route::middleware('permission:vouchers-refunds.manage')->group(function () {
            Route::post('/vouchers', [VoucherController::class, 'store']);
        });
        // Any authenticated user may check a voucher's discount at checkout.
        Route::post('/vouchers/validate', [VoucherController::class, 'validateCode'])->middleware('throttle:lookup');

        Route::post('/participants/{participant:uuid}/referral-code', [ReferralController::class, 'store']);
        Route::get('/participants/{participant:uuid}/referrals', [ReferralController::class, 'index']);

        // Announcements: base module access follows the seeded matrix
        // (management/participant/guardian view, super-admin/administrator
        // manage — coach/finance have neither). Fine-grained visibility
        // within that (target_type/publish window) is enforced inside the
        // controller via Announcement::isVisibleTo().
        Route::middleware('permission:announcements.view,announcements.manage')->group(function () {
            Route::get('/announcements', [AnnouncementController::class, 'index']);
            Route::get('/announcements/{announcement}', [AnnouncementController::class, 'show']);
        });
        Route::middleware('permission:announcements.manage')->group(function () {
            Route::post('/announcements', [AnnouncementController::class, 'store']);
            Route::put('/announcements/{announcement}', [AnnouncementController::class, 'update']);
            Route::patch('/announcements/{announcement}', [AnnouncementController::class, 'update']);
            Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy']);
        });
    });
});
