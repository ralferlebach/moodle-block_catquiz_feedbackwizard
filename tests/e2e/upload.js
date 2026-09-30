/**
 * Uploads a feedback CSV through the real Moodle filepicker.
 *
 * The filepicker is not a plain <input type="file">: it is a YUI dialogue that
 * uploads into a draft area and hands the form a draft item id. Unit tests
 * cannot reach that path, and the Behat scenarios in tests/behat/ carry no
 * @javascript, so this walkthrough is the only thing that exercises it.
 *
 * It also covers the reason the form no longer uses
 * moodleform::get_file_content(): calling it from validation() recurses,
 * because validate_defined_fields() sets $this->_validated only after
 * validation() has returned.
 */
const { chromium } = require('playwright');
const fs = require('fs');
const os = require('os');
const path = require('path');

const BASE = process.env.MOODLE_URL || 'http://127.0.0.1:8000';
const SHOTS = process.env.SHOT_DIR || '/tmp/catquiz-upload-shots';
const USER = process.env.MOODLE_USER || 'teacher1';
const PASS = process.env.MOODLE_PASS || 'Teacher123!';
const COURSE_ID = parseInt(process.env.COURSE_ID || '0', 10);

const problems = [];
const log = (m) => console.log(m);

const PATTERN = JSON.stringify({
    format: 'catquiz-settings-pattern',
    version: 1,
    exported_at: '2026-09-29T00:00:00Z',
    meta: { name: 'uploaded-pattern', generator: 'e2e' },
    settings: { scenario: 'placement', precisionmode: 'high', questioncount: 17 },
    scales: { main: { id: 0, name: '' }, subscales: [] },
    feedback: {
        reportingstrategy: 'main_only',
        rangecount: 2,
        includes_texts: 1,
        ranges: [
            { label: 'Pattern low', lower: -3, upper: 0, text: 'From the pattern.' },
            { label: 'Pattern high', lower: 0, upper: 3, text: 'Also from the pattern.' },
        ],
    },
    routing: { mode: 'none' },
}, null, 2);

const CSV = [
    'range,label,text',
    '1,Imported low,"Uploaded text for {{result.scalename}}."',
    '2,Imported high,"Well done in {{test.name}}."',
].join('\n');

/** Boost hides blocks in a collapsed drawer; open it if needed. */
async function openBlockDrawer(page) {
    const trigger = page.locator('.js-open-catquiz_feedbackwizard').first();
    if (await trigger.isVisible().catch(() => false)) {
        return;
    }
    const toggle = page.locator(
        '[data-toggler="drawers"][data-target*="block"], .drawertoggle, button[data-target="#theme_boost-drawers-blocks"]'
    ).first();
    if (await toggle.count()) {
        await toggle.click().catch(() => {});
        await page.waitForTimeout(1200);
    }
    if (!(await trigger.isVisible().catch(() => false))) {
        // Last resort: reveal it so the walkthrough tests the wizard, not the theme.
        await page.evaluate(() => {
            document.querySelectorAll('[data-region="blocks-column"], .drawer').forEach((el) => {
                el.classList.add('show');
                el.style.display = 'block';
                el.style.visibility = 'visible';
            });
        });
        await page.waitForTimeout(600);
    }
}

async function shot(page, name) {
    await page.screenshot({ path: `${SHOTS}/${name}.png` }).catch(() => {});
}

/** Walk the modal forward and wait for the next step's heading. */
async function advance(page, heading) {
    await page.locator('.modal-footer .btn-primary').last().click();
    await page.waitForFunction(
        (t) => document.body.innerText.includes(t),
        heading,
        { timeout: 30000 }
    );
    await page.waitForTimeout(900);
}

/**
 * Upload a file through the Moodle filepicker of the given form element.
 */
async function uploadViaFilepicker(page, elementName, filepath) {
    // The visible control is a button inside the filepicker wrapper.
    const wrapper = page.locator(`.modal-body [data-fieldtype="filepicker"]`).filter({
        has: page.locator(`input[name="${elementName}"]`),
    }).last();

    const picker = (await wrapper.count()) ? wrapper : page.locator('.modal-body .filepicker-container').last();
    await picker.locator('.fp-btn-choose, .btn').first().click();

    // The picker dialogue is rendered at body level, not inside the modal.
    const dialog = page.locator('.file-picker.fp-generallayout').last();
    await dialog.waitFor({ state: 'visible', timeout: 20000 });

    const uploadTab = dialog.locator('.fp-repo-area .fp-repo', { hasText: /upload/i }).first();
    if (await uploadTab.count()) {
        await uploadTab.click();
        await page.waitForTimeout(500);
    }

    await dialog.locator('input[type="file"]').first().setInputFiles(filepath);
    await page.waitForTimeout(400);

    await dialog.locator('.fp-upload-btn').first().click();
    await dialog.waitFor({ state: 'hidden', timeout: 30000 });
    await page.waitForTimeout(1200);
}

