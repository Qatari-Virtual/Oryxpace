<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Livewire\DepartmentProjectList;
use App\Livewire\NotificationSettings;
use App\Livewire\ProjectKanban;
use App\Livewire\ProjectListView;
use App\Livewire\ScheduleTemplateManager;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/login', [AuthController::class, 'showLogin'])->middleware('guest')->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('guest');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::prefix('api')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::middleware(\App\Http\Middleware\AuthenticatePilotToken::class)->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
    });
});

Route::middleware('auth')->group(function () {
Route::get('/', function () {
    return redirect()->route('departments');
});

Route::get('/departments', DepartmentProjectList::class)->name('departments');
Route::get('/projects/{project}/kanban', ProjectKanban::class)->name('projects.kanban');
Route::get('/projects/{project}/list', ProjectListView::class)->name('projects.list');
Route::get('/schedules', ScheduleTemplateManager::class)->name('schedules');
Route::get('/notifications', NotificationSettings::class)->name('notifications');
});

Route::get('/storage/{path}', function (string $path) {
    $publicFile = public_path('storage/' . $path);
    if (file_exists($publicFile) && !is_dir($publicFile)) {
        return response()->file($publicFile);
    }
    $storageFile = storage_path('app/public/' . $path);
    if (file_exists($storageFile) && !is_dir($storageFile)) {
        return response()->file($storageFile);
    }
    abort(404);
})->where('path', '.*');

require __DIR__.'/api.php';
