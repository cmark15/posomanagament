<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AddEnforcer;
use App\Http\Controllers\EnforcerAcc;
use App\Http\Controllers\ViolationController;
use App\Http\Controllers\EnforcerAuthController;
use App\Http\Controllers\EnforcerManagementController;
use App\Http\Controllers\ViolatorManagementController;
use App\Http\Controllers\ViolatorAuthController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\AdminTicketController;
use App\Http\Controllers\ImpoundedController;
use App\Http\Controllers\ViolatorTableController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\LocalTestTicketController;


// Route::get('/', function () {
//     return view('welcome');
// });

/* health check used by isReallyOnline() */
Route::get('/ping', fn() => response()->noContent());

// background sync JSON submit (CSRF exempt)
Route::post('/pwa/sync/ticket', [TicketController::class, 'storeJson'])->name('ticket.sync');


// Admin login routes
Route::get('/alogin', [AuthController::class, 'showLogin'])->name('admin.showLogin');
Route::post('/alogin', [AuthController::class, 'login'])->name('admin.login');
Route::post('/alogout', [AuthController::class, 'logout'])->name('admin.logout');

// Enforcer login routes
Route::get('/plogin', [EnforcerAuthController::class, 'showLogin'])->name('enforcer.showLogin');
Route::post('/plogin', [EnforcerAuthController::class,'login'])->name('enforcer.login');
Route::post('/plogout', [EnforcerAuthController::class,'logout'])->name('enforcer.logout');

// Violator login routes
Route::get('/vlogin', [ViolatorAuthController::class, 'showLogin'])->name('violator.showLogin');
Route::post('/vlogin', [ViolatorAuthController::class, 'login'])->name('violator.login');
Route::post('/vlogout', [ViolatorAuthController::class, 'logout'])->name('violator.logout');


// Enforcer protected routes
Route::middleware('enforcer')->group(function () {
    // PWA start url should serve the Issue Ticket page
    Route::view('/offline','offline');
    Route::get('/pwa', [TicketController::class, 'create'])->name('pwa');
    Route::resource('enforcerCreate', EnforcerAcc::class);
    Route::resource('enforcerTicket', TicketController::class);
    Route::resource('enf', EnforcerManagementController::class);
    Route::get('/violators/check-license', [TicketController::class, 'checkLicense'])
        ->name('violators.checkLicense');
    Route::get('violators/suggestions', [TicketController::class, 'suggestions'])->name('enforcer.violators.suggestions');
    Route::get('violators/{id}', [TicketController::class, 'show'])->name('enforcer.violators.show');
    Route::get('enforcer/change/password', [EnforcerAuthController::class, 'showChangePassword'])->name('enforcer.password.edit');
    Route::post('enforcer/change/password', [EnforcerAuthController::class, 'changePassword'])->name('enforcer.password.update');
    Route::get('/wasm/tesseract-core.wasm', function () {
    return response()->file(public_path('vendor/tesseract/tesseract-core.wasm'), [
        'Content-Type' => 'application/wasm',
        'Cache-Control' => 'public, max-age=31536000'
        ]);
    });
    Route::get('/wasm/eng.traineddata.gz', function () {
        return response()->file(public_path('vendor/tesseract/eng.traineddata.gz'), [
            'Content-Type' => 'application/gzip',
            'Cache-Control' => 'public, max-age=31536000'
        ]);
    });
});

// Admin protected routes
Route::middleware('admin')->group(function () {
    Route::get('/admin/dashboard', [AdminDashboardController::class,'adminDash'])->name('admin.dashboard');
    Route::get('/admin/profile/update', [AdminDashboardController::class,'edit'])->name('admin.profile.edit');
    Route::put('/admin/profile/update', [AdminDashboardController::class,'update'])->name('admin.profile.update');



    //Issue Ticket 
    Route::get('/admin/tickets/create', [AdminTicketController::class, 'create'])
        ->name('admin.tickets.create');
    Route::post('/admin/tickets', [AdminTicketController::class, 'store'])
        ->name('admin.tickets.store');
        
    //Violation routes
    Route::get('/violation/partial', [ViolationController::class,'partial'])->name('violation.partial');
    
    Route::resource('violation', ViolationController::class)->except(['destroy']); 
    // soft-delete (deactivate)
    Route::delete('violation/{violation}', [ViolationController::class,'destroy'])->name('violation .destroy');
    Route::get('violation/{violation}/json', [ViolationController::class, 'json'])
     ->name('violation.json');


    //Enforcer routes
    Route::get('/enforcer/partial', [AddEnforcer::class, 'partial'])->name('enforcer.partial');
    Route::resource('enforcer', AddEnforcer::class)->except(['destroy']);
    
    // soft-delete (deactivate)
    Route::delete('enforcer/{enforcer}', [AddEnforcer::class,'destroy'])->name('enforcer.destroy');
    Route::post('enforcer/{enforcer}/restore', [AddEnforcer::class,'restore'])->name('enforcer.restore');
    Route::get('enforcer/{enforcer}/json', [AddEnforcer::class, 'json'])
     ->name('enforcer.json');

     //Violator routes
    Route::get('/violatorTable/partial', [ViolatorTableController::class, 'partial'])->name('violatorTable.partial');
    Route::resource('violatorTable', ViolatorTableController::class);
    Route::post('paid/{ticket}/status', [ViolatorTableController::class, 'updateStatus'])
     ->name('ticket.updateStatus');

    //Ticket routes
    Route::get('ticket/partial', [AdminTicketController::class, 'partial'])->name('ticket.partial');
    Route::post('ticket/{ticket}/status', [AdminTicketController::class, 'updateStatus'])->name('ticket.updateStatus');
    Route::resource('ticket', AdminTicketController::class);

    //Impound Vehicle routes
    Route::post('/impounded/resolve', [ImpoundedController::class, 'resolve'])->name('impound.resolve');
    Route::resource('impoundedVehicle', ImpoundedController::class);

    Route::get('dataAnalytics', [AnalyticsController::class,'index'])
     ->name('dataAnalytics.index');

    Route::get('dataAnalytics/latest', [AnalyticsController::class,'latest'])
        ->name('dataAnalytics.latest');
    // NEW: list tickets near a hotspot (for the modal)
    Route::get('dataAnalytics/hotspotTickets', [AnalyticsController::class,'hotspotTickets'])
        ->name('dataAnalytics.hotspotTickets');
    
    //ANALYTICS ROUTE
    Route::get('/logs/activity', [ActivityLogController::class, 'index'])->name('logs.activity');

    // On-demand report downloads
    Route::get('reports/download/{format}', [AnalyticsController::class,'download'])
        ->where('format','xlsx|docx')
        ->name('reports.download');

    Route::get('/superadmin/activity-logs', [ActivityLogController::class, 'index'])
        ->name('admin.activity-logs.index');
});

Route::middleware('violator')->group(function () {
    Route::get('/vdash', [ViolatorManagementController::class,'violatorDash'])->name('violator.dashboard');
    Route::get('/violator/password/change', [ViolatorAuthController::class, 'showChangePasswordForm'])->name('violator.password.change');
    Route::post('/violator/password/change', [ViolatorAuthController::class, 'changePassword'])->name('violator.password.update');
});


//Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
