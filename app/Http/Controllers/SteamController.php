<?php

namespace App\Http\Controllers;

use App\Services\Steam\SteamWebApi;
use App\Support\Seo;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class SteamController extends Controller
{
    public function __invoke(Request $request, SteamWebApi $steam): Response
    {
        $query = trim((string) $request->query('q', ''));
        $lookup = null;

        if ($query !== '') {
            $request->validate([
                'q' => ['string', 'max:64', 'regex:/^[A-Za-z0-9_-]+$/'],
            ]);

            try {
                $lookup = $steam->lookup($query);
            } catch (Throwable) {
                $lookup = ['error' => 'Steam did not return this profile.'];
            }
        }

        return Inertia::render('Steam/Index', [
            'configured' => $steam->configured(),
            'stats' => $steam->overview(),
            'lookup' => $lookup,
            'query' => $query,
            'seo' => Seo::make(
                'CS2 on Steam',
                'Current CS2 players, Steam news, and global achievement percentages from the Steamworks Web API.',
                url('/steam'),
                $query === '' ? 'index,follow' : 'noindex,follow',
            ),
        ]);
    }
}
