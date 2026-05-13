<?php

namespace App\Starter\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Starter\Services\SitemapGenerator;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
  public function __invoke(SitemapGenerator $sitemap): Response
  {
    return response()
      ->view('sitemap', ['urls' => $sitemap->urls()])
      ->header('Content-Type', 'application/xml');
  }
}
