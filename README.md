moodle-block_catquiz_feedbackwizard
==================================

[![Moodle Plugin CI](https://github.com/ralferlebach/moodle-block_catquiz_feedbackwizard/actions/workflows/moodle-ci.yml/badge.svg?branch=develop)](https://github.com/ralferlebach/moodle-block_catquiz_feedbackwizard/actions?query=workflow%3A%22Moodle+Plugin+CI%22+branch%3Adevelop)

A course block that guides teachers through the configuration of an adaptive
(CAT) test in six steps, so that a test can be set up without visiting the
technical administration pages of the CAT engine.


Requirements
------------

This plugin requires Moodle 4.5+

It also requires the CAT engine stack, which is declared as a hard dependency
in `version.php`. Moodle refuses to install the block while any of these is
missing:

* `mod_adaptivequiz` (2026081900+) - the carrier activity, which defines the
  `adaptivequizcatmodel` subplugin type
* `adaptivequizcatmodel_catquiz` (2026081900+) - the bridge between the
  activity and the engine
* `local_catquiz` (2026083025+) - the CAT engine itself, providing scales,
  contexts and test environments

`local_catquiz` in turn requires `local_wunderbyte_table`.


Motivation for this plugin
--------------------------

Setting up a CAT test in `local_catquiz` means touching several administration
screens that assume knowledge of scales, contexts, ability ranges and test
environments. That is reasonable for an administrator and a poor fit for a
teacher who simply wants a placement test in their own course.

This plugin is a user interface layer, not a second engine. It collects the
decisions a teacher actually has to make, checks them for plausibility, and
writes the result back through the public API of `local_catquiz`. No CAT logic
is reimplemented here, and nothing is written to the engine tables directly.


Installation
------------

Install the plugin like any other plugin to folder
/blocks/catquiz_feedbackwizard

See http://docs.moodle.org/en/Installing_plugins for details on installing
Moodle plugins.


Usage & Settings
----------------

After installing the plugin, add the "CATQuiz Wizard" block to a course that
contains at least one adaptive quiz using the CATQuiz cat model. Teachers with
the appropriate capability then see an entry point that opens the wizard in a
modal dialogue.

The wizard has six steps:

1. **Choose CAT test** - pick the adaptive quiz to configure, with a readiness
   indication per test
2. **Choose setup mode** - edit the test, clone another test in the same
   course, start from a scenario preset, or import a settings pattern
3. **Edit test settings** - main scale, subscales, question counts, precision
   mode, time limit and completion
4. **Configure feedback ranges** - up to ten ranges with labels, boundaries and
   template-ready texts, plus the optional follow-up actions
5. **Configure matching** - routing rules, either as a single rule or as CSV
6. **Confirm and save** - a review summary, a settings pattern export, and the
   write back to `local_catquiz`

Work in progress is kept in a draft record so that an interrupted session can
be resumed. Drafts are deleted automatically once they exceed their configured
lifetime.

To configure the plugin and its behaviour, please visit:
Site administration -> Plugins -> Blocks -> CATQuiz Wizard

There, you find these settings:

* **Enable course provisioning** - allows the wizard to offer course follow-up
  actions for a feedback range. Disabled by default.
* **Enable group creation** - allows the wizard to offer group follow-up
  actions for a feedback range. Disabled by default.
* **Enable AI refinement of feedback texts** - allows teachers to send feedback
  texts to Moodle's AI subsystem for language refinement. Disabled by default,
  and additionally requires a configured provider for the text generation
  action.
* **AI system prompt** - the system prompt used for refinement. Empty means the
  built-in default is used.
* **Draft lifetime (hours)** - how long an unfinished draft is kept before the
  scheduled task removes it. Default 72.
* **Allowed target categories** - restricts which course categories may be used
  as provisioning targets. Empty means no restriction.
* **Maximum pattern file size (bytes)** - the largest settings pattern accepted
  by the import step. Default 262144.
* **Include feedback texts in exports** - whether exported settings patterns
  carry the feedback texts themselves or only the range structure.

The three "enable" settings gate features that change courses, groups or texts
beyond the CAT test itself. They are off by default and enforced twice: the
user interface hides what is disabled, and the submitted state is stripped
server side before anything is stored.

If you want to learn more about using block plugins in Moodle, please see
https://docs.moodle.org/en/Blocks.


Capabilities
------------

This plugin also introduces these additional capabilities:

* **block/catquiz_feedbackwizard:use** - Allows a user to open the wizard in a
  course. Assigned to managers and editing teachers by default.
* **block/catquiz_feedbackwizard:writeconfig** - Allows a user to write the
  collected configuration back to the CAT test. Assigned to managers and
  editing teachers by default. Keeping this separate from the capability above
  makes read-only access to the wizard possible.


Scheduled Tasks
---------------

This plugin also introduces these additional scheduled tasks:

* **\block_catquiz_feedbackwizard\task\cleanup_drafts** - Deletes wizard drafts
  that exceeded the configured lifetime, and submitted drafts after a short
  grace period.\ By default, the task is enabled and runs daily at 03:17.


How this plugin works / Pitfalls
--------------------------------

The block is deliberately thin. It renders an entry point and a modal form; the
work happens in services under `classes/local/`.

All write access to `local_catquiz` goes through
`classes/local/adapter/local_catquiz_adapter.php`, which uses
`\local_catquiz\testenvironment`. This is not decoration. Writing to the engine
tables directly skips two things the engine does on every save: it purges the
settings cache, and it recalculates the context id when the main scale changed.
Both failures are silent, which is what makes them expensive.

Settings patterns are versioned JSON documents that describe how a test is set
up, not which test they came from. Draft ids, course ids and test ids are
dropped on export. Scale ids travel with their names, because scale ids are
site local: on import every id is checked against the local site and dropped
with a warning when it does not resolve there.

AI refinement never talks to a vendor directly. It hands a prompt to Moodle's
`core_ai` subsystem, so provider choice, credentials, rate limits, the user
policy and the action log stay where an administrator can see them. Only the
feedback text and the wording instructions are sent - no names, course data or
results. If a response loses a placeholder, the original text is kept.

Pitfall worth knowing: a PHPUnit run without the CAT engine installed skips
exactly the tests that exercise the write path. Green without the engine means
less than it looks like. See `docs/dev/environment-setup.md`.


Theme support
-------------

This plugin is developed and tested on Moodle Core's Boost theme.
It should also work with Boost child themes, including Moodle Core's Classic
theme. However, we can't support any other theme than Boost.


Plugin repositories
-------------------

This plugin is not published in the Moodle plugins repository.

The latest development version can be found on Github:
https://github.com/ralferlebach/moodle-block_catquiz_feedbackwizard


Bug and problem reports / Support requests
------------------------------------------

This plugin is carefully developed and thoroughly tested, but bugs and problems
can always appear.

Please report bugs and problems on Github:
https://github.com/ralferlebach/moodle-block_catquiz_feedbackwizard/issues

We will do our best to solve your problems, but please note that due to limited
resources we can't always provide per-case support.


Feature proposals
-----------------

Due to limited resources, the functionality of this plugin is primarily
implemented for our own local needs and published as-is to the community. We
are aware that members of the community will have other needs and would love to
see them solved by this plugin.

Please issue feature proposals on Github:
https://github.com/ralferlebach/moodle-block_catquiz_feedbackwizard/issues

Please create pull requests on Github:
https://github.com/ralferlebach/moodle-block_catquiz_feedbackwizard/pulls

We are always interested to read about your feature proposals or even get a
pull request from you, but please accept that we can handle your issues only as
feature _proposals_ and not as feature _requests_.


Moodle release support
----------------------

Due to limited resources, this plugin is only maintained for the most recent
major release of Moodle as well as the most recent LTS release of Moodle.
Bugfixes are backported to the LTS release. However, new features and
improvements are not necessarily backported to the LTS release.

Apart from these maintained releases, previous versions of this plugin which
work in legacy major releases of Moodle are still available as-is without any
further updates in the Moodle Plugins repository.

There may be several weeks after a new major release of Moodle has been
published until we can do a compatibility check and fix problems if necessary.
If you encounter problems with a new major release of Moodle - or can confirm
that this plugin still works with a new major release - please let us know on
Github.

This plugin is designed to be compatible with all currently supported versions
of Moodle, leveraging its latest APIs. However, if you are using a legacy
version of Moodle, we kindly advise against installing or using this plugin.
Instead, we strongly recommend updating your Moodle instance to a supported
version to ensure security and compliance with current technological standards.
Thank you for your understanding.


Translating this plugin
-----------------------

This Moodle plugin is currently provided with an English language pack only. A
German language pack is planned but not yet part of the plugin. Translations
into other languages must be managed through AMOS (https://lang.moodle.org),
where they will become part of Moodle's official language pack.

Thank you for supporting the global Moodle community!


Right-to-left support
---------------------

This plugin has not been tested with Moodle's support for right-to-left (RTL)
languages. If you want to use this plugin with a RTL language and it doesn't
work as-is, you are free to send us a pull request on Github with
modifications.


Maintainers
-----------

The plugin is maintained by\
Ralf Erlebach


Copyright
---------

The copyright of this plugin is held by\
Ralf Erlebach

Individual copyrights of individual developers are tracked in PHPDoc comments
and Git commits.
