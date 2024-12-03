<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CollaboratorController;
use App\Mail\CollaboratorProcessed;
use Illuminate\Support\Facades\Mail;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

#### LOGIN
Route::post('/login', [AuthController::class, 'login']);

// Rotas protegidas (requerem autenticação JWT)
Route::middleware('auth:api')->group(function () {
    
    #### ROTAS USER
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('/users', [UserController::class, 'store']);
    Route::get('/users', [UserController::class, 'index']);
    
    ## ROTAS COLABORADORES
    Route::post('/collaborators', [CollaboratorController::class, 'store']);
    Route::get('/collaborators', [CollaboratorController::class, 'index']);
    Route::put('/collaborators/{id}', [CollaboratorController::class, 'update'])->name('collaborators.update');
    Route::delete('/collaborators/{id}', [CollaboratorController::class, 'destroy'])->name('collaborators.destroy');
    Route::post('/collaborators/upload', [CollaboratorController::class, 'uploadCsv']);

});