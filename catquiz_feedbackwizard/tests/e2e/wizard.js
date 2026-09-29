/**
 * Interactive walkthrough of the CATQuiz Wizard.
 *
 * Drives the modal through all six steps as a teacher would, capturing a
 * screenshot per step and failing loudly on a PHP notice, an exception or a
 * step that does not advance.
 */
const { chromium } = require('playwright');
const fs = require('fs');

const BASE = process.env.MOODLE_URL || 'http://127.0.0.1:8000';
const SHOTS = process.env.SHOT_DIR || '/tmp/catquiz-wizard-shots';
const USER = process.env.MOODLE_USER || 'teacher1';
const PASS = process.env.MOODLE_PASS || 'Teacher123!';
const COURSE_ID = parseInt(process.env.COURSE_ID || '0', 10);

const problems = [];
const log = (m) => console.log(m);

function watch(page) {
    page.on('console', (msg) => {
        if (msg.type() === 'error') {
            // Chromium complains about external resources it cannot verify in
            // this offline container. Not our concern.
            if (/ERR_CERT|ERR_NAME_NOT_RESOLVED|ERR_INTERNET_DISCONNECTED/.test(msg.text())) {
                return;
            }
            problems.push('console error: ' + msg.text());
        }
    });
    page.on('pageerror', (err) => problems.push('page error: ' + err.message));
    page.on('response', (res) => {
        if (res.status() >= 500) {
            problems.push('HTTP ' + res.status() + ' on ' + res.url());
        }
    });
}

async function checkForPhpErrors(page, label) {
    const body = await page.locator('body').innerText().catch(() => '');
    // Match PHP diagnostics, not the wizard's own "Warning: ..." review lines.
    for (const needle of ['Fatal error', 'PHP Warning', 'PHP Notice', 'Deprecated:',
        'Exception - ', 'Debug info', 'Coding error detected', 'Whoops']) {
        if (body.includes(needle)) {
            problems.push(`${label}: page contains "${needle}"`);
        }
    }
}

async function shot(page, name) {
    await page.screenshot({ path: `${SHOTS}/${name}.png`, fullPage: false });
}

/** Return the visible modal body, waiting for it to settle. */
async function modal(page) {
    const dialog = page.locator('[role="dialog"], .modal-dialog').last();
    await dialog.waitFor({ state: 'visible', timeout: 20000 });
    return dialog;
}

/** Click the modal's primary (save/next) button and wait for the step to change. */
async function advance(page, expectedHeadingAfter) {
    const btn = page.locator('.modal-footer .btn-primary').last();
    await btn.waitFor({ state: 'visible', timeout: 20000 });
    await btn.click();
    if (expectedHeadingAfter) {
        try {
            await page.waitForFunction(
                (t) => document.body.innerText.includes(t),
                expectedHeadingAfter,
                { timeout: 25000 }
            );
        } catch (e) {
            const body = await page.locator('.modal-body').last().innerText().catch(() => '(no modal)');
            const errs = await page.locator('.modal-body .error, .modal-body .invalid-feedback, .modal-body .form-control-feedback')
                .allTextContents().catch(() => []);
            problems.push('did not reach "' + expectedHeadingAfter + '"');
            problems.push('  visible validation errors: ' + JSON.stringify(errs.filter(Boolean)));
            problems.push('  modal body was: ' + body.slice(0, 900).replace(/\n+/g, ' | '));
            throw e;
        }
    }
    await page.waitForTimeout(800);
}

