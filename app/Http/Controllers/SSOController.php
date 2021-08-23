<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class SSOController extends Controller
{
    public function getLogin(Request $request) {
        $request->session()->put("sso_state", $state =  Str::random(40));
        $query = http_build_query([
            "client_id" => "9438bc54-87b7-4532-abe3-5e293ab00801",
            "redirect_uri" => route("sso.callback"),
            "response_type" => "code",
            "scope" => "view-user",
            "state" => $state,
        ]);
        return redirect(env('SSO_SERVER_URL')."/oauth/authorize?".$query);
    }

    public function getCallback(Request $request) {
        $state = $request->session()->pull("sso_state");
        throw_unless(strlen($state) > 0 && $state == $request->state, InvalidArgumentException::class);
        $response = Http::asForm()->post(env('SSO_SERVER_URL')."/oauth/token", [
            "grant_type" => "authorization_code",
            "client_id" => "9438bc54-87b7-4532-abe3-5e293ab00801",
            "client_secret" => "54YN2eYZuYkjC12Q0TLqcO3YyjyR9k3TmVEI5c4y",
            "redirect_uri" => route("sso.callback"),
            "code" => $request->code,
        ]);
        $request->session()->put($response->json());
        return $response->json();
    }

    public function connect(Request $request) {
        $access_token = $request->session()->get("access_token");
        $response = Http::withHeaders([
            "Accept" => "application/json",
            "Authorization" => "Bearer ".$access_token,
        ])->get(env('SSO_SERVER_URL')."/api/user");
        return $response->json();
    }
}
