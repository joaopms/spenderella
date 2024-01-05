<?php

uses(
    Tests\TestCase::class,
    Illuminate\Foundation\Testing\RefreshDatabase::class,
);

use App\Integrations\Nordigen\NordigenClient;
use Mockery\MockInterface;
use Nordigen\NordigenPHP\API\Requisition;

it('should create a new requisition', function () {
    $client = new NordigenClient();

    $mockedRequisitionData = [
        'id' => '76f487ad-bdd9-4b38-bbe1-0d2ccb38dc5c',
        'link' => 'https://ob.gocardless.com/psd2/start/76f487ad-bdd9-4b38-bbe1-0d2ccb38dc5c/SANDBOXFINANCE_SFIN0000',
        'created' => now()->toISOString(),
    ];

    // Get the request handler from the client needed for mocking the creation of requisitions
    $reflection = new ReflectionProperty(\Nordigen\NordigenPHP\API\NordigenClient::class, 'requestHandler');
    $reflection->setAccessible(true);
    $requestHandler = $reflection->getValue($client);

    // Mock the creation of requisitions
    $requisitionMock = $this->partialMock(Requisition::class, function (MockInterface $mock) use ($mockedRequisitionData, $requestHandler) {
        $mock->shouldReceive('__construct')->with($requestHandler);
        $mock->shouldReceive('createRequisition')->andReturn($mockedRequisitionData);
    });

    // Swap the client's requisition with a mocked one
    $client->requisition = $requisitionMock;

    // Actually test the requisition creation
    $requisition = $client->newRequisition();

    expect(\App\Models\NordigenRequisition::count())->toBe(1)
        ->and($requisition->toArray())->toContain(...$mockedRequisitionData);
});
