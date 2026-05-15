<?php

it('serves the offline page used by the service worker', function () {
    $response = $this->get('/offline');

    $response->assertOk();
    $response->assertSee(__('offline.message'));
});