(async () => {
    if (!COURSE_ID) {
        console.error('Set COURSE_ID; seed_scales.php prints it.');
        process.exitCode = 1;
        return;
    }

    fs.mkdirSync(SHOTS, { recursive: true });
    const csvpath = path.join(os.tmpdir(), 'catquiz-feedback-import.csv');
    fs.writeFileSync(csvpath, CSV);
    const patternpath = path.join(os.tmpdir(), 'catquiz-settings-pattern.json');
    fs.writeFileSync(patternpath, PATTERN);

    let exportedpath = null;

    const browser = await chromium.launch({ args: ['--no-sandbox', '--disable-dev-shm-usage'] });
    const context = await browser.newContext({
        viewport: { width: 1400, height: 1000 },
        acceptDownloads: true,
    });
    const page = await context.newPage();

    page.on('pageerror', (e) => problems.push('page error: ' + e.message));
    page.on('response', (r) => {
        if (r.status() >= 500) {
            problems.push('HTTP ' + r.status() + ' on ' + r.url());
        }
    });

    try {
        await page.goto(`${BASE}/login/index.php`, { waitUntil: 'domcontentloaded' });
        await page.fill('#username', USER);
        await page.fill('#password', PASS);
        await page.click('#loginbtn');
        await page.waitForLoadState('domcontentloaded');

        await page.goto(`${BASE}/course/view.php?id=${COURSE_ID}`, { waitUntil: 'domcontentloaded' });
        await openBlockDrawer(page);
        await page.locator('.js-open-catquiz_feedbackwizard').first().click();
        await page.locator('.modal-dialog').last().waitFor({ state: 'visible', timeout: 20000 });
        await page.waitForTimeout(1200);

        // Step 1: pick the only test.
        const testSelect = page.locator('.modal-body select[name="selectedtest"]').last();
        const values = await testSelect.locator('option').evaluateAll(
            (els) => els.map((e) => e.value).filter((v) => v && v !== '0')
        );
        if (!values.length) {
            throw new Error('no CAT test available');
        }
        await testSelect.selectOption(values[0]);
        await advance(page, 'Choose setup mode');

        // Step 2 and 3 pass through unchanged.
        await page.locator('.modal-body select[name="wizardmode"]').last().selectOption('edit');
        await advance(page, 'Edit test settings');
        await advance(page, 'Configure feedback ranges');
        await shot(page, '01-step4-before-upload');

        // Step 4: the actual subject of this test.
        log('uploading ' + csvpath + ' through the filepicker');
        await uploadViaFilepicker(page, 'feedbackimportfile', csvpath);
        await shot(page, '02-step4-after-upload');

        const picked = await page.locator('.modal-body .fp-filename-field, .modal-body .filepicker-filename')
            .first().innerText().catch(() => '');
        log('filepicker shows: ' + JSON.stringify(picked.trim()));
        if (!/\.csv/i.test(picked)) {
            problems.push('the filepicker does not show the uploaded file');
        }

        await advance(page, 'Configure matching');
        await advance(page, 'Confirm and save');
        await shot(page, '03-step6');

        const review = await page.locator('.modal-body').last().innerText();
        log('--- review ---');
        log(review.slice(0, 1500));
        log('--- end review ---');

        // The imported label and the resolved placeholder must both be visible.
        if (!review.includes('Imported low')) {
            problems.push('the imported label did not reach the review step');
        }
        if (!review.includes('Uploaded text for')) {
            problems.push('the imported text did not reach the review step');
        }
        if (review.includes('{{result.scalename}}')) {
            problems.push('the placeholder was not resolved in the preview');
        }

        // ---- Download the settings pattern before saving -----------------
        // The link is rendered with a sesskey, but nothing so far has checked
        // that it actually serves a file, let alone a valid pattern.
        const exportLink = page.locator('.modal-body a[href*="export.php"]').last();
        if (!(await exportLink.count())) {
            problems.push('no export link in the review step');
        } else {
            const [download] = await Promise.all([
                page.waitForEvent('download', { timeout: 30000 }),
                exportLink.click(),
            ]);
            exportedpath = path.join(os.tmpdir(), 'catquiz-exported-pattern.json');
            await download.saveAs(exportedpath);
            log('downloaded as ' + download.suggestedFilename());

            const raw = fs.readFileSync(exportedpath, 'utf8');
            let pattern = null;
            try {
                pattern = JSON.parse(raw);
            } catch (e) {
                problems.push('the export is not valid JSON: ' + e.message);
            }
            if (pattern) {
                if (pattern.format !== 'catquiz-settings-pattern') {
                    problems.push('wrong format marker: ' + JSON.stringify(pattern.format));
                }
                if (pattern.version !== 1) {
                    problems.push('wrong version: ' + JSON.stringify(pattern.version));
                }
                const labels = (pattern.feedback && pattern.feedback.ranges || []).map((r) => r.label);
                log('exported labels: ' + JSON.stringify(labels));
                if (!labels.includes('Imported low')) {
                    problems.push('the export does not carry the imported labels');
                }
                // The export must stay free of instance references.
                for (const key of ['selectedtest', 'draftid', 'courseid', 'sourcetestid']) {
                    if (raw.includes(key)) {
                        problems.push('the export leaks the instance reference "' + key + '"');
                    }
                }
                // Placeholders belong in a pattern: it is a template, not output.
                const texts = (pattern.feedback && pattern.feedback.ranges || []).map((r) => r.text).join(' ');
                if (!texts.includes('{{result.scalename}}')) {
                    problems.push('the export resolved the placeholders; a pattern must keep them');
                }
            }
        }

        await page.locator('.modal-footer .btn-primary').last().click();
        await page.waitForTimeout(3000);
        await shot(page, '04-saved');

        // ---- Second scenario: the settings pattern upload in step 2 -------
        // This is the path whose file is read during validation(), so it is
        // the one that used to risk recursing through get_file_content().
        log('');
        log('== pattern import in step 2 ==');
        await page.goto(`${BASE}/course/view.php?id=${COURSE_ID}`, { waitUntil: 'domcontentloaded' });
        await openBlockDrawer(page);
        await page.locator('.js-open-catquiz_feedbackwizard').first().click();
        await page.locator('.modal-dialog').last().waitFor({ state: 'visible', timeout: 20000 });
        await page.waitForTimeout(1200);

        const select2 = page.locator('.modal-body select[name="selectedtest"]').last();
        const values2 = await select2.locator('option').evaluateAll(
            (els) => els.map((e) => e.value).filter((v) => v && v !== '0')
        );
        await select2.selectOption(values2[0]);
        await advance(page, 'Choose setup mode');

        await page.locator('.modal-body select[name="wizardmode"]').last().selectOption('import');
        await page.waitForTimeout(700);
        await shot(page, '05-step2-import-mode');

        // Round trip: prefer the file the plugin itself produced.
        const importsource = exportedpath || patternpath;
        log('importing ' + (exportedpath ? 'the exported pattern' : 'the hand-written pattern'));
        await uploadViaFilepicker(page, 'patternfile', importsource);
        await shot(page, '06-step2-after-upload');

        // If validation() recursed or the file were unreadable, this step would
        // either hang or bounce back with "Choose a settings pattern file".
        await advance(page, 'Edit test settings');
        log('step 2 accepted the uploaded pattern');

        // The pattern carries no scale id (they are site local), so step 3
        // asks for one. Picking it here keeps the walkthrough about the upload.
        const mainscale = page.locator('.modal-body select[name="mainscaleid"]').last();
        if (await mainscale.count()) {
            const scalevalues = await mainscale.locator('option').evaluateAll(
                (els) => els.map((e) => e.value).filter((v) => v && v !== '0')
            );
            if (scalevalues.length) {
                await mainscale.selectOption(scalevalues[0]);
                log('selected main scale ' + scalevalues[0]);
            }
        }

        await advance(page, 'Configure feedback ranges');
        // Labels live in input values, which innerText does not contain.
        const label1 = await page.locator('.modal-body input[name="feedbacklabel_1"]').last()
            .inputValue().catch(() => '');
        const text1 = await page.locator('.modal-body textarea[name="feedbacktext_1"]').last()
            .inputValue().catch(() => '');
        log('step 4 field feedbacklabel_1 = ' + JSON.stringify(label1));
        log('step 4 field feedbacktext_1  = ' + JSON.stringify(text1));
        const expectedlabel = exportedpath ? 'Imported low' : 'Pattern low';
        const expectedtext = exportedpath ? 'Uploaded text for' : 'From the pattern';
        if (label1 !== expectedlabel) {
            problems.push('the pattern label did not reach the step 4 field (got ' + JSON.stringify(label1) + ')');
        }
        if (!text1.includes(expectedtext)) {
            problems.push('the pattern text did not reach the step 4 field (got ' + JSON.stringify(text1) + ')');
        }
        if (exportedpath && !text1.includes('{{result.scalename}}')) {
            problems.push('the round trip lost the placeholder');
        }
        await shot(page, '07-step4-from-pattern');

    } catch (e) {
        problems.push('EXCEPTION: ' + e.message);
        await shot(page, '99-failure');
    } finally {
        await browser.close();
    }

    log('');
    if (problems.length) {
        log('PROBLEMS (' + problems.length + '):');
        problems.forEach((p) => log('  - ' + p));
        process.exitCode = 1;
    } else {
        log('Upload walkthrough completed without problems.');
    }
})();
