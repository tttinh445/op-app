<?php

test('the root URL redirects to customers list', function () {
    $response = $this->get('/');

    $response->assertRedirect(route('customers.index'));
});
