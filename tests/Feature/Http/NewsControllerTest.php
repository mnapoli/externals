<?php

declare(strict_types=1);

test('renders news page', function (): void {
    $response = $this->get('/news');

    $response->assertOk();
    $response->assertSee("What's new?", false);
});
