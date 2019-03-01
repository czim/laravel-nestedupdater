<?php
namespace Czim\NestedModelUpdater\Test;

use Czim\NestedModelUpdater\Test\Helpers\Requests\NestedPostRequest;

class NestedValidatorFormRequestTest extends TestCase
{

    public function setUp(): void
    {
        parent::setUp();

        // will return 'ok' if the validation passes or json errors if it fails
        $this->app['router']->any('testing', function(NestedPostRequest $request) {
            return response('ok');
        });
    }

    /**
     * @test
     */
    function it_performs_validation_for_invalid_nested_create_data_through_a_form_request()
    {
        $response = $this->post('testing', [
            'title' => 'disallowed title that is way and way too long to be allowed '
                     . 'by the nested validator',
            'genre' => [
                'id'   => 999,
                'name' => 'some genre name',
            ],
        ]);

        $response
            ->assertStatus(422)
            ->assertJson([
                "title"    => ["The title may not be greater than 50 characters."],
                "genre.id" => ["The selected genre.id is invalid."],
            ]);
    }

    /**
     * @test
     */
    function it_performs_validation_for_invalid_nested_update_data_through_a_form_request()
    {
        // the title for updates may be no longer than 10 characters,
        // for the create
        $response = $this->put('testing', [
            'title' => 'ten characters allowed',
            'genre' => [
                'id'   => 999,
                'name' => 'some genre name',
            ],
        ]);

        $response
            ->assertStatus(422)
            ->assertJson([
                "body"     => ["The body field is required."],
                "genre.id" => ["The selected genre.id is invalid."],
                "title"    => ["The title may not be greater than 10 characters."],
            ]);
    }

    /**
     * @test
     */
    function it_performs_validation_for_valid_nested_create_data_through_a_form_request()
    {
        $response = $this->post('testing', [
            'title' => 'allowed title',
            'genre' => [
                'name' => 'allowed genre name',
            ],
        ]);

        $response->assertStatus(200);
        $this->assertEquals('ok', $response->getContent());
    }

}
