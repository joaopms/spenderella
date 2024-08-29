<?php

uses(
    Tests\TestCase::class,
    Illuminate\Foundation\Testing\RefreshDatabase::class,
);

use App\Models\NordigenAgreement;
use App\Services\NordigenService;
use Mockery\MockInterface;

beforeEach(function () {
});

it('creates an end user agreement', function () {
    $institutionId = 'FAKEINSTITUTION';
    $mockedRequisitionData = [
        'id' => '76f487ad-bdd9-4b38-bbe1-0d2ccb38dc5c',
        'institution_id' => $institutionId,
        'created' => now()->toISOString(),
    ];

    $mock = $this->mock(\App\Integrations\Nordigen\NordigenClient::class, function (MockInterface $mock) use ($mockedRequisitionData) {
        $mock->shouldReceive('endUserAgreementCreate')
            ->once()
            ->andReturn($mockedRequisitionData);
    });

    $service = new NordigenService($mock);
    $agreement = $service->createEndUserAgreement($institutionId);

    expect(NordigenAgreement::count())->toBe(1);
});

//it('should create a new requisition', function () {
//    $mockedRequisitionData = [
//        'id' => '76f487ad-bdd9-4b38-bbe1-0d2ccb38dc5c',
//        'link' => 'https://ob.gocardless.com/psd2/start/76f487ad-bdd9-4b38-bbe1-0d2ccb38dc5c/SANDBOXFINANCE_SFIN0000',
//        'created' => now()->toISOString(),
//    ];
//
//    // Get the request handler from the client needed for mocking the creation of requisitions
//    //    $reflection = new ReflectionProperty(\Nordigen\NordigenPHP\API\NordigenClient::class, 'requestHandler');
//    //    $reflection->setAccessible(true);
//    //    $requestHandler = $reflection->getValue($client);
//
//    // Mock the creation of requisitions
//    //    $requisitionMock = $this->partialMock(Requisition::class, function (MockInterface $mock) use ($mockedRequisitionData, $requestHandler) {
//    //        $mock->shouldReceive('__construct')->with($requestHandler);
//    //        $mock->shouldReceive('createRequisition')->andReturn($mockedRequisitionData);
//    //    });
//
//    // Swap the client's requisition with a mocked one
//    //    $client->requisition = $requisitionMock;
//
//    $client = Mockery::mock(\Nordigen\NordigenPHP\API\NordigenClient::class);
//    $client->shouldReceive('newRequisition')->andReturn($mockedRequisitionData);
//
//    // Actually test the requisition creation
//    $requisition = $client->newRequisition();
//
//    expect(\App\Models\NordigenRequisition::count())->toBe(1)
//        ->and($requisition->toArray())->toContain(...$mockedRequisitionData);
//});
