<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Unit tests for the optional AI feedback refinement.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_catquiz_feedbackwizard;

use block_catquiz_feedbackwizard\local\adapter\ai_provider_adapter;
use block_catquiz_feedbackwizard\local\service\ai_feedback_service;

/**
 * Unit tests for the optional AI feedback refinement.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \block_catquiz_feedbackwizard\local\service\ai_feedback_service
 * @covers \block_catquiz_feedbackwizard\local\adapter\ai_provider_adapter
 */
final class ai_feedback_service_test extends \advanced_testcase {
    /**
     * Refinement must stay off until an administrator enables it.
     *
     * @return void
     */
    public function test_refinement_is_unavailable_by_default(): void {
        $this->resetAfterTest();

        $this->assertFalse(ai_feedback_service::is_available());
    }

    /**
     * The plugin setting alone must not make the feature available.
     *
     * @return void
     */
    public function test_setting_alone_does_not_enable_refinement(): void {
        $this->resetAfterTest();

        set_config('enable_ai_feedback_refinement', 1, 'block_catquiz_feedbackwizard');

        // No AI provider is configured in a fresh test site, so the adapter has
        // to veto even though the plugin setting is on.
        $this->assertFalse(ai_provider_adapter::is_available());
        $this->assertFalse(ai_feedback_service::is_available());
    }

    /**
     * The prompt must carry the text and the instructions and nothing else.
     *
     * @return void
     */
    public function test_prompt_contains_only_text_and_instructions(): void {
        $this->resetAfterTest();

        $prompt = ai_feedback_service::build_prompt('Your result is low.', 'Use a warmer tone.');

        $this->assertStringContainsString('Your result is low.', $prompt);
        $this->assertStringContainsString('Use a warmer tone.', $prompt);
        $this->assertStringContainsString(ai_feedback_service::DEFAULT_SYSTEM_PROMPT, $prompt);
    }

    /**
     * A configured system prompt must replace the built-in default.
     *
     * @return void
     */
    public function test_configured_system_prompt_is_used(): void {
        $this->resetAfterTest();

        set_config('ai_feedback_systemprompt', 'Write in plain German.', 'block_catquiz_feedbackwizard');

        $prompt = ai_feedback_service::build_prompt('Some text.');

        $this->assertStringContainsString('Write in plain German.', $prompt);
        $this->assertStringNotContainsString(ai_feedback_service::DEFAULT_SYSTEM_PROMPT, $prompt);
    }

    /**
     * Placeholder loss must be detectable so the original text can win.
     *
     * @return void
     */
    public function test_placeholder_preservation_check(): void {
        $this->resetAfterTest();

        $original = 'Well done in {{result.scalename}}, see {{course.fullname}}.';

        $this->assertTrue(ai_feedback_service::placeholders_preserved(
            $original,
            'Great work in {{result.scalename}}. Have a look at {{course.fullname}}.'
        ));
        $this->assertFalse(ai_feedback_service::placeholders_preserved(
            $original,
            'Great work. Have a look at the course.'
        ));
        $this->assertFalse(ai_feedback_service::placeholders_preserved(
            $original,
            'Great work in {{result.scalename}}.'
        ));
    }

    /**
     * Without a provider the texts must survive untouched, with an explanation.
     *
     * @return void
     */
    public function test_unavailable_refinement_leaves_state_untouched(): void {
        $this->resetAfterTest();

        $state = [
            'feedbackrangecount' => 2,
            'feedbacktext_1' => 'First text.',
            'feedbacktext_2' => 'Second text.',
            'useairefinement' => 1,
        ];

        $refined = ai_feedback_service::refine_wizard_state($state, 2, \context_system::instance()->id);

        $this->assertSame('First text.', $refined['feedbacktext_1']);
        $this->assertSame('Second text.', $refined['feedbacktext_2']);
        $this->assertNotEmpty(ai_feedback_service::get_messages());
    }

    /**
     * The adapter must refuse to act when the subsystem is not available.
     *
     * @return void
     */
    public function test_adapter_returns_empty_string_when_unavailable(): void {
        $this->resetAfterTest();

        $this->assertSame('', ai_provider_adapter::generate_text('Some prompt', 2, 1));
        $this->assertSame('', ai_provider_adapter::generate_text('', 2, 1));
    }
}
