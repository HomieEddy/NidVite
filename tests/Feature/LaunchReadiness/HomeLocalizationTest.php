<?php

use App\Actions\Reports\GetPublicReportStatsAction;
use Spatie\ResponseCache\Middlewares\CacheResponse;

it('renders localized homepage call-to-actions in french and english', function () {
    $this->withoutMiddleware(CacheResponse::class);

    $this->mock(GetPublicReportStatsAction::class, function ($mock): void {
        $mock->shouldReceive('__invoke')
            ->twice()
            ->andReturn([
                'totalReported' => 0,
                'totalFixed' => 0,
                'totalPending' => 0,
                'velocity' => 'N/D',
            ]);
    });

    $this->withSession(['locale' => 'fr'])
        ->get('/')
        ->assertOk()
        ->assertSeeText('Faire un signalement')
        ->assertSee('Numéro de signalement', false);

    $this->withSession(['locale' => 'en'])
        ->get('/')
        ->assertOk()
        ->assertSeeText('Report an issue')
        ->assertSee('Report ID', false);
});

it('switches locale using a cookie without writing to the session', function () {
    $this->from('/')
        ->get(route('locale.switch', ['locale' => 'en']))
        ->assertRedirect('/')
        ->assertCookie('locale')
        ->assertSessionMissing('locale');
});

it('rejects unsupported locale switches without writing to the session', function () {
    $this->from('/')
        ->get('/locale/unsupported')
        ->assertNotFound()
        ->assertSessionMissing('locale');
});

it('switches locale safely without a referrer', function () {
    $this->get(route('locale.switch', ['locale' => 'en']))
        ->assertRedirect('/')
        ->assertCookie('locale')
        ->assertSessionMissing('locale');
});

it('renders english homepage copy from locale cookie', function () {
    $this->withoutMiddleware(CacheResponse::class);

    $this->mock(GetPublicReportStatsAction::class, function ($mock): void {
        $mock->shouldReceive('__invoke')
            ->once()
            ->with('en')
            ->andReturn([
                'totalReported' => 0,
                'totalFixed' => 0,
                'totalPending' => 0,
                'velocity' => 'N/A',
            ]);
    });

    $this->withCookie('locale', 'en')
        ->get('/')
        ->assertOk()
        ->assertSeeText('Report an issue')
        ->assertSee('Report ID', false);
});

it('ignores unsupported locale cookies', function () {
    $this->withoutMiddleware(CacheResponse::class);

    $this->mock(GetPublicReportStatsAction::class, function ($mock): void {
        $mock->shouldReceive('__invoke')
            ->once()
            ->andReturn([
                'totalReported' => 0,
                'totalFixed' => 0,
                'totalPending' => 0,
                'velocity' => 'N/D',
            ]);
    });

    $this->withCookie('locale', 'zz')
        ->get('/')
        ->assertOk()
        ->assertSeeText('Faire un signalement');
});

it('falls back to default locale copy when locale is unsupported', function () {
    $this->withoutMiddleware(CacheResponse::class);

    $this->mock(GetPublicReportStatsAction::class, function ($mock): void {
        $mock->shouldReceive('__invoke')
            ->once()
            ->andReturn([
                'totalReported' => 0,
                'totalFixed' => 0,
                'totalPending' => 0,
                'velocity' => 'N/D',
            ]);
    });

    $this->withSession(['locale' => 'zz'])
        ->get('/')
        ->assertOk()
        ->assertSeeText('Faire un signalement');
});

it('uses default locale copy when no locale session is present', function () {
    $this->withoutMiddleware(CacheResponse::class);

    $this->mock(GetPublicReportStatsAction::class, function ($mock): void {
        $mock->shouldReceive('__invoke')
            ->once()
            ->andReturn([
                'totalReported' => 0,
                'totalFixed' => 0,
                'totalPending' => 0,
                'velocity' => 'N/D',
            ]);
    });

    $this->get('/')
        ->assertOk()
        ->assertSeeText('Faire un signalement');
});
