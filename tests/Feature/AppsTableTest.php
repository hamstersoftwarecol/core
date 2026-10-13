<?php

use AppCore\Core\Models\PluginApp;

test('la tabla apps existe y el modelo persiste con casts', function () {
    expect(Schema::hasTable('apps'))->toBeTrue();

    $row = PluginApp::create([
        'slug' => 'demo',
        'name' => 'Demo',
        'version' => '1.0.0',
        'status' => PluginApp::STATUS_INACTIVE,
        'config' => ['per_page' => 10],
    ]);

    expect($row->config)->toBe(['per_page' => 10])
        ->and($row->fresh()->status)->toBe('inactive');
});
