<?php

namespace Tests\Unit\VictoryGames;

use App\Ai\Agents\VictoryGames\NativeRunPlannerAgent;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Laravel\Ai\ObjectSchema;
use Tests\TestCase;

class NativeRunPlannerAgentTest extends TestCase
{
    public function test_instructions_describe_live_browser_and_javascript_capabilities(): void
    {
        $instructions = (string) NativeRunPlannerAgent::make()->instructions();

        $this->assertStringContainsString('live browser page', $instructions);
        $this->assertStringContainsString('execute_js action runs inside the real page context', $instructions);
        $this->assertStringContainsString('one focused thing', $instructions);
        $this->assertStringContainsString('Do not write long looping scripts', $instructions);
        $this->assertStringContainsString('Respect the remaining step budget', $instructions);
        $this->assertStringContainsString('do not fail or give_up from the initial page state', $instructions);
    }

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
