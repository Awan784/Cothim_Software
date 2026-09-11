<?php

it('shows the login page at the root url', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('name="email"', false)
        ->assertDontSee('Start now for free')
        ->assertDontSee('Pricing')
        ->assertDontSee('عربي')
        ->assertDontSee('14-day free trial');
});

it('does not expose the public website or signup', function () {
    $this->get('/pricing')->assertNotFound();
    $this->get('/register')->assertNotFound();
    $this->get('/locale/ar')->assertNotFound();
    $this->get('/settings/plans')->assertNotFound();
    $this->get('/get-started')->assertNotFound();
    $this->get('/inbox')->assertNotFound();
    $this->get('/bills')->assertNotFound();
    $this->get('/investors')->assertNotFound();
    $this->get('/nominal-accounts')->assertNotFound();
    $this->get('/stock-movements')->assertNotFound();
});
