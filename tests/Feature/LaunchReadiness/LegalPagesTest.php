<?php

it('serves privacy and terms pages in french', function () {
    $this->withSession(['locale' => 'fr']);

    $this->get('/confidentialite')
        ->assertOk()
        ->assertSeeText('Politique de confidentialité');

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
