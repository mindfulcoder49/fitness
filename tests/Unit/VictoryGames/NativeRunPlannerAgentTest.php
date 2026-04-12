<?php

namespace Tests\Unit\VictoryGames;

use App\Ai\Agents\VictoryGames\NativeRunPlannerAgent;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Laravel\Ai\ObjectSchema;
use Tests\TestCase;

class NativeRunPlannerAgentTest extends TestCase
{
    public function test_openai_structured_output_schema_requires_all_fields(): void
    {
        $schema = (new ObjectSchema(
            NativeRunPlannerAgent::make()->schema(new JsonSchemaTypeFactory)
        ))->toSchema();

        $this->assertSame([
            'action',
            'url',
            'script',
            'summary',
            'reason',
            'key',
            'value',
            'intent',
            'reasoning',
            'last_action_result',
        ], $schema['required']);

        $this->assertSame(['string', 'null'], $schema['properties']['url']['type']);
        $this->assertSame(['string', 'null'], $schema['properties']['script']['type']);
        $this->assertSame(['string', 'null'], $schema['properties']['summary']['type']);
        $this->assertSame(['string', 'null'], $schema['properties']['reason']['type']);
        $this->assertSame(['string', 'null'], $schema['properties']['key']['type']);
        $this->assertSame(['string', 'null'], $schema['properties']['value']['type']);
        $this->assertSame(['string', 'null'], $schema['properties']['last_action_result']['type']);
    }
}
