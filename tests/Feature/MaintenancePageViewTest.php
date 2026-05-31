<?php

it('renders the tracked maintenance page copy and robots directives', function () {
    $html = view('maintenance')->render();

    expect($html)
        ->toContain('Maintenance Mode')
        ->toContain('We&rsquo;re on a lunch break')
        ->toContain('Sorry, this development project is temporarily unavailable.')
        ->toContain('Please check back a little later.')
        ->toContain('noindex,nofollow,noarchive');
});