(async () => {
    fs.mkdirSync(SHOTS, { recursive: true });
    const browser = await chromium.launch({
        args: ['--no-sandbox', '--disable-dev-shm-usage'],
    });
    const context = await browser.newContext({ viewport: { width: 1400, height: 1000 } });
    const page = await context.newPage();
    watch(page);

    try {
        // --- Login -------------------------------------------------------
        await page.goto(`${BASE}/login/index.php`, { waitUntil: 'domcontentloaded' });
        await page.fill('#username', USER);
        await page.fill('#password', PASS);
        await page.click('#loginbtn');
        await page.waitForLoadState('domcontentloaded');
        log('logged in as ' + USER);

        // --- Course page -------------------------------------------------
        await page.goto(`${BASE}/course/view.php?id=${COURSE_ID}`, { waitUntil: 'domcontentloaded' });
        await checkForPhpErrors(page, 'course page');
        await shot(page, '00-course');

        const trigger = page.locator('.js-open-catquiz_feedbackwizard[data-action="open-wizard"]');
        const count = await trigger.count();
        log('wizard trigger elements found: ' + count);
        if (count === 0) {
            problems.push('course page: wizard trigger not rendered');
            throw new Error('no wizard trigger');
        }

        // --- Step 1: choose CAT test -------------------------------------
        await trigger.first().click();
        await modal(page);
        await page.waitForTimeout(1200);
        await checkForPhpErrors(page, 'step 1');
        await shot(page, '01-step1');
        const step1text = await page.locator('.modal-body').last().innerText();
        log('step 1 body starts: ' + step1text.slice(0, 120).replace(/\n/g, ' | '));

        const testSelect = page.locator('.modal-body select[name="selectedtest"]').last();
        const options = await testSelect.locator('option').allTextContents();
        log('step 1 test options: ' + JSON.stringify(options));
        // Pick the first option that carries a real test id.
        const values = await testSelect.locator('option').evaluateAll(
            (els) => els.map((e) => e.value).filter((v) => v && v !== '0')
        );
        if (!values.length) {
            problems.push('step 1: no selectable CAT test');
            throw new Error('no CAT test to select');
        }
        await testSelect.selectOption(values[0]);
        log('selected test value ' + values[0]);

        await advance(page, 'Choose setup mode');

        // --- Step 2: setup mode ------------------------------------------
        await checkForPhpErrors(page, 'step 2');
        await shot(page, '02-step2');
        const modes = await page.locator('.modal-body select[name="wizardmode"]').last()
            .locator('option').allTextContents();
        log('step 2 modes: ' + JSON.stringify(modes));
        await page.locator('.modal-body select[name="wizardmode"]').last().selectOption('edit');
        await advance(page, 'Edit test settings');

        // --- Step 3: test settings ---------------------------------------
        await checkForPhpErrors(page, 'step 3');
        await shot(page, '03-step3');
        const step3 = await page.locator('.modal-body').last().innerText();
        log('step 3 mentions subscales: ' + /subscale/i.test(step3));
        const mainscale = page.locator('.modal-body select[name="mainscaleid"]').last();
        if (await mainscale.count()) {
            const opts = await mainscale.locator('option').allTextContents();
            log('step 3 main scale options: ' + JSON.stringify(opts));
        }
        const qcount = page.locator('.modal-body input[name="questioncount"]').last();
        if (await qcount.count()) {
            await qcount.fill('18');
            log('set questioncount to 18');
        }
        await advance(page, 'Configure feedback ranges');

        // --- Step 4: feedback ranges -------------------------------------
        await checkForPhpErrors(page, 'step 4');
        await shot(page, '04-step4');
        const label1 = page.locator('.modal-body input[name="feedbacklabel_1"]').last();
        if (await label1.count()) {
            await label1.fill('Needs support');
            const t1 = page.locator('.modal-body textarea[name="feedbacktext_1"]').last();
            if (await t1.count()) {
                await t1.fill('Please review the basics of {{result.scalename}}.');
            }
            log('filled feedback range 1');
        }
        const step4 = await page.locator('.modal-body').last().innerText();
        log('step 4 shows gating notice: ' + /disabled by the site administrator/i.test(step4));
        log('step 4 shows AI section: ' + /AI text refinement/i.test(step4));
        await advance(page, 'Configure matching');

        // --- Step 5: matching (the step that used to fatal) --------------
        await checkForPhpErrors(page, 'step 5');
        await shot(page, '05-step5');
        const step5 = await page.locator('.modal-body').last().innerText();
        log('step 5 body starts: ' + step5.slice(0, 200).replace(/\n/g, ' | '));
        const mode = page.locator('.modal-body select[name="matchingmode"]').last();
        log('step 5 has matchingmode select: ' + (await mode.count() > 0));
        if (await mode.count()) {
            await mode.selectOption('rule');
            await page.waitForTimeout(600);
            const cat = page.locator('.modal-body select[name="matchingcategoryid"]').last();
            if (await cat.count()) {
                const catvals = await cat.locator('option').evaluateAll(
                    (els) => els.map((e) => e.value).filter((v) => v && v !== '0')
                );
                if (catvals.length) {
                    await cat.selectOption(catvals[0]);
                    log('selected matching category ' + catvals[0]);
                }
            }
            const pat = page.locator('.modal-body input[name="matchingpattern"]').last();
            if (await pat.count()) {
                await pat.fill('INTRO');
                log('set matching pattern INTRO');
            }
            const target = page.locator('.modal-body input[name="matchingtargetvalue"]').last();
            if (await target.count()) {
                await target.fill('REMEDIAL-01');
            }
        }
        await advance(page, 'Confirm and save');

        // --- Step 6: review ----------------------------------------------
        await checkForPhpErrors(page, 'step 6');
        await shot(page, '06-step6');
        const review = await page.locator('.modal-body').last().innerText();
        log('--- review summary ---');
        log(review.slice(0, 1200));
        log('--- end review ---');
        const exportLink = page.locator('.modal-body a[href*="export.php"]').last();
        log('step 6 export link present: ' + (await exportLink.count() > 0));
        if (await exportLink.count()) {
            log('export href: ' + (await exportLink.getAttribute('href')).replace(/sesskey=[^&]*/, 'sesskey=***'));
        }

        // --- Save ---------------------------------------------------------
        await page.locator('.modal-footer .btn-primary').last().click();
        await page.waitForTimeout(3000);
        await checkForPhpErrors(page, 'after save');
        await shot(page, '07-saved');
        const after = await page.locator('body').innerText();
        log('after save, notification present: ' + /saved|success|gespeichert/i.test(after));

    } catch (e) {
        problems.push('EXCEPTION: ' + e.message);
        await shot(page, '99-failure').catch(() => {});
    } finally {
        await browser.close();
    }

    log('');
    if (problems.length) {
        log('PROBLEMS (' + problems.length + '):');
        problems.forEach((p) => log('  - ' + p));
        process.exitCode = 1;
    } else {
        log('No problems detected.');
    }
})();
