<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The playground's "Parsed AST" panel shows how each operator was read (N5-M7). It is built
 * from the package's @internal parser, so a change there fails this test, not the page.
 */
class PlaygroundAstTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_ast_panel_names_each_node_type_and_operator(): void
    {
        $page = $this->get('/search/playground?q=' . urlencode('^Smith Doe$ !banned | email:~jonh'))->assertOk();

        $this->assertSame([
            'type' => 'OrNode', 'operator' => '|', 'children' => [
                ['type' => 'AndNode', 'operator' => 'AND', 'children' => [
                    ['type' => 'PrefixTerm', 'operator' => '^', 'term' => 'Smith'],
                    ['type' => 'SuffixTerm', 'operator' => '$', 'term' => 'Doe'],
                    ['type' => 'NotNode', 'operator' => '!', 'child' => ['type' => 'FuzzyTerm', 'term' => 'banned']],
                ]],
                ['type' => 'FieldTerm', 'operator' => 'field:', 'field' => 'email', 'term' => ['type' => 'TypoTerm', 'operator' => '~', 'term' => 'jonh']],
            ],
        ], $page->viewData('ast'));

        $page->assertSee('Parsed AST')->assertSee('"type": "PrefixTerm"')->assertSee('"operator": "^"');
    }
}
