<?php

it('keeps composer constraints deterministic and avoids unstable branch versions', function () {
    $composer = json_decode((string) file_get_contents(base_path('composer.json')), true, 512, JSON_THROW_ON_ERROR);

    $sections = ['require', 'require-dev'];

    foreach ($sections as $section) {
        $deps = $composer[$section] ?? [];
        expect($deps)->toBeArray();

        foreach ($deps as $package => $constraint) {
            if ($package === 'php') {
                continue;
            }

            $value = strtolower((string) $constraint);

            expect($value)
                ->not->toContain('*')
                ->and($value)->not->toContain('dev-')
                ->and($value)->not->toContain('@dev')
                ->and($value)->not->toContain('x-dev');
        }
    }
});
