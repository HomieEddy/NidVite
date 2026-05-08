<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('serves privacy and terms pages in french', function () {
    $this->withSession(['locale' => 'fr']);

    $this->get('/confidentialite')
        ->assertOk()
        ->assertSeeText('Politique de confidentialite');

    $this->get('/conditions')
        ->assertOk()
        ->assertSeeText('Conditions d\'utilisation');
});

it('serves privacy and terms pages in english', function () {
    $this->withSession(['locale' => 'en']);

    $this->get('/confidentialite')
        ->assertOk()
        ->assertSeeText('Privacy Policy');

    $this->get('/conditions')
        ->assertOk()
        ->assertSeeText('Terms of Service');
});
