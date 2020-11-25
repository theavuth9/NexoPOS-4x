<?php

use App\Http\Controllers\Dashboard\CustomersController;
use App\Http\Controllers\Dashboard\CustomersGroupsController;
use App\Http\Controllers\Dashboard\ProductsController;
use App\Http\Controllers\Dashboard\OrdersController;
use App\Http\Controllers\DashboardController;
use App\Http\Middleware\CheckMigrationStatus;
use App\Events\WebRoutesLoadedEvent;
use App\Http\Controllers\Dashboard\ModulesController;
use App\Http\Middleware\StoreDetectorMiddleware;
use Illuminate\Support\Facades\Route;
use dekor\ArrayToTextTable;
use Illuminate\Routing\Route as RoutingRoute;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::middleware([ 'ns.installed', CheckMigrationStatus::class ])->group( function() {
    Route::get( '/sign-in', 'AuthController@signIn' )->name( 'ns.login' );
    Route::get( '/sign-up', 'AuthController@signUp' )->name( 'ns.register' );
    Route::get( '/password-lost', 'AuthController@passwordLost' );
    Route::get( '/new-password', 'AuthController@newPassword' );

    Route::post( '/auth/sign-in', 'AuthController@postSignIn' );
    Route::post( '/auth/sign-up', 'AuthController@postSignUp' )->name( 'ns.register.post' );
    Route::get( '/sign-out', 'AuthController@signOut' )->name( 'ns.logout' );
    Route::get( '/database-update/', 'UpdateController@updateDatabase' )->withoutMiddleware([ CheckMigrationStatus::class ])
        ->name( 'ns.database-update' );

    Route::middleware([ 
        'auth',
        'ns.check-application-health',
    ])->group( function() {
        Route::prefix( 'dashboard' )->group( function() {

            require( dirname( __FILE__ ) . '/nexopos.php' );

            event( new WebRoutesLoadedEvent( 'dashboard' ) );
    
            Route::get( '/modules', [ ModulesController::class, 'listModules' ])->name( 'ns.dashboard.modules.list' );
            Route::get( '/modules/upload', [ ModulesController::class, 'showUploadModule' ])->name( 'ns.dashboard.modules.upload' );
            Route::get( '/modules/download/{identifier}', [ ModulesController::class, 'downloadModule' ])->name( 'ns.dashboard.modules.upload' );
            Route::get( '/modules/migrate/{namespace}', [ ModulesController::class, 'migrateModule' ])->name( 'ns.dashboard.modules.migrate' );
        });
    });
});

Route::middleware([ 'ns.not-installed' ])->group( function() {
    Route::prefix( '/do-setup/' )->group( function() {
        Route::get( '', 'SetupController@welcome' )->name( 'setup' );
    });
});

Route::get( '/routes', function() {
    $values     =   collect( array_values( ( array ) app( 'router' )->getRoutes() )[1] )->map( function( RoutingRoute $route ) {
        return [
            'uri'       =>  $route->uri(),
            'methods'   =>  collect( $route->methods() )->join( ', ' ),
        ];
    })->values();

    return ( new ArrayToTextTable( $values->toArray() ) )->render();
});
