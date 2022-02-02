<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/redirect-to-blocktogo', function (Request $request) {
    $request->session()->put('state', $state = Str::random(40));

    $query = http_build_query([
        'client_id' => '95661e3c-4e3a-4475-931b-69d0764d228c', // cek di table: oauth_clients
        'redirect_uri' => 'http://127.0.0.1:8001/auth/callback',
        'response_type' => 'code',
        'scope' => '',
        'state' => $state,
    ]);

    return redirect(env('OAUTH2_SERVER') . '/oauth/authorize?' . $query);
});

Route::get('/auth/callback', function (Request $request) {
    $response = Http::asForm()->post(env('OAUTH2_SERVER') . '/oauth/token', [
        'grant_type' => 'authorization_code',
        'client_id' => '95661e3c-4e3a-4475-931b-69d0764d228c',
        'client_secret' => 'r7BZAlVHaRbw2xXBNKUf42yPIDW7nVIS7pX411Df',
        'redirect_uri' => 'http://127.0.0.1:8001/auth/callback',
        'code' => $request->code,
    ]);

    $response = $response->json();

    $res = Http::withHeaders([
        'Accept' => 'application/json',
        'Authorization' => 'Bearer ' . $response['access_token'],
    ])->get(env('OAUTH2_SERVER') . '/api/auth/user');

    if($res->json() === null) return 'gagal';
    else {
        $res = $res->json();

        $user = User::firstOrCreate(
            ['email' => $res['email']],
            [
                'name' => $res['name'],
                'password' => Hash::make($res['id'] . ':' . $res['email'] . ':' . $res['name']),
            ]
        );

        Auth::login($user, true);

        return redirect('dashboard');
    }
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

require __DIR__ . '/auth.php';
